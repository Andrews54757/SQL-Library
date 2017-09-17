# SuperSQL
![SuperSQL](https://img.shields.io/badge/SuperSQL-v1.1.5-brightgreen.svg)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/ThreeLetters/SuperSQL/master/LICENSE)
[![Docs](https://img.shields.io/badge/Docs-supersql.tk-blue.svg)](http://supersql.tk)
[![GitHub stars](https://img.shields.io/github/stars/ThreeLetters/SuperSQL.svg)](https://github.com/ThreeLetters/SuperSQL/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/ThreeLetters/SuperSQL.svg)](https://github.com/ThreeLetters/SuperSQL/network)


The most lightest, efficient and most powerful php sql database framework. Allows you to quickly and securely develop anything using sql databases.

## Purpose

1. To provide a very fast and efficient way to edit sql databases.
2. To provide an easy method of access.

### Main Features

1. Very small - 27.4KB one file (Unminified, `dist/SuperSQL.php`. Minified version: 12.4KB)
2. Simple and easy - Very easy to learn. SuperSQL was designed to be easy and simple, to the ability that a noob can use it.
3. Compatability - Supports all major SQL databases
4. Efficiency - This module was built with speed and efficiency in mind.
5. Complexity - This module allows you to make all kinds of complex queries.
6. Security - This module prevents SQL injections, so hackers bye bye!
7. Availability - This module is FREE. Licensed under the [MIT license](https://github.com/ThreeLetters/SuperSQL/blob/master/LICENSE).

## Usage
You may either

1. Use the built file ([/dist/SuperSQL.php](https://github.com/ThreeLetters/SuperSQL/blob/master/dist/SuperSQL.php) - preferred)
2. Use the library (Autoload all in `SuperSQL/`, we also provide a [simple loader](https://github.com/ThreeLetters/SuperSQL/blob/master/autoload.php))
3. Use the [composer package](https://packagist.org/packages/threeletters/supersql) (`composer require threeletters/supersql`)

```php
new SuperSQL($dsn,$user,$pass);
```
```php
use SuperSQL\SuperSQL;

// MySql setup
$host = "localhost";
$db = "test";
$user = "root";
$pass = "1234";

$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=utf8";
$SuperSQL = new SuperSQL($dsn,$user,$pass);
```

```php
use SuperSQL\SQLHelper;

// MySql setup
$host = "localhost";
$db = "test";
$user = "root";
$pass = "1234";

$SuperSQL = SQLHelper::connect($host, $db, $user,$pass);
```
```php
$result = $SuperSQL->select("test",[],[
    "condition" => 12345,
    "[||][&&]" => [
        "something" => "value",
        "anotherthing" => "val"
    ]
]); // SELECT * FROM `test` WHERE `condition` = 12345 OR (`something` = 'value' AND `anotherthing` = 'val')

if (!$result->error()) {
foreach ($result as $val) { // NOTE, $result is NOT an array
    echo $val;
}
} else {
echo json_encode($result->error());
}
```

## Build
To build this library, you need [NodeJS](https://nodejs.org/en/). Then execute `builder.js`

> node builder.js

It will build to `/dist/SuperSQL*.php`

## Documentation

Full documentation is here: http://supersql.tk/

![supersql.tk](https://user-images.githubusercontent.com/13282284/29477701-7e6385c6-8437-11e7-9e87-74a12393c49a.png)

## FAQ

**Whats the difference between this and Medoo?**

While on the most basic level, SuperSQL and Medoo are the same, they are quite different.

* Response class - SuperSQL has a response class to access crucial information, such as errors
* Helper - SuperSQL comes with a helper class, with helper functions, while meedoo has it built right in.
* Smaller & lightweight - SuperSQL is smaller than medoo
* Development - SuperSQL's code is well structured and it is commented - so you can understand it more
* SuperSQL is faster - Using xdebug, we found that superSQL's parser is faster than medoo's. (x1000,100%)
* SuperSQL is less confusing. (EG, ``SELECT * FROM `table` `` is just `$SuperSQL->select('table');`)
* SuperSQL has more features - (EG, multi-querying, dynamic responses)

**How fast is superSQL compared to Medoo?**

[speed](https://user-images.githubusercontent.com/13282284/30243699-b4c76e32-957d-11e7-9bdb-ec96f53816b1.png)

**Whats the difference between this an SlickInject?**

SuperSQL uses the same concepts and design as slickInject. However, supersql is much much more advanced.

**Eww, why PDO**

PDO is much better than mysqli. Main reason because it supports so many different databases. Also, PDO is easier.

**How did you make the documentation?**

The nice documentation was created using [Slate - Check it out](https://github.com/lord/slate).

## Special thanks
* [@LegitSoulja](https://github.com/LegitSoulja) - [SlickInject](https://github.com/LegitSoulja/SlickInject), 
* [@catfan](https://github.com/catfan) - [Medoo](https://github.com/catfan/Medoo)
* [Slate](https://github.com/lord/slate) (Documentation)

## Contributing
Contributing is open. If you want to contribute, make a pull request. Please use the [PEAR format](https://pear.php.net/manual/en/standards.php).

> NOTE: 
> please do not do [] for array. Please use array() instead

## License

```
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
```

