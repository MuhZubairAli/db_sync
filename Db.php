<?php

class Db {
    private $conn;

    public function __construct($host,$user,$pwd,$db){
        try {  
           $this->conn = new PDO( "sqlsrv:server=$host;Database = $db", $user, $pwd);   
           $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION, PDO::ATTR_STRINGIFY_FETCHES );   
        }  
        
        catch( PDOException $e ) {  
           throw new Exception( "Error connecting to SQL Server | ". $e->getMessage() );   
        }

        catch( Exception $e ) {
            throw new Exception( "Error connecting to SQL Server | ". $e->getMessage() );
        }
    }

    public function insert($table,$params,$replace=false) {
        $cols = '(';
        $vals = "VALUES (";
        foreach ($params as $key=>$val){
            $cols .= "`" . $key . "`, ";
            $vals .= "'" . $val . "', ";
        }
        $cols = trim($cols,", ");
        $cols .= ")";
        $vals = trim($vals,", ");
        $vals .= ")";

        if($replace)
            $sql = "UPDATE {$table} SET {$cols} {$vals} WHERE Column1='SomeValue' IF @@ROWCOUNT=0 INSERT INTO {$table} {$cols} {$vals}";
        else
            $sql = "INSERT INTO {$table} $cols $vals";

        return $this->conn->query( $sql );
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