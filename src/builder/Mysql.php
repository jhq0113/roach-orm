<?php
/**
 * Created by PhpStorm.
 * User: Jiang Haiqiang
 * Date: 2020/7/4
 * Time: 6:55 PM
 */
namespace roach\orm\builder;

use roach\orm\SqlBuilder;

/**
 * Class Mysql
 * @package roach\orm\builder
 * @datetime 2020/7/4 6:55 PM
 * @author roach
 * @email jhq0113@163.com
 */
class Mysql extends SqlBuilder
{
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
     * @return string
     * @datetime 2020/7/4 7:26 PM
     * @author roach
     * @email jhq0113@163.com
     */
    public function build()
    {
        $this->_params = [];

        if(empty($this->_select)){
            $fields = '*';
        } elseif(is_array($this->_select)) {
            $fields = implode(',', array_map(function($field){
                return static::formatField($field);
            },$this->_select));
        }else {
            $fields = $this->_select;
        }

        $group = '';
        if(!empty($this->_group)) {
            if(is_array($this->_group)) {
                $group = ' GROUP BY '.implode(',', array_map(function($field){
                        return static::formatField($field);
                    },$this->_group));
            }else {
                $group = ' GROUP BY '.$this->_group;
            }
        }

        $order = '';
        if(!empty($this->_order)) {
            if(is_array($this->_order)) {
                $orderArr = [];
                foreach ($this->_order as $key => $value ) {
                    switch ($value) {
                        case SORT_ASC:
                            array_push($orderArr,static::formatField($key).' ASC');
                            break;
                        case SORT_DESC:
                            array_push($orderArr,static::formatField($key).' DESC');
                            break;
                        default:
                            array_push($orderArr,static::formatField($value));
                            break;
                    }
                }

                $order = ' ORDER BY '.implode(',',$orderArr);
            }else {
                $order = ' ORDER BY '.$this->_order;
            }
        }

        return 'SELECT '.$fields.' FROM '.static::formatField($this->_table).static::_analyWhere($this->_where,$this->_params).
            $group.$order.' LIMIT '.(string)$this->_offset.','.$this->_limit;
    }
}