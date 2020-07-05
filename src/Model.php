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
     * @var string
     * @datetime 2020/7/5 12:56 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public static $builderClass = 'roach\orm\builder\Mysql';

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
     * @return SqlBuilder
     * @datetime 2020/7/5 10:54 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function find()
    {
        return Container::createRoach([
            'class' => static::$builderClass,
            'calls' => [
                'from' => [
                    static::$tableName
                ]
            ]
        ]);
    }

    /**
     * @param SqlBuilder $query
     * @param bool       $useMaster
     * @return array
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:55 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function all(SqlBuilder $query, $useMaster = false)
    {
        $sql = $query->build();
        return static::getDb()->queryAll($sql, $query->getParams(), $useMaster);
    }

    /**
     * @param SqlBuilder $query
     * @param bool       $useMaster
     * @return array
     * @throws exceptions\Exception
     * @datetime 2020/7/5 12:43 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function one(SqlBuilder $query, $useMaster = false)
    {
        $sql = $query->limit(1)
                    ->build();
        $rows = static::getDb()->queryAll($sql, $query->getParams(), $useMaster);
        if(isset($rows[0])) {
            return $rows[0];
        }
        return [];
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
        $sql    = call_user_func_array(static::$builderClass.'::count', [static::$tableName, $where, $params ]);
        $rows = static::getDb()->queryAll($sql, $params, $useMaster);
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
        $sql = call_user_func_array(static::$builderClass.'::multiInsert', [static::$tableName, [ $row ], $params, $ignore]);
        return static::getDb()->execute($sql, $params);
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
        $sql = call_user_func_array(static::$builderClass.'::multiInsert', [static::$tableName, $rows, $params, $ignore]);
        return static::getDb()->execute($sql, $params);
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
        $sql = call_user_func_array(static::$builderClass.'::updateAll', [static::$tableName, $set, $where, $params ]);
        return static::getDb()->execute($sql, $params);
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
        $sql = call_user_func_array(static::$builderClass.'::deleteAll', [static::$tableName, $where, $params]);
        return static::getDb()->execute($sql, $params);
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