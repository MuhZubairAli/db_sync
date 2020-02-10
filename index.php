<?php
    require_once 'Sync.php';
    require_once 'Response.php';
    require_once 'Config.php';
    
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
        $sync->__destruct();
    }
    
?>