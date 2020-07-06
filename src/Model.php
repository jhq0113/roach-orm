<?php
/**
 * Created by PhpStorm.
 * User: Jiang Haiqiang
 * Date: 2020/7/5
 * Time: 10:17 AM
 */
namespace roach\orm;

use roach\Container;
use roach\Roach;

/**
 * Class Model
 * @package roach\orm
 * @datetime 2020/7/5 10:17 AM
 * @author roach
 * @email jhq0113@163.com
 */
class Model extends Roach
{
    /**
     * @var string
     * @datetime 2020/7/5 10:51 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static $tableName;

    /**
     * @return Connection
     * @datetime 2020/7/5 10:51 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function getDb()
    {
        return Container::get('db');
    }

    /**
     * @param Connection $db
     * @return string
     * @datetime 2020/7/6 1:49 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
    public static function getBuilderClass(Connection $db)
    {
        return 'roach\orm\builder\\'.$db->getDriver();
    }

    /**
     * @return object
     * @throws \ReflectionException
     * @datetime 2020/7/6 1:58 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
    public static function find()
    {
        $db = static::getDb();
        return Container::createRoach([
            'class' => static::getBuilderClass($db),
            'calls' => [
                'db'   => [
                    $db,
                ],
                'from' => [
                    static::$tableName
                ]
            ]
        ]);
    }

    /**
     * @param array|string $where
     * @param bool         $useMaster
     * @return int
     * @throws exceptions\Exception
     * @datetime 2020/7/5 12:47 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function count($where, $useMaster = false)
    {
        $params = [];
        $db = static::getDb();
        $builderClass = static::getBuilderClass($db);
        $sql    = call_user_func_array($builderClass.'::count', [static::$tableName, $where, $params ]);
        $rows = $db->queryAll($sql, $params, $useMaster);
        if(isset($rows[0]['count'])) {
            return (int)$rows[0]['count'];
        }
        return 0;
    }

    /**
     * @param array $row
     * @param bool  $ignore
     * @return int
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:56 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function insert($row, $ignore = false)
    {
        $params = [];
        $db = static::getDb();
        $builderClass = static::getBuilderClass($db);
        $sql = call_user_func_array($builderClass.'::multiInsert', [static::$tableName, [ $row ], $params, $ignore]);
        return $db->execute($sql, $params);
    }

    /**
     * @param array $rows
     * @param bool  $ignore
     * @return int
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:59 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function batchInsert($rows, $ignore = false)
    {
        $params = [];
        $db = static::getDb();
        $builderClass = static::getBuilderClass($db);
        $sql = call_user_func_array($builderClass.'::multiInsert', [static::$tableName, $rows, $params, $ignore]);
        return $db->execute($sql, $params);
    }

    /**
     * @param array|string $set
     * @param array|string $where
     * @return int
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:57 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function updateAll($set, $where)
    {
        $params = [];
        $db = static::getDb();
        $builderClass = static::getBuilderClass($db);
        $sql = call_user_func_array($builderClass.'::updateAll', [static::$tableName, $set, $where, $params ]);
        return $db->execute($sql, $params);
    }

    /**
     * @param array|string $where
     * @return int
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:58 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function deleteAll($where)
    {
        $params = [];
        $db = static::getDb();
        $builderClass = static::getBuilderClass($db);
        $sql = call_user_func_array($builderClass.'::deleteAll', [static::$tableName, $where, $params]);
        return $db->execute($sql, $params);
    }

    /**
     * @return string
     * @datetime 2020/7/5 10:58 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function lastInsertId()
    {
        return static::getDb()->lastInsertId();
    }
}