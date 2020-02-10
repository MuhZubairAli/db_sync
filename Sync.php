<?php
require_once 'Db.php';

class Sync
{
    protected $src_db;
    protected $dest_db;

    public function __construct($cons, &$db_map)
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
    }

    /* Main Driver function for syncing databases */
    public function execute()
    {
        return $this->syncTable('HH');
    }

    public function syncTable($tbl_name)
    {
        $src_count = $this->src_db->count($tbl_name)['COUNT'];
        $dest_count = $this->dest_db->count($tbl_name)['COUNT'];

        $s_blocks = ceil(bcdiv($src_count, BLOCK_SIZE, 3));
        $d_blocks = floor(bcdiv($dest_count, BLOCK_SIZE, 3));
        $aer_count = bcmod($dest_count, BLOCK_SIZE);

        $logs = [
            'table' => $tbl_name,
            'src_row_count' => $src_count,
            'dest_row_count' => $dest_count,
            'src_block_count' => $s_blocks,
            'dest_block_count' => $d_blocks,
            'rows_skip_first_block' => $aer_count,
            'blockwise_stats' => array()
        ];

        if (bccomp($src_count, $dest_count) === 0) {
            $logs['status'] = 'EQUAL_ROW_COUNT';
            return $logs;
        }
        //return $logs;
        try {
            //Attempting to sync first block
            $current_block = $d_blocks;

            $dirty_block = $this->src_db->get_block($tbl_name, $current_block, $aer_count);
            $logs['blockwise_stats'][$current_block] = $this->dest_db->store_block($tbl_name, $dirty_block);

            for ($current_block += 1; $current_block <= $s_blocks; $current_block++) {
                $dirty_block = $this->src_db->get_block($tbl_name, $current_block);
                $logs['blockwise_stats'][$current_block] = $this->dest_db->store_block($tbl_name, $dirty_block);
            }
        } catch (Exception $e) {
            $ecode = substr($e->getMessage(), strpos($e->getMessage(), "SQLSTATE["), 15);
            if ($ecode === "SQLSTATE[23000]") {
                //Verify block hashes to find position of dirty rows
                return $this->match_blocks($tbl_name, $current_block);
            }
        }

        return $logs;
    }

    private function match_blocks($table, $blockNumber)
    {
        $sb = $this->src_db->get_block($table, $blockNumber);
        $db = $this->dest_db->get_block($table, $blockNumber);

        $src_hash = $this->src_db->get_hash($table, bcmul($blockNumber, BLOCK_SIZE), BLOCK_SIZE);
        $dest_hash = $this->dest_db->get_hash($table, bcmul($blockNumber, BLOCK_SIZE), BLOCK_SIZE);

        return [$sb, $db];
    }
    private function get_store_block($tbl_name, $current_block, $aer_count, &$logs)
    {
        $dirty_block = $this->src_db->get_block($tbl_name, $current_block, $aer_count);
        $logs['blockwise_stats'][$current_block] = $this->dest_db->store_block($tbl_name, $dirty_block);

        for ($current_block += 1; $current_block <= $s_blocks; $current_block++) {
            $dirty_block = $this->src_db->get_block($tbl_name, $current_block);
            $logs['blockwise_stats'][$current_block] = $this->dest_db->store_block($tbl_name, $dirty_block);
        }
    }
    public function __destruct()
    {
        //$this->src_db->__destruct();
        //$this->dest_db->__destruct();
        $this->src_db = null;
        $this->dest_db = null;
    }
}
