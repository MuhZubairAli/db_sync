<?php

class Config {
    public static $connections = array(
        'source' => [
            'server'    => 'M-ZUBAIR-DPO\\DEVSERVER',
            'user'      => 'root',
            'password'  => 'admin123',
            'database'  => 'PSLM1920'
        ],
        
        'destination' => [
            'server'    => 'M-ZUBAIR-DPO\\DEVSERVER',
            'user'      => 'root',
            'password'  => 'admin123',
            'database'  => 'PSLM-SUBS'
        ]
    );

    public static $database_structure = array();
}