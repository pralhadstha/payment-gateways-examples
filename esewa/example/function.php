<?php

if (!function_exists('getCode')) {
    function getCode($lettersLength = 4, $numbersLength = 3)
    {
        $letters = implode('', array_map(function () {
            return chr(rand(65, 90));
        }, range(1, $lettersLength)));

        $numbers = implode('', array_map(function () {
            return rand(0, 9);
        }, range(1, $numbersLength)));

        return "$letters$numbers";
    }
}
