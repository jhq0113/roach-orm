<?php
//执行测试脚本前需要执行composer install，这个命令会自动安装依赖并且注册命名空间
require dirname(__DIR__).'/vendor/autoload.php';

//注册db
roach\Container::set('db', [
    'class' => 'roach\orm\Connection',
    'masters' => [
        [
            'dsn'      => 'mysql:host=10.16.49.113;port=3306;dbname=doctor_v6;charset=utf8',
            'username' => 'browser',
            'password' => 'browser.360',
        ],
        [
            'dsn'      => 'mysql:host=10.16.49.113;port=3306;dbname=doctor_v6;charset=utf8',
            'username' => 'browser',
            'password' => 'browser.360',
            //可以通过options指定配置属性
            'options'  => [
                \PDO::ATTR_TIMEOUT => 3,
            ]
        ],
    ],
    //如果没有slave节点，可以不配置，会自动复用master节点
    'slaves' => [
        [
            'dsn'      => 'mysql:host=10.16.49.113;port=3306;dbname=doctor_v6;charset=utf8',
            'username' => 'browser',
            'password' => 'browser.360',
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
    public static $tableName = 't_user';

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
