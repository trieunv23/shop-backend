<?php

namespace App\Helpers;

use App\Http\Controllers\Controller;

class StringHelper extends Controller
{
    public static function convertToCamelCase($data)
    {
        $toCamelCase = function ($string) {
            $parts = explode('_', $string);
            $camelCaseString = array_shift($parts);
            foreach ($parts as $part) {
                $camelCaseString .= ucfirst(strtolower($part));
            }
            return $camelCaseString;
        };

        $processData = function ($input) use (&$processData, $toCamelCase) {
            if (is_array($input)) {
                $result = [];
                foreach ($input as $key => $value) {
                    $newKey = $toCamelCase($key);
                    if (is_array($value) || is_object($value)) {
                        $value = $processData($value);
                    }
                    $result[$newKey] = $value;
                }
                return $result;
            } elseif (is_object($input)) {
                $arrayData = json_decode(json_encode($input), true);
                return $processData($arrayData);
            }
            return $input;
        };

        return $processData($data);
    }
}