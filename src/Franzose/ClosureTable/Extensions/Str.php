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
     * @param $name
     * @return string
     */
    public static function classify($name)
    {
        return studly_case(str_singular($name));
    }

    /**
     * Makes database table name from given class name.
     *
     * @param $name
     * @return string
     */
    public static function tableize($name)
    {
        $name = str_replace('\\', '', $name);

        return (ends_with($name, 'Closure') ? snake_case($name) : snake_case(str_plural($name)));
    }
}
