<?php

class Db {
    protected $serverName = "M-ZUBAIR-DPO\\DEVSERVER";   
    protected $database = "PSLM1920";  
    
    protected $uid = "root";  
    protected $pwd = "admin123";

    public $tbl_map = array(
        'insert' => array(
            'LoginActivityLog'
        ),
        'update' => array(
            'tblQuintile'
        )
    );

    public $conn;

    public function __construct(){
        try {  
           $this->conn = new PDO( "sqlsrv:server=$this->serverName;Database = $this->database", $this->uid, $this->pwd);   
           $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );   
        }  
        
        catch( PDOException $e ) {  
           die( "Error connecting to SQL Server | ". $e->getMessage() );   
        }  
    }

    public function insert(){

    }
    
    public function destroy(){
        $this->conn = null;
    }
}