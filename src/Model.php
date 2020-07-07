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
     * @return SqlBuilder
     * @datetime 2020/7/6 1:49 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
    public static function getBuilder(Connection $db)
    {
        return Container::createRoach([
            'class' => 'roach\orm\builder\\'.$db->getDriver(),
            'calls' => [
                'db' => [ $db ],
            ]
        ]);
    }

    /**
     * @return SqlBuilder
     * @throws \ReflectionException
     * @datetime 2020/7/6 1:58 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
    public static function find()
    {
        $builder = static::getBuilder(static::getDb());
        return $builder->from(static::$tableName);
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
        return static::getBuilder(static::getDb())->count(static::$tableName, $where, $useMaster);
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
        return static::getBuilder(static::getDb())->multiInsert(static::$tableName, [ $row ], $ignore);
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
    public static function multiInsert($rows, $ignore = false)
    {
        return static::getBuilder(static::getDb())->multiInsert(static::$tableName, $rows, $ignore);
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
        return static::getBuilder(static::getDb())->updateAll(static::$tableName, $set, $where);
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
        return static::getBuilder(static::getDb())->deleteAll(static::$tableName, $where);
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