<?php

if (!function_exists('getTransAttributes')) {
    function getTransAttributes(array $fields): array
    {
        $languages = array_keys(config('langs'));
        $attributes = [];

        foreach ($fields as $field) {
            foreach ($languages as $lang) {
                $attributes["$field.$lang"] = $field;
            }
        }

        return $attributes;
    }
}
