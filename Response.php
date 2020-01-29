<?php

class Response {

    public static function json($output){
        if(!is_array($output) && !is_object($output))
            $output = [ $output ];

        header('Content-Type: application/json');
        echo json_encode($output);
    }

    public static function log($message){
        error_log(PHP_EOL . $message . PHP_EOL);
    }
}