<?php
/**
 * Created by PhpStorm.
 * User: Jiang Haiqiang
 * Date: 2020/7/5
 * Time: 10:17 AM
 */
namespace roach\orm;

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
     * @param array $configs
     * @return \PDO
     * @datetime 2020/7/5 10:29 AM
     * @author roach
     * @email jhq0113@163.com
     */
    protected function _select($configs = [])
    {
        shuffle($configs);

        foreach ($configs as $config) {
            try {
                $pdo = new \PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
                return $pdo;
            }catch (\Throwable $throwable) {
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
     * @return \PDOStatement
     * @throws Exception
     * @datetime 2020/7/5 10:39 AM
     * @author roach
     * @email jhq0113@163.com
     */
    protected function _createCommand($useMaster, $sql)
    {
        if($useMaster) {
            $pdo = $this->_master();
            return $pdo->prepare($sql);
        } else {
            $pdo = $this->_slave();
            return $pdo->prepare($sql);
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
     * @datetime 2020/7/5 10:40 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public function queryAll($sql, $params = [], $useMaster = false)
    {
        $stmt = $this->_createCommand($useMaster, $sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $rows;
    }

    /**
     * @param string $sql
     * @param array  $params
     * @return int
     * @throws Exception
     * @datetime 2020/7/5 10:42 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->_createCommand(true, $sql);
        $stmt->execute($params);
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