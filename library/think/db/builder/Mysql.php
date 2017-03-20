<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\db\builder;

use think\db\Builder;

/**
 * mysql数据库驱动
 */
class Mysql extends Builder
{
    protected $updateSql = 'UPDATE %TABLE% %JOIN% SET %SET% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @param array  $options
     * @return string
     */
    protected function parseKey($key, $options = [])
    {
        $key = trim($key);
        if (strpos($key, '$.') && false === strpos($key, '(')) {
            // JSON字段支持
            list($field, $name) = explode('$.', $key);
            $key                = 'json_extract(' . $field . ', \'$.' . $name . '\')';
        } elseif (strpos($key, '.') && !preg_match('/[,\'\"\(\)`\s]/', $key)) {
            list($table, $key) = explode('.', $key, 2);
            if (isset($options['alias'][$table])) {
                $table = $options['alias'][$table];
            } elseif ('__TABLE__' == $table) {
                $table = $this->query->getTable();
            }
        }
        if (!preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        if (isset($table)) {
            $key = '`' . $table . '`.' . $key;
        }
        return $key;
    }

    /**
     * field分析
     * @access protected
     * @param mixed     $fields
     * @param array     $options
     * @return string
     */
    protected function parseField($fields, $options = [])
    {
        $fieldsStr = parent::parseField($fields, $options);
        if (!empty($options['point'])) {
            $array = [];
            foreach ($options['point'] as $key => $field) {
                $key     = !is_numeric($key) ? $key : $field;
                $array[] = 'AsText(' . $this->parseKey($key, $options) . ') AS ' . $this->parseKey($field, $options);
            }
            $fieldsStr .= ',' . implode(',', $array);
        }
        return $fieldsStr;
    }

    /**
     * 数组数据解析
     * @access protected
     * @param array  $data
     * @return mixed
     */
    protected function parseArrayData($data)
    {
        list($type, $value) = $data;
        switch (strtolower($type)) {
            case 'exp':
                $result = $value;
                break;
            case 'point':
                $fun   = isset($data[2]) ? $data[2] : 'GeomFromText';
                $point = isset($data[3]) ? $data[3] : 'POINT';
                if (is_array($value)) {
                    $value = implode(' ', $value);
                }
                $result = $fun . '(\'' . $point . '(' . $value . ')\')';
                break;
            default:
                $result = false;
        }
        return $result;
    }

    /**
     * 随机排序
     * @access protected
     * @return string
     */
    protected function parseRand()
    {
        return 'rand()';
    }

}
