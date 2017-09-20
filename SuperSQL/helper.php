<?php
/*
MIT License

Copyright (c) 2017 Andrew S (Andrews54757_at_gmail.com)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
namespace SuperSQL;
use SuperSQL\lib\Parser as Parser;
// BUILD BETWEEN
class SQLHelper
{
    public $s;
    public $connections;
    /**
     * Constructs helper
     *
     * @param {SuperSQL} SuperSQL - SuperSQL object
     */
    function __construct($dt, $db = null, $user = null, $pass = null, $options = array())
    {
        $this->connections = array();
        if (is_array($dt)) {
            if (is_array($dt[0])) {
                foreach ($dt as $key => $v) {
                    $host = isset($v['host']) ? $v['host'] : '';
                    $db   = isset($v['db']) ? $v['db'] : '';
                    $user = isset($v['user']) ? $v['user'] : '';
                    $pass = isset($v['password']) ? $v['password'] : '';
                    $opt  = isset($v['options']) ? $v['options'] : array();
                    $s    = self::connect($host, $db, $user, $pass, $opt);
                    array_push($this->connections, $s);
                }
            } else {
                foreach ($dt as $key => $v) {
                    array_push($this->connections, $v);
                }
            }
            $this->s = $this->connections[0];
        } else if (is_string($dt)) {
            $this->s = self::connect($dt, $db, $user, $pass, $options);
            array_push($this->connections, $this->s);
        } else {
            array_push($this->connections, $dt);
            $this->s = $dt;
        }
    }
    /**
     * Connects to a database
     *
     * @param {String} host - Host
     * @param {String} db - Database name
     * @param {String} user - Username
     * @param {String} pass - Password
     * @param {DSN|String|Object} - options - DSN string, dbtype, or options array
     *
     * @returns {SuperSQL} SuperSQL - SuperSQL object
     */
    static function connect($host, $db, $user, $pass, $options = array())
    {
        $dbtype = 'mysql';
        $dsn    = false;
        if (is_string($options)) {
            if (strpos($options, ':') !== false) {
                $dsn = $options;
            } else {
                $dbtype = strtolower($options);
            }
        } else if (isset($options['dbtype']))
            $dbtype = strtolower($options['dbtype']);
        if (!$dsn) {
            $driver = '';
            switch ($dbtype) {
                case 'pgsql':
                    $driver = 'pgsql';
                    $data   = array(
                        'dbname' => $db,
                        'host' => $host
                    );
                    if (isset($options['port']))
                        $data['port'] = $options['port'];
                    break;
                case 'sybase':
                    $driver = 'dblib';
                    $data   = array(
                        'dbname' => $db,
                        'host' => $host
                    );
                    if (isset($options['port']))
                        $data['port'] = $options['port'];
                    break;
                case 'oracle':
                    $driver = 'oci';
                    $data   = array(
                        'dbname' => isset($host) ? '//' . $host . ':' . (isset($options['port']) ? $options['port'] : '1521') . '/' . $db : $db
                    );
                    break;
                default:
                    $driver = 'mysql';
                    $data   = array(
                        'dbname' => $db
                    );
                    if (isset($options['socket']))
                        $data['unix_socket'] = $options['socket'];
                    else {
                        $data['host'] = $host;
                        if (isset($options['port']))
                            $data['port'] = $options['port'];
                    }
                    break;
            }
            $dsn = $driver . ':';
            if (isset($options['charset'])) {
                $data['charset'] = $options['charset'];
            }
            $dsn = $driver . ':';
            $b   = 0;
            foreach ($data as $key => $val) {
                if ($b != 0) {
                    $dsn .= ';';
                }
                $dsn .= $key . '=' . $val;
                $b++;
            }
        }
        return new SuperSQL($dsn, $user, $pass);
    }
    function escape($value)
    {
        $var = strtolower(gettype($value));
        if ($var == 'boolean') {
            $value = $value ? '1' : '0';
        } else if ($var == 'string') {
            $value = $this->s->con->db->quote($value);
        } else if ($var == 'double' || $var == 'integer') {
            $value = (int) $value;
        } else if ($var == 'null') {
            $value = '0';
        }
        return $value;
    }
    function change($id)
    {
        $this->s = $this->connections[$id];
        return $this->s;
    }
    function getCon($all = false)
    {
        if ($all) {
            return $this->connections;
        } else {
            return $this->s;
        }
    }
    /**
     * Gets the first row
     *
     * @param {String} table - Table to search
     * @param {Array} columns - Columns to return
     * @param {Array} where - where conditions
     * @param {Array} join - join conditions
     *
     * @returns {Array|false} - Returns row or false if none
     */
    function get($table, $columns = array(), $where = array(), $join = null)
    {
        $d = $this->s->SELECT($table, $columns, $where, $join, 1)->getData();
        return ($d && $d[0]) ? $d[0] : false;
    }
    /**
     * Creates a table
     *
     * @param {String} table - Table name to create
     * @param {Array} data - Columns to create
     */
    function create($table, $data)
    {
        $sql = 'CREATE TABLE `' . $table . '` (';
        $i   = 0;
        foreach ($data as $key => $val) {
            if ($i != 0) {
                $sql .= ', ';
            }
            $sql .= '`' . $key . '` ' . $val;
            $i++;
        }
        $sql .= ')';
        return $this->s->query($sql);
    }
    /**
     * Deletes a table
     *
     * @param {String} table - Table name to delete
     */
    function drop($table)
    {
        return $this->s->query('DROP TABLE `' . $table . '`');
    }
    function replace($table, $data, $where = array())
    {
        $newData = array();
        foreach ($data as $key => $val) {
            $str = '`' . Parser::rmComments($key) . '`';
            foreach ($val as $k => $v) {
                $str = 'REPLACE(' . $str . ', ' . $this->escape($k) . ', ' . $this->escape($v) . ')';
            }
            $newData['#' . $key] = $str;
        }
        return $this->s->UPDATE($table, $newData, $where);
    }
    function select($table, $columns = array(), $where = array(), $join = null, $limit = false)
    {
        return $this->s->SELECT($table, $columns, $where, $join, $limit);
    }
    function insert($table, $data)
    {
        return $this->s->INSERT($table, $data);
    }
    function update($table, $data, $where = array())
    {
        return $this->s->UPDATE($table, $data, $where);
    }
    function delete($table, $where = array())
    {
        return $this->s->DELETE($table, $where);
    }
    function sqBase($sql, $where, $join)
    {
        $i = 0;
        $values = array();
        if ($join) {
            Parser::JOIN($join, $sql, $values, $i);
        }
        if (count($where) != 0) {
            $sql .= ' WHERE ';
            $sql .= Parser::conditions($where, $values);
        }
        $res = $this->_query($sql, $values);
        return $res[0]->fetchColumn();
    }
    function count($table, $where = array(), $join = array())
    {
        return $this->sqBase('SELECT COUNT(*) FROM `' . $table . '`', $where, $join);
    }
    function avg()
    {
        return $this->sqBase('SELECT AVG(`' . $column . '`) FROM `' . $table . '`', $where, $join);
    }
    function max($table, $column, $where = array(), $join = array())
    {
        return $this->sqBase('SELECT MAX(`' . $column . '`) FROM `' . $table . '`', $where, $join);
    }
    function min($table, $column, $where = array(), $join = array())
    {
        return $this->sqBase('SELECT MIN(`' . $column . '`) FROM `' . $table . '`', $where, $join);
    }
    function sum($table, $column, $where = array(), $join = array())
    {
        return $this->sqBase('SELECT SUM(`' . $column . '`) FROM `' . $table . '`', $where, $join);
    }
    function _query($sql, $obj)
    {
        $q = $this->s->con->db->prepare($sql);
        foreach ($obj as $key => &$va) {
            $q->bindParam($key + 1, $va[0], $va[1]);
        }
        $e = $q->execute();
        return array(
            $q,
            $e
        );
    }
    function query($a, $b = null)
    {
        return $this->s->con->query($a, $b);
    }
    function transact($func)
    {
        return $this->s->transact($func);
    }
    function selectMap($table, $map, $where = array(), $join = null, $limit = false)
    {
        $columns  = array();
        $filtered = array();
        function recurse($data, &$in, &$columns, &$filtered)
        {
            foreach ($data as $key => $val) {
                if (is_int($key)) {
                    array_push($columns, $val);
                    $alias = Parser::getType($val); // get type || alias
                    if ($alias) {
                        $s = Parser::getType($val);
                        if ($s && ($s === 'int' || $s === 'bool' || $s === 'string' || $s === 'json' || $s === 'object' || $s === 'double')) { // name[alias][type]
                            $alias = false;
                        }
                    }
                    if ($alias) {
                        array_push($in, $alias);
                    } else {
                        preg_match('/(?:[^\.]*\.)?(.*)/', $val, $m);
                        array_push($in, $m[1]);
                    }
                } else {
                    $in[$key] = array();
                    recurse($val, $in[$key], $columns, $filtered);
                }
            }
        }
        recurse($map, $filtered, $columns, $filtered);
        $r = $this->s->select($table, $columns, $where, $join, $limit);
        $d = $r->getData();
        function recurse2($data, $row, &$out)
        {
            $out = array();
            foreach ($data as $key => $val) {
                if (is_int($key)) {
                    $out[$val] = $row[$val];
                } else {
                    recurse2($val, $row, $out[$key]);
                }
            }
        }
        $r->result = array();
        foreach ($d as $i => $row) {
            recurse2($filtered, $row, $r->result[$i]);
        }
        return $r;
    }
    function info() {
        return array(
            'server' => $this->s->con->db->getAttribute(\PDO::ATTR_SERVER_INFO),
            'driver' => $this->s->con->db->getAttribute(\PDO::ATTR_DRIVER_NAME),
            'client' => $this->s->con->db->getAttribute(\PDO::ATTR_CLIENT_VERSION),
            'version' => $this->s->con->db->getAttribute(\PDO::ATTR_SERVER_VERSION),
            'connection' => $this->s->con->db->getAttribute(\PDO::ATTR_CONNECTION_STATUS)
            );
    }
    function getLog() {
        $log = $this->s->getLog();
        $out = array();
        
        foreach ($log as $val) { 
            $split = explode('?',$val[0]);
            $str = '';
            foreach ($split as $i => $s) {
                $str .= $s . (isset($val[1][$i]) ? $this->escape($val[1][$i][0]) : '');
            }
            array_push($out,$str);
            
            if (isset($val[2])) {
                foreach ($val[2] as $in) {
                  foreach ($in as $k => $v) {
                      $val[1][$k][0] = $v;
                  }
                    $str = '';
                    foreach ($split as $i => $s) {
                    $str .= $s . (isset($val[1][$i]) ? $this->escape($val[1][$i][0]) : '');
                    }
                    array_push($out,$str);
                }
            }
        }
        return $out;
    }
    function dev() {
        $this->s->dev();
    }
}
// BUILD BETWEEN
?>
