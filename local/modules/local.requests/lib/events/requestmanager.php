<?php

namespace Local\Requests\Events;

final class RequestManager
{
    public static function onBeforeRequestAdd(&$arFields)
    {
        echo '<pre>';
        var_dump($arFields);
        echo '</pre>';

        die();
    }
}