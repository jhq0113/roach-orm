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
$rows = UserModel::batchInsert([
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

* 查询

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
//SELECT * FROM `user` WHERE `id` BETWEEN 1 AND 3 LIMIT 1000
$query = UserModel::find()
            ->where([
               'id BETWEEN' => [1, 3] 
            ]);

$userList = UserModel::all($query);
```

> 范围查询(`>`, `>=`, `<`, `<=`, `><`, `!=`)

```php
<?php
//SELECT * FROM `user` WHERE `id`<3 LIMIT 1000
$query = UserModel::find()
            ->where([
               'id <' => 3
            ]);

$userList = UserModel::all($query);
```

> LIKE查询

```php
<?php
//SELECT * FROM `user` WHERE `true_name` LIKE '周%' LIMIT 1000
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

> `GROUP BY`查询

```php
<?php
//SELECT COUNT(`is_on`) AS `count`,`is_on` FROM `user` GROUP BY `is_on` LIMIT 1000
$query = UserModel::find()
    ->select('COUNT(`is_on`) AS `count`,`is_on`')
    ->group([
        'is_on', //可以接多个
    ]);

$user = UserModel::all($query);
```

> `ORDER BY`查询

```php
<?php
//SELECT 'id', 'true_name' FROM `user` ORDER BY `id` DESC LIMIT 1000
$query = UserModel::find()
    ->select([
       'id', 'true_name' 
    ])
    ->order([
        'id'    => SORT_DESC, 
        'is_on' => SORT_ASC,
    ]);

$user = UserModel::all($query);
```

> 分页查询

```php
<?php
//SELECT * FROM `user` LIMIT 0,10
$query = UserModel::find()
    ->offset(0)
    ->limit(10);

$user = UserModel::all($query);
```

* 更新操作

```php
<?php
//这里返回的是受影响行数
//UPDATE `user` SET `true_name`='sun boss' WHERE `id`=1;
$rows = UserModel::updateAll(['true_name' => 'sun boss'], ['id' => 1]);
```

* 删除操作

```php
<?php
//这里返回的是受影响行数
//DELETE FROM `user` WHERE `id`=1;
$rows = UserModel::deleteAll(['id' => 4]);
```

* 使用事务

```php
<?php
$success = UserModel::getDb()->transaction(function (\roach\orm\Connection $connection){
    $query = UserModel::find()
                ->where([
                    'id'    => 1,
                    'is_on' => 1
                ]);
    //这里最好用主库查询
    $user = UserModel::one($query, true);
    if(!isset($user['id'])) {
        //返回false会自动回滚事务
        return false;
    }
    
    //.....其他操作
    
    $rows = $connection->execute('UPDATE `user` SET `true_name`=? WHERE id=1 AND version=?', [
        'zheng boss', $user['version']
    ]);
    
    //如果受影响函数是1，返回true，返回true会自动提交事务
    return $rows === 1;
});

if(!$success) {
    exit('事务提交失败'.PHP_EOL);
}
exit('事务提交成功'.PHP_EOL);
```

## 2.读写分离

* 默认情况下，查询使用从库进行查询，如果想使用主库查询，需要将`all`、`one`方法的第二个参数变为`true`即可

```php
<?php
//SELECT * FROM `user` WHERE `id`=1 AND `is_on`=1
$query = UserModel::find()
    ->where([
        'id'    => 1,
        'is_on' => 1
    ]);
//主库查询
$user = UserModel::one($query, true);
```

* 所有写操作都是走的主库

* 执行原生sql

> 读操作

```php
<?php
$users = UserModel::getDb()->queryAll('SELECT * FROM `user` WHERE id=? UNION SELECT * FROM `user` WHERE id=?', [
    1, 2
]);
```

> 写操作

```php
<?php
//这里返回受影响行数
$rows = UserModel::getDb()->execute('UPDATE `user` SET `true_name`=? WHERE id=1 AND version=1', [
    'wu boss'
]);
```

## 3.切库

> 如果我们的项目使用的不是一个数据库集群，这样我们的项目就需要跨集群访问数据库，可以通过如下方式实现

```php
<?php
//将一组新的数据库集群注册到`Container`中，key自己定义即可
\roach\Container::set('tradeDb', [
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

> Model类中

```php
<?php
/**
 * Created by PhpStorm.
 * User: Jiang Haiqiang
 * Date: 2020/7/5
 * Time: 1:33 PM
 */

/**
 * Class TradeModel
 * @datetime 2020/7/5 1:33 PM
 * @author roach
 * @email jhq0113@163.com
 */
class TradeModel extends \roach\orm\Model
{
    /**表名称
     * @var string
     * @datetime 2020/7/5 1:33 PM
     * @author roach
     * @email jhq0113@163.com 
     */
    public static $tableName = 'trade';
    
    /**
    * @return mixed|\roach\orm\Connection
    * @throws ReflectionException
    * @datetime 2020/7/5 2:22 PM
    * @author roach
    * @email jhq0113@163.com
     */
    public static function getDb()
    {
        return \roach\Container::get('tradeDb');
    }
}
```

> 这样我们就完成切库，当我们使用TradeModel访问数据库是自动调用的是`tradeDb`集群的数据库

## 4. 事件处理

> `roach-orm`支持四种事件

|事件名称|常量|触发机制|
|:-----|:---:|:----|
|db:connect:exception|roach\orm\Connection::EVENT_EXCEPTION_CONNECT|在连接数据时，某个节点连接异常，此事件不会抛出异常，只有当所有连接都连接不上才会抛出异常|
|db:query:before|roach\orm\Connection::EVENT_BEFORE_QUERY|执行sql之前触发|
|db:query:after|roach\orm\Connection::EVENT_AFTER_QUERY|执行sql之后触发|
|db:connect:lost|roach\orm\Connection::EVENT_EXCEPTION_CONNECT_LOST|在执行sql时，连接断了，此事件不会抛出异常，只有当所有连接都连接不上才会抛出异常|

> 我们可以在向`Container`中注册数据库组件时监听这些事件，等事件触发时做相应的处理即可，直接获取到对象自己手动调用`on`方法进行监听。

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
    ],
]);

/**此处不会去连接数据库，只是创建\roach\orm\Connection类而已，主要当真正执行sql的时候才会真正的去连接数据库
 * @var \roach\orm\Connection $db 
 */
$db = \roach\Container::get('db');
$db->on(\roach\orm\Connection::EVENT_EXCEPTION_CONNECT, function(\roach\events\EventObject $event){
    //。。。打日志报警等各种处理，该事件触发了，并不一定所有的节点都不能用了
    //exception中是异常信息，config是节点配置
    var_dump($event->data['exception'], $event['config']);
});

$db->on(\roach\orm\Connection::EVENT_EXCEPTION_CONNECT_LOST, function (\roach\events\EventObject $event){
    //...各种操作
    //sql是指当执行某条sql时，mysql连接断了，但是会自动重连一次，如果重连失败，不会再触发该事件，会抛出异常
    var_dump($event->data['sql'], $event->data['exception']);
});

$db->on(\roach\orm\Connection::EVENT_BEFORE_QUERY, function (\roach\events\EventObject $event){
    //params为参数绑定查询的参数
    var_dump($event->data['stmt'], $event->data['sql'], $event->data['params']);
});

$db->on(\roach\orm\Connection::EVENT_AFTER_QUERY, function (\roach\events\EventObject $event){
    //params为参数绑定查询的参数
    var_dump($event->data['stmt'], $event->data['sql'], $event->data['params']);
});
```

