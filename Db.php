<?php

class Db {
    public $conn;

    public function __construct($host,$user,$pwd,$db){
        try {  
           $this->conn = new PDO( "sqlsrv:server=$host;Database = $db", $user, $pwd);   
           $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );   
        }  
        
        catch( PDOException $e ) {  
           throw new Exception( "Error connecting to SQL Server | ". $e->getMessage() );   
        }
    }

    public function insert(){

    }

    public function update(){

    }
    
    public function select($query){
        try {
            $stmt = $this->conn->query( $query ); 
            $ds = array();  
            while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){   
                $ds[] = $row;
            }
            return $ds;
        } catch (PDOException $e) {
            throw new Exception("Failed to execute SQL statement | ". $e->getMessage());
        }
        
    }
    
    public function destroy(){
        $this->conn = null;
    }
}