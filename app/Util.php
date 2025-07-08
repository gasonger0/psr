<?php

namespace App;

class Util
{
    public static function getDefaults($line_id = false)
    {
        $defs = config('lines_defaults');
        if ($line_id !== false) {
            $index = array_search($line_id, array_column($defs, 'line_id'));
            if ($index !== false) {
                return $defs[$index];
            } else {
                return false;
            }
        } else {
            return $defs;
        }
    }
}
