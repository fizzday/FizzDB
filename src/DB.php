<?php

namespace Fizzday\FizzDB;

use Fizzday\Facades\Facade;

/**
 * Class DB
 * @package Fizzday\FizzDB
 * @see \Fizzday\FizzDB\DBBuilder
 */
class DB extends Facade
{
    protected static $builder = '\Fizzday\FizzDB\Builder';
//    protected static $builder = 'Builder';
}
