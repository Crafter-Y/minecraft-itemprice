<?php

/**
 * Contains a wrapper-class for mysqli, so models can access mysql
 *
 * @copyright Gameforge Productions GmbH*
 * @author Bernhard Sobotzik <bernhard.sobotzik@gameforge.com>
 * @package kata_model
 */

/**
 * this class is used by the model to access the database itself
 * @package kata_model
 */
class dbo_mysqli
{ //implements dbo_interface {

    /**
     * A connection to the MySQl server
     * @var mysqli
     */
    private $connection = null;

    /**
     * a copy of the matching db-config entry in config/database.php
     * @var array
     */
    private $dbconfig = null;

    /**
     * a placeholder for any result the database returned
     * @var array
     */
    private $result = null;

    /**
     * an array that holds all queries and some relevant information about them if DEBUG>1
     * @var array
     */
    private $queries = array();

    /**
     * constants used to quote table and field names
     */
    private $quoteLeft = '`',
        $quoteRight = '`';
    private $async_pool;

    /**
     * connect to the database
     */
    function connect()
    {
        $this->connection = new mysqli($this->dbconfig['host'], $this->dbconfig['login'], $this->dbconfig['password']);

        if (!($this->connection instanceof mysqli)) {
            throw new DatabaseConnectException("Could not connect to server " . $this->dbconfig['host']);
        }
        if (false === $this->connection->select_db($this->dbconfig['database'])) {
            throw new DatabaseConnectException("Could not select database " . $this->dbconfig['database']);
        }
        if (!empty ($this->dbconfig['encoding'])) {
            $this->setEncoding($this->dbconfig['encoding']);
        }
    }

    /**
     * if we are already connected
     * @return bool
     */
    function isConnected()
    {
        return $this->connection instanceof mysqli;
    }

    /**
     * return the current connection (link) to the database, connect first if needed
     */
    public function getLink()
    {
        if (!($this->connection instanceof mysqli)) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * inject database connection (link) into dbo
     */
    public function setLink(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * execute this query
     * @return mixed
     */
    private function execute($sql)
    {
        if (!($this->connection instanceof mysqli)) {
            $this->connect();
        }

        $start = microtime(true);
        $this->result = mysqli_query($this->connection, $sql);
        $this->handleErrorDebug($sql, $start);
    }

    private function reap_async_query($sql, $start)
    {
        $this->result = $this->connection->reap_async_query();

        $this->handleErrorDebug($sql, $start, true);
    }

    private function handleErrorDebug($sql, $start, $async = false)
    {
        if (false === $this->result) {
            switch ($this->connection->errno) {
                case 1062:
                    throw new DatabaseDuplicateException($this->connection->error);
                    break;
                default:
                    writeLog($this->connection->error . ': ' . $sql, KATA_ERROR);
                    throw new DatabaseErrorException($this->connection->error, $sql);
                    break;
            }
        }
        if (DEBUG > 0) {
            $this->queries[] = array(
                kataFunc::getLineInfo(),
                trim($sql),
                $this->connection->affected_rows,
                $this->connection->error,
                (microtime(true) - $start) . 'sec' . ($async ? ' async.' : '')
            );
        }
    }

    /**
     * unused right now, later possibly used by model to set right encoding
     */
    function setEncoding($enc)
    {
        $this->execute('SET NAMES ' . $enc);
        return $this->result;
    }

    /**
     * return numbers of rows affected by last query
     * @return int
     */
    private function lastAffected()
    {
        if ($this->connection instanceof mysqli) {
            return $this->connection->affected_rows;
        }
        return null;
    }

    /**
     * return id of primary key of last insert
     * @return int
     */
    private function lastInsertId()
    {
        $id = $this->connection->insert_id;
        if ($id) {
            return $id;
        }
        //may lie to you: if you have an auto-increment-table and you supply a primary key LAST_INSERT_ID is unchanged
        $result = $this->query('SELECT LAST_INSERT_ID() as id');
        if (!empty ($result)) {
            return $result[0]['id'];
        }
        return null;
    }

    /**
     * return the result of the last query.
     * @param mixed $idname if $idname is false keys are simply incrementing from 0, if $idname is string the key is the value of the column specified in the string
     */
    private function & lastResult($idnames = false, $fields = false)
    {
        $result = array();
        if ($this->result instanceof mysqli_result && $this->result->num_rows > 0) {
            if ($idnames === false) {
                while ($row = $this->result->fetch_assoc()) {
                    $result[] = $row;
                }
            } else {
                while ($row = $this->result->fetch_assoc()) {
                    $current = &$result;
                    foreach ($idnames as $idname) {
                        if (!array_key_exists($idname, $row)) {
                            throw new InvalidArgumentException('Cant order result by a field thats not in the resultset (forgot to select it?)');
                        }
                        if ($row[$idname] === null) {
                            $row[$idname] = 'null';
                        }
                        $current = &$current[$row[$idname]];
                    } //foreach
                    $current = $row;
                } //while fetch
            } //idnames
        } //rows>0
        return $result;
    }

    /**
     * REPLACE works exactly like INSERT,
     * except that if an old row in the table has the same value as a new row for a PRIMARY KEY or a UNIQUE  index,
     * the old row is deleted before the new row is inserted
     *
     * @param string $tableName replace from this table
     * @param array $fields name=>value pairs of new values
     * @param string $pairs enquoted names to escaped pairs z.B.[name]='value'
     * @return int modified rows.
     */
    function replace($tableName, $fields, $pairs)
    {
        return $this->query('REPLACE INTO ' . $tableName . ' SET ' . $pairs);
    }

    /**
     * execute query and return useful data depending on query-type
     *
     *    SELECT / SHOW                                            => resultset array
     *        REPLACE / UPDATE / DELETE / ALTER                        => affected rows (int)
     *        INSERT                                                    => last insert id (int)
     *        RENAME / LOCK / UNLOCK / TRUNCATE /SET / CREATE / DROP    => Returns if the operation was successfull (boolean)
     *
     * @param string $s sql-statement
     * @param string $idname which field-value to use as the key of the returned array (false=dont care)
     * @return array
     */
    function & query($s, $idnames = false, $fields = false)
    {
        $result = null;
        switch ($this->getSqlCommand($s)) {
            case 'replace' :
            case 'update' :
            case 'delete' :
            case 'alter' :
            case 'call':
                $this->execute($s);
                $result = $this->lastAffected();
                break;
            case 'insert' :
                $this->execute($s);
                $result = $this->lastInsertId();
                break;
            case 'select' :
            case 'show' :
                if (is_string($idnames)) {
                    $idnames = array(
                        $idnames
                    );
                }
                $this->execute($s);
                $result = $this->lastResult($idnames, $fields);
                break;
            default :
                $this->execute($s);
                $result = $this->result;
                break;
        }
        return $result;
    }

    function isAsyncSupported()
    {
        return (defined('MYSQLI_ASYNC') && $this->getConfig('driver') == 'mysqli');
    }

    function fetchAsyncResult()
    {
        if (!$this->isAsyncSupported()) {
            return array();
        } else {
            return $this->getAsyncPool()->fetchResult();
        }
    }

    function isAsyncQueryPending()
    {
        return ($this->isAsyncSupported() && !$this->getAsyncPool()->isEmpty());
    }


    public function queryAsync($s, $idnames = false, $fields = false)
    {
        if ($this->isAsyncSupported()) {
            $dboClass = new self();
            $dboClass->setConfig($this->getConfig());
            $pool = $this->getAsyncPool();
            $pool->queryAsync($dboClass, $s, $idnames, $fields);
        } else {
            return $this->query($s, $idnames, $fields);
        }
        return array();
    }

    public function getAsyncPool()
    {
        if ($this->async_pool == null) {
            $this->async_pool = classRegistry:: getObject('mysqli_async_pool');
        }
        return $this->async_pool;
    }

    public function _fetchAsync($s, $start, $idnames = false, $fields = false)
    {
        $result = null;
        switch ($this->getSqlCommand($s)) {
            case 'replace' :
            case 'update' :
            case 'delete' :
            case 'alter' :
            case 'call':
                $result = $this->lastAffected();
                break;
            case 'insert' :
                $result = $this->lastInsertId();
                break;
            case 'select' :
            case 'show' :
                if (is_string($idnames)) {
                    $idnames = array(
                        $idnames
                    );
                }
                $this->reap_async_query($s, $start);
                $result = $this->lastResult($idnames, $fields);
                break;
            default :
                $this->reap_async_query($s, $start);
                $result = $this->result;
                break;
        }
        return $result;
    }

    /**
     * escape the given string so it can be safely appended to any sql
     * @param string $sql string to escape
     * @return string
     */
    function escape($sql)
    {
        if (!($this->connection instanceof mysqli)) {
            $this->connect();
        }
        return $this->connection->real_escape_string($sql);
    }

    /**
     * return sql needed to convert unix timestamp to datetime
     * @param integer $t unixtime
     * @return string
     */
    function makeDateTime($t)
    {
        return 'FROM_UNIXTIME(' . $t . ')';
    }

    /**
     * output any queries made, how long it took, the result and any errors if DEBUG>1
     */
    function __destruct()
    {
        if (DEBUG > 0) {
            array_unshift($this->queries, array(
                'line',
                'sql',
                'affected',
                'error',
                'time'
            ));
            kataDebugOutput(
                $this->dbconfig['connName'] . ' (' .
                $this->dbconfig['login'] . '@' .
                $this->dbconfig['host'] . '/' .
                $this->dbconfig['database'] . ')',
                false
            );
            kataDebugOutput($this->queries, true);
        }
        if ($this->connection instanceof mysqli) {
            @$this->connection->close();
            $this->connection = null;
        }
    }

    private function getFieldSize($str)
    {
        $x1 = strpos($str, '(');
        $x2 = strpos($str, ')');
        if ((false !== $x1) && (false !== $x2)) {
            return substr($str, $x1 + 1, $x2 - $x1 - 1);
        }
        return 0;
    }

    /**
     * return the Sql-Command of given Query
     * @param string $sql query
     * @return string Sql-Command
     */
    private function getSqlCommand($sql)
    {
        $sql = str_replace(array(
            "(",
            "\t",
            "\n"
        ), " ", $sql);
        $Sqlparts = explode(" ", trim($sql));
        return strtolower($Sqlparts[0]);
    }

    /**
     * build a sql-string that returns first matching row
     * @param string $sql query
     * @param string $perPage expression
     * @return string (limited) Query
     */
    function getFirstRowQuery($sql, $perPage)
    {
        return sprintf('%s LIMIT %d', $sql, $perPage);
    }

    /**
     * build a sql-string that returns paged data
     * @return string finished query
     */
    function getPageQuery($sql, $page, $perPage)
    {
        return sprintf('%s LIMIT %d,%d', $sql, ($page - 1) * $perPage, $perPage);
    }

    /**
     * try to reduce the fields of given table to the basic types bool, unixdate, int, string, float, date, enum
     *
     * <code>example:
     *
     * Array
     * (
     *     [table] => test
     *     [primary] => Array
     *       [identity]=> a
     *     [cols] => Array
     *         (
     *             [a] => Array
     *                 (
     *                     [default] => CURRENT_TIMESTAMP
     *                     [null] =>
     *                       [key]    => 'PRI'
     *                     [length] => 0
     *                     [type] => date
     *                 )
     *
     *             [g] => Array
     *                 (
     *                     [default] =>
     *                     [null] =>
     *                       [key]    => 'UNI'
     *                     [length] => 0
     *                     [type] => unsupported:time
     *                 )
     *         )
     *
     * )
     * </code>
     *
     * @param string $tableName name of the table to analyze
     * @return unknown
     */
    function & describe($tableName)
    {
        $primaryKey = array();
        $identity = null;
        $desc = array();
        $cols = array();
        $sql = "SHOW COLUMNS FROM " . $tableName;
        $result = $this->connection->query($sql);
        if (false === $result) {
            throw new Exception('model: cant describe, missing rights?');
        }
        $noResult = true;
        while ($row = $result->fetch_assoc()) {
            $noResult = false;
            $data = array();
            $data['default'] = $row['Default'];
            $data['null'] = 'NO' != $row['Null'];
            $data['length'] = 0;
            if ('auto_increment' == $row['Extra']) {
                $identity = $row['Field'];
            }
            //keys
            if ('PRI' == $row['Key']) {
                $primaryKey[] = $row['Field'];
            }
            $data['key'] = $row['Key'];

            //type
            $x = strpos($row['Type'], '(');
            $type = $x !== false ? substr($row['Type'], 0, $x) : $row['Type'];
            switch ($type) {
                case 'bit' :
                    $data['type'] = 'bool';
                    $data['length'] = 1;
                    break;
                case 'bigint' :
                case 'int' :
                case 'smallint' :
                case 'tinyint' :
                case 'decimal':
                    $data['length'] = $this->getFieldSize($row['Type']);
                    $data['type'] = 'int';
                    break;
                case 'char' :
                case 'varchar' :
                    $data['length'] = $this->getFieldSize($row['Type']);
                    $data['type'] = 'string';
                    break;
                case 'text' :
                    $data['type'] = 'string';
                    break;
                case 'float' :
                case 'double' :
                case 'real' :
                    $data['type'] = 'float';
                    break;
                case 'date' :
                case 'datetime' :
                case 'time' :
                case 'timestamp' :
                    $data['type'] = 'date';
                    break;
                case 'set':
                    $data['type'] = 'set';
                    $data['values'] = 'foo';
                case 'blob':

                    break;
            }
            $cols[$row['Field']] = $data;
        }

        if ($noResult === true) {
            throw new Exception('Table does not exists in selected database');
        }

        $desc = array(
            'table' => str_replace(array(
                $this->quoteLeft,
                $this->quoteRight
            ), '', $tableName),
            'primary' => $primaryKey,
            'identity' => $identity,
            'cols' => $cols
        );
        return $desc;
    }


    /**
     * a copy of the matching db-config entry in config/database.php
     * @param $string $what spezifies what to get ... null=complete config array
     * @return array|string
     */
    function getConfig($what = null)
    {
        if (!empty ($what)) {
            return (isset ($this->dbconfig[$what])) ? $this->dbconfig[$what] : '';
        }
        return $this->dbconfig;
    }

    /**
     * set db-config entry
     * @param $array $config
     */
    function setConfig($config)
    {
        if (empty ($this->dbconfig)) {
            $this->dbconfig = $config;
        }
    }

    /**
     * used to quote table and field names
     * @param string $s string to enquote;
     * @return string enquoted string
     */
    function quoteName($s)
    {
        return $this->quoteLeft . $s . $this->quoteRight;
    }

    /**
     * checks if given operator is valid
     * @param string $operator
     * @return boolean
     */
    function isValidOperator($operator)
    {
        if (empty ($operator)) {
            return false;
        }
        $ops = array(
            '<=>' => 1,
            '=' => 1,
            '>=' => 1,
            '>' => 1,
            '<=' => 1,
            '<' => 1,
            '<>' => 1,
            '!=' => 1,
            'like' => 1,
            'not like' => 1,
            'is not null' => 1,
            'is null' => 1,
            'in' => 1,
            'not in' => 1,
            'between' => 1
        );
        return isset($ops[$operator]);
    }
}

class mysqli_async_pool
{
    private $pool = array();
    private $pooled = 0;
    private $cnt = 0;

    private $finished = array();
    private $errors = array();
    private $rejected = array();

    private $trash = array();

    public function queryAsync($mysqli_conn, $query, $idnames = false, $fields = false)
    {
        $handle = new mysqli_async_handle($mysqli_conn, $query, $idnames, $fields);
        $this->addHandle($handle);


    }

    private function addHandle($handle)
    {
        $this->pool[$handle->getId()] = $handle;
        $this->cnt++;
        $this->pooled++;
    }

    private function pollResult()
    {
        $finished = $errors = $rejected = array();
        while (true) {
            foreach ($this->pool as $handle) {
                $finished[] = $errors[] = $rejected[] = $handle->getLink();
            }

            if (0 == ($ready = mysqli_poll($finished, $errors, $rejected, 1, 0))) {
                //writeLog("sleep");
            } else {
                break;
            }
            $finished = $errors = $rejected = array();
        }
        foreach ($finished as $f) {
            $this->finished[] = $this->extractFromPool($f);
        }
        foreach ($errors as $e) {
            $this->errors[] = $this->extractFromPool($e);
            $this->cnt--;
        }
        foreach ($rejected as $r) {
            $this->rejected[] = $this->extractFromPool($r);
            $this->cnt--;
        }
    }

    private function extractFromPool($link)
    {
        $handle_id = mysqli_async_handle::getHandleId($link);
        $handle = $this->pool[$handle_id];

        unset($this->pool[$handle_id]);
        $this->trash[] = $handle;
        $this->pooled--;
        return $handle;
    }

    public function fetchResult()
    {
        if ($this->cnt == $this->pooled && $this->pooled >= 1) {
            $this->pollResult();
        }
        if ($this->cnt > $this->pooled) {
            $handle = array_shift($this->finished);
            $this->cnt--;
            return $handle->getResult();
        } else {
            $this->checkErrorsAndRejected();
            return false;
        }
    }

    public function isEmpty()
    {
        if ($this->cnt == $this->pooled && $this->pooled >= 1) {
            $this->pollResult();
        }
        if ($this->cnt > $this->pooled) {
            return false;
        } else {
            return true;
        }
    }

    private function checkErrorsAndRejected()
    {
        foreach ($this->errors as $e) {
            writeLog($this->connection->error . ': ' . $e->getQuery(), KATA_ERROR);
        }
        foreach ($this->rejected as $r) {
            writeLog('rejected query: ' . $r->getQuery(), KATA_ERROR);
        }
        // FIXME: throw meaning-full error here
    }
}

class mysqli_async_handle
{
    private $dbo = null;
    private $query = null;
    private $start = null;
    private $idnames = null;
    private $fields = null;

    public function __construct($dbo, $query, $idnames = false, $fields = false)
    {
        $this->dbo = $dbo;
        $this->query = $query;
        $this->idnames = $idnames;
        $this->fields = $fields;
        $this->start = microtime(true);
        $dbo->getLink()->query($query, MYSQLI_ASYNC);
    }

    public function getId()
    {
        return self::getHandleId($this->getLink());
    }

    static public function getHandleId($link)
    {
        return $link->thread_id;
    }

    public function getLink()
    {
        return $this->dbo->getLink();
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getResult()
    {
        return $this->dbo->_fetchAsync($this->query, $this->start, $this->idnames, $this->fields);
    }
}