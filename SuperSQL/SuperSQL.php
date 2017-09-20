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
use SuperSQL\lib\SQLConnector as SQLConnector;
// BUILD BETWEEN
class SuperSQL
{
    public $con;
    /**
     * Creates a connection
     * @param {String} dsn - DSN of the connection
     * @param {String} user - Username
     * @param {String} pass - Password
     */
    function __construct($dsn, $user, $pass)
    {
        $this->con = new SQLConnector($dsn, $user, $pass);
    }
    /**
     * Queries a SQL table (SELECT)
     *
     * @param {String} table - SQL Table
     * @param {Array} columns - Columns to return
     * @param {Object|Array} where - Where clause
     * @param {Object|null} join - Join clause 
     * @param {String} limit - Limit clause 
     *
     * @returns {SQLResponse|SQLResponse[]}
     */
    function SELECT($table, $columns = array(), $where = array(), $join = null, $limit = false)
    {
        if ((is_int($join) || is_string($join) || isset($join[0])) && !$limit) {
            $limit = $join;
            $join  = null;
        }
        $d = Parser::SELECT($table, $columns, $where, $join, $limit);
        return $this->con->_query($d[0], $d[1], $d[2], $d[3], 1);
    }
    /**
     * Inserts data into a SQL table
     *
     * @param {String} table - SQL Table
     * @param {Object|Array} data - Data to insert
     *
     * @returns {SQLResponse|SQLResponse[]}
     */
    function INSERT($table, $data, $append = null)
    {
        $d = Parser::INSERT($table, $data, $append);
        return $this->con->_query($d[0], $d[1], $d[2]);
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
    function UPDATE($table, $data, $where = array())
    {
        $d = Parser::UPDATE($table, $data, $where);
        return $this->con->_query($d[0], $d[1], $d[2]);
    }
    /**
     * Deletes from a SQL table
     *
     * @param {String} table - SQL Table
     * @param {Object|Array} where - Where clause
     *
     * @returns {SQLResponse|SQLResponse[]}
     */
    function DELETE($table, $where = array())
    {
        $d = Parser::DELETE($table, $where);
        return $this->con->_query($d[0], $d[1], $d[2]);
    }
    /**
     * Query
     */
    function query($query, $obj = null, $outtypes = null, $mode = 0)
    {
        return $this->con->query($query, $obj, $outtypes, $mode);
    }
    /**
     * Closes the connection
     */
    function close()
    {
        $this->con->close();
    }
    /**
     * Turns on dev mode
     */
    function dev()
    {
        $this->con->dev = true;
    }
    /**
     * Get log
     */
    function getLog()
    {
        return $this->con->log;
    }
    /**
     * Transaction
     */
    function transact($func)
    {
        $this->con->db->beginTransaction();
        try {
        $r = $func($this);
        } catch (\Exception $e) {
            $this->con->db->rollBack();
            return false;
        }
        if ($r === false)
            $this->con->db->rollBack();
        else
            $this->con->db->commit();
        return $r;
    }
}
// BUILD BETWEEN
?>
