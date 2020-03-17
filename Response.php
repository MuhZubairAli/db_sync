<?php

class Response
{

    public static function json($output, $log = false)
    {
        if (!is_array($output) && !is_object($output))
            $output = [$output];

        header('Content-Type: application/json');
        $output = json_encode($output, JSON_PRETTY_PRINT);
        if ($log)
            self::log(
                "\n===================================\n
                \n{$output}\n
                \n====================================\n"
            );
        echo $output;
    }

    public static function log($message)
    {
        error_log(PHP_EOL . $message . PHP_EOL);
    }
}
