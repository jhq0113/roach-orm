<?php
/**
 * Created by PhpStorm.
 * User: Jiang Haiqiang
 * Date: 2020/7/4
 * Time: 6:53 PM
 */
namespace roach\orm;

use roach\Roach;

/**
 * Class SqlBuilder
 * @package roach\orm
 * @datetime 2020/7/4 6:54 PM
 * @author roach
 * @email jhq0113@163.com
 */
abstract class SqlBuilder extends Roach
{
    /**
     * @var Connection
     * @datetime 2020/7/6 1:52 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
    protected $_db;

    /**
     * @var string
     * @datetime 2019/9/17 10:55 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_table;

    /**
     * @var array|string
     * @datetime 2019/9/17 10:56 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_select = '*';

    /**
     * @var array|string
     * @datetime 2019/9/17 10:58 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_where;

    /**
     * @var array|string
     * @datetime 2019/9/17 11:01 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_group;

    /**
     * @var array|string
     * @datetime 2019/9/17 11:02 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_order;

    /**
     * @var int
     * @datetime 2019/9/17 11:04 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_offset = 0;

    /**
     * @var int
     * @datetime 2019/9/17 11:06 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_limit = 1000;

    /**
     * @var array
     * @datetime 2019/9/18 10:41
     * @author roach
     * @email jhq0113@163.com
     */
    protected $_params = [];

    /**
     * @param Connection $db
     * @return $this
     * @datetime 2020/7/6 1:53 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
    public function db(Connection $db)
    {
        $this->_db = $db;
        return $this;
    }

    /**
     * @param array|string $fields
     * @return $this
     * @datetime 2019/9/17 10:58 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function select($fields)
    {
        $this->_select = $fields;
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     * @datetime 2019/9/17 10:57 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function from($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * @param array|string $where
     * @return $this
     * @datetime 2019/9/17 10:58 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function where($where)
    {
        $this->_where = $where;
        return $this;
    }

    /**
     * @param array|string $group
     * @return $this
     * @datetime 2019/9/17 11:01 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function group($group)
    {
        $this->_group = $group;
        return $this;
    }

    /**
     * @param array|string $order
     * @return $this
     * @datetime 2019/9/17 11:03 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function order($order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     * @datetime 2019/9/17 11:05 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function offset($offset)
    {
        $this->_offset = $offset;
        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     * @datetime 2019/9/17 11:06 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function limit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * @return array
     * @datetime 2020/7/4 8:44 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @param string $field
     * @return string
     * @datetime 2020/7/4 7:00 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function formatField($field)
    {
        return $field[0] === '`' ? $field : '`'.$field.'`';
    }

    /**
     * @param int $count
     * @return string
     * @datetime 2020/7/4 7:12 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public static function createParamsString($count)
    {
        return rtrim(str_repeat('?,', $count), ',');
    }

    /**
     * @param array|string $where
     * @param array        $params
     * @return mixed
     * @datetime 2020/7/4 7:01 PM
     * @author roach
     * @email jhq0113@163.com
     */
    protected static function _analyWhere($where, &$params = [])
    {
        if(empty($where)) {
            return '';
        } elseif (is_array($where)) {
            $finallyWhere = [];

            foreach ($where as $field => $value) {
                $operator = '=';
                if(strpos($field,' ') > 0) {
                    list($field,$operator) = explode(' ',$field,2);
                }

                //绑参
                if(is_array($value)) {
                    if($operator == '=') {
                        $operator = 'IN';
                    }
                    $params = array_merge($params,$value);
                }else {
                    array_push($params,$value);
                }

                $field = static::formatField($field);
                $operator = strtoupper($operator);

                switch ($operator) {
                    case 'IN':
                        $subWhere = $field.' IN('.static::createParamsString(count($value)).')';
                        break;
                    case 'BETWEEN':
                        $subWhere = $field.' BETWEEN ? AND ? ';
                        break;
                    case 'LIKE':
                        $subWhere = $field.' '.$operator.' ?';
                        break;
                    default:
                        $subWhere = $field.' '.$operator.'?';
                        break;
                }
                array_push($finallyWhere,'('.$subWhere.')');
            }

            $where = implode(' AND ', $finallyWhere);
            unset($finallyWhere);
        }

        return ' WHERE '.$where;
    }


    /**
     * @return string
     * @datetime 2020/7/4 7:25 PM
     * @author roach
     * @email jhq0113@163.com
     */
     abstract public function build();

    /**
     * @param Connection|null $db
     * @param bool $useMaster
     * @return array
     * @throws exceptions\Exception
     * @datetime 2020/7/6 1:55 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
     public function all(Connection $db = null, $useMaster = false)
     {
         if(is_null($db)) {
             return $this->_db->queryAll($this->build(), $this->_params, $useMaster);
         }
         return $db->queryAll($this->build(), $this->_params, $useMaster);
     }

    /**
     * @param Connection|null $db
     * @param bool $useMaster
     * @return array
     * @throws exceptions\Exception
     * @datetime 2020/7/6 1:57 下午
     * @author   roach
     * @email    jhq0113@163.com
     */
     public function one(Connection $db = null, $useMaster = false)
     {
         $this->limit(1);

         if(is_null($db)) {
             $rows = $this->_db->queryAll($this->build(), $this->_params, $useMaster);
         } else {
             $rows = $db->queryAll($this->build(), $this->_params, $useMaster);
         }

         if(isset($rows[0])) {
             return $rows[0];
         }

         return [];
     }

    /**
     * @param string $table
     * @param array  $rows
     * @param array  $params
     * @param bool   $ignore
     * @return string
     * @datetime 2020/7/5 10:13 AM
     * @author roach
     * @email jhq0113@163.com
     */
     static public function multiInsert($table, $rows, &$params = [], $ignore = false)
     {
        $fields = array_map(function($field){
            return static::formatField($field);
        }, array_keys($rows[0]));


        $placeHolder = '';
        foreach ($rows as $row) {
            $placeHolder.= '('.static::createParamsString(count($rows[0])).'),';
            $params = array_merge($params, array_values($row));
        }

        $placeHolder = rtrim($placeHolder, ',');
        return 'INSERT '.($ignore ? 'IGNORE' :'').' INTO '.static::formatField($table).'('.implode(',',$fields).')VALUES'.$placeHolder;
     }

    /**
     * @param string       $table
     * @param array|string $set
     * @param array|string $where
     * @param array        $params
     * @return string
     * @datetime 2020/7/5 10:14 AM
     * @author roach
     * @email jhq0113@163.com
     */
     static public function updateAll($table,$set, $where, &$params = [])
     {
        if(is_array($set)) {
            $sets = [];
            foreach ($set as $field => $value) {
                array_push($params,$value);
                array_push($sets,static::formatField($field).'=?');
            }

            $set = implode(',',$sets);
        }

        return 'UPDATE '.static::formatField($table).' SET '.$set.static::_analyWhere($where,$params);
     }

    /**
     * @param string       $table
     * @param array|string $where
     * @param array        $params
     * @return string
     * @datetime 2020/7/5 10:14 AM
     * @author roach
     * @email jhq0113@163.com
     */
     static public function deleteAll($table, $where, &$params = [])
     {
         return 'DELETE FROM '.static::formatField($table).static::_analyWhere($where,$params);
     }

    /**
     * @param string       $table
     * @param array|string $where
     * @param array        $params
     * @return string
     * @datetime 2020/7/5 12:41 PM
     * @author roach
     * @email jhq0113@163.com
     */
     static public function count($table, $where, &$params = [])
     {
         return 'SELECT COUNT(*) AS `count` FROM '.static::formatField($table).static::_analyWhere($where, $params);
     }
}