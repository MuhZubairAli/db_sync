<?php
require_once 'Db.php';

class Scan
{
    protected $src_db;
    protected $dest_db;
    protected $map;
    protected $target;
    public function __construct($cons, &$db_map, $target)
    {
        $this->src_db = new Db(
            $cons['source']['server'],
            $cons['source']['user'],
            $cons['source']['password'],
            $cons['source']['database'],
            $db_map
        );
        $this->dest_db = new Db(
            $cons['destination']['server'],
            $cons['destination']['user'],
            $cons['destination']['password'],
            $cons['destination']['database'],
            $db_map
        );
        $this->map = &$db_map;
        $this->target = $target;
    }

    public function execute()
    {
        $logs = array();
        if ($this->target === 'whole_database') {
            foreach ($this->map as $table => $columns) {
                $logs[$table] = $this->verifyTable($table);
            }
        } else
            $logs[$this->target] = $this->verifyTable($this->target, true);

        return $logs;
    }

    private function verifyTable($tbl_name, $detailed = false)
    {
        $src_count = $this->src_db->count($tbl_name)['COUNT'];
        $dest_count = $this->dest_db->count($tbl_name)['COUNT'];

        $s_blocks = ceil(bcdiv($src_count, V_BLOCK_SIZE, 3));
        $d_blocks = ceil(bcdiv($dest_count, V_BLOCK_SIZE, 3));

        $s_cs = $this->src_db->get_checksum($tbl_name);
        $d_cs = $this->dest_db->get_checksum($tbl_name);

        $logs = [
            'table' => $tbl_name,
            'src_row_count' => $src_count,
            'dest_row_count' => $dest_count,
            'src_block_count' => $s_blocks,
            'dest_block_count' => $d_blocks,
            'src_checksum' => $s_cs,
            'dest_checksum' => $d_cs,
            'blockwise_stats' => array(),
            'status' => 'not specified'
        ];

        if ($s_cs === $d_cs) {
            $logs['status'] = STATUS_COMPLETE;
            return $logs;
        }

        if ($dest_count > 0 || $detailed) {
            $OK = $NOK = 0;
            for ($blockNumber = 1; $blockNumber <= $s_blocks; $blockNumber++) {
                $offset = bcmul(($blockNumber - 1), V_BLOCK_SIZE);
                $src_hash = $this->src_db->get_hash($tbl_name, $offset, V_BLOCK_SIZE);
                $dest_hash = $this->dest_db->get_hash($tbl_name, $offset, V_BLOCK_SIZE);
                if ($detailed) {
                    $logs['blockwise_stats'][$blockNumber] = [
                        'src_hash' => $src_hash['HASH'],
                        'dest_hash' => $dest_hash['HASH'],
                        'equal' => strcmp($src_hash['HASH'], $dest_hash['HASH'])
                    ];
                }
                (strcmp($src_hash['HASH'], $dest_hash['HASH']) === 0) ? $OK++ : $NOK++;
            }

            $logs['blockwise_stats']['OK'] = $OK;
            $logs['blockwise_stats']['NOK'] = $NOK;
            if ($OK === $s_blocks)
                $logs['status'] = STATUS_COMPLETE;
            else if ($NOK > 0 && $OK > 0)
                $logs['status'] = STATUS_PARTIAL;
            else if ($NOK === $s_blocks)
                $logs['status'] = STATUS_PENDING;
        }

        return $logs;
    }
}
