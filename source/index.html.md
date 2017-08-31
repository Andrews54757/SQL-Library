---
title: SuperSQL

language_tabs:
  - php
  
toc_footers:
 - <a href='https://github.com/ThreeLetters/SuperSQL/archive/master.zip'>Download</a>
 - <a href='https://github.com/ThreeLetters/SuperSQL/'>Github</a>
 
search: true
---

# SuperSQL - Overview

> Setup

```php
<?php
// MySql setup
$host = "localhost";
$db = "test";
$user = "root";
$pass = "1234";

$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=utf8";
$SuperSQL = new SuperSQL($dsn,$user,$pass);
?>
```

> Setup with helper

```php
<?php
$SuperSQL = SQLHelper::connect($host,$db,$user,$pass);
?>
```


SlickInject and Medoo on steroids - The most advanced and lightweight library of it's kind. Develop using PHP & SQL quickly and securely.

### Purpose

1. To provide a very fast and efficient way to edit sql databases
2. To provide a easy method of access

### Main Features

1. Very small - 28.3KB one file (Unminified, `dist/SuperSQL.php`. Minified version: 12.5KB)
2. Simple and easy - Very easy to lean. We also provide a simple and advanced API
3. Compatability - Supports major SQL databases
4. Customisability - We offer multiple files for your needs
5. Efficiency - This module was built with speed in mind.
6. Complexity - This module allows you to make all kinds of complex queries
7. Security - This module prevents SQL injections.
8. Availability - This module is FREE. Licensed under the MIT license.

### Usage

```php
<?php
new SuperSQL($dsn,$user,$pass);
?>
```

You may either

1. Use the built file (/dist/SuperSQL.php - preferred)
2. Use the library (Autoload all in SuperSQL/)
3. Use composer (`composer require threeletters/supersql`)

### Build
To build this library, do 

`node builder.js`

It will build to `/dist/SuperSQL*.php`

# Basics
These are the basic functionalities of SuperSQL. 

## Responses

> Error handling

```php
<?php
$Response = $SuperSQL->select("test",[],[
    "#a" => "WHERE SELECT INSERT LOL" // raw
]); // SELECT * FROM `test` WHERE `a` = WHERE SELECT INSERT 

echo json_encode($Response->getData()); // NULL

echo json_encode($response->error()); // ["42000",1064,"You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'WHERE SELECT INSERT LOL' at line 1"]
?>
```

> Iterator usage

```php
<?php
$Response = $SuperSQL->select("test",[],[
    "a" => "WHERE SELECT INSERT LOL"
]); // SELECT * FROM `test` WHERE `a` = 'WHERE SELECT INSERT' 

echo $response->error(); // FALSE

while ($row = $response->next()) { // Use the iterator to iterate through rows easily

.... // Do some stuff

}

$response->reset(); // Reset iterator so you can do the above code again
?>
```

When you make a query, SuperSQL will return a SQLResponse object.

### Response->getData($dontFetch = false)
Get all rows. If dontfetch is true, then it will only return the results that have already been fetched

### Response->error() 
Returns error object if there is one. False otherwise

### Response->getAffected()
Get number of rows affected by the query

### Response->next()
Get next row

### Response->reset()
Reset iterator.

<aside class="notice">
Using Response->next is recommended as it is more efficient - It will fetch the row from db when it gets there, rather than fetching all at start
</aside>

## Conditionals

> Conditionals (WHERE & JOIN)

```php
<?php
$where = array(
 "arg1" => "val1", // AND arg1 = val1
 "[>>]arg2" => "val2", // AND arg2 > val2
 "[<<]arg3" => "val3", // AND arg3 < val3
 "[>=]arg4" => "val4", // AND arg4 >= val4
 "[<=]arg5" => "val5", // AND arg5 <= val5
 "[!=]arg6" => "val6", // AND arg6 != val6
 "[||]" => [ // Bind ||.
     "arg7" => "val7"
 ],
 "[&&][>>]" => [ // Bind >.
     "arg8" => "val8"
 ],
 "arg9" => ["val9a","val9b"],
 
 "arg10[~~]" => "%arg10%",
 "arg11[!~]" => "%arg11%"
);
?>
```

Conditional statements are extremly customisable. WHERE and JOIN clauses are conditional statements. 

<aside class="success">
Available operators: `==`, `>>`, `<<`, `>=`, `<=`, `!=`, `~~`, `!~`
</aside>

<aside class="success">
Available joins: `&&`(AND), `||`(OR)
</aside>

<aside class="notice">
To make duplicate keys work for binds, you can add a name to the bind. Ex: `[&&]name`
</aside>

<aside class="notice">
You can also bind operators. Not just `&&`(AND) or `||`(OR). However, the join (&& or ||), must come first
</aside>

## Multi-queries

> Multi-Querying

```php
<?php
// Way 1

array(
array( // NOTE: While the all the arrays dont have to be identical, the first one should have the most items
"arg1"=> "val1",
"arg2"=> "val2"
),
array(
"arg2"=> "val3"
}
); // -> [["val1","val2"],["val1","val3"]] - Two queries

// Way 2 (only works with the data argument in INSERT and UPDATE)

array(
"arg1" => "val1",
"arg2" => array("val2","val3")
); // -> [["val1","val2"],["val1","val3"]] - Two queries


// Binds
array(
    array(
        "hello" => "world"
        "[||]" => array(
            "foo" => "bar",
            "num" => 123
        )
    ),
    array( // this also works
    "hello" => "hi!",
    "foo" => "lol"
    )
);

array(
    array( // Uh-oh - collision
        "[>>]lol" => 3
        "[||]bind1" => array(
            "foo" => "bar",
            "lol" =>  5
        ),
        "[>>]bind2" => array(
            "lols" => 231
        )
    ),
    array( // args will be preserved
    "lol" => 2,
    "bind1" => array(
        "foo" => "lol"
    )
    )
);
?>
```

Multiqueries can be done too. This allows for highly efficient repetative queries. Note: Only the values of WHERE and INSERT work with this. VALUES, not KEYS.

<aside class="success">
The keys for the second or later multi-query arrays do not have to be  exact. For example, the first array might have `[>>]test[int]` as a key, but the second one can have just `test`. Anything within brakets  in the key will be discarded
</aside>

<aside class="success">
The keys do not have to be in order - SuperSQL will do that for you.
</aside>

<aside class="success">
Binds dont have to be reproduced in the second or more arrays. You can put it in global scope if you like.
</aside>

<aside class="notice">
If there are key collisions with binds - no problem! You can reproduce the bind in the second or more arrays too.
</aside>

## Key-collisions

Since SuperSQL uses associative arrays, key collisions can occur. To solve this problem, add "#id (replace id with something)" to the key.

<aside class="notice">
This is wrong: `[>>]somecolumn[int]#someid`, this is right: `[>>]somecolumn#someid[int]`
</aside>

## Multi-Table support

> Multi-Table queries

```php
<?php
$SuperSQL->SELECT(["table1","table2"],[],[]);
?>
```

If you want to query multiple tables at once, put the tables in as an array

## Type Casting

> Type Casting

```php
<?php
$SuperSQL->INSERT("sensitive_data",[ // NOTE: Also works with any other query. ALSO NOTE: Types are case-insensitive
   "nuclear_codes[int]" => 138148347734, // Integer (Use [int] or [integer]
   "in_state_of_emergency[bool]" => false, // Boolean (Use [bool] or [boolean]
   "secret_files[lob]" => $file // Large Objects/Resources (Use [lob] or [resource])
   "fake_data[null]" => null // Null values (use [null])
]);
?>
```

If you want to set the type of the input, you can set it by adding `[type] (replace type with type)`.

<aside class="success">
Available types: `int`, `bool`, `lob`, `null`, `json`, `obj`
</aside>

## SQL Functions/raw

> SQL functions/Raw

```php
<?php
$SuperSQL->INSERT("times",[
    "#time" => "NOW()"
]);
?>
```

If you want to use SQL functions such as `NOW()` or want use insert raw, unescaped data, add `#` at the beginning of the key

<aside class="notice">
Programming guide: Please try to avoid SQL functions. If you can, use php. (EX:, replace `NOW()` with `date('Y-m-d H:i:s')`)
</aside>

## Alias

> Alias

```php
<?php
$SuperSQL->SELECT("users",["user_id[id]"]);
?>
```

You can use an alias for columns and/or tables by adding `[aliasname]`.

<aside class="notice">
Aliases go after the key, not before ex: `[alias]key` is WRONG, `key[alias]` is RIGHT
</aside>

## Custom Queries

Custom queries can be made using `$SuperSQL->query($query)`.

<aside class="notice">
Programming guide: If you can, use raw queries. For example, theres nothing sensitive in `SELECT * FROM `table`, just do that instead of `$SuperSQL->SELECT("table");`
</aside>


# Queries

```php
<?php
$SuperSQL->SELECT($table, $columns, $where[,$join[, $limit/$append);

$SuperSQL->INSERT($table, $data);

$SuperSQL->UPDATE($table, $data, $where);

SuperSQL->DELETE($table, $where);

?>
```

## SELECT
```php
<?php
$SuperSQL->SELECT("horizon", [], [], array(
    // [>>] - Right join
    // [<<] - Left join
    // [><] - Inner join (Default)
    // [<>] - Full join
    "[><]meteors" => array("horizon.object" => "meteors.object"), // JOIN
),5); // only 5 rows

$SuperSQL->SELECT("table", [
    "DISTINCT", // Distinct items only
    "col1",
    "col2[alias][int]", // alias and type-casting
    "col3[alias2]", // alias
    "col4[json]" // type casting
]); // SELECT DISTINCT `col1`, `col2` AS `alias`, `col3` AS `alias2`, `col4` FROM `table`

$SuperSQL->SELECT("table", [
    'DISTINCT',
    '*', // select all
    'data[json]' // data is converted from json
],[5,2]); // SELECT DISTINCT * FROM `table` LIMIT 5 OFFSET 2

$SuperSQL->SELECT("users", [], null, [
    'GROUP' => 'user_group',
    'HAVING' => 'COUNT(`users`) > 10',
    'LIMIT' => 5,
    'OFFSET' => 2,
    'ORDER' => 'column'
]); // SELECT * FROM `users` GROUP BY `user_group` HAVING COUNT(`users`) > 10 LIMIT 5 OFFSET 2 ORDER BY `column`
?>
```

**SuperSQL->SELECT($table, $columns, $where[,$join[, $limit/$append);**

* `(String|Array)table` - Table(s) to query
* `(Array)columns` - Array of columns to return. `[]` will query using the `*` selector.
* `(Array)where` - Array of conditions for WHERE (See above for documentation on WHERE)
* `(Array|Null)join` - Array of conditions for JOIN. Usage below
* `(Int|String|Array)limit` - Number of rows to retrieve. if string, will be treated as an append - it will be appended to the sql query.

<aside class="notice">
You may also put `DISTINCT`, in the top of the columns array to use the DISTINCT keyword. Other available keywords include: `INSERT INTO table` and `INTO table` (replace table with table name)
</aside>

<aside class="notice">
You may also do `SuperSQL->SELECT($table, $columns, $where, $limit)`
</aside>

<aside class="notice">
You can order tables by setting limit/append to "ORDER BY column"
</aside>

<aside class="notice">
You can also have SuperSQL cast the output. To do so, add `[type]` at the end of the column name. Available types: `json`, `obj`, `int`, `bool`, `string`
</aside>

## INSERT
```php
<?php
$SuperSQL->INSERT("table",array(
"hello" => "world",
"SuperSQL" => "rocks"
));
?>
```

**SuperSQL->INSERT($table, $data);**

* `(String|Array)table` - Table(s) to insert to
* `(Array)data` - Data to insert

## UPDATE
```php
<?php
$SuperSQL->UPDATE("citizens",array(
"SuperSQL" => "To the rescue!"
),array(
"needs_help" => 1
));
?>
```

**SuperSQL->UPDATE($table, $data, $where);**

* `(String|Array)table` - Table(s) to insert to
* `(Array)data` - Data to update
* `(Array)where` - Conditional statements

<aside class="notice">
You can also use some special operators on the data argument. Example: `$SuperSQL->UPDATE("test", ["increment[+=]" => 1]);`. Available: `+=`, `-=`, `/=`, `*=`.
</aside>

## DELETE

```php
<?php
$SuperSQL->DELETE("persons",
"is_villain" => 1
));
?>
```

**SuperSQL->DELETE($table, $where);**

* `(String|Array)table` - Table(s) to insert to
* `(Array)where` - Conditional statements

# Helper Functions/Queries
SuperSQL provides some helper functions to allow for easier access. The helper functions allow you to:

* Connect easily
* Manage multiple database connections
* Other queries

<aside class="notice">
If your using the built/compiled file, you must include `dist/SuperSQL_helper.php` too
</aside>

## SQLHelper::connect

```php
<?php
$SuperSQL = SQLHelper::connect("localhost","mydb","root","1234"); // mysql

$SuperSQL = SQLHelper::connect("localhost","mydb","root","1234", $dbtype); // others
?>
```

Connect easily to any database.

**connect($host,$db,$user,$pass,$options)**

* `(String)host` - Host to connect to
* `(String)db` - DB name
* `(String)user` - Username
* `(String)pass` - Password
* `(Array)options` - Options (Optional)

**connect($host,$db,$user,$pass,$dbtype)**

* `(String)host` - Host to connect to
* `(String)db` - DB name
* `(String)user` - Username
* `(String)pass` - Password
* `(String)dbtype` - Database type (`mysql`,`pgsql`,`sybase`,`oracle`)

**connect($host,$db,$user,$pass,$dsn)**

* `(String)host` - Host to connect to
* `(String)db` - DB name
* `(String)user` - Username
* `(String)pass` - Password
* `(String)dbtype` - DSN string


## new SQLHelper()

```php
<?php
$Helper = new SQLHelper("localhost","test","root","1234"); // mysql

$Helper = new SQLHelper("localhost","test","root","1234", $dbtype); // others

$Helper = new SQLHelper($array); // array of connections

$Helper = new SQLHelper(array( // array of connection configs
    array(
    "host"=>"localhost",
    "db"=>"test",
    "user"=>"root",
    "password"=> "1234"
    ),
    array(
    "host"=> "192.168.1.2",
    "db"=>"test2",
    "user"=>"root",
    "password"=> "1234",
    "options" => "pgsql" // dbtype
    ),
));
?>
```

Initialise the helper

**new SQLHelper($SuperSQL)**

* `(SuperSQL)SuperSQL` - SuperSQL object

**new SQLHelper($host,$db,$user,$pass,$options)**

* `(String)host` - Host to connect to
* `(String)db` - DB name
* `(String)user` - Username
* `(String)pass` - Password
* `(Array)options` - Options (Optional)

**new SQLHelper($connect)**

* `(Array)connect` - Array of connection data - Uses Helper::connect

## Change
**$SQLHelper->change($id)**

Changes the selected connection

* `(Int)id` - Connection id

## getCon
**$SQLHelper->getCon($all = false)**

* `(Bool)all` - if true, will return all connections. If not, then will only return the selected one

## SELECT
**$SQLHelper->SELECT($table,$columns,$where,$join,$limit/$append)**

The SELECT query

## SELECTMAP

```php
<?php
$SQLHelper->SELECTMAP("test",array(
    "name[username]",
    "id",
    "data" => [
        "admin[is_admin]",
        "user_posts[posts][json]",
    ]
));

/*
SELECT `name` AS `username`, `id`, `admin` AS `is_admin`, `user_posts` AS `posts` FROM `test`

Output:

[
    {
    "username": "admin",
    "id": 1,
    "data": [
        "is_admin": 1,
        "posts": [
            {
            "msg": "DA BAN HAMMER HAS SPOKEN!",
            "timestamp": 1503011190
            }
        ]
    ]
    },
    {
    "username": "testuser",
    "id": 2,
    "data": [
        "is_admin": 0,
        "posts": [
            {
            "msg": "hello world",
            "timestamp": 1503011186
            }
        ]
    ]
    }
]
*/
?>
```

**$SQLHelper->SELECTMAP($table,$map,$where,$join,$limit/$append)**

The SELECT query for source-mapping.

## INSERT

**$SQLHelper->INSERT($table,$data)**

The INSERT query. 

## UPDATE

**$SQLHelper->UPDATE($table,$data,$where)**

The UPDATE query.

## DELETE

**$SQLHelper->DELETE($table,$where)**

The DELETE query.

## REPLACE

```php
<?php
$SQLHelper->REPLACE("luggage",[
    "items"=>array("bomb"=>"pillow","poison"=>"perfume")
]); // UPDATE `luggage` SET `items` = REPLACE(REPLACE(`items`,'bomb','pillow'),'poison','perfume');
?>
```

**$SQLHelper->REPLACE($table,$data,$where)**

* `(String|Array)table` - table(s) to replace in
* `(Array)data` - columns to replace
* `(Array)where` - conditional statements

## get
**$SQLHelper->get($table,$columns,$where,$join)**

Gets the first row

## count
```php
<?php
echo $SQLHelper->count("table",array( // returns row count (int)
"condition" => 1
);
```
**$SQLHelper->count($table,$where,$join)**

Get num of rows

## max
```php
<?php
echo $SQLHelper->max("table","column"); // Returns biggest value for column
```
**$SQLHelper->max($table,$column,$where,$join)**

Get the maximum value of a column

## min
```php
<?php
echo $SQLHelper->min("table","column"); // Returns smallest value for column
```
**$SQLHelper->min($table,$column,$where,$join)**

Get the minimum value of a column

## avg

```php
<?php
echo $SQLHelper->avg("table","column"); // Returns average value for column
```
**$SQLHelper->avg($table,$column,$where,$join)**

Get the average value of a column

## sum

```php
<?php
echo $SQLHelper->min("table","column"); // Returns sum of values in the column
```
**$SQLHelper->sum($table,$column,$where,$join)**

Get the sum of values in a column

## create
**$SQLHelper->create($table,$data)**

Creates a table

* `(String)table` - Table name to create
* `(Array)data` - Array of keys and types

## drop
**$SQLHelper->drop($table)**

Removes a table

* `(String)table` - Table name to delete

# Advanced

## Transactions

```php
<?php
$SuperSQL->transact(function () {

$SuperSQL->DELETE("citizens",[
    "near_explosion" => 1
]);

return false; // SuperSQL to the rescue! He reversed time (the query)
});
?>
```

**SuperSQL->transact($call);**

* `(Callable)call` - Transaction Function. Return false to rollback

## Logging

> Logging

```php
<?php
$SuperSQL->dev(); // Turn on logging

... // do some queries

echo json_encode($a->getLog()); // Get some data
?>
```

You find something isnt working for your website. You either:

1. Rage quit, break everything, scream "I #%@&$@! HATE SUPERSQL"
2. Use the log function to figure out whats wrong - LIKE A CIVILISED PERSON

To enable the logger, do `$SuperSQL->dev()`. Then make some queries.


Afterwords, do `$SuperSQL->getLog()` to get the log.

### Da log - What does it mean?

```php
<?php
$log = [
    [
    "SELECT * FROM `table` WHERE `test` = ?", // SQL base
    [[24424,1]], // Array of initial values with types. In this case, the value is 24424 and the type is an INT (PDO::PARAM_INT)
    [["0":234]] // Multi-query array
    ]
]
?>
```

* SQL - SQL base. `?` are replaced with values
* Values - Initial values with types. NOTE: This is bound onto the SQL base string
* Insert - Multi-query array

## modeLock
```php
<?php
$SuperSQL->modeLock(true);
?>
```

Modelock locks the fetch mode. By default, fetching is dynamic for performance. However, if you want to have it fetch all rows at the start, then set to true.
