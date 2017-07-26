<?php

namespace Fizzday\FizzDB;

interface DBInterface
{
    public function getPdo();

    public function setConfig($config = []);

    public function table($con = '');
    /**
     * 设置要查询的字段, 可以是多个字段一起, 也可以是放数组中, 也可以是一个参数一个字段 (python's *args)
     * @return $this
     */
    public function fields();

    public function where();
    /**
     * 原始 pdo 查询语句
     * @param string $sql
     * @param string $param
     * @return mixed
     */
    public function query($sql = '', $param = []);
    /**
     * 执行原生 pdo 增删改操作
     * @param string $sql
     * @param array $param
     * @return mixed
     */
    public function execute($sql = '', $param = []);
    /**
     * 获取最后执行的语句
     * @return mixed
     */
    public function lastSql();
}