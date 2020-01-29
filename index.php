<?php

    require 'Sync.php';
    require 'Response.php';
    require 'Config.php';
    
    $response = array();
    try {
        $sync = new Sync(
            Config::$connections,
            Config::$database_structure
        );
        $response = $sync->execute();
    } catch(Exception $e){
        $response = array(
            'message' => $e->getMessage(),
            'backtrace' => $e->getTrace()
        );
    } finally {
        Response::json($response);
    }
    
?>