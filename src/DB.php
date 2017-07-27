<?php

namespace Fizzday\FizzDB;

class DB
{
//    protected $builder = '\Fizzday\Database\DBBuilder';

//    use \Fizzday\FizzTraits\CallTrait;
    public static $instance;

    /**
     * @param $method
     * @param $params
     * @return mixed
     */
    public function __call( $method, $params ) {
        if ( !( self::$instance ) ) {
            $class_name = empty(self::$service) ? get_called_class() . 'Builder' : self::$service;
            self::$instance = new $class_name();
        }

        return call_user_func_array( [ self::$instance, $method ], $params );
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([new static(), $method], $params);
    }
}
