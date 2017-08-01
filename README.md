# fizzdb
小巧强悍的php orm, 简单易用, 支持数据库主从, 读写分离, 支持临时链接任意数据库操作, 底层采用pdo链接, 语法模仿laravel的db操作, [参考文档](http://laravelacademy.org/post/6955.html)
## 安装
使用composer
```sh
composer require fizzday/fizzdb dev-master
```
## 配置
```php
<?php
$config['db_default'] = 'mysql';
$config['mysql'] = [
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'port'      => 3306,
    'database'  => 'fizzday',
    'username'  => 'root',
    'password'  => 'root',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
];
$config['mysql2'] = [
    'driver'    => 'mysql',
    'host'      => '192.168.200.248',
    'port'      => 3306,
    'database'  => 'wcc_service_fooddrug',
    'username'  => 'gcore',
    'password'  => 'gcore',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => 'fd_',
    'read'      => [
      'host'      => '192.168.200.248',
      'database'  => 'wcc_service_fooddrug',
    ],
    'write'      => [
      'host'      => '192.168.200.248',
      'database'  => 'wcc_service_fooddrug',
    ]
];
return $config;
```
## 用法示例
### 链接数据库
```php
<?php
use Fizzday\FizzDB\DB;
$config = require CONF_PATH.'database.php';

$db = DB::connection($config[$config['db_default']]);

$userinfo = $db->table('user')->first();    // select * from user limit 1;
```
*说明:* 这里是将数据库的配置文件写入了配置目录(CONF_PATH)下的`database.php`, 可以配置多个数据库链接, 只需要在`db_default`下指定链接名字即可.  
如果想直接使用, 则直接讲配置数据传入 `connecttion()` 方法即可
### 基本用法
此处默认已将配置写入配置文件
```php
<?php
use Fizzday\FizzDB\DB;

// 原生语句(查询)
DB::query("SELECT * FROM `user` where `id`>?", [1]);    // pdo用法
// 或者
DB::query("SELECT * FROM `user` where `id`>1");

// 原生语句(非查询)
DB::execute("UPDATE `user` SET `age`=?", [25]);    // pdo用法
// 或者
DB::execute("UPDATE `user` SET `age`=25");
```
### 链式操作
```php
<?php
use Fizzday\FizzDB\DB;

// 链式操作
DB::table('user')->where('id',  1)->where(['name'=>1])->first();    // select * from user where id=1 and name=1 limit 1

// 长查询,支持多种模式(limit(limit, offset))
DB::table('user')->fields('id','name','age')->where('id','>',1)->group('age')->having('count(age)>2')->order('age', 'desc')->limit(10)->offset(0)->get(); // select id, name, age from user where (id>1) group by age having count(age)>2 order by age desc limit 10 offset 0;
// where, orWhere, whereRaw, orWhereRaw, whereNull, whereNotNull, orWhereNull, orWhereNotNull, whereIn, orWhereIn, whereNotIn, orWhereNotIn
DB::table('user')->whereNull('score')->orWhereRaw('age > 3')->orWhereNotIn('class', [2,3])->get(); // select * from user where (score is null) or (age>3) or (class is not in (2,3));
```
- `whereRaw`: 支持直接写sql语句  
- `whereNull('name')`: `where name is null`
- `orWhereIn('id', [1,2,3])`: `or id in (1,2,3)`
- 依次类推, 其他where类条件类似
### 聚合用法
```php
<?php
use Fizzday\FizzDB\DB;

// count, sum, avg, max, min
DB::table('user')->count();     // select count(1) as count from user;
DB::table('user')->sum('age');  // select sum(age) as sum from user;
DB::table('user')->avg('age');  // select round(avg(age), 0) as avg from user;
DB::table('user')->max('age');  // select max(age) as max from user;
DB::table('user')->min('age');  // select min(age) as min from user;
```
### join
```php
<?php
use Fizzday\FizzDB\DB;

DB::table('users a')->join('userinfo b', 'a.id', '=', 'b.uid')->first(); // select * from users a inner join userinfo b on a.id=b.uid limit 1;
// leftJoin, rightJoin, innerJoin
DB::table('users a')->leftJoin('userinfo b', 'a.id', '=', 'b.uid')->fields('a.id', 'b.card')->where('a.age', '>', 18)->limit(10)->get(); // select a.id,b.card from users a left join userinfo b on a.id=b.uid where (a.age>18) limit 10 offset 0;
```


## todo 
- []连接池  
- []缓存  