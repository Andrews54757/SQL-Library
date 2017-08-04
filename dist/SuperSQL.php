<?php
/*
MIT License
Copyright (c) 2017 Andrew S

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

/*
 Author: Andrews54757
 License: MIT
 Source: https://github.com/ThreeLetters/SQL-Library
 Build: v1.0.0
 Built on: 04/08/2017
*/

// lib/connector/index.php
class Response
{
    public $result;
    public $affected;
    public $ind;
    public $error;
    public $errorData;
    
   /**
    * Gets data from a query
    */
    function __construct($data, $error)
    {
        
        $this->error = !$error;
        if (!$error) {
            $this->errorData = $data->errorInfo();
        } else {
            $this->result   = $data->fetchAll();
            $this->affected = $data->rowCount();
        }
        $this->ind = 0;
    }
    
   /**
    * Returns error data if there is one
    */
    function error()
    {
        return $this->error ? $this->errorData : false;
    }

   /**
    * Gets data from the query
    *
    * @returns {Array}
    */
    function getData()
    {
        return $this->result;
    }
    
   /**
    * Gets number of affected rows from the query
    *
    * @returns {Int}
    */
    function getAffected() {
        return $this->affected;
    }
    
   /**
    * Gets next row
    */
    function next()
    {
        return $this->result[$this->ind++];
    }
    
   /**
    * Resets ititerator
    */
    function reset()
    {
        $this->ind = 0;
    }
    
}

class Connector
{
    private $lastSQL;
    private $last;
    public $db;
    
   /**
    * Creates a connection
    * @param {String} dsn - DSN of the connection
    * @param {String} user - Username
    * @param {String} pass - Password
    */
    function __construct($dsn, $user, $pass)
    {
       $this->db = new \PDO($dsn, $user, $pass);
    }
    
   /**
    * Queries database
    * @param {String} query - Query to make
    *
    * @returns {SQLResponse}
    */
    function query($query)
    {
        $q = $this->$db->prepare($query);
        $q->execute();
        return new Response($q);
    }
    
   /**
    * Queries database efficiently
    * @param {String} sql - Base query
    * @param {Array} insert - array of args
    *
    * @returns {SQLResponse|SQLResponse[]}
    */
    function _query($sql, $insert)
    {
        
          //echo json_encode(array($sql,$insert));
          //return;
        if ($sql == $this->lastSQL) { // Cache
            $q = $this->last;
        } else {
            $q             = $this->db->prepare($sql);
            $this->lastSQL = $sql;
            $this->last    = $q;
        }
        
        if (count($insert) == 1) { // Single query
            $e = $q->execute($insert[0]);
            return new Response($q, $e);
        } else { // Multi Query
            $responses = array();
            foreach ($insert as $key => $value) {
                $e = $q->execute($insert[0]);
                array_push($responses, new Response($q, $e));
            }
            return responses;
        }
    }
    
   /**
    * Closes the connection
    */
    function close()
    {
        $this->$db = null;
        $this->last = null;
        $this->lastSQL = null;
    }
    
    
}

// lib/parser/index.php
class Parser
{
   /**
    * Reads argument data from a string
    *
    * @param {String} str - String to read from
    *
    * @returns {String|Boolean}
    */
    private static function parseArg(&$str)
    {
        if (substr($str, 0, 1) == "[") {
            $out = substr($str, 1, 3);
             $str = substr($str,4);
            return $out;
        } else {
            return false;
        }
    }
    
   /**
    * Reads argument data from a string
    *
    * @param {String} str - String to read from
    *
    * @returns {String|Boolean}
    */
    private static function parseArgs(&$str)
    {
        $arr = array();
        
        for ($i = 0; $i < 5; $i++) {
            
        if (substr($str, 0, 1) == "[") {
          array_push($arr,substr($str, 1, 3));
         $str = substr($str,4);
        } else {
            return $arr;
        }
            
        }
    }
   /**
    * Appends value(s) to arguments
    *
    * @param {&Array} args - Arguments to append to
    * @param {String|Int|Boolean|Array} val - Value(s) to append
    */
    private static function append(&$args, $val)
    {
        $type = gettype($val);
        if ($type == "array") {
            foreach ($val as $k => $v) {
                $k = (int)$k;
                if (!isset($args[$k]))
                    $args[$k] = array_slice($args[0], 0);
                if (!$first)
                    $first = $v;
            }
            foreach ($args as $k => $v) {
                $k = (int)$k;
                if (isset($val[$k])) {
                    array_push($args[$k], $val[$k]);
                } else {
                    array_push($args[$k], $first);
                }
            }
        } else {
            foreach ($args as $k => $v) {
                array_push($args[$k], $val);
            }
        }
    }
    
   /**
    * Appends values to arguments
    *
    * @param {&Array} insert - Arguments to append to
    * @param {Object} indexes - Indexes of keys
    * @param {Array} dt - Values to append
    */
    
    private static function append2(&$insert, $indexes, $dt)
    {
        function recurse(&$holder,$val,$indexes,$par,$lvl) {
            foreach ($val as $k => $v) {
                if (gettype($v) == "array") {
                    $a = substr($k,0,4);
                    $b = strrpos($k,"]", -1);
                     if ($b != false) {
                     $k = substr($k,$b+1);
                     }
                    if ($a != "[||]" && $a != "[&&]") {
                    $d = $indexes[$k . "#" . $lvl . "." . $par . "*"];
                        if (!$d) $d = $indexes[$k . "*"];
                     foreach ($v as $i => $j) {
                         $holder[$d + $i] = $j;
                     }   
                    } else {
                    recurse($holder,$v,$indexes,$k, $lvl + 1);
                    }
                } else {
                     $b = strrpos($k,"]", -1);
                     if ($b != false) {
                     $k = substr($k,$b+1);
                     }
                    $d = $indexes[$k . "#" . $lvl . "." . $par];
                    if (!$d) $d = $indexes[$k];
                $holder[$d] = $v;
                }
            }
        }
        
        $last = false;
        foreach ($dt as $key => $val) {
            if (!isset($insert[$key]))
                $insert[$key] = array();
            $holder = $last ? array_slice($last, 0) : array();
            
            recurse($holder,$val,$indexes,"",0);
            if (!$last)
                $last = $holder;
            $c = count($holder);
            
            for ($i = 0; $i < $c; $i++) {
                array_push($insert[$key], $holder[$i]);
            }
        }
        
    }
    
   /**
    * Constructs logical conditional statements
    *
    * @param {Object|Array} arr - Conditions
    * @param {Array} args
    *
    * @returns {String}
    */
    private static function conditions($arr, &$args, $quotes = true)
    {
     
        $cond = function(&$cond,&$arr,&$args,$quotes,$statement,$default,&$indexes,&$i,$lvl,$parent,$append = false) {
            
            $b = 0;
            foreach ($arr as $key => $value) {
                
                $arg = self::parseArg($key);
                
                $type = gettype($value);
                
                $s = $statement;
                
                $o = $default;
                
                $useBind = false;
                
                if ($type == "array") {
                switch ($arg) {
                    case "&&]":
                        $s = " AND ";
                        $arg = self::parseArg($key);
                        $useBind = true;
                        break;
                    case "||]":
                        $s = " OR ";
                        $arg = self::parseArg($key);
                        $useBind = true;
                         break;
                    }
                }
                
                    switch ($arg) {
                case ">>]":
                    $o = " > ?";
             
                    break;
                case "<<]":
                    $o = " < ?";
            
                    break;
                case ">=]":
                    $o = " >= ?";
                
                    break;
                case "<=]":
                    $o = " <= ?";
                    break;
                default:      
                if ($useBind) $o = " = ?";
                    break;
                    }
                
                if ($b != 0) $sql .= $statement;
                
                if ($type == "array") {
                  if ($useBind) {
                    $sql .= "(" .$cond($cond,$value,$args,$quotes,$s,$o,$indexes,$i,$lvl + 1,$key,$append) . ")";
                  } else {
                       $indexes[$key . "*"] = $i;
                       $indexes[$key . "#" . $lvl . "." . $parent . "*"] = $i;
                      foreach ($value as $k => $v) {
                        if ($k != 0) $sql .= $statement;
                         if ($quotes) {
                          $sql .= "`" . $key . "`" . $o;
                         } else {
                            $sql .= $key . $o;
                         }
                          $i++;   
                          if ($append) {
                               array_push($args[0],$v); 
                          }
                      }
                  }
                } else {
                 if ($quotes) {
                  $sql .= "`" . $key . "`" . $o;
                 } else {
                   $sql .= $key . $o;
                 }
                    if ($append) {
                      array_push($args[0],$value);
                    } 
                     $indexes[$key] = $i;
                      $indexes[$key . "#" . $lvl . "." . $parent] = $i++;
                }
                $b++;
                }
            
              return $sql;
            
        };
        
$indexes = array();
     $i = 0;
      if (isset($arr[0])) {
           $sql = $cond($cond,$arr[0],$args,$quotes," AND ", " = ?",$indexes,$i,0,false);
           self::append2($args,$indexes,$arr);
      } else {
            $sql = $cond($cond,$arr,$args,$quotes," AND ", " = ?",$indexes,$i,0,false,true);
     
          
      }
        return $sql;
    }
    
   /**
    * Constructs SQL commands (SELECT)
    *
    * @param {String} table - SQL Table
    * @param {Array} columns - Columns to return
    * @param {Object|Array} where - Where clause
    * @param {Object|null} join - Join clause 
    * 
    * @returns {Array}
    */
    static function SELECT($table, $columns, $where, $join)
    {
        
        $sql = "SELECT ";
        
        $len = count($columns);
        
        $insert = array(
            array()
        );
        
        
        if ($len == 0) { // none
            $sql .= "*";
        } else { // some
            if ($columns[0] == "DISTINCT") {
                $dis = true;
                $sql .= "DISTINCT ";
            }
            
            if (!$dis || $len != 2) { // has var
                
                for ($i = $dis ? 1 : 0; $i < $len; $i++) {
                    
                    if ($i != 0 && (!$dis || $i != 1)) {
                        $sql .= ", ";
                    }
                    $sql .= "`" . $columns[$i] . "`";
                }
                
            } else
                $sql .= "*";
        }
        
        $sql .= " FROM `" . $table . "`";
        
        if ($join) {
            foreach ($join as $key => $val) {
                $arg = self::parseArg($key);
                switch ($arg) {
                    case "<<]":
                       
                        $sql .= " RIGHT JOIN ";
                        break;
                    case ">>]":
                  
                        $sql .= " LEFT JOIN ";
                        break;
                    case "<>]":
                    
                        $sql .= " FULL JOIN ";
                        break;
                    default: // inner join
                        
                        $sql .= " JOIN ";
                        break;
                }
                
                $sql .= "`" . $key . "` ON ";
                
                $c = self::conditions($val, $insert,false);
                $sql .= $c;
            }
        }
        
        if (count($where) != 0) {
            $sql .= " WHERE ";
            $c = self::conditions($where, $insert);
            $sql .= $c;
        }
        
        return array(
            $sql,
            $insert
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
    static function INSERT($table, $data)
    {
        $sql    = "INSERT INTO `" . $table . "` (";
        $insert = array(
            array()
        );
        $values = "";
        
        $i = 0;
        
        if (isset($data[0])) {
            $indexes = array();
            foreach ($data[0] as $key => $val) {
                if ($i != 0) {
                    $sql .= ", ";
                    $values .= ", ";
                }
                $sql .= "`" . $key . "`";
                $values .= "?";
                $indexes[$key] = $i++;
            }
            self::append2($insert, $indexes, $data);
        } else {
            foreach ($data as $key => $val) {
                if ($i != 0) {
                    $sql .= ", ";
                    $values .= ", ";
                }
                $sql .= "`" . $key . "`";
                $values .= "?";
                $i++;
                
                self::append($insert, $val);
            }
        }
        
        $sql .= ") VALUES (" . $values . ")";
        
        return array(
            $sql,
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
        $sql    = "UPDATE `" . $table . "` SET ";
        $insert = array(
            array()
        );
        
        $i = 0;
        if (isset($data[0])) {
            $indexes = array();
            foreach ($data[0] as $key => $val) {
                if ($i != 0) {
                    $sql .= ", ";
                }
                $indexes[$key] = $i++;
                $sql .= "`" . $key . "` = ?";
            }
            self::append2($insert, $indexes, $data);
        } else {
            foreach ($data as $key => $val) {
                if ($i != 0) {
                    $sql .= ", ";
                }
                $i++;
                $sql .= "`" . $key . "` = ?";
                self::append($insert, $val);
            }
        }
        
        if (count($where) != 0) {
            $sql .= " WHERE ";
            $c = self::conditions($where, $insert);
            $sql .= $c;
        }
        return array(
            $sql,
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
        $sql    = "DELETE FROM `" . $table . "`";
        $insert = array(
            array()
        );
        
        if (count($where) != 0) {
            $sql .= " WHERE ";
            $c = self::conditions($where, $insert);
            $sql .= $c;
        }
        return array(
            $sql,
            $insert
        );
    }
    
    
}

// index.php
class SuperSQL
{
    public $connector;
    
   /**
    * Creates a connection
    * @param {String} dsn - DSN of the connection
    * @param {String} user - Username
    * @param {String} pass - Password
    */
    function __construct($dsn, $user, $pass)
    {
        $this->connector = new Connector($dsn, $user, $pass);
    }
    
   /**
    * Queries a SQL table (SELECT)
    *
    * @param {String} table - SQL Table
    * @param {Array} columns - Columns to return
    * @param {Object|Array} where - Where clause
    * @param {Object|null} join - Join clause 
    * 
    * @returns {SQLResponse|SQLResponse[]}
    */
    function SELECT($table, $columns, $where, $join = null)
    {
        $d = Parser::SELECT($table, $columns, $where, $join);
        return $this->connector->_query($d[0], $d[1]);
    }
    
   /**
    * Inserts data into a SQL table
    *
    * @param {String} table - SQL Table
    * @param {Object|Array} data - Data to insert
    *
    * @returns {SQLResponse|SQLResponse[]}
    */
    function INSERT($table, $data)
    {
        $d = Parser::INSERT($table, $data);
        return $this->connector->_query($d[0], $d[1]);
    }
    
   /**
    * Updates a SQL table
    *
    * @param {String} table - SQL Table
    * @param {Object|Array} data - Data to update
    * @param {Object|Array} where - Where clause
    *
    * @returns {SQLResponse|SQLResponse[]}
    */
    function UPDATE($table, $data, $where)
    {
        $d = Parser::UPDATE($table, $data, $where);
        return $this->connector->_query($d[0], $d[1]);
    }
    
   /**
    * Deletes from a SQL table
    *
    * @param {String} table - SQL Table
    * @param {Object|Array} where - Where clause
    *
    * @returns {SQLResponse|SQLResponse[]}
    */
    function DELETE($table, $where)
    {
        $d = Parser::DELETE($table, $where);
        return $this->connector->_query($d[0], $d[1]);
    }
    
    
}
?>