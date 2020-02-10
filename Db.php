<?php

/**
 * Database connections and operation class
 * query to force identity insertion 
 * //SET IDENTITY_INSERT {$table} ON;
 */
class Db
{
    private $conn;
    private $tblPrefix;
    protected $map;

    /**
     * Explicit constructor
     *
     * @param [string] $host
     * @param [string] $user
     * @param [string] $pwd
     * @param [string] $db
     * @param [mixed] $db_map
     */
    public function __construct($host, $user, $pwd, $db, $db_map)
    {
        try {
            $this->conn = new PDO("sqlsrv:server=$host;Database = $db", $user, $pwd);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //, PDO::ATTR_STRINGIFY_FETCHES  
            $this->tblPrefix = "[{$db}].[dbo]";
            $this->map = &$db_map;
        } catch (PDOException $e) {
            throw new Exception("Error connecting to SQL Server | " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Error connecting to SQL Server | " . $e->getMessage());
        }
    }

    private function get_predicate($table, $row)
    {
        $prd = "";
        foreach ($this->map[$table] as $key) {
            $prd .= "[{$table}].[{$key}] = '" . $row[$key] . "' AND ";
        }
        return trim($prd, " AND ");
    }

    public function insert($table_name, $params, $replace = false)
    {
        $table = $this->tblPrefix . '.[' . $table_name . ']';
        $cols = $vals = "(";
        $set = '';
        foreach ($params as $key => $val) {
            $cols .= "[" . $key . "], ";
            $vals .= "'" . $val . "', ";
            if (array_search($key, $this->map[$table_name]) !== FALSE)
                continue;
            $set .= "[" . $key . "]='" . $val . "', ";
        }
        $cols = trim($cols, ", ") . ")";
        $vals = trim($vals, ", ") . ")";
        $set = trim($set, ", ");

        if ($replace) {
            $sql = " 
            UPDATE {$table} 
            SET {$set} 
            WHERE {$this->get_predicate($table_name,$params)}; 
            IF @@ROWCOUNT=0
            BEGIN
                INSERT INTO {$table} {$cols}
                    OUTPUT Inserted.* 
                VALUES {$vals};
            END";

            return $this->exec_stmt($sql);
        } else {
            $sql = "
            INSERT INTO {$table} {$cols} 
                OUTPUT Inserted.* 
            VALUES {$vals};";
            return $this->exec_query($sql);
        }
    }

    public function update($table_name, $params)
    {
        $table = $this->tblPrefix . '.[' . $table_name . ']';
        $cols = $vals = "(";
        $set = "";
        foreach ($params as $key => $val) {
            $cols .= "[" . $key . "], ";
            $vals .= "'" . $val . "', ";

            if (array_search($key, $this->map[$table_name]) !== FALSE)
                continue;
            $set .= "[" . $key . "]='" . $val . "', ";
        }
        $cols = trim($cols, ", ") . ")";
        $vals = trim($vals, ", ") . ")";
        $set = trim($set, ", ");

        $sql = "
        UPDATE {$table} 
        SET {$set}
        WHERE {$this->get_predicate($table_name,$params)}
        IF @@ROWCOUNT=0 
        BEGIN
            INSERT INTO {$table} {$cols} 
                OUTPUT Inserted.* 
            VALUES {$vals};
        END
        ";
        return $this->exec_stmt($sql);
    }

    public function select($table_name, $cols = '*', $offset = 0, $limit = 100, $predicate = '')
    {
        $table = $this->tblPrefix . '.[' . $table_name . ']';
        $selection = '';
        if (is_array($cols)) {
            array_map(
                function ($col) use (&$selection, $table) {
                    $selection .= $table . '[' . $col . '], ';
                },
                array_keys($cols)
            );
        } elseif ($cols === '*' || $cols === '' || $cols === null)
            $selection = $table . '.*';
        $predicate = (!empty($predicate)) ? "WHERE {$predicate}" : '';
        $sql = "
        SELECT
            ROW_NUMBER() OVER(ORDER BY {$this->get_primary_cols($table_name)}) as ROW_ID,
            {$selection}
        FROM {$table} {$predicate}
        ORDER BY {$this->get_primary_cols($table_name)}
        OFFSET {$offset} ROWS
        FETCH NEXT {$limit} ROWS ONLY;
        ";
        return $this->exec_query($sql);
    }

    public function hash($table, $offset = 0, $limit = 100)
    {
        $cols = $this->get_cols_string($table);
        $sql = "
        DECLARE @EncryptAlgorithm VARCHAR(3)
        DECLARE @DatatoEncrypt VARCHAR(MAX)
        DECLARE @Index INTEGER
        DECLARE @DataToEncryptLength INTEGER
        DECLARE @EncryptedResult VARBINARY(MAX)

        SET @EncryptAlgorithm = 'MD5'
        SET @DatatoEncrypt = LEFT((
            SELECT
                CONCAT({$cols})
            FROM 
                [{$table}]
            ORDER BY {$this->get_primary_cols_string($table)}
            OFFSET {$offset} ROWS
            FETCH NEXT {$limit} ROWS ONLY
            FOR XML Path('')
        ), 9999999999)
        
        IF @DataToEncrypt IS NOT NULL
        BEGIN
            SET @Index = 1
            SET @DataToEncryptLength = DATALENGTH(@DataToEncrypt)
            WHILE @Index <= @DataToEncryptLength
            BEGIN
                IF(@EncryptedResult IS NULL )
                    SET @EncryptedResult = HASHBYTES(@EncryptAlgorithm, SUBSTRING(@DataToEncrypt, @Index, 8000))
                ELSE
                    SET @EncryptedResult = @EncryptedResult + HASHBYTES(@EncryptAlgorithm,   SUBSTRING(@DataToEncrypt, @Index, 8000))
                SET @Index = @Index   + 8000
            END 
            SET @EncryptedResult =   HASHBYTES(@EncryptAlgorithm, @EncryptedResult)
        END

        SELECT CAST(@EncryptedResult as varchar) as [HASH]";

        return $this->exec_query($sql);
    }

    public function get_cols($table)
    {
        $cols = $this->exec_query("
        DECLARE @s VARCHAR(1000)
        SELECT @s =  ISNULL(@s+';','') + c.name   
        FROM
            sys.all_columns c join sys.tables  t 
            ON  c.object_id = t.object_id
        WHERE t.name = '{$table}'
        SELECT  @s cols
        ");
        return explode(";", $cols[0]['cols']);
    }

    public function get_cols_string($table)
    {
        $cols_raw = $this->get_cols($table);
        $cols = '';
        array_map(function ($col) use (&$cols, $table) {
            $cols .= "[{$table}].[{$col}], ";
        }, $cols_raw);
        return trim($cols, ', ');
    }

    public function get_primary_cols($table)
    {
        return $this->map[$table];
    }

    public function get_primary_cols_string($table)
    {
        $cols = '';
        array_map(
            function ($col) use (&$cols, $table) {
                $cols .= "[{$table}].[{$col}], ";
            },
            $this->get_primary_cols($table)
        );
        return trim($cols, ', ');
    }

    public function exec_query($sql)
    {
        try {
            $ds = array();
            $stmt = $this->conn->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ds[] = $row;
            }
            return $ds;
        } catch (PDOException $e) {
            throw new Exception("Failed to execute SQL query | " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Unknown error occurred while executing SQL query | " . $e->getMessage());
        }
    }

    public function exec_stmt($sql)
    {
        try {
            return $this->conn->query($sql);
        } catch (PDOException $e) {
            throw new Exception("Failed to execute SQL statement | " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Unknown error occurred while executing SQL statement | " . $e->getMessage());
        }
    }
    public function __destruct()
    {
        $this->conn = null;
    }
}
