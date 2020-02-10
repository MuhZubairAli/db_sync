<?php

class Config
{
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

    public static $database_structure = array(
        // "table_name" => [ primary_key columns list]
        "LoginActivityLog"  => ["Id"],
        "SECTION_A"     => ["Prcode"],
        "SECTION_B"     => ["Prcode", "IDC"],
        "SECTION_B2"    => ["Prcode", "IDC"],
        "Sample_Replacements"   => ["Prcode", "BlockCode", "sno1"]
    );
}
