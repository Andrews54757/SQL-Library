<?php
namespace SuperSQL\lib;
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
// BUILD BETWEEN
class Parser
{
    /**
     * Reads argument data from a string
     *
     * @param {String} str - String to read from
     *
     * @returns {String|Boolean}
     */
    static function getArg(&$str)
    {
        preg_match('/^(?:\[(.{2})\])(.*)/', $str, $m);
        if (isset($m[1])) {
            $str = $m[2];
            return $m[1];
        }
        return false;
    }
    static function isRaw(&$key)
    {        
        if ($key[0] === '#') { // 71
            $key = substr($key, 1);
            return true;
        }
        return false;
    }
    static function isSpecial($type) {
        return $type === 'json' || $type === 'object';
    }
    /**
     * Appends value(s) to arguments
     *
     * @param {&Array} args - Arguments to append to
     * @param {String|Int|Boolean|Array} val - Value(s) to append
     */
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
    static function stripArgs(&$key)
    {
        preg_match('/(?:\[.{2}\]){0,3}([^\[]*)/', $key, $m); // 13 steps
        return $m[1];
    }
    /**
     * Appends values to arguments
     *
     * @param {&Array} insert - Arguments to append to
     * @param {Object} indexes - Indexes of keys
     * @param {Array} dt - Values to append
     */
    static function append2(&$insert, $indexes, $dt, &$values, $m = false)
    {
        $len = count($dt);
        if ($m) {
            self::recurse($values,$dt[0],$indexes,'',$values,0);
        }
        for ($key = 1; $key < $len; $key++) {
            if (!isset($insert[$key - 1]))
                $insert[$key - 1] = array();
            self::recurse($insert[$key - 1], $dt[$key], $indexes, '', $values,1);
        }
    }
    private static function recurse(&$holder, $val, $indexes, $par, $values,$m)
    {
        foreach ($val as $k => $v) {
            $k = self::stripArgs($k);
            $k1 = $k . '#' . $par;
            if (isset($indexes[$k1]))
                $d = $indexes[$k1];
            else
                $d = $indexes[$k];
            if (is_array($v) && !self::isSpecial($values[$d][2])) {
                if (isset($v[0])) {
                    foreach ($v as $i => $j) {
                        $i += $d;
                        if ($m && isset($holder[$i]))
                            trigger_error('Key collision: ' . $k, E_USER_WARNING);
                        $holder[$i] = self::value($values[$i][2],$j);
                       if ($m) $holder[$i] = $holder[$i][0];
                    }
                } else {
                    self::recurse($holder, $v, $indexes, $par . '/' . $k, $values,$m);
                }
            } else {
                if ($m && isset($holder[$d]))
                    trigger_error('Key collision: ' . $k, E_USER_WARNING);
                $holder[$d] = self::value($values[$d][2],$v);
                if ($m) $holder[$d] = $holder[$d][0];
            }
        }
    }
    /**
     * Puts quotes around a string
     *
     * @param {String} str - String to quote
     *
     * @returns {String}
     */
    static function quote($str)
    {
        preg_match('/([a-zA-Z0-9_]*)\.?([a-zA-Z0-9_]*)?/', $str, $m); // 8 steps
        if ($m[2] !== '') {
            return '`' . $m[1] . '`.`' . $m[2] . '`';
        } else {
            return '`' . $m[1] . '`';
        }
    }
    static function quoteArray(&$arr)
    {
        foreach ($arr as &$v) {
            $v = self::quote($v);
        }
    }
    /**
     * Forms the table
     *
     * @param {String|Array} table - tables(s)
     *
     * @returns {String}
     */
    static function table($table)
    {
        if (is_array($table)) {
            $sql = '';
            foreach ($table as $i => $val) {
                $alias = self::getType($val);
                if ($i !== 0)
                    $sql .= ', ';
                $sql .= '`' . $val . '`';
                if ($t)
                    $sql .= ' AS `' . $alias . '`';
            }
            return $sql;
        } else {
            return '`' . $table . '`';
        }
    }
    static function value($type, $value)
    {
        if (!$type) $type = gettype($value);
        $code  = \PDO::PARAM_STR;
        if ($type === 'integer' || $type === 'int') {
            $code  = \PDO::PARAM_INT;
            $value = (int) $value;
        } else if ($type === 'string' || $type === 'str' || $type === 'double') {
            $value = (string) $value;
        } else if ($type === 'boolean' || $type === 'bool') {
            $code  = \PDO::PARAM_BOOL;
            $value = $value ? '1' : '0';
        } else if ($type === 'null' || $type === 'NULL') {
            $code  = \PDO::PARAM_NULL;
            $value = null;
        } else if ($type === 'resource' || $type === 'lob') {
            $code  = \PDO::PARAM_LOB;
        } else if ($type === 'json') {
            $value = json_encode($value);
        } else if ($type === 'object') {
            $value = serialize($value);
        } else {
            trigger_error('Invalid type ' . $type, E_USER_WARNING);
        }
        return array(
            $value,
            $code,
            $type
        );
    }
    static function getType(&$str)
    {
        preg_match('/([^\[]*)(?:\[([^\]]*)\])?/', $str, $m);
        $str = $m[1];
        return isset($m[2]) ? $m[2] : false;
    }
    static function rmComments($str)
    {
        preg_match('/([^#]*)/', $str, $m);
        return $m[1];
    }
    /**
     * Constructs logical conditional statements
     *
     * @param {Object|Array} arr - Conditions
     * @param {Array} values - Output values
     *
     * @returns {String}
     */
    static function conditions($dt, &$values, &$map = false, &$index = 0, $join = ' AND ', $operator = ' = ', $parent = '')
    {
        $num = 0;
        $sql = '';
        foreach ($dt as $key => $val) {
            if (is_int($key)) 
                $key = $val;   
            preg_match('/^(?<r>\#)?(?:(?:\[(?<a>.{2})\])(?:(?:\[(?<b>.{2})\])(?:\[(?<c>.{2})\])?)?)?(?<out>.*)/', $key, $m); // 14 steps
            $raw  = ($m['r'] === '#');
            $arg  = $m['a'];
            $key  = $m['out'];
            $newJoin     = $join;
            $newOperator = $operator;
            $type        = $raw ? false : self::getType($key);
            $arr         = is_array($val) && !self::isSpecial($type);
            $useBind     = $arr && !isset($val[0]);
            if ($arg && ($arg === '||' || $arg === '&&')) {
                $newJoin = ($arg === '||') ? ' OR ' : ' AND ';
                $arg     = $m['b'];
                if ($arr && $arg && ($arg === '||' || $arg === '&&')) {
                    $join    = $newJoin;
                    $newJoin = ($arg === '||') ? ' OR ' : ' AND ';
                    $arg     = $m['c'];
                }
            }
            $between = false;
            $match = false;
            if ($arg && $arg !== '==') {
                if ($arg === '!=' || $arg === '>=' || $arg === '<=') {
                    $newOperator = ' ' . $arg . ' ';
                } else if ($arg === '>>') {
                    $newOperator = ' > ';
                } else if ($arg === '<<') {
                    $newOperator = ' < ';
                } else if ($arg === '~~') {
                    $newOperator = ' LIKE ';
                } else if ($arg === '!~') {
                    $newOperator = ' NOT LIKE ';
                } else if ($arg === '><' || $arg === '<>') {
                    $between = true;
                } else if ($arr && $arg === 'MM') {
                    $match = $m['c'] ? $m['c'] : $m['b'];
                    $mode_array = array(
                        'NN' => 'IN NATURAL LANGUAGE MODE',
                        'NQ' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
                        'BB' => 'IN BOOLEAN MODE',
                        'QQ' => 'WITH QUERY EXPANSION'
                    );
                    $match = isset($mode_array[$match]) ? ' ' . $mode_array[$match] : '';
                } else {
                    throw new \Exception('Invalid operator ' . $arg . ' Available: ==,!=,>>,<<,>=,<=,~~,!~,<>,><');
                }
            } else if ($useBind || $arg === '==')
                $newOperator = ' = '; // reset
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
                    if ($match !== false) {
                        $keyword = $val['keyword'];
                        unset($val['keyword']);
                        self::quoteArray($val);
                        $sql .= 'MATCH(' . implode($val, ', ') . ') AGAINST (?' . $match . ')';
                        array_push($values, self::value($type, $keyword));
                    } else if ($between) {
                        $index += 2;
                        $sql .= $column . ($arg === '<>' ? 'NOT' : '') . ' BETWEEN ';
                        if ($raw) {
                            $sql .= $val[0] . ' AND ' . $val[1];
                        } else {
                            $sql .= '? AND ?';
                            array_push($values, self::value($type, $val[0]));
                            array_push($values, self::value($type, $val[1]));
                        }
                    } else {
                        $m = isset($val[1]);
                        $len = $m ? count($val) : $val[0];
                        for ($k = 0; $k < $len; $k++) {
                            $v = $m ? $val[$k] : '';
                            if ($k !== 0)
                                $sql .= $newJoin;
                            ++$index;
                            $sql .= $column . $newOperator;
                            if ($raw) {
                                $sql .= $v;
                            } else {
                                $sql .= '?';
                                array_push($values, self::value($type, $v));
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
                    $sql .= '?';
                    array_push($values, self::value($type, $val));
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
                    $sql .= ' RIGHT';
                    break;
                case '>>':
                    $sql .= ' LEFT';
                    break;
                case '<>':
                    $sql .= ' FULL';
                    break;
                case '>~':
                    $sql .= ' LEFT OUTER';
                    break;
            }
            
            $sql .= ' JOIN `' . $key . '` ON ';
            if ($raw) {
                $sql .= $val;
            } else {
                $sql .= self::conditions($val, $values, $f, $i);
            }
        }
    }
    static function WHERE(&$sql,$where,&$values,&$insert,$i = 0) {
        $sql .= ' WHERE ';
            if (isset($where[0])) {
                 $index = array();
                $sql .= self::conditions($where[0], $values, $index, $i);
                $m = isset($where[1][0]);
                self::append2($insert, $index, $m ? $where[1] : $where, $values,$m);
            } else {
                $sql .= self::conditions($where, $values);
            }
    }
    static function columns($columns, &$sql, &$outTypes)
    {
        $into = '';
        $f    = $columns[0][0];
        if ($f === 'D' || $f === 'I') {
            if ($columns[0] === 'DISTINCT') {
                $sql .= 'DISTINCT ';
                array_splice($columns, 0, 1);
            } else if (substr($columns[0], 0, 11) === 'INSERT INTO') {
                $sql = $columns[0] . ' ' . $sql;
                array_splice($columns, 0, 1);
            } else if (substr($columns[0], 0, 4) === 'INTO') {
                $into = ' ' . $columns[0] . ' ';
                array_splice($columns, 0, 1);
            }
        }
        if (isset($columns[0])) { // has var
            if ($columns[0] === '*') {
                array_splice($columns, 0, 1);
                $sql .= '*';
                foreach ($columns as $i => $val) {
                    $type = self::getType($val);
                    $outTypes[$val] = $type;
                }
            } else {
                foreach ($columns as $i => $val) {
                    $a = self::getType($val);
                    $alias = false;
                    if ($a) { // name[alias][type]
                        $alias = $a;
                        $b = self::getType($val);
                        if ($b) {
                            $type = $b;
                        } else {
                            if ($alias === 'json' || $alias === 'object' || $alias === 'int' || $alias === 'string' || $alias === 'bool' || $alias === 'double') {
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
                    if ($i !== 0) {
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
    /**
     * Constructs SQL commands (SELECT)
     *
     * @param {String} table - SQL Table
     * @param {Array} columns - Columns to return
     * @param {Object|Array} where - Where clause
     * @param {Object|null} join - Join clause 
     * @param {Int} limit - Limit clause
     * 
     * @returns {Array}
     */
    static function SELECT($table, $columns, $where, $join, $limit)
    {
        $sql      = 'SELECT ';
        $values   = $insert = array();
        $outTypes = null;
        $i        = 0;
        if (!isset($columns[0])) { // none
            $sql .= '*';
        } else { // some
            self::columns($columns, $sql, $outTypes);
        }
        $sql .= ' FROM ' . self::table($table);
        if ($join) {
            self::JOIN($join, $sql, $values, $i);
        }
        if (!empty($where)) {
            self::WHERE($sql,$where,$values,$insert,$i);
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
    /**
     * Constructs SQL commands (INSERT INTO)
     *
     * @param {String} table - SQL Table
     * @param {Object|Array} data - Data to insert
     *
     * @returns {Array}
     */
    static function INSERT($table, $data, $append)
    {
        $sql      = 'INSERT INTO ' . self::table($table) . ' (';
        $values   = $insert = $index = array();
        $valuestr = '';
        $i        = 0;
        $multi    = isset($data[0]);
        $temp     = isset($data[1][0]);
        $dt       = $multi ? $data[0] : $data;
        foreach ($dt as $key => $val) {
            if ($temp) $key = $val;
            $raw = self::isRaw($key);
            if ($i) {
                $sql .= ', ';
                $valuestr .= ', ';
            } else $i = 1;
            if (!$raw)
                $type = self::getType($key);
            if ($temp) $val = $data[1][0][$key];
            $sql .= '`' . $key . '`';
            if ($raw) {
                $valuestr .= $val;
            } else {
                $valuestr .= '?';
                $m = !$multi && (!$type || !self::isSpecial($type)) && is_array($val);
                array_push($values, self::value($type, $m ? $val[0] : $val));
                if ($multi) {
                    $index[$key] = array(
                        $val,
                        $type
                    );
                } else if ($m) {
                    self::append($insert, $val, $i++, $values);
                }
            }
        }
        $sql .= ') VALUES (' . $valuestr . ')';
        
        if ($multi) {
            if ($temp) $data = $data[1];
            unset($data[0]);
            foreach ($data as $v) {
                $sql .= ', (' . $valuestr . ')';
                foreach ($index as $key => $val) {
                    array_push($values, self::value($val[1], isset($v[$key]) ? $v[$key] : $val[0]));
                }
            }
        }
        if ($append) $sql .= ' ' . $append;
        return array(
            $sql,
            $values,
            $insert
        );
    }
    /**
     * Constructs SQL commands (UPDATE)
     *
     * @param {String} table - SQL Table
     * @param {Object|Array} data - Data to update
     * @param {Object|Array} where - Where clause
     *
     * @returns {Array}
     */
    static function UPDATE($table, $data, $where)
    {
        $sql    = 'UPDATE ' . self::table($table) . ' SET ';
        $values = $insert = $indexes = array();
        $i      = $j = 0;
        $multi  = isset($data[0]);
        $dt     = $multi ? $data[0] : $data;
        $temp   = isset($data[1][0]);
        foreach ($dt as $key => $val) {
            if ($temp) $key = $val;
            $raw = self::isRaw($key);
            if ($j) {
                $sql .= ', ';
            } else $j = 1;
            if ($raw) {
                $sql .= '`' . $key . '` = ' . $val;
            } else {
                $arg = self::getArg($key);
                $type = self::getType($key);
                if ($temp) $val = $data[1][0][$key];
                $sql .= '`' . $key . '` = ';
                if ($arg) {
                    $sql .= '`' . $key . '` ';
                    switch ($arg) {
                        case '+=':
                            $sql .= '+ ?';
                            break;
                        case '-=':
                            $sql .= '- ?';
                            break;
                        case '/=':
                            $sql .= '/ ?';
                            break;
                        case '*=':
                            $sql .= '* ?';
                            break;
                    }
                } else $sql .= '?';
                $m   = (!$type || !self::isSpecial($type)) && is_array($val);
                array_push($values, self::value($type, $m ? $val[0] : $val));
                if ($multi) {
                    $indexes[$key] = $i++;
                } else if ($m) {
                    self::append($insert, $val, $i++, $values);
                }
            }
        }
        if ($multi)
            self::append2($insert, $indexes, $temp ? $data[1] : $data, $values);
        if (!empty($where))
            self::WHERE($sql,$where,$values,$insert,$i);
        
        return array(
            $sql,
            $values,
            $insert
        );
    }
    /**
     * Constructs SQL commands (DELETE)
     *
     * @param {String} table - SQL Table
     * @param {Object|Array} where - Where clause
     *
     * @returns {Array}
     */
    static function DELETE($table, $where)
    {
        $sql    = 'DELETE FROM ' . self::table($table);
        $values = $insert = array();
        if (!empty($where)) {
            self::WHERE($sql,$where,$values,$insert);
        }
        return array(
            $sql,
            $values,
            $insert
        );
    }
}
// BUILD BETWEEN
?>
