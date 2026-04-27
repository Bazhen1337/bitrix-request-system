<?php

namespace Local\Requests\Agents;

class RequestAgents
{
    public static function archiveRequest($elementId)
    {
        if ($elementId <= 0) return "";
        //todo: не вызывается статически эта залупа
        //todo: он походу перезатирает нахуй все остальные поля
        \Bitrix\Main\Loader::includeModule('iblock');
        $el = new \CIBlockElement;
        $res = $el->Update($elementId, [
            "ACTIVE" => "N",
        ]);

        \CEventLog::Add([
            "SEVERITY" => "INFO",
            "AUDIT_TYPE_ID" => "requests",
            "MODULE_ID" => "local.requests",
            "ITEM_ID" => $elementId,
            "DESCRIPTION" => "агент отработал, результат: " . $res,
        ]);
        return "";
    }
}