<?php
require_once 'constants.php';
require_once 'Scan.php';
require_once 'Response.php';
require_once 'config.php';

$response = array();
try {
    $target = (!empty($_GET['t'])) ? $_GET['t'] : 'whole_database';
    $scanner = new Scan(
        Config::$connections,
        Config::$database_structure,
        $target
    );
    $response = $scanner->execute();
} catch (Exception $e) {
    $response = array(
        'message' => $e->getMessage(),
        'backtrace' => $e->getTrace()
    );
} finally {
    Response::json($response, true);
}
