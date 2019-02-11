<?php

if (! function_exists('nn')) {
    // nn = no nulls
    function nn($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = nn($haystack[$key]);
            }

            if ((is_array($haystack[$key]) && empty($haystack[$key])) || is_null($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }
}

if (! function_exists('objectProperties')) {
    function objectProperties($object)
    {
        return get_object_vars($object);
    }
}
