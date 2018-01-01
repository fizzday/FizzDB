<?php

namespace Fizzday\FizzDB;

//use Fizzday\Facades\Facade;
/**
 * Class DB
 * @package Fizzday\FizzDB
 * @see \Fizzday\FizzDB\DBBuilder
 */
//class DB extends Facade
class DB
{
    protected static $builder = '\Fizzday\FizzDB\Builder';
//    protected static $builder = 'Builder';

    /**
     * 单例容器, 依赖注入
     * @var
     */
    public static $instance;

    /**
     * @param $method
     * @param $params
     * @return mixed
     */
    public function __call( $method, $params ) {
        return self::callReal($method, $params);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        return self::callReal($method, $params);
    }

    /**
     * 统一调用
     * @param $method
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public static function callReal($method, $params)
    {
        $class = get_called_class();

        if ( empty( self::$instance[$class] ) ) {
            $namespace = substr($class, 0, strrpos($class, '\\')+1);
//            $class_name = empty(static::$builder) ? $namespace.'Builder' : $namespace.static::$builder;

            if (empty(static::$builder)) throw new \Exception('builder real needed');
            if (strpos(static::$builder, '\\') === false) {
                $class_name = $namespace.static::$builder;
            } else {
                $class_name = static::$builder;
            }

            self::$instance[$class] = new $class_name();
        }

        return call_user_func_array( [ self::$instance[$class], $method ], $params );
    }
}
