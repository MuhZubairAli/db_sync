<?php
require_once 'Db.php';

class Sync {
    protected $db;

    public function __construct(){
        $this->db = new Db();
    }

    public function execute(){
        $query = 'select * from [PSLM1920].[DBO].[HH]';   
        $stmt = $this->db->conn->query( $query );   
        while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){   
            print_r( $row );   
        }
    }

    public function destroy() {
        $this->db->destroy();
        $this->db = null;
    }
}