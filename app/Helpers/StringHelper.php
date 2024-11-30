<?php

namespace App\Helpers;

use App\Http\Controllers\Controller;
use Cocur\Slugify\Slugify;

class StringHelper extends Controller
{
    public static function toCamelCase($string) 
    {
        $parts = explode('_', $string);
        $camelCaseString = array_shift($parts);
        foreach ($parts as $part) { 
            $camelCaseString .= ucfirst(strtolower($part)); 
        }
        return $camelCaseString; 
    }

    public static function convertKeysToCamelCase($array) 
    { 
        $result = []; 
        foreach ($array as $key => $value) { 
            $newKey = self::toCamelCase($key); 
            if (is_array($value)) { 
                $value = self::convertKeysToCamelCase($value); 
            } elseif (is_object($value)) {
                $value = self::convertKeysToCamelCase($value->toArray());
            } 
            $result[$newKey] = $value; 
        }
        return $result; 
    }

    public static function convertListKeysToCamelCase($list) 
    { 
        return array_map(function ($item) { 
            return self::convertKeysToCamelCase($item); 
        }, $list); 
    }
}