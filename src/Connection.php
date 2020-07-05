<?php
/**
 * Created by PhpStorm.
 * User: Jiang Haiqiang
 * Date: 2020/7/5
 * Time: 10:17 AM
 */
namespace roach\orm;

use roach\events\Event;
use roach\events\EventObject;
use roach\orm\exceptions\Exception;
use roach\Roach;

/**
 * Class Connection
 * @package roach\orm
 * @datetime 2020/7/5 10:18 AM
 * @author roach
 * @email jhq0113@163.com
 */
class Connection extends Roach
{
    /**
     * 使用trait
     */
    use Event;

    /**
     * 执行sql前事件
     */
    const EVENT_BEFORE_QUERY = 'db:query:before';

    /**
     * 执行sql后事件
     */
    const EVENT_AFTER_QUERY = 'db:query:after';

    /**
     * 某个节点连接异常事件，此事件不会抛出异常，只有当所有连接都连接不上才会抛出异常
     */
    const EVENT_EXCEPTION_CONNECT = 'db:connect:exception';

    /**
     * 连接断了，此事件不会抛出异常，只有当所有连接都连接不上才会抛出异常
     */
    const EVENT_EXCEPTION_CONNECT_LOST    = 'db:connect:lost';

    /**
     * @var array
     * @datetime 2020/7/5 10:20 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public $masters = [];

    /**
     * @var array
     * @datetime 2020/7/5 10:20 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public $slaves = [];

    /**
     * @var \PDO
     * @datetime 2020/7/5 10:21 AM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_master;

    /**
     * @var \PDO
     * @datetime 2020/7/5 10:21 AM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_slave;

    /**
     * @var array
     * @datetime 2020/7/5 11:50 AM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_defaultOptions =[
        \PDO::ATTR_TIMEOUT      => 3,
        \PDO::ATTR_ERRMODE      => \PDO::ERRMODE_EXCEPTION,
    ];

    /**
     * @param array $configs
     * @return \PDO
     * @datetime 2020/7/5 12:19 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected function _select($configs = [])
    {
        shuffle($configs);

        foreach ($configs as $config) {
            try {
                if(substr($config['dsn'], 0, 6) === 'sqlite') {
                    $config['options'] = null;
                } else {
                    $config['options'] = isset($config['options']) ? array_merge($this->_defaultOptions, $config['options']) : $this->_defaultOptions;
                }

                $pdo = new \PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
                return $pdo;
            }catch (\Throwable $throwable) {
                if($this->hasHandlers(self::EVENT_EXCEPTION_CONNECT)) {
                    $event = new EventObject([
                        'sender' => $this,
                        'data'   => [
                            'exception' => $throwable,
                            'config'    => $config,
                        ],
                    ]);

                    //触发一个事件，将异常信息传递给handler，前端用户是无感知的，但是开发人员得紧急查看一下数据库状态了。
                    $this->trigger(self::EVENT_EXCEPTION_CONNECT, $event);
                }
                continue;
            }
        }
    }

    /**
     * @return \PDO
     * @throws Exception
     * @datetime 2020/7/5 10:30 AM
     * @author roach
     * @email jhq0113@163.com
     */
    protected function _master()
    {
        if(is_null($this->_master)) {
            $this->_master = $this->_select($this->masters);
            if(is_null($this->_master)) {
                throw new Exception('没有可用的master数据库了');
            }
        }

        return $this->_master;
    }

    /**
     * @return \PDO
     * @throws Exception
     * @datetime 2020/7/5 10:31 AM
     * @author roach
     * @email jhq0113@163.com
     */
    protected function _slave()
    {
        if(is_null($this->_slave)) {
            if(empty($this->slaves)) {
                $this->slaves = $this->masters;
            }

            $this->_slave = $this->_select($this->slaves);
            if(is_null($this->_slave)) {
                throw new Exception('没有可用的slave数据库了');
            }
        }

        return $this->_slave;
    }

    /**
     * @param bool   $useMaster
     * @param string $sql
     * @return bool|\PDOStatement
     * @throws Exception
     * @datetime 2020/7/5 12:12 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected function _createCommand($useMaster, $sql)
    {
        if($this->hasHandlers(self::EVENT_BEFORE_QUERY)) {
            $event = new EventObject([
                'sender' => $this,
                'data'   => [
                    'sql' => $sql
                ],
            ]);

            $this->trigger(self::EVENT_BEFORE_QUERY, $event);
        }

        try {
            if($useMaster) {
                $pdo = $this->_master();
                return $pdo->prepare($sql);
            } else {
                $pdo = $this->_slave();
                return $pdo->prepare($sql);
            }
        }catch (\Throwable $throwable) {
            if($this->hasHandlers(self::EVENT_EXCEPTION_CONNECT_LOST)) {
                $event->data['exception'] = $throwable;
                //触发一个事件，将异常信息传递给handler，前端用户是无感知的。
                $this->trigger(self::EVENT_EXCEPTION_CONNECT_LOST, $event);
            }

            //连接断了，原因可能是超时、mysql宕机等，会重新选择一个数据库，仅重新选择一次
            if($useMaster) {
                $this->_master = null;
                $pdo = $this->_master();
                return $pdo->prepare($sql);
            } else {
                $this->_slave = null;
                $pdo = $this->_slave();
                return $pdo->prepare($sql);
            }
        }
    }

    /**
     * @return string
     * @datetime 2020/7/5 10:42 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public function lastInsertId()
    {
        return $this->_master->lastInsertId();
    }

    /**
     * @param string $sql
     * @param array  $params
     * @param bool   $useMaster
     * @return array
     * @throws Exception
     * @datetime 2020/7/5 12:13 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function queryAll($sql, $params = [], $useMaster = false)
    {
        $stmt = $this->_createCommand($useMaster, $sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        //如果有事件处理函数
        if($this->hasHandlers(self::EVENT_AFTER_QUERY)) {
            $event = new EventObject([
                'sender' => $this,
                'data'   => [
                    'sql'     => $sql,
                    'params'  => $params,
                    'stmt'    => $stmt
                ],
            ]);

            $this->trigger(self::EVENT_AFTER_QUERY, $event);
        }

        return $rows;
    }

    /**
     * @param $sql
     * @param array $params
     * @return int
     * @throws Exception
     * @throws \ReflectionException
     * @datetime 2020/7/5 12:13 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->_createCommand(true, $sql);
        $stmt->execute($params);

        //如果有事件处理函数
        if($this->hasHandlers(self::EVENT_AFTER_QUERY)) {
            $event = new EventObject([
                'sender' => $this,
                'data'   => [
                    'sql'     => $sql,
                    'params'  => $params,
                    'stmt'    => $stmt
                ],
            ]);

            $this->trigger(self::EVENT_AFTER_QUERY, $event);
        }

        return $stmt->rowCount();
    }

    /**
     * @return bool
     * @throws Exception
     * @datetime 2020/7/5 10:44 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public function begin()
    {
        return $this->_master()->beginTransaction();
    }

    /**
     * @return bool
     * @throws Exception
     * @datetime 2020/7/5 10:45 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public function rollback()
    {
        return $this->_master()->rollBack();
    }

    /**
     * @return bool
     * @throws Exception
     * @datetime 2020/7/5 10:48 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public function commit()
    {
        return $this->_master()->commit();
    }

    /**
     * @param callable $handler
     * @return bool
     * @throws Exception
     * @datetime 2020/7/5 10:50 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public function transaction(callable $handler)
    {
        $result = $this->begin();
        if(!$result) {
            return false;
        }

        $result = call_user_func($handler, $this);
        if($result) {
           return $this->commit();
        }

        $this->rollback();
        return false;
    }
}