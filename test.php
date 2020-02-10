<?php
    require_once 'Sync.php';
    require_once 'Response.php';
    require_once 'Config.php';

    class Test {
        protected $src_db;
        protected $dest_db;
        
        public function __construct($cons,&$db_map){
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
    
        public function match_blocks2(){
            $table = "LoginActivityLog";
            $cols = implode(',',$this->src_db->get_cols($table));
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



        public function match_blocks(){
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


            if($hash1===$hash2)
                die("Passed");
            else
                die("Failed");
        }

        public function run() {
            return $this->dest_db->insert('LoginActivityLog', [
                'Id' => 15,
                'UserID' => 334,
                'TimeStamp' => '2020-12-11',
                'DeviceID' => '98765'
            ]);
        }
    
    }


    /*----------------------------------------------------------------------------------
    ----------------------------------------------------------------------------------*/


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

    } catch(Exception $e){
        $response = array(
            'message' => $e->getMessage(),
            'backtrace' => $e->getTrace()
        );
        Response::json($response);
    }
    
?>