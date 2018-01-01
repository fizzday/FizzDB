<?php

namespace Fizzday\FizzDB;

use PDO;
use Exception;

class Builder
{
    protected $config;  // 数据库配置
    protected $pdo;     // pdo链接
    protected $sqlLogs; // 执行sql的日志记录
    protected $table;   // 当前操作的表名
    protected $join;    // 联表
    protected $data;    // 更新或增加的数据, 键值对数组
    protected $dataRaw; // 更新或增加的数据, sql语句
    protected $fields;  // 查询字段, 数组
    protected $bindValues;  // 绑定参数, 键值对数组
    protected $wheres;  // where条件, 不同情况, 例如: compact('type', 'column', 'operator', 'value', 'boolean');
    protected $group;   // 聚合
    protected $having;  // 聚合
    protected $order;   // 排序, id asc
    protected $limit = 1000;   // 查询数
    protected $offset = 0;  // 偏移量

    protected $return = [
        're' => '',
        'val' => ''
    ];

//    public function reset()
//    {
//        $this->table = null;
//        $this->join = null;
//        $this->data = null;
//        $this->dataRaw = null;
//        $this->fields = null;
//        $this->bindValues = null;
//        $this->wheres = null;
//        $this->group = null;
//        $this->having = null;
//        $this->order = null;
//        $this->limit = null;
//        $this->offset = null;
//        $this->return = [
//            're' => '',
//            'val' => ''
//        ];
//    }

    public function connection($con = '')
    {
        if ($con) {
            $this->setConfig($con);
        }

        return $this;
    }

    /**
     * 获取默认的数据库连接
     * @return PDO
     */
    public function getConnection($read = 'read')
    {
        $dbconf = $this->getConfig($read);

        $dsn = "mysql:host=" . $dbconf['host'] . ";port=" . $dbconf['port'] . ";dbname=" . $dbconf['database'] . ";charset=utf8";

        $this->pdo[$read] = new PDO($dsn, $dbconf['username'], $dbconf['password']);

        return $this;
    }

    /**
     * 手动设置config
     * @param array $config
     * @return $this
     */
    public function setConfig($config = [])
    {
        if (!$config) {
            $db = config('database', '');
            if ($db)
                $config = $db[$db['db_default']];
            else throw new Exception('数据库配置缺失');
        }
        $this->config = $config;

        return $this;
    }

    /**
     * 获取配置
     * @return mixed
     */
    public function getConfig($read = '')
    {
        if (!$this->config) {
            $this->setConfig();
        }

        $config = $this->config;
        if ($read) {
            if (!empty($config[$read])) {
                foreach ($config[$read] as $k => $v) {
                    $config[$k] = $v;
                }

                unset($config['read']);
                unset($config['write']);
            }
        }

        return $config;
    }

    /**
     * 获取原生PDO
     * @return mixed
     */
    public function getPdo($read = 'read')
    {
        if (empty($this->pdo[$read])) $this->getConnection($read);

        return $this->pdo[$read];
    }

    /**
     * 原始 pdo 查询语句
     * @param string $sql
     * @param array $param
     * @param bool $returnOne 返回一条数据(默认多条)
     * @return null
     * @throws Exception
     */
    public function query($sql = '', $param = [], $returnOne = false)
    {
        $sql = $sql ?: $this->buildSql('select');
        if (!$sql) throw new Exception('sql empty');

        // 检查是否为select语句
        if (strtoupper(substr(trim($sql), 0, 6)) != 'SELECT') throw new Exception('query() need select sql');

        if ($param) $this->bindValues = array_merge((array)$this->bindValues, $param);

        if ($returnOne) $sql .= ' limit 1';
//        else $sql .= ' limit 10';

        $stmt = $this->getPdo('read')->prepare($sql);
        $res = $stmt->execute($this->bindValues);

        // 默认取出为 object
        if ($res) {
            if ($returnOne) $res = $stmt->fetch(PDO::FETCH_OBJ);
            else $res = $stmt->fetchAll(PDO::FETCH_OBJ);
        }

//        $this->reset();

        return $res ?: null;
    }

    /**
     * 执行原生 pdo 增删改操作
     * @param string $sql
     * @param array $param
     * @return mixed
     */
    public function execute($sql = '', $param = [], $operation = '')
    {
        $oper = ['insert', 'update', 'delete'];
        $flag = $operation;

        if ($sql) {
            $flag = strtolower(substr(trim($sql), 0, 6));
        } else {
            $sql = $this->buildSql($flag);
        }

        if (!$sql) {
            throw new Exception('sql empty');
        }

        // 检查是否为select语句
        if (!in_array($flag, $oper)) throw new Exception('excute() need insert,update or delete');

        if ($param) $this->bindValues = array_merge((array)$this->bindValues, $param);

        $stmt = $this->getPdo('write')->prepare($sql);
        $res = $stmt->execute($this->bindValues);

        if ($res && ($flag == 'insert')) $insertId = $this->getPdo('write')->lastInsertId();

//        $this->reset();

        if ($res && ($flag == 'insert')) return $insertId;

        return $res;
    }

    /**
     * 指定表名
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;

        return $this;
    }

    public function data($data = [])
    {
        if ($data) {
            if (count($data) == count($data, 1)) {
                $this->data[] = $data;
            } else {
                foreach ($data as $v) {
                    if (count($data) == count($data, 1)) {
                        $this->data[] = $v;
                    } else throw new Exception('非法数据格式');
                }
            }
        }

        return $this;
    }

    public function insert($data = [])
    {
        $this->data($data);

        $res = $this->execute('', '', 'insert');

        return $res;
    }

    public function update($data = [])
    {
        $this->data($data);

        $res = $this->execute('', '', 'update');

        return $res;
    }

    public function delete($id = '')
    {
        $res = $this->execute('', '', 'delete');

        return $res;
    }

    public function get($re = 'get', $getOne = false)
    {
        $this->return['re'] = $re;

        return $this->query('', [], $getOne);
    }

    public function first()
    {
        return $this->get(__FUNCTION__, true);
    }

    public function pluck($field)
    {
        $res = $this->first();

        return $res->$field;
    }

    public function chunks($num = 100)
    {
        // 查询对应的数据量是否已经小于既定的offset
        $count = $this->count();

        if ($count < $this->offset) return false;

        $this->limit = $num;

        $data = $this->get(__FUNCTION__);

        $this->offset = (int)$this->offset + $num;

        return $data;
    }

    public function chunk($num = 100, callable $fun)
    {
        $page = floor(((int)$this->offset) / $num);
        do {
            $results = $this->limit($num)->offset($page * $num)->get();

            $count = count($results);

            if (!$count) return false;

            $fun($results);

            $page++;

        } while ($num == $count);

        return true;
//
//        $data = $this->get(__FUNCTION__);
//
//        $this->offset = (int)$this->offset + $num;
//
//        call_user_func($fun, $data);
    }

    public function find($id)
    {
    }

    public function count()
    {
        $this->return['val'] = 'count(1) as count';

        return $this->_calcResult(__FUNCTION__);
    }

    public function sum($field = '')
    {
        $this->return['val'] = "sum($field) as sum";

        return $this->_calcResult(__FUNCTION__);
    }

    public function avg($field = '', $decimal = 0)
    {
        $this->return['val'] = "round(avg($field), $decimal) as avg";

        return $this->_calcResult(__FUNCTION__);
    }

    public function max($field = '')
    {
        $this->return['val'] = "max($field) as max";

        return $this->_calcResult(__FUNCTION__);
    }

    public function min($field = '')
    {
        $this->return['val'] = "min($field) as min";

        return $this->_calcResult(__FUNCTION__);
    }

    private function _calcResult($fun)
    {
        $res = $this->get($fun, true);

        if ($res) {
            if (isset($res->$fun)) return $res->$fun;
        }

        return false;
    }

    public function join($table, $one, $operator = null, $two = null, $type = 'inner')
    {
        $this->join[] = "$type join " . $this->getPrefix() . $table . " on $one $operator $two";

        return $this;
    }

    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    public function increment($field, $num = 1)
    {
        $this->dataRaw[] = "$field = $field + $num";
        return $this->update();
    }

    public function decrement($field, $num = 1)
    {
        $this->dataRaw[] = "$field = $field - $num";
        return $this->update();
    }

    public function group($column, $desc = 'asc')
    {
        $this->group[] = "$column $desc";
        return $this;
    }

    public function having($raw, $bindValues = [])
    {
        $this->having = compact('raw', 'bindValues');
        return $this;
    }

    public function transaction(\Closure $closure)
    {
        try {
            $this->beginTransaction();
            //执行事务
            $closure();
            $this->commit();
            return true;
        } catch (Exception $e) {
            //回滚事务
            $this->rollBack();
            return false;
        }
    }

    public function beginTransaction()
    {
        return $this->getPdo('write')->beginTransaction();
    }

    public function rollBack()
    {
        return $this->getPdo('write')->rollBack();
    }

    public function commit()
    {
        return $this->getPdo('write')->commit();
    }

    public function getTable()
    {
        $this->checkTable();

        return $this->getPrefix() . $this->table;
    }

    public function getPrefix()
    {
        $config = $this->getConfig();

        return $config['prefix'];
    }

    /**
     * 构建sql
     * @return string
     */
    public function buildSql($oper = 'select')
    {
        $regx = [];
        $regx['table'] = $this->getTable();
        // update
        $regx['data'] = $this->_buildData($oper);
        $regx['where'] = $this->_buildWhere();
        // select
        $regx['fields'] = !$this->fields ? "*" : implode(',', $this->fields);
        $regx['join'] = !$this->join ? "" : implode(',', $this->join);
        $regx['group'] = !$this->group ? "" : " GROUP BY " . implode(' ', $this->group);
        $regx['having'] = $this->_buildHaving();
        $regx['order'] = !$this->order ? "" : " ORDER BY " . implode(' ', $this->order);
        $regx['limit'] = !$this->limit ? "" : " LIMIT " . $this->limit;
        $regx['offset'] = ($this->offset === false) ? "" : " OFFSET " . $this->offset;
        // insert
        $inserts = $this->_buildInsert($oper);
        $regx['insertKeys'] = $inserts['insertKeys'];
        $regx['insertValues'] = $inserts['insertValues'];
        // count, sum, avg, max, min
        if (in_array($this->return['re'], ['count', 'sum', 'avg', 'max', 'min'])) {
            $regx['fields'] = $this->return['val'];
        }

        $query = [
            "select" => "SELECT {$regx['fields']} FROM {$regx['table']} {$regx['join']} {$regx['where']} {$regx['group']} {$regx['having']} {$regx['order']} {$regx['limit']} {$regx['offset']}",
            "insert" => "INSERT INTO {$regx['table']} {$regx['insertKeys']}  VALUES {$regx['insertValues']}",
            "update" => "UPDATE {$regx['table']} SET {$regx['data']} {$regx['where']}",
            "delete" => "DELETE FROM {$regx['table']} {$regx['where']}"
        ];

        $sql = $query[strtolower($oper)];

        $this->setSqlLog($sql, $this->bindValues);

        return $sql;
    }

    private function _buildInsert($oper)
    {
        $inserts = [];
        $inserts['insertKeys'] = '';
        $inserts['insertValues'] = '';
        if ($oper != 'insert') return $inserts;

        if ($this->data) {
            $keys = array_keys($this->data[0]);
            $values = [];
            foreach ($this->data as $v) {
                $values_arr = [];
                foreach ($keys as $item) {
                    $this->bindValues[] = $v[$item];
                    $values_arr[] = '?';
                }

                $values[] = "(" . implode(',', $values_arr) . ")";
            }

            $inserts['insertKeys'] = "(" . implode(',', $keys) . ")";

            $inserts['insertValues'] = implode(',', $values);
        }
//        print_r($inserts);die;

        return $inserts;
    }

    private function _buildData($oper)
    {
        $data_all = '';
        if ($oper != 'update') return $data_all;

        if ($this->data || $this->dataRaw) {

            $data = '';
            $dataRaw = '';
            if ($this->data) {
                $val = [];
                foreach ($this->data as $v) {
                    $val = array_merge($val, $v);
                }
                $bindKeys = [];
                foreach ($val as $key => $value) {
                    $bindKeys[] = $key . '=?';
                    $this->bindValues[] = $value;
                }
                $data = implode(',', $bindKeys);
            }

            if ($this->dataRaw) {
                $dataRaw = implode(',', $this->dataRaw);
            }

            if ($data && $dataRaw) {
                $data_all = $data . ',' . $dataRaw;
            } else {
                $data_all = $data . $dataRaw;
            }
        }

        return $data_all;
    }

    /**
     * 构建sql语句前, 检查表名是否定义
     */
    protected function checkTable()
    {
        // 检查是否有table
        if (empty($this->table)) {
            $table = get_called_class();

            // 获取当前class的名字, 检查是否为 AR 类名
            if (strtoupper($table) == 'DB') {
                throw new Exception('table is needed');
            }

            $this->table($table);
        }

        return true;
    }

    /**
     * 存储最后执行的语句
     * @param string $sql
     * @param array $param
     */
    public function setSqlLog($sql = '', $param = [])
    {
        $this->sqlLogs[] = preg_replace("#\s+#", ' ', trim($sql)) . ',' . json_encode($param, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * 获取所有的sql执行记录
     * @return mixed
     */
    public function sqlLogs()
    {
        return $this->sqlLogs;
    }

    /**
     * 获取最后执行的语句
     * @return mixed
     */
    public function lastSql()
    {
        return $this->sqlLogs ? end($this->sqlLogs) : '';
    }

    /**
     * 设置要查询的字段, 可以是多个字段一起, 也可以是放数组中, 也可以是一个参数一个字段 (python's *args)
     * @return $this
     */
    public function fields()
    {
        $fields = (array)$this->fields;
        $args = func_get_args();
        if ($args) {
            $count = count($args);
            for ($i = 0; $i < $count; $i++) {
                if (is_array($args[$i])) {
                    $fields = array_merge($fields, $args[$i]);
                } else {
                    $fields = array_merge($fields, explode(',', $args[$i]));
                }
            }
        }

        $this->fields = array_unique($fields);

        return $this;
    }

    /**
     * where条件
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     */
    public function where($column = null, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column) {
            $where = $this->_parseWhere($column, $operator, $value, $boolean);

            // 做一下类型处理
            if ($where) {
                $type = 'Basic';
                foreach ($where as $item) {
                    list($column, $operator, $value) = [$item[0], $item[1], $item[2]];
                    $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');
                }
            }
        }

        return $this;
    }

    /**
     * 或者关系where条件
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    public function whereRaw($raw = '', $bindValues = [], $boolean = 'and')
    {
        if ($raw) {
            if (is_array($raw)) throw new \Exception('只能为字符串');

            $type = 'Raw';

            $this->wheres[] = compact('type', 'raw', 'bindValues', 'boolean');
        }

        return $this;
    }

    public function orWhereRaw($query = '', $bindValues = [], $boolean = 'or')
    {
        return $this->whereRaw($query, $bindValues, $boolean);
    }

    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';

        $this->wheres[] = compact('type', 'column', 'boolean');

        return $this;
    }

    public function whereNotNull($column, $boolean = 'and')
    {
        return $this->whereNull($column, $boolean, true);
    }

    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'or');
    }

    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, 'or');
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';

        if (!is_array($values)) throw new Exception('非法数据格式');

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        return $this;
    }

    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, 'or');
    }

    public function orWhereNotIn($column, $values)
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * 排序
     * @param $field
     * @param string $type
     * @return $this
     */
    public function order($field, $type = '')
    {
        $this->order[] = $field . ' ' . $type;

        return $this;
    }

    /**
     * 数据条数
     * @param $int
     * @return $this
     */
    public function limit($limit, $offset = 0)
    {
        $this->limit = $limit;

        if ($offset) $this->offset($offset);

        return $this;
    }

    /**
     * 从第几条开始
     * @param $int
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * 构建where语句
     * @return string
     */
    private function _buildWhere()
    {
        if (!$this->wheres) return false;

        $sql = '';

        foreach ($this->wheres as $v) {
            switch ($v['type']) {
                case 'Raw':
                    $sql .= " {$v['boolean']} ";
                    $sql .= "({$v['raw']})";
                    $this->bindValues = array_merge((array)$this->bindValues, $v['bindValues']);
                    break;
                case 'In':
                    $sql .= " {$v['boolean']} ";
                    $sql .= '(' . $v['column'] . ' in (' . implode(',', $v['values']) . '))';
                    break;
                case 'NotIn':
                    $sql .= " {$v['boolean']} ";
                    $sql .= '(' . $v['column'] . ' not in (' . implode(',', $v['values']) . '))';
                    break;
                case 'Null':
                    $sql .= " {$v['boolean']} ";
                    $sql .= '(' . $v['column'] . ' is null)';
                    break;
                case 'NotNull':
                    $sql .= " {$v['boolean']} ";
                    $sql .= '(' . $v['column'] . ' is not null)';
                    break;
                case 'Basic':
                    $sql .= " {$v['boolean']} (" . $v['column'] . $v['operator'] . "?)";
                    $this->bindValues[] = $v['value'];
                    break;
            }
        }

        return ' WHERE ' . ltrim(ltrim(trim($sql), 'or'), 'and');
    }

    private function _buildHaving()
    {
        $sql = '';
        if ($having = $this->having) {
            $sql .= " HAVING ({$having['raw']})";
            $this->bindValues = array_merge((array)$this->bindValues, $having['bindValues']);
        }

        return $sql;
    }

    private function _parseWhere($column, $oper = null, $value = null)
    {
        $where = [];

        if (is_array($column)) {
            if (count($column) == count($column, true)) { // 一维
                foreach ($column as $k => $v) {
                    $where[] = [$k, '=', $v];
                }
            } else {
                foreach ($column as $item) {
                    if (!is_array($item)) throw new Exception('非法参数格式');

                    $where[] = $this->_parseWhereItem($item);
                }
            }
        } else {
            list($oper, $value) = [$value ? $oper : '=', $value ?: $oper];
            $where[] = [$column, $oper, $value];
        }

        return $where;
    }

    private function _parseWhereItem($item)
    {
        if (!$item[1]) return [$item[0], '=', $item[1]];
        return $item;
    }

    /**
     * 解析where参数
     * @param $args
     * @return array
     * @throws Exception
     */
    private function _parseWhere_bak($args)
    {
        $allow_operation = [
            '=',
            '>',
            '<',
            '>=',
            '<=',
            '!=',
            '<>',
            'like'
        ];

        unset($args['boolean']);
        $where = [];

        $count_args = count($args);

        if ($count_args) {
            switch ($count_args) {
                case 3:
                    if (!in_array($args[1], $allow_operation)) throw new Exception('非法关系操作符');
                    $this->_ifArrayThenThrowBad($args[0], $args[1], $args[2]);

                    $where[] = [$args[0], $args[1], $args[2]];
                    break;

                case 2:
                    $this->_ifArrayThenThrowBad($args[0], $args[1]);

                    $where[] = [$args[0], '=', $args[1]];
                    break;

                case 1:
                    if (!is_array($args[0])) throw new Exception('非法参数格式');

                    if (is_callable($args[0])) {

                    } else {
                        foreach ($args[0] as $v) {
                            if (!is_array($v)) throw new Exception('非法参数格式');
                            if (count($v) == 3) {
                                $this->_ifArrayThenThrowBad($v[0], $v[1], $v[2]);

                                $where[] = [$v[0], $v[1], $v[2]];
                            } elseif (count($v) == 2) {
                                $this->_ifArrayThenThrowBad($v[0], $v[1]);

                                $where[] = [$v[0], '=', $v[1]];
                            } else throw new Exception('非法参数格式');
                        }
                    }
                    break;

                default:
                    throw new Exception('非法参数格式');
                    break;
            }
        }

        return $where;
    }

    /**
     * 如果是array, 抛出异常
     * @param $arr
     */
    private function _ifArrayThenThrowBad()
    {
        $args = func_get_args();

        if ($args) {
            foreach ($args as $v) {
                if (is_array($v)) throw new Exception('非法参数格式');
            }
        }
    }

    /**
     * 给表名或字段名添加反引号
     * @param string $tab
     * @return string
     */
    private function _addQuotes($tab = '')
    {
        if ($tab) return '`' . $tab . '`';
    }
}
