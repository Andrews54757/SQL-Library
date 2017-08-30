<?php
/*
 Author: Andrews54757
 License: MIT (https://github.com/ThreeLetters/SuperSQL/blob/master/LICENSE)
 Source: https://github.com/ThreeLetters/SQL-Library
 Build: v1.0.61
 Built on: 29/08/2017
*/

namespace SuperSQL;

// lib/connector.php
class Response implements \ArrayAccess, \Iterator
{
    public $result;
    public $affected;
    public $ind = 0;
    public $error;
    public $errorData;
    public $outTypes;
    public $complete = false;
    public $stmt;
    function __construct($data, $error, &$outtypes, $mode)
    {
        $this->error = !$error;
        if (!$error) {
            $this->errorData = $data->errorInfo();
        } else {
            $this->outTypes = $outtypes;
            $this->init($data, $mode);
            $this->affected = $data->rowCount();
        }
    }
    private function init(&$data, &$mode)
    {
        if ($mode === 0) { 
            $outtypes = $this->outTypes;
            $d        = $data->fetchAll(\PDO::FETCH_ASSOC);
            if ($outtypes) {
                foreach ($d as $i => &$row) {
                    $this->map($row, $outtypes);
                }
            }
            $this->result   = $d;
            $this->complete = true;
        } else if ($mode === 1) { 
            $this->stmt   = $data;
            $this->result = array();
        }
    }
    function close()
    {
        $this->complete = true;
        if ($this->stmt) {
            $this->stmt->closeCursor();
            $this->stmt = null;
        }
    }
    private function fetchNextRow()
    {
        $row = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            if ($this->outTypes) {
                $this->map($row, $this->outTypes);
            }
            array_push($this->result, $row);
            return $row;
        } else {
            $this->complete = true;
            $this->stmt->closeCursor();
            $this->stmt = null;
            return false;
        }
    }
    private function fetchAll()
    {
        while ($this->fetchNextRow()) {
        }
    }
    function map(&$row, &$outtypes)
    {
        foreach ($outtypes as $col => $dt) {
            if (isset($row[$col])) {
                switch ($dt) {
                    case 'int':
                        $row[$col] = (int) $row[$col];
                        break;
                    case 'string':
                        $row[$col] = (string) $row[$col];
                        break;
                    case 'bool':
                        $row[$col] = $row[$col] ? true : false;
                        break;
                    case 'json':
                        $row[$col] = json_decode($row[$col]);
                        break;
                    case 'obj':
                        $row[$col] = unserialize($row[$col]);
                        break;
                }
            }
        }
    }
    function error()
    {
        return $this->error ? $this->errorData : false;
    }
    function getData($current = false)
    {
        if (!$this->complete && !$current)
            $this->fetchAll();
        return $this->result;
    }
    function getAffected()
    {
        return $this->affected;
    }
    function countRows()
    {
        return count($this->result);
    }
    function offsetSet($offset, $value) 
    {
    }
    function offsetExists($offset)
    {
        return $this->offsetGet($offset) === null ? false : true;
    }
    function offsetUnset($offset)
    {
    }
    function offsetGet($offset)
    {
        if (is_int($offset)) {
            if (isset($this->result[$offset])) {
                return $this->result[$offset];
            } else if (!$this->complete) {
                while ($this->fetchNextRow()) {
                    if (isset($this->result[$offset]))
                        return $this->result[$offset];
                }
            }
        }
        return null;
    }
    function next()
    {
        if (isset($this->result[$this->ind])) {
            return $this->result[$this->ind++];
        } else if (!$this->complete) {
            $row = $this->fetchNextRow();
            $this->ind++;
            return $row;
        } else {
            return false;
        }
    }
    function rewind()
    {
        $this->ind = 0;
    }
    function current()
    {
        return $this->result[$this->ind];
    }
    function key()
    {
        return $this->ind;
    }
    function valid()
    {
        return $this->offsetExists($this->ind);
    }
}
class Connector
{
    public $db;
    public $log = array();
    public $dev = false;
    function __construct($dsn, $user, $pass)
    {
        $this->db  = new \PDO($dsn, $user, $pass);
        $this->log = array();
    }
    function query($query, $obj = null, $outtypes = null, $mode = 0)
    {
        $q = $this->db->prepare($query);
        if ($obj)
            $e = $q->execute($obj);
        else
            $e = $q->execute();
        if ($this->dev)
            array_push($this->log, array(
                $query,
                $obj
            ));
        if ($mode !== 3) {
            return new Response($q, $e, $outtypes, $mode);
        } else {
            return $q;
        }
    }
    function _query(&$sql, $values, &$insert, &$outtypes = null, $mode = 0)
    {
        $q = $this->db->prepare($sql);
        if ($this->dev)
            array_push($this->log, array(
                $sql,
                $values,
                $insert
            ));
        foreach ($values as $key => &$va) {
            $q->bindParam($key + 1, $va[0], $va[1]);
        }
        $e = $q->execute();
        if (!isset($insert[0])) { 
            return new Response($q, $e, $outtypes, $mode);
        } else { 
            $responses = array();
            array_push($responses, new Response($q, $e, $outtypes, 0));
            foreach ($insert as $key => $value) {
                foreach ($value as $k => &$val) {
                    $values[$k][0] = $val;
                }
                $e = $q->execute();
                array_push($responses, new Response($q, $e, $outtypes, 0));
            }
            return $responses;
        }
    }
    function close()
    {
        $this->db      = null;
        $this->queries = null;
    }
}

// lib/parser.php
class Parser
{
    static function getArg(&$str)
    {
        preg_match('/^(?:\[(?<a>.{2})\])(?<out>.*)/', $str, $m);
        if (isset($m['a'])) {
            $str = $m['out'];
            return $m['a'];
        } else {
            return false;
        }
    }
    static function isRaw(&$key)
    {
        if ($key[0] === '#') {
            $key = substr($key, 1);
            return true;
        }
        return false;
    }
    static function append(&$args, $val, $index, $values)
    {
        if (is_array($val) && $values[$index][2] < 5) {
            $len = count($val);
            for ($k = 1; $k < $len; $k++) {
                if (!isset($args[$k - 1]))
                    $args[$k - 1] = array();
                $args[$k - 1][$index] = $val[$k];
            }
        }
    }
    static function escape($val)
    {
        if (is_int($val)) {
            return (int) $val;
        } else {
            return '\'' . $val . '\''; 
        }
    }
    static function escape2($val, $dt)
    {
        switch ($dt[2]) {
            case 0: 
                return $val ? '1' : '0';
                break;
            case 1: 
                return (int) $val;
                break;
            case 2: 
                return (string) $val;
                break;
            case 3: 
                return $val;
                break;
            case 4: 
                return null;
                break;
            case 5: 
                return json_encode($val);
                break;
            case 6: 
                return serialize($val);
                break;
        }
    }
    static function stripArgs(&$key)
    {
        preg_match('/(?:\[.{2}\]){0,2}([^\[]*)/', $key, $matches); 
        return $matches[1];
    }
    static function append2(&$insert, $indexes, $dt, $values)
    {
        $len = count($dt);
        for ($key = 1; $key < $len; $key++) {
            $val = $dt[$key];
            if (!isset($insert[$key - 1]))
                $insert[$key - 1] = array();
            self::recurse($insert[$key - 1], $val, $indexes, '', $values);
        }
    }
    private static function recurse(&$holder, $val, $indexes, $par, $values)
    {
        foreach ($val as $k => &$v) {
            if ($k[0] === '#')
                continue;
            self::stripArgs($k);
            $k1 = $k . '#' . $par;
            if (isset($indexes[$k1]))
                $d = $indexes[$k1];
            else
                $d = $indexes[$k];
            $isArr = is_array($v) && (!isset($values[$d][2]) || $values[$d][2] < 5);
            if ($isArr) {
                if (isset($v[0])) {
                    foreach ($v as $i => &$j) {
                        $a = $d + $i;
                        if (isset($holder[$a]))
                            trigger_error('Key collision: ' . $k, E_USER_WARNING);
                        $holder[$a] = self::escape2($j, $values[$a]);
                    }
                } else {
                    self::recurse($holder, $v, $indexes, $par . '/' . $k, $values);
                }
            } else {
                if (isset($holder[$d]))
                    trigger_error('Key collision: ' . $k, E_USER_WARNING);
                $holder[$d] = self::escape2($v, $values[$d]);
            }
        }
    }
    static function quote($str)
    {
        preg_match('/([^.]*)\.?(.*)?/', $str, $matches); 
        if ($matches[2] !== '') {
            return '`' . $matches[1] . '.' . $matches[2] . '`';
        } else {
            return '`' . $matches[1] . '`';
        }
    }
    static function quoteArray(&$arr)
    {
        foreach ($arr as &$v) {
            $v = self::quote($v);
        }
    }
    static function table($table)
    {
        if (is_array($table)) {
            $sql = '';
            foreach ($table as $i => &$val) {
                $t = self::getType($val);
                if ($i !== 0)
                    $sql .= ', ';
                $sql .= '`' . $val . '`';
                if ($t)
                    $sql .= ' AS `' . $t . '`';
            }
            return $sql;
        } else {
            return '`' . $table . '`';
        }
    }
    static function value($type, $value)
    {
        $var   = $type ? $type : gettype($value);
        $type  = \PDO::PARAM_STR;
        $dtype = 2;
        if ($var === 'integer' || $var === 'int' || $var === 'double' || $var === 'doub') {
            $type  = \PDO::PARAM_INT;
            $dtype = 1;
            $value = (int) $value;
        } else if ($var === 'string' || $var === 'str') {
            $value = (string) $value;
            $dtype = 2;
        } else if ($var === 'boolean' || $var === 'bool') {
            $type  = \PDO::PARAM_BOOL;
            $value = $value ? '1' : '0';
            $dtype = 0;
        } else if ($var === 'null' || $var === 'NULL') {
            $dtype = 4;
            $type  = \PDO::PARAM_NULL;
            $value = null;
        } else if ($var === 'resource' || $var === 'lob') {
            $type  = \PDO::PARAM_LOB;
            $dtype = 3;
        } else if ($var === 'json') {
            $dtype = 5;
            $value = json_encode($value);
        } else if ($var === 'obj' || $var === 'object') {
            $dtype = 6;
            $value = serialize($value);
        } else {
            $value = (string) $value;
            trigger_error('Invalid type ' . $var . ' Assumed STRING', E_USER_WARNING);
        }
        return array(
            $value,
            $type,
            $dtype
        );
    }
    static function getType(&$str)
    {
        preg_match('/(?<out>[^\[]*)(?:\[(?<a>[^\]]*)\])?/', $str, $m);
        $str = $m['out'];
        return isset($m['a']) ? $m['a'] : false;
    }
    static function rmComments($str)
    {
        preg_match('/([^#]*)/', $str, $matches);
        return $matches[1];
    }
    static function conditions($dt, &$values = false, &$map = false, &$index = 0, $join = ' AND ', $operator = ' = ', $parent = '')
    {
        $num = 0;
        $sql = '';
        foreach ($dt as $key => &$val) {
            preg_match('^(?<r>\#)?(?:\[(?<a>.{2})\])(?:\[(?<b>.{2})\])?(?<out>.*)', $key, $matches); 
            $raw = isset($matches['r']);
            if (isset($matches['a'])) {
                $arg  = $matches['a'];
                $key  = $matches['out'];
                $arg2 = isset($matches['b']) ? $matches['b'] : false;
            } else {
                $arg = false;
            }
            $useBind     = !isset($val[0]);
            $newJoin     = $join;
            $newOperator = $operator;
            $type        = $raw ? false : self::getType($key);
            $arr         = is_array($val) && $type !== 'json' && $type !== 'obj';
            if ($arg && ($arg === '||' || $arg === '&&')) {
                $newJoin = ($arg === '||') ? ' OR ' : ' AND ';
                $arg     = $arg2;
                if ($arr && $arg && ($arg === '||' || $arg === '&&')) {
                    $join    = $newJoin;
                    $newJoin = ($arg === '||') ? ' OR ' : ' AND ';
                    $arg     = self::getArg($key);
                }
            }
            $between = false;
            if ($arg && $arg !== '==') {
                switch ($arg) { 
                    case '!=':
                        $newOperator = ' != ';
                        break;
                    case '>>':
                        $newOperator = ' > ';
                        break;
                    case '<<':
                        $newOperator = ' < ';
                        break;
                    case '>=':
                        $newOperator = ' >= ';
                        break;
                    case '<=':
                        $newOperator = ' <= ';
                        break;
                    case '~~':
                        $newOperator = ' LIKE ';
                        break;
                    case '!~':
                        $newOperator = ' NOT LIKE ';
                        break;
                    default:
                        if ($arg !== '><' && $arg !== '<>')
                            throw new \Exception('Invalid operator ' . $arg . ' Available: ==,!=,>>,<<,>=,<=,~~,!~,<>,><');
                        else
                            $between = true;
                        break;
                }
            } else {
                if (!$useBind || $arg === '==')
                    $newOperator = ' = '; 
            }
            if (!$arr)
                $join = $newJoin;
            if ($num !== 0)
                $sql .= $join;
            $column = self::rmComments($key);
            if (!$raw)
                $column = self::quote($column);
            if ($arr) {
                $sql .= '(';
                if ($useBind) {
                    $sql .= self::conditions($val, $values, $map, $index, $newJoin, $newOperator, $parent . '/' . $key);
                } else {
                    if ($map !== false && !$raw) {
                        $map[$key]                 = $index;
                        $map[$key . '#' . $parent] = $index++;
                    }
                    if ($between) {
                        $index += 2;
                        $sql .= $column . ($arg === '<>' ? 'NOT' : '') . ' BETWEEN ';
                        if ($raw) {
                            $sql .= $val[0] . ' AND ' . $val[1];
                        } else if ($values !== false) {
                            $sql .= '? AND ?';
                            array_push($values, self::value($type, $val[0]));
                            array_push($values, self::value($type, $val[1]));
                        } else {
                            $sql .= self::escape($val[0]) . ' AND ' . self::escape($val[1]);
                        }
                    } else {
                        foreach ($val as $k => &$v) {
                            if ($k !== 0)
                                $sql .= $newJoin;
                            ++$index;
                            $sql .= $column . $newOperator;
                            if ($raw) {
                                $sql .= $v;
                            } else if ($values !== false) {
                                $sql .= '?';
                                array_push($values, self::value($type, $v));
                            } else {
                                $sql .= self::escape($v);
                            }
                        }
                    }
                }
                $sql .= ')';
            } else {
                $sql .= $column . $newOperator;
                if ($raw) {
                    $sql .= $val;
                } else {
                    if ($values !== false) {
                        $sql .= '?';
                        array_push($values, self::value($type, $val));
                    } else {
                        $sql .= self::escape($val);
                    }
                    if ($map !== false) {
                        $map[$key]                 = $index;
                        $map[$key . '#' . $parent] = $index++;
                    }
                }
            }
            ++$num;
        }
        return $sql;
    }
    static function JOIN($join, &$sql, &$values, &$i)
    {
        foreach ($join as $key => &$val) {
            $raw = self::isRaw($key);
            $arg = self::getArg($key);
            switch ($arg) {
                case '<<':
                    $sql .= ' RIGHT JOIN ';
                    break;
                case '>>':
                    $sql .= ' LEFT JOIN ';
                    break;
                case '<>':
                    $sql .= ' FULL JOIN ';
                    break;
                case '>~':
                    $sql .= ' LEFT OUTER JOIN ';
                    break;
                default: 
                    $sql .= ' JOIN ';
                    break;
            }
            $sql .= '`' . $key . '` ON ';
            if ($raw) {
                $sql .= $val;
            } else {
                $sql .= self::conditions($val, $values, $f, $i);
            }
        }
    }
    static function columns($columns, &$sql, &$outTypes)
    {
        $into = '';
        $f    = $columns[0][0];
        if ($f === 'D' || $f === 'I') {
            if ($columns[0] === 'DISTINCT') {
                $req = 1;
                $sql .= 'DISTINCT ';
                array_splice($columns, 0, 1);
            } else if (substr($columns[0], 0, 11) === 'INSERT INTO') {
                $req = 1;
                $sql = $columns[0] . ' ' . $sql;
                array_splice($columns, 0, 1);
            } else if (substr($columns[0], 0, 4) === 'INTO') {
                $req  = 1;
                $into = ' ' . $columns[0] . ' ';
                array_splice($columns, 0, 1);
            }
        }
        if (isset($columns[0])) { 
            if ($columns[0] === '*') {
                array_splice($columns, 0, 1);
                $sql .= '*';
                foreach ($columns as $i => &$val) {
                    preg_match('/(?<column>[a-zA-Z0-9_\.]*)(?:\[(?<type>[^\]]*)\])?/', $val, $match);
                    $outTypes[$match['column']] = $match['type'];
                }
            } else {
                foreach ($columns as $i => &$val) {
                    preg_match('/(?<column>[a-zA-Z0-9_\.]*)(?:\[(?<alias>[^\]]*)\])?(?:\[(?<type>[^\]]*)\])?/', $val, $match); 
                    $val   = $match['column'];
                    $alias = false;
                    if (isset($match['alias'])) { 
                        $alias = $match['alias'];
                        if (isset($match['type'])) {
                            $type = $match['type'];
                        } else {
                            if ($alias === 'json' || $alias === 'obj' || $alias === 'int' || $alias === 'string' || $alias === 'bool') {
                                $type  = $alias;
                                $alias = false;
                            } else
                                $type = false;
                        }
                        if ($type) {
                            if (!$outTypes)
                                $outTypes = array();
                            $outTypes[$alias ? $alias : $val] = $type;
                        }
                    }
                    if ($i != 0) {
                        $sql .= ', ';
                    }
                    $sql .= self::quote($val);
                    if ($alias)
                        $sql .= ' AS `' . $alias . '`';
                }
            }
        } else
            $sql .= '*';
        $sql .= $into;
    }
    static function SELECT($table, $columns, $where, $join, $limit)
    {
        $sql      = 'SELECT ';
        $values   = array();
        $insert   = array();
        $outTypes = null;
        $i        = 0;
        if (!isset($columns[0])) { 
            $sql .= '*';
        } else { 
            self::columns($columns, $sql, $outTypes);
        }
        $sql .= ' FROM ' . self::table($table);
        if ($join) {
            self::JOIN($join, $sql, $values, $i);
        }
        if (!empty($where)) {
            $sql .= ' WHERE ';
            if (isset($where[0])) {
                $index = array();
                $sql .= self::conditions($where[0], $values, $index, $i);
                self::append2($insert, $index, $where, $values);
            } else {
                $sql .= self::conditions($where, $values);
            }
        }
        if ($limit) {
            if (is_int($limit)) {
                $sql .= ' LIMIT ' . $limit;
            } else if (is_string($limit)) {
                $sql .= ' ' . $limit;
            } else if (is_array($limit)) {
                if (isset($limit[0])) {
                    $sql .= ' LIMIT ' . (int) $limit[0] . ' OFFSET ' . (int) $limit[1];
                } else {
                    if (isset($limit['GROUP'])) {
                        $sql .= ' GROUP BY ';
                        if (is_string($limit['GROUP'])) {
                            $sql .= self::quote($limit['GROUP']);
                        } else {
                            self::quoteArray($limit['GROUP']);
                            $sql .= implode(', ', $limit['GROUP']);
                        }
                        if (isset($limit['HAVING'])) {
                            $sql .= ' HAVING ' . (is_string($limit['HAVING']) ? $limit['HAVING'] : self::conditions($limit['HAVING'], $values, $f, $i));
                        }
                    }
                    if (isset($limit['ORDER'])) {
                        $sql .= ' ORDER BY ' . self::quote($limit['ORDER']);
                    }
                    if (isset($limit['LIMIT'])) {
                        $sql .= ' LIMIT ' . (int) $limit['LIMIT'];
                    }
                    if (isset($limit['OFFSET'])) {
                        $sql .= ' OFFSET ' . (int) $limit['OFFSET'];
                    }
                }
            }
        }
        return array(
            $sql,
            $values,
            $insert,
            $outTypes
        );
    }
    static function INSERT($table, $data)
    {
        $sql     = 'INSERT INTO ' . self::table($table) . ' (';
        $values  = array();
        $insert  = array();
        $append  = '';
        $i       = 0;
        $b       = 0;
        $indexes = array();
        $multi   = isset($data[0]);
        $dt      = $multi ? $data[0] : $data;
        foreach ($dt as $key => &$val) {
            $raw = self::isRaw($key);
            if ($b !== 0) {
                $sql .= ', ';
                $append .= ', ';
            }
            if (!$raw) {
                preg_match('/(?<out>[^\[]*)(?:\[(?<type>[^]]*)\])?/', $key, $matches);
                $key = $matches['out'];
            }
            $sql .= '`' . $key . '`';
            if ($raw) {
                $append .= $val;
            } else {
                $type = isset($matches['type']) ? $matches['type'] : false;
                $append .= '?';
                $m2 = (!$type || ($type !== 'json' && $type !== 'obj')) && is_array($val);
                array_push($values, self::value($type, $m2 ? $val[0] : $val));
                if ($multi) {
                    $indexes[$key] = $i++;
                } else if ($m2) {
                    self::append($insert, $val, $i++, $values);
                }
            }
            ++$b;
        }
        if ($multi)
            self::append2($insert, $indexes, $data, $values);
        $sql .= ') VALUES (' . $append . ')';
        return array(
            $sql,
            $values,
            $insert
        );
    }
    static function UPDATE($table, $data, $where)
    {
        $sql     = 'UPDATE ' . self::table($table) . ' SET ';
        $values  = array();
        $insert  = array();
        $i       = 0;
        $b       = 0;
        $indexes = array();
        $multi   = isset($data[0]);
        $dt      = $multi ? $data[0] : $data;
        foreach ($dt as $key => &$val) {
            $raw = self::isRaw($key);
            if ($b !== 0) {
                $sql .= ', ';
            }
            if ($raw) {
                $sql .= '`' . $key . '` = ' . $val;
            } else {
                preg_match('/(?:\[(?<arg>.{2})\])?(?<out>[^\[]*)(?:\[(?<type>[^\]]*)\])?/', $key, $matches);
                $key = $matches['out'];
                $sql .= '`' . $key . '` = ';
                if (isset($matches['arg'])) {
                    switch ($matches['arg']) {
                        case '+=':
                            $sql .= '`' . $key . '` + ?';
                            break;
                        case '-=':
                            $sql .= '`' . $key . '` - ?';
                            break;
                        case '/=':
                            $sql .= '`' . $key . '` / ?';
                            break;
                        case '*=':
                            $sql .= '`' . $key . '` * ?';
                            break;
                        default:
                            $sql .= '?';
                            break;
                    }
                }
                $type = isset($matches['type']) ? $matches['type'] : false;
                $m2   = (!$type || ($type !== 'json' && $type !== 'obj')) && is_array($val);
                array_push($values, self::value($type, $m2 ? $val[0] : $val));
                if ($multi) {
                    $indexes[$key] = $i++;
                } else if ($m2) {
                    self::append($insert, $val, $i++, $values);
                }
            }
            ++$b;
        }
        if ($multi)
            self::append2($insert, $indexes, $data, $values);
        if (!empty($where)) {
            $sql .= ' WHERE ';
            $index = array();
            if (isset($where[0])) {
                $sql .= self::conditions($where[0], $values, $index, $i);
                self::append2($insert, $index, $where, $values);
            } else {
                $sql .= self::conditions($where, $values, $f, $i);
            }
        }
        return array(
            $sql,
            $values,
            $insert
        );
    }
    static function DELETE($table, $where)
    {
        $sql    = 'DELETE FROM ' . self::table($table);
        $values = array();
        $insert = array();
        if (!empty($where)) {
            $sql .= ' WHERE ';
            $index = array();
            if (isset($where[0])) {
                $sql .= self::conditions($where[0], $values, $index);
                self::append2($insert, $index, $where, $values);
            } else {
                $sql .= self::conditions($where, $values);
            }
        }
        return array(
            $sql,
            $values,
            $insert
        );
    }
}

// index.php
class SuperSQL
{
    public $con;
    public $lockMode = false;
    function __construct($dsn, $user, $pass)
    {
        $this->con = new Connector($dsn, $user, $pass);
    }
    function SELECT($table, $columns = array(), $where = array(), $join = null, $limit = false)
    {
        if ((is_int($join) || is_string($join) || isset($join[0])) && !$limit) {
            $limit = $join;
            $join  = null;
        }
        $d = Parser::SELECT($table, $columns, $where, $join, $limit);
        return $this->con->_query($d[0], $d[1], $d[2], $d[3], $this->lockMode ? 0 : 1);
    }
    function INSERT($table, $data)
    {
        $d = Parser::INSERT($table, $data);
        return $this->con->_query($d[0], $d[1], $d[2]);
    }
    function UPDATE($table, $data, $where = array())
    {
        $d = Parser::UPDATE($table, $data, $where);
        return $this->con->_query($d[0], $d[1], $d[2]);
    }
    function DELETE($table, $where = array())
    {
        $d = Parser::DELETE($table, $where);
        return $this->con->_query($d[0], $d[1], $d[2]);
    }
    function query($query, $obj = null, $outtypes = null, $mode = 0)
    {
        return $this->con->query($query, $obj, $outtypes, $mode);
    }
    function close()
    {
        $this->con->close();
    }
    function dev()
    {
        $this->con->dev = true;
    }
    function getLog()
    {
        return $this->con->log;
    }
    function transact($func)
    {
        $this->con->db->beginTransaction();
        $r = $func($this);
        if ($r === false)
            $this->con->db->rollBack();
        else
            $this->con->db->commit();
        return $r;
    }
    function modeLock($val)
    {
        $this->lockMode = $val;
    }
}
?>