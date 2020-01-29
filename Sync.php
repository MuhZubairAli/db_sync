<?php
require_once 'Db.php';

class Sync {
    protected $src_db;
    protected $dest_db;
    protected $map;
    
    public function __construct($cons,$db_map){
        $this->src_db = new Db(
            $cons['source']['server'],
            $cons['source']['user'],
            $cons['source']['password'],
            $cons['source']['database']
        );
        $this->dest_db = new Db(
            $cons['destination']['server'],
            $cons['destination']['user'],
            $cons['destination']['password'],
            $cons['destination']['database']
        );

        $this->map = $db_map;
    }

    public function execute(){
        return $this->src_db->select('select * from [PSLM1920].[DBO].[HH]');
    }

    public function syncTable($tbl_name){

    }

    public function destroy() {
        $this->src_db->destroy();
        $this->dest_db->destroy();
        $this->src_db = null;
        $this->dest_db = null;
    }
}