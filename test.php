<?php
require_once 'Sync.php';
require_once 'Response.php';
require_once 'Config.php';

class Test
{
    protected $src_db;
    protected $dest_db;

    public function __construct($cons, &$db_map)
    {
        $this->src_db = new Db(
            $cons['source']['server'],
            $cons['source']['user'],
            $cons['source']['password'],
            $cons['source']['database'],
            $db_map
        );
        $this->dest_db = new Db(
            $cons['destination']['server'],
            $cons['destination']['user'],
            $cons['destination']['password'],
            $cons['destination']['database'],
            $db_map
        );
    }

    public function match_blocks2()
    {
        $table = "LoginActivityLog";
        $cols = implode(',', $this->src_db->get_cols($table));
        $sql = "
            DECLARE @EncryptAlgorithm VARCHAR(3)
            DECLARE @DatatoEncrypt VARCHAR(MAX)
            DECLARE @Index INTEGER
            DECLARE @DataToEncryptLength INTEGER
            DECLARE @EncryptedResult VARBINARY(MAX)

            SET @EncryptAlgorithm = 'MD5'
            SEt @DatatoEncrypt = LEFT((
                        SELECT
                            CONCAT({$cols})
                        FROM 
                            [{$table}] 
                        WHERE
                            [Id] BETWEEN 1 AND 100
                        FOR XML Path('')
                        ), 9999999999)

            IF @DataToEncrypt IS NOT NULL
            BEGIN
                    SET @Index = 1
                    SET @DataToEncryptLength = DATALENGTH(@DataToEncrypt)
                    WHILE @Index <= @DataToEncryptLength
                    BEGIN
                        IF(@EncryptedResult   IS NULL )
                            SET @EncryptedResult = HASHBYTES(@EncryptAlgorithm, SUBSTRING(@DataToEncrypt,   @Index, 8000))
                        ELSE
                            SET @EncryptedResult = @EncryptedResult + HASHBYTES(@EncryptAlgorithm,   SUBSTRING(@DataToEncrypt, @Index, 8000))
                        SET @Index = @Index   + 8000
                    END 
                    SET @EncryptedResult =   HASHBYTES(@EncryptAlgorithm, @EncryptedResult)
            END

            SELECt CAST(@EncryptedResult as varchar) as [HASH]";

        return $this->src_db->exec_query($sql);
    }



    public function match_blocks()
    {
        $hash1 = $this->src_db->select("SELECT CAST(
                HASHBYTES('MD5',
                    LEFT(
                        (
                        SELECT
                            CONCAT([Id],[UserID],[TimeStamp],[DeviceID]) 
                        FROM 
                            [LoginActivityLog] 
                        WHERE
                            [Id] BETWEEN 1 AND 100
                        FOR XML Path('')
                        ),
                        99999999
                    )
                ) AS VARCHAR
            ) AS [HASH]
            ")[0]['HASH'];

        $hash2 = $this->src_db->select("SELECT CAST(
                HASHBYTES('MD5',
                    LEFT(
                        (
                        SELECT
                            CONCAT([Id],[UserID],[TimeStamp],[DeviceID]) 
                        FROM 
                            [LoginActivityLog] 
                        WHERE
                            [Id] BETWEEN 1 AND 100
                        FOR XML Path('')
                        ),
                        99999999
                    )
                ) AS VARCHAR
            ) AS [HASH]
            ")[0]['HASH'];


        if ($hash1 === $hash2)
            die("Passed");
        else
            die("Failed");
    }

    public function insert()
    {
        //return $this->dest_db->get_primary_cols('SECTION_B');
        return $this->dest_db->insert('SECTION_A', [
            "Prcode" => "1002304",
            "B_Code" => "123",
            "Int_Code" => 345,
            "Int_Date" => "2204-2-35",
            "Int_Start_time" => "00:00:00",
            "Int_End_time" => "23:59:59",
            "Stat_Int" => 1,
            "Beh_resp" => 1,
            "Lang_Int" => 2,
            "Dist_PSU_off" => 5,
            "Sup_Code" => 5,
            "Sup_ver_date" => null,
            "Edt_Code" => null,
            "Edt_Date" => "",
            "Remarks" => "N A",
            "RespondentId" => 1,
            "StartLAT" => 1,
            "StartLONG" => 1,
            "EndLAT" => 1,
            "EndLONG" => 1
        ]);
    }

    public function run()
    {

        return $this->src_db->hash("Sample_Replacements", 0, 3);
        return $this->src_db->select("SECTION_B", "*", 5, 3);
    }
}


/*------------------------------------------------------------------------
    --------------------------------------------------------------------*/


$response = array();
try {
    // $sync = new Sync(
    //     Config::$connections,
    //     Config::$database_structure
    // );
    // $response = $sync->execute();

    $test = new Test(
        Config::$connections,
        Config::$database_structure
    );

    Response::json(
        $test->run()
    );
} catch (Exception $e) {
    $response = array(
        'message' => $e->getMessage(),
        'backtrace' => $e->getTrace()
    );
    Response::json($response);
}
