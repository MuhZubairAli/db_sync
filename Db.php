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
    protected $invalid_chars = ["'", '"', "`"];

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
            $this->conn = new PDO("sqlsrv:server=$host;Database=$db", $user, $pwd);
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
        foreach ($this->get_primary_cols($table) as $key) {
            $prd .= "[{$table}].[{$key}] = '" . $row[$key] . "' AND ";
        }
        return trim($prd, " AND ");
    }

    public function insert($table_name, &$params, $force = false)
    {
        $table = $this->tblPrefix . '.[' . $table_name . ']';
        $cols = $vals = "(";
        $is2D = false;
        $first = true;
        foreach ($params as $key => $val) {
            if (is_array($val))
                $is2D = true;
            break;
        }
        if ($is2D) {
            foreach ($params as $num => $row) {
                if (!$first) {
                    $vals .= " (";
                }
                foreach ($row as $key => $val) {
                    if ($first)
                        $cols .= "[{$table_name}].[" . $key . "], ";
                    $vals .= "'" . $val . "', ";
                }
                if ($first)
                    $cols = trim($cols, ", ") . ")";
                $vals = trim($vals, ", ") . "), ";
                $first = false;
            }
            $vals = trim($vals, ", ");
        } else {
            foreach ($params as $key => $val) {
                $cols .= "[" . $key . "], ";
                $vals .= "'" . $val . "', ";
            }
            $cols = trim($cols, ", ") . ")";
            $vals = trim($vals, ", ") . ")";
        }

        $sql = "
        INSERT INTO {$table} {$cols} 
            OUTPUT Inserted.{$this->get_primary_cols($table_name)[0]}
        VALUES {$vals};";

        if ($force)
            $sql = "SET IDENTITY_INSERT {$table} ON; {$sql} SET IDENTITY_INSERT {$table} OFF;";

        return $this->exec_query($sql);
    }

    public function update($table_name, &$params, $force = false)
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

        if (empty($set)) {
            foreach ($params as $key => $val) {
                $set .= "[" . $key . "]='" . $val . "', ";
            }
            $set = trim($set, ", ");
        }

        $sql = "
        UPDATE {$table} 
        SET {$set}
        WHERE {$this->get_predicate($table_name,$params)}
        IF @@ROWCOUNT=0 
        BEGIN
            INSERT INTO {$table} {$cols}
            VALUES {$vals};
        END; 
        ";

        if ($force)
            $sql = "SET IDENTITY_INSERT {$table} ON; {$sql} SET IDENTITY_INSERT {$table} OFF;";

        return $this->exec_stmt($sql);
    }

    public function insert_filtered($table_name, &$params, $force = false)
    {
        $table = $this->tblPrefix . '.[' . $table_name . ']';
        $cols = $vals = "(";
        $is2D = false;
        $first = true;
        foreach ($params as $key => $val) {
            if (is_array($val))
                $is2D = true;
            break;
        }
        if ($is2D) {
            foreach ($params as $num => $row) {
                if (!$first) {
                    $vals .= " (";
                }
                foreach ($row as $key => $val) {
                    if ($first)
                        $cols .= "[{$table_name}].[" . $key . "], ";
                    $vals .= "'" . str_replace($this->invalid_chars, '', $val) . "', ";
                }
                if ($first)
                    $cols = trim($cols, ", ") . ")";
                $vals = trim($vals, ", ") . "), ";
                $first = false;
            }
            $vals = trim($vals, ", ");
        } else {
            foreach ($params as $key => $val) {
                $cols .= "[" . $key . "], ";
                $vals .= "'" . str_replace($this->invalid_chars, '', $val) . "', ";
            }
            $cols = trim($cols, ", ") . ")";
            $vals = trim($vals, ", ") . ")";
        }

        $sql = "
        INSERT INTO {$table} {$cols} 
            OUTPUT Inserted.{$this->get_primary_cols($table_name)[0]}
        VALUES {$vals};";

        if ($force)
            $sql = "SET IDENTITY_INSERT {$table} ON; {$sql} SET IDENTITY_INSERT {$table} OFF;";

        return $this->exec_query($sql);
    }

    public function update_filtered($table_name, &$params, $force = false)
    {
        $table = $this->tblPrefix . '.[' . $table_name . ']';
        $cols = $vals = "(";
        $set = "";
        foreach ($params as $key => $val) {
            $cols .= "[" . $key . "], ";
            $vals .= "'" . str_replace($this->invalid_chars, '', $val) . "', ";

            if (array_search($key, $this->map[$table_name]) !== FALSE)
                continue;
            $set .= "[" . $key . "]='" . str_replace($this->invalid_chars, '', $val) . "', ";
        }
        $cols = trim($cols, ", ") . ")";
        $vals = trim($vals, ", ") . ")";
        $set = trim($set, ", ");

        if (empty($set)) {
            foreach ($params as $key => $val) {
                $set .= "[" . $key . "]='" . str_replace($this->invalid_chars, '', $val) . "', ";
            }
            $set = trim($set, ", ");
        }

        $sql = "
        UPDATE {$table} 
        SET {$set}
        WHERE {$this->get_predicate($table_name,$params)}
        IF @@ROWCOUNT=0 
        BEGIN
            INSERT INTO {$table} {$cols}
            VALUES {$vals};
        END; 
        ";

        if ($force)
            $sql = "SET IDENTITY_INSERT {$table} ON; {$sql} SET IDENTITY_INSERT {$table} OFF;";

        return $this->exec_stmt($sql);
    }

    public function count($table)
    {
        return $this->exec_query("SELECT COUNT(*) as [COUNT] FROM {$table}")[0];
    }

    public function select($table_name, $cols = '*', $offset = 0, $limit = BLOCK_SIZE, $predicate = '', $ROW_ID = FALSE)
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

        if ($ROW_ID)
            $selection = "ROW_NUMBER() OVER(ORDER BY {$this->get_primary_cols_string($table_name)}) as ROW_ID, {$selection}";

        $sql = "
        SELECT
            {$selection}
        FROM {$table} {$predicate}
        ORDER BY {$this->get_primary_cols_string($table_name)}
        OFFSET {$offset} ROWS
        FETCH NEXT {$limit} ROWS ONLY;
        ";
        return $this->exec_query($sql);
    }

    public function get_checksum($table)
    {
        $table_name = $this->tblPrefix . '.[' . $table . ']';
        return $this->exec_query("SELECT CHECKSUM_AGG(BINARY_CHECKSUM(*)) as [HASH] FROM {$table_name}")[0];
    }

    public function get_hash($table, $offset = 0, $limit = BLOCK_SIZE)
    {
        $table_name = $this->tblPrefix . '.[' . $table . ']';
        $c = $this->get_cols($table);
        $cols = '';
        if (count($c) > 1) {
            $cols = "CONCAT(";
            foreach ($c as $col) {
                $cols .= "[{$table}].[{$col}], ";
            }
            $cols = trim($cols, ', ') . ")";
        } else
            $cols = $c[0];
        $sql = "
        DECLARE @EncryptAlgorithm VARCHAR(3)
        DECLARE @DatatoEncrypt VARCHAR(MAX)
        DECLARE @Index INTEGER
        DECLARE @DataToEncryptLength INTEGER
        DECLARE @EncryptedResult VARBINARY(MAX)

        SET @EncryptAlgorithm = 'MD5'
        SET @DatatoEncrypt = LEFT((
            SELECT
                {$cols}
            FROM 
                {$table_name}
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

        SELECT CAST(@EncryptedResult AS VARCHAR) as [HASH]";
        // echo $sql;
        // die;
        return $this->exec_query($sql)[0];
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
        if (!empty($this->map[$table]))
            return $this->map[$table];
        else
            throw new Exception("Given table as {$table} does not exist in the database map");
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

    public function get_block($table, $blockNumber, $skipRows = 0)
    {
        if ($blockNumber < 1)
            throw new Exception("Block number must be a Natural number");

        $offset = bcadd(bcmul(($blockNumber - 1), BLOCK_SIZE), $skipRows);
        $limit = bcsub(BLOCK_SIZE, $skipRows);
        return $this->select($table, '*', $offset, $limit);
    }

    public function get_block_hash($table, $blockNumber)
    {
        if ($blockNumber < 1)
            throw new Exception("Block number must be a Natural number");

        $offset = bcmul(($blockNumber - 1), BLOCK_SIZE);
        return $this->get_hash($table, $offset);
    }

    public function store_block($table, &$blockData, $force = false, $filter = false)
    {
        if (count($blockData) > 0) {
            if ($filter)
                return $this->insert_filtered($table, $blockData, $force);
            else
                return $this->insert($table, $blockData, $force);
        }

        return null;
    }

    public function update_block($table, &$blockData, $force = false)
    {
        if (count($blockData) > 0) {
            $OK = $NOK = 0;
            foreach ($blockData as $row) {
                if ($force)
                    $res = $this->update_filtered($table, $row);
                else
                    $res = $this->update($table, $row);
                if ($res)
                    $OK++;
                else
                    $NOK++;
            }
            if ($NOK === 0)
                $logs = 'block_updated';
            else if ($NOK > 0 && $OK > 0)
                $logs = 'block_partially_updated';
            else if ($OK === 0)
                $logs = 'block_update_failed';

            return $logs;
        }

        return null;
    }
}
