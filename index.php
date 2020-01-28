<?php

    require 'Sync.php';
    require 'Response.php';

    $response = array();
    try {
        $sync = new Sync();
        $response = $sync->execute();
    }catch(Exception $e){
        $response = array(
            'message' => $e->getMessage(),
            'backtrace' => $e->getTrace()
        );
    } finally {
        Response::json($response);
    }
    
?>