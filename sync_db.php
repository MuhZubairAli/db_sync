<?php
require_once 'constants.php';
require_once 'Sync.php';
require_once 'Response.php';
require_once 'config.php';

$response = array();
try {
    $target = (!empty($_GET['t'])) ? $_GET['t'] : 'whole_database';
    $sync = new Sync(
        Config::$connections,
        Config::$database_structure,
        $target
    );
    $response = $sync->execute();
} catch (Exception $e) {
    $response = array(
        'message' => $e->getMessage(),
        'backtrace' => $e->getTrace()
    );
} finally {
    Response::json($response, true);
}
