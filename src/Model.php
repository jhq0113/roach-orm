<?php
/**
 * Created by PhpStorm.
 * User: Jiang Haiqiang
 * Date: 2020/7/5
 * Time: 10:17 AM
 */
namespace roach\orm;

use roach\Container;
use roach\orm\builder\Mysql AS SqlBuilder;
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
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     * @datetime 2020/7/5 10:54 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function find()
    {
        $query = new SqlBuilder();
        $query->from(static::$tableName);
        return $query;
    }

    /**
     * @param SqlBuilder $query
     * @return array
     * @throws \ReflectionException
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:55 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function queryAll(SqlBuilder $query)
    {
        $sql = $query->build();
        return static::getDb()->queryAll($sql, $query->getParams());
    }

    /**
     * @param array $row
     * @param bool  $ignore
     * @return int
     * @throws \ReflectionException
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:56 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function insert($row, $ignore = false)
    {
        $params = [];
        $sql = SqlBuilder::multiInsert(static::$tableName, [ $row ], $params, $ignore);
        return static::getDb()->execute($sql, $params);
    }

    /**
     * @param array $rows
     * @param bool  $ignore
     * @return int
     * @throws \ReflectionException
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:59 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function batchInsert($rows, $ignore = false)
    {
        $params = [];
        $sql = SqlBuilder::multiInsert(static::$tableName, $rows, $params, $ignore);
        return static::getDb()->execute($sql, $params);
    }

    /**
     * @param array|string $set
     * @param array|string $where
     * @return int
     * @throws \ReflectionException
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:57 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function updateAll($set, $where)
    {
        $params = [];
        $sql = SqlBuilder::updateAll(static::$tableName, $set, $where, $params);
        return static::getDb()->execute($sql, $params);
    }

    /**
     * @param array|string $where
     * @return int
     * @throws \ReflectionException
     * @throws exceptions\Exception
     * @datetime 2020/7/5 10:58 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function deleteAll($where)
    {
        $params = [];
        $sql = SqlBuilder::deleteAll(static::$tableName, $where, $params);
        return static::getDb()->execute($sql, $params);
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @datetime 2020/7/5 10:58 AM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function lastInsertId()
    {
        return static::getDb()->lastInsertId();
    }
}