<?php
require_once 'Db.php';

class Sync {
    protected $serverName = "M-ZUBAIR-DPO\\DEVSERVER";
    protected $uid = "root";  
    protected $pwd = "admin123";

    protected $src_db_name = "PSLM1920";  
    protected $dest_db_name = "PSLM-SUBS";

    protected $src_db;
    protected $dest_db;

    public function __construct(){

        try {  
            $this->src_db = new Db($this->serverName,$this->uid,$this->pwd,$this->src_db_name);
            $this->dest_db = new Db($this->serverName,$this->uid,$this->pwd,$this->dest_db_name);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function execute(){
        return $this->src_db->select('select * from [PSLM1920].[DBO].[HH]');
    }

    public function destroy() {
        $this->src_db->destroy();
        $this->dest_db->destroy();
        $this->src_db = null;
        $this->dest_db = null;
    }
}