<?php
require_once 'Db.php';
require_once 'Scan.php';
class Sync
{
    protected $src_db;
    protected $dest_db;
    protected $map;
    protected $target;
    protected $scanner;

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
        $this->scanner = new Scan(
            $cons,
            $db_map,
            $target
        );
    }

    /* Main Driver function for syncing databases */
    public function execute()
    {
        $logs = array();
        if ($this->target === "whole_database") {
            foreach ($this->map as $table => $KeyColumns) {
                $logs[$table] = $this->syncTable($table);
            }
        } else
            $logs[$this->target] = $this->syncTable($this->target);
        return $logs;
    }

    private function syncTable($tbl_name)
    {
        $src_count = $this->src_db->count($tbl_name)['COUNT'];
        $dest_count = $this->dest_db->count($tbl_name)['COUNT'];

        $s_blocks = ceil(bcdiv($src_count, BLOCK_SIZE, 3));
        $d_blocks = floor(bcdiv($dest_count, BLOCK_SIZE, 3));
        $aer_count = bcmod($dest_count, BLOCK_SIZE);

        // $s_cs = $this->src_db->get_checksum($tbl_name);
        // $d_cs = $this->dest_db->get_checksum($tbl_name);

        $logs = [
            'status' => 'not-specified',
            'table' => $tbl_name,
            'src_row_count' => $src_count,
            'dest_row_count' => $dest_count,
            'src_block_count' => $s_blocks,
            'dest_block_count' => $d_blocks,
            'rows_skip_first_block' => $aer_count,
            // 'src_checksum' => $s_cs,
            // 'dest_checksum' => $d_cs,
            'blockwise_stats' => array()
        ];

        $stt = $this->scanner->execute($tbl_name)[$tbl_name];

        if (strcmp($stt['src_checksum']['HASH'], $stt['dest_checksum']['HASH']) === 0) {
            $logs['status'] = STATUS_COMPLETE;
            return $logs;
        } else if (isset($stt['blockwise_stats']['OK']) && isset($stt['blockwise_stats']['NOK'])) {
            if (
                $stt['src_block_count'] == $stt['blockwise_stats']['OK']
                && $stt['blockwise_stats']['NOK'] === 0
            ) {
                $logs['status'] = STATUS_COMPLETE;
                return $logs;
            }
        }


        if (bccomp($src_count, $dest_count) === 0) {
            //todo
            // match whole table hash to verify sync status [only for updatable tables]

            for ($blockNumber = 1; $blockNumber <= $s_blocks; $blockNumber++) {
                if (!$this->match_blocks($tbl_name, $blockNumber)) {
                    $this->get_store_block($tbl_name, $blockNumber, $logs);
                }
            }
            return $logs;
        }

        for ($current_block = $d_blocks + 1; $current_block <= $s_blocks; $current_block++) {
            try {
                $dirty_block = $this->src_db->get_block($tbl_name, $current_block);
                $logs['blockwise_stats'][$current_block] = $this->dest_db->store_block($tbl_name, $dirty_block);
            } catch (Exception $e) {
                $ecode = $this->get_ecode($e);
                if ($ecode === 1) {
                    $logs['blockwise_stats'][$current_block] = $this->dest_db->update_block($tbl_name, $dirty_block);
                } else if ($ecode === 2) {
                    try {
                        $logs['blockwise_stats'][$current_block] = $this->dest_db->store_block($tbl_name, $dirty_block, true);
                    } catch (Exception $e) {
                        $logs['blockwise_stats'][$current_block] = $this->dest_db->update_block($tbl_name, $dirty_block);
                    }
                } else {
                    for ($blockNumber = 1; $blockNumber <= $s_blocks; $blockNumber++) {
                        if (!$this->match_blocks($tbl_name, $blockNumber)) {
                            $this->get_store_block($tbl_name, $blockNumber, $logs);
                        }
                    }
                    $logs['message'] = "Whole table is blockwise scanned and updated";
                    return $logs;
                }
            } finally {
                $dirty_block = null;
            }
        }

        return $logs;
        // try {
        //     //Attempting to sync first block
        //     // $current_block = $d_blocks + 1;

        //     // $dirty_block = $this->src_db->get_block($tbl_name, $current_block, $aer_count);
        //     // // return $dirty_block;
        //     // // die;

        //     // $logs['blockwise_stats'][$current_block] = $this->dest_db->store_block($tbl_name, $dirty_block);

        //     for ($current_block = $d_blocks + 1; $current_block <= $s_blocks; $current_block++) {
        //         $dirty_block = $this->src_db->get_block($tbl_name, $current_block);
        //         $logs['blockwise_stats'][$current_block] = $this->dest_db->store_block($tbl_name, $dirty_block);
        //         $dirty_block = null;
        //     }
        // } catch (Exception $e) {
        //     if ($this->get_ecode($e) === "SQLSTATE[23000]") {
        //         //forcing to ignore identity related issues in table
        //         $this->dest_db->set_config('IDENTITY_INSERT', true);
        //         try {
        //             //Verify block hashes to find position of dirty rows
        //             $logs['blockwise_stats'] = array();
        //             for ($blockNumber = 1; $blockNumber <= $s_blocks; $blockNumber++) {
        //                 if (!$this->match_blocks($tbl_name, $blockNumber)) {
        //                     $this->get_store_block($tbl_name, $blockNumber, $logs);
        //                 }
        //             }
        //         } catch (Exception $e) {
        //             $ecode = substr($e->getMessage(), strpos($e->getMessage(), "SQLSTATE["), 15);
        //             if ($ecode === "SQLSTATE[23000]") {
        //                 $this->get_store_block($tbl_name, $blockNumber, $logs);
        //             }
        //         } finally {
        //             $this->dest_db->set_config('IDENTITY_INSERT', false);
        //         }
        //     }
        // } finally {
        //     if (count($OK) === $s_blocks)
        //         $logs['status'] = STATUS_COMPLETE;
        //     else if (count($NOK) > 0 && count($OK) > 0)
        //         $logs['status'] = STATUS_PARTIAL;
        //     else if (count($NOK) === $s_blocks)
        //         $logs['status'] = STATUS_PENDING;

        //     return $logs;
        // }
    }

    private function match_blocks($table, $blockNumber)
    {
        $src_hash = $this->src_db->get_block_hash($table, $blockNumber);
        $dest_hash = $this->dest_db->get_block_hash($table, $blockNumber);

        return $src_hash['HASH'] === $dest_hash['HASH'];
    }

    private function get_store_block($tbl_name, $blockNumber, &$logs)
    {
        try {
            $dirty_block = $this->src_db->get_block($tbl_name, $blockNumber);
            $logs['blockwise_stats'][$blockNumber] = $this->dest_db->store_block($tbl_name, $dirty_block, false, true);
        } catch (Exception $e) {
            $ecode = $this->get_ecode($e);
            if ($ecode === 1) {
                $logs['blockwise_stats'][$blockNumber] = $this->dest_db->update_block($tbl_name, $dirty_block, true);
            } else if ($ecode === 2) {
                $logs['blockwise_stats'][$blockNumber] = $this->dest_db->store_block($tbl_name, $dirty_block, true, true);
            } else {
                //throw $e;
                $logs['blockwise_stats'][$blockNumber] = 'failed';
            }
        } finally {
            $dirty_block = null;
        }
    }

    private function get_ecode(Exception $e)
    {
        $err = $e->getMessage();
        $ecode = substr($err, strpos($err, "SQLSTATE["), 15);
        if ($ecode === "SQLSTATE[23000]" && strpos($err, "duplicate") && strpos($err, "PRIMARY KEY"))
            return 1;
        else if ($ecode === "SQLSTATE[23000]" && strpos($err, "IDENTITY_INSERT"))
            return 2;
        else
            return $ecode;
    }
}
