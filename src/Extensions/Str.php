<?php
namespace Franzose\ClosureTable\Extensions;

use Illuminate\Support\Str as BaseStr;

/**
 * Extension of the base Str class.
 *
 * @package Franzose\ClosureTable\Extensions
 */
class Str extends BaseStr
{
    /**
     * Makes appropriate class name from given string.
     *
     * @param string $name
     * @return string
     */
    public static function classify($name)
    {
        return static::studly(static::singular($name));
    }

    /**
     * Makes database table name from given class name.
     *
     * @param string $name
     * @return string
     */
    public static function tableize($name)
    {
        $name = str_replace('\\', '', $name);

        return static::endsWith($name, 'Closure')
            ? static::snake($name)
            : static::snake(static::plural($name));
    }
}
