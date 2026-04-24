<?php

namespace Local\Requests\Agents;

class RequestAgents
{
    public static function archiveRequest($elementId)
    {

        if ($elementId <= 0) return "";
        //todo: не вызывается статически эта залупа
        \Bitrix\Main\Loader::includeModule('iblock');
        $res = \CIBlockElement::Update($elementId, [
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