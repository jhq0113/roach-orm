<?php
//执行测试脚本前需要执行composer install，这个命令会自动安装依赖并且注册命名空间
require dirname(__DIR__).'/vendor/autoload.php';

//注册db
roach\Container::set('db', [
    'class' => 'roach\orm\Connection',
    'masters' => [
        [
            'dsn'      => 'mysql:host=192.168.1.13;port=3306;dbname=roach;charset=utf8',
            'username' => 'roach',
            'password' => 'roach',
            'options'  => [
                \PDO::ATTR_TIMEOUT => 2,
            ]
        ],
    ],
    //如果没有slave节点，可以不配置，会自动复用master节点
    'slaves' => [
        [
            'dsn'      => 'mysql:host=192.168.1.12;port=3306;dbname=roach;charset=utf8',
            'username' => 'roach',
            'password' => 'roach',
            'options'  => [
                \PDO::ATTR_TIMEOUT => 2,
            ]
        ],
        [
            'dsn'      => 'mysql:host=192.168.1.11;port=3306;dbname=roach;charset=utf8',
            'username' => 'roach',
            'password' => 'roach',
            'options'  => [
                \PDO::ATTR_TIMEOUT => 2,
            ]
        ],
    ],
    'calls' => [
        [
            'method' => 'on',
            'params' => [
                \roach\orm\Connection::EVENT_EXCEPTION_CONNECT,
                function(\roach\events\EventObject $event){
                    //。。。打日志报警等各种处理，该事件触发了，并不一定所有的节点都不能用了
                    //exception中是异常信息，config是节点配置
                    var_dump($event->data['exception'], $event->data['config']);
                }
            ]
        ],
        [
            'method' => 'on',
            'params' => [
                \roach\orm\Connection::EVENT_EXCEPTION_CONNECT_LOST, function (\roach\events\EventObject $event){
                    //...各种操作
                    //sql是指当执行某条sql时，mysql连接断了，但是会自动重连一次，如果重连失败，不会再触发该事件，会抛出异常
                    var_dump($event->data['sql'], $event->data['exception']);
                }
            ]
        ],
        [
            'method' => 'on',
            'params' => [
                \roach\orm\Connection::EVENT_BEFORE_QUERY, function (\roach\events\EventObject $event){
                    //params为参数绑定查询的参数
                    //var_dump($event->data['stmt'], $event->data['sql'], $event->data['params']);
                }
            ]
        ],
        [
            'method' => 'on',
            'params' => [
                \roach\orm\Connection::EVENT_AFTER_QUERY, function (\roach\events\EventObject $event){
                    //params为参数绑定查询的参数
                    //var_dump($event->data['stmt'], $event->data['sql'], $event->data['params']);
                }
            ]
        ],
    ]
]);

/**
 * Class UserModel
 * @datetime 2020/7/6 2:09 下午
 * @author   roach
 * @email    jhq0113@163.com
 */
class UserModel extends \roach\orm\Model
{
    /**
     * @var string
     * @datetime 2020/7/6 2:15 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
    public static $tableName = 'user';

    /**示例表
     * sql
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
     */
}

$rows = UserModel::insert([
    'user_name'   => 'zhou boss '.time(),
    'true_name'   => '周**',
    'password'    => hash_hmac('md5', 'Mr.zhou', 'sdfs#$#@3fd'),
    'update_time' => time()
]);

if($rows < 1) {
    exit('插入失败'.PHP_EOL);
}

//如果想获取刚刚插入数据的`id`,通过如下方式
$newUserId = UserModel::getDb()->lastInsertId();
echo '插入成功，用户id为'.$newUserId.PHP_EOL;


$rows = UserModel::multiInsert([
    [
        'user_name'   => 'zhao boss '.time(),
        'true_name'   => '赵**',
        'password'    => hash_hmac('md5', 'Mr.zhao', 'sdfs#$#@3fd'),
        'update_time' => time()
    ],
    [
        'user_name'   => 'li boss '.time(),
        'true_name'   => '李**',
        'password'    => hash_hmac('md5', 'Mr.li', 'sdfs#$#@3fd'),
        'update_time' => time()
    ],
]);

var_dump($rows);

$user = UserModel::find()
    ->where([
        'id' => 1,
    ])
    ->one();

var_dump($user);

$userList = UserModel::find()
    ->where([
        'id' => [1, 2, 3]
    ])
    ->all();

var_dump($userList);


$userList = UserModel::find()
    ->where([
        'id BETWEEN' => [1, 3]
    ])
    ->all();

var_dump($userList);


$userList = UserModel::find()
    ->where([
        'id <' => 3
    ])
    ->all();

var_dump($userList);

$user = UserModel::find()
    ->where([
        'id'    => 1,
        'is_on' => 0
    ])
    ->one();

var_dump($user);

$list = UserModel::find()
    ->select('COUNT(`is_on`) AS `count`,`is_on`')
    ->group([
        'is_on', //可以接多个
    ])
    ->all();
var_dump($list);


$userList = UserModel::find()
    ->select([
        'id', 'true_name'
    ])
    ->order([
        'id'    => SORT_DESC,
        'is_on' => SORT_ASC,
    ])
    ->all();
var_dump($userList);

$userList = UserModel::find()
    ->offset(0)
    ->limit(10)
    ->all();

var_dump($userList);

$rows = UserModel::updateAll(['true_name' => 'sun boss'], ['id' => 1]);
var_dump($rows);

$rows = UserModel::deleteAll(['id' => 4]);
var_dump($rows);
