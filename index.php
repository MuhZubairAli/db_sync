<?php

    require 'Sync.php';

    $sync = new Sync();
    $sync->execute();
    $sync->destroy();
    
?>