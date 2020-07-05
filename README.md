# roach-orm

> `roach-orm`是一个简单高性能的PHP语言`ORM`框架，支持数据库的`负载均衡`、`预防SQL注入`、`故障自动摘除`、`自动恢复`以及`读写分离`等强大功能，整个代码文件不到`20K`。

# 安装方式
```bash
composer require jhq0113/roach-orm
```

## 1.使用`Model`

### 1.1 配置`db`

> 通过`composer`下载完本依赖后，在您的项目中加载`composer`依赖(一般在您的项目入口文件中`require`上`vendor/autoload.php`即可)，如果已经加载忽略此步骤。

> 在使用`Model`前需要将`db`组件注册到`roach\Container`中，配置是数组格式，可以放到配置文件中，注册方式如下

```php
<?php
\roach\Container::set('db', [
    'class' => 'roach\orm\Connection',
    //
    'masters' => [
        [
            'dsn'      => 'mysql:host=192.168.1.14;port=3306;dbname=roach;charset=utf-8',
            'username' => 'roach', 
            'password' => 'roach',   
        ],
        [
            'dsn'      => 'mysql:host=192.168.1.13;port=3306;dbname=roach;charset=utf-8',
            'username' => 'roach', 
            'password' => 'roach',
            //可以通过options指定配置属性
            'options'  => [
                \PDO::ATTR_TIMEOUT => 3,   
            ]    
        ],
    ],
    //如果没有slave节点，可以不配置，会自动复用master节点
    'slaves' => [
        [
           'dsn'      => 'mysql:host=192.168.1.15;port=3306;dbname=roach;charset=utf-8',
           'username' => 'roach', 
           'password' => 'roach',  
           'options'  => [
                \PDO::ATTR_TIMEOUT => 2,   
           ] 
        ],
        [
           'dsn'      => 'mysql:host=192.168.1.16;port=3306;dbname=roach;charset=utf-8',
           'username' => 'roach', 
           'password' => 'roach', 
           'options'  => [
               \PDO::ATTR_TIMEOUT => 2,   
           ]   
        ], 
    ]
]);
```

### 1.2 使用`Model`进行`CRUD`

> 假入您的数据库中有如下表

```sql
CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `user_name` varchar(32) CHARACTER SET utf8 DEFAULT 'NULL' COMMENT '登录名',
  `true_name` varchar(32) CHARACTER SET utf8 DEFAULT '' COMMENT '真实姓名',
  `password` char(32) CHARACTER SET utf8 DEFAULT '' COMMENT '密码',
  `is_on` tinyint(3) unsigned DEFAULT 0 COMMENT '是否启用(0禁用1启用)',
  `last_login_ip` bigint(20) unsigned DEFAULT 0 COMMENT '上次登录ip',
  `add_time` timestamp NULL DEFAULT current_timestamp() COMMENT '添加时间',
  `update_time` int(10) unsigned DEFAULT 0 COMMENT '修改时间',
  `version` int(10) unsigned DEFAULT 0 COMMENT '乐观锁版本',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT COMMENT='用户表'
```

> 创建`UserModel`类，使之继承`roach\orm\Model`，如下

```php
<?php
/**
 * Created by PhpStorm.
 * User: Jiang Haiqiang
 * Date: 2020/7/5
 * Time: 1:33 PM
 */

/**
 * Class UserModel
 * @datetime 2020/7/5 1:33 PM
 * @author roach
 * @email jhq0113@163.com
 */
class UserModel extends \roach\orm\Model
{
    /**表名称
     * @var string
     * @datetime 2020/7/5 1:33 PM
     * @author roach
     * @email jhq0113@163.com 
     */
    public static $tableName = 'user';
}
```

* A.insert数据

> 插入单条数据

```php
<?php
/**
 * 此处返回受影响行数 
 */
$rows = UserModel::insert([
    'user_name'   => 'zhou boss',
    'true_name'   => '周**',
    'password'    => hash_hmac('md5', 'Mr.zhou', 'sdfs#$#@3fd'),
    'update_time' => time()
]);

if($rows < 1) {
    exit('插入失败'.PHP_EOL);
}

//如果想获取刚刚插入数据的`id`,通过如下方式
$newUserId = UserModel::getDb()->lastInsertId();
exit('插入成功，用户id为'.$newUserId.PHP_EOL);
```

> 插入多条数据

```php
<?php
/**
 * 此处返回受影响行数 
 */
$rows = UserModel::insert([
    [
        'user_name'   => 'zhao boss',
        'true_name'   => '赵**',
        'password'    => hash_hmac('md5', 'Mr.zhao', 'sdfs#$#@3fd'),
        'update_time' => time()
    ],
    [
        'user_name'   => 'li boss',
        'true_name'   => '李**',
        'password'    => hash_hmac('md5', 'Mr.li', 'sdfs#$#@3fd'),
        'update_time' => time()
    ],
]);

var_dump($rows);
```

* B.使用`where`

> `where`条件可以是数组，也可以是字符串，当`where`条件为数组时，多个条件之间是`AND`关系，`all`、`one`、`updateAll`和`deleteAll`等方法中的`where`条件的表达式解析是一致的

> 相等查询

```php
<?php
//SELECT * FROM `user` WHERE `id`=1 LIMIT 1
$query = UserModel::find()
    ->where([
        'id' => 1,
    ]);

$user = UserModel::one($query);
```

> IN查询

```php
<?php
//SELECT * FROM `user` WHERE `id` IN(1,2,3) LIMIT 1000
$query = UserModel::find()
            ->where([
               'id' => [1, 2, 3] 
            ]);

$userList = UserModel::all($query);
```

> BETWEEN查询

```php
<?php
//SELECT * FROM `user` WHERE `id` IN(1,2,3) LIMIT 1000
$query = UserModel::find()
            ->where([
               'id BETWEEN' => [1, 3] 
            ]);

$userList = UserModel::all($query);
```

> 范围查询(`>`, `>=`, `<`, `<=`, `><`, `!=`)

```php
<?php
//SELECT * FROM `user` WHERE `id` IN(1,2,3) LIMIT 1000
$query = UserModel::find()
            ->where([
               'id <' => 3
            ]);

$userList = UserModel::all($query);
```

> LIKE查询

```php
<?php
$query = UserModel::find()
            ->where([
               'true_name LIKE' => '周%'
            ]);

$userList = UserModel::all($query);
```

> 多条件查询

```php
<?php
//SELECT * FROM `user` WHERE `id`=1 AND `is_on`=1
$query = UserModel::find()
    ->where([
        'id'    => 1,
        'is_on' => 1
    ]);

$user = UserModel::one($query);
```