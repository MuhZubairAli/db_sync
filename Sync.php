<?php
require_once 'Db.php';

class Sync {
    protected $src_db;
    protected $dest_db;
    
    public function __construct($cons,&$db_map){
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
    public function execute(){
        return $this->src_db->select('select * from [PSLM1920].[DBO].[HH]');
    }

    public function syncTable($tbl_name){

    }

    public function __destruct() {
        $this->src_db->__destruct();
        $this->dest_db->__destruct();
        $this->src_db = null;
        $this->dest_db = null;
    }
}