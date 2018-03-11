<?php

namespace Archivr;

abstract class ArrayUtils
{
    /**
     * @see http://php.net/manual/en/function.array-diff.php#91756
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function recursiveArrayDiff(array $array1, array $array2): array
    {
        $return = [];

        foreach ($array1 as $key => $value)
        {
            if (array_key_exists($key, $array2))
            {
                if (is_array($value) && is_array($array2[$key]))
                {
                    $recursiveDiff = static::recursiveArrayDiff($value, $array2[$key]);

                    if (count($recursiveDiff))
                    {
                        $return[$key] = $recursiveDiff;
                    }
                }
                elseif ($value !== $array2[$key])
                {
                    $return[$key] = $value;
                }
            }
            else
            {
                $return[$key] = $value;
            }
        }

        return $return;
    }
}
