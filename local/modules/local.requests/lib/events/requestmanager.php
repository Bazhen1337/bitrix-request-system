<?php

namespace Local\Requests\Events;

use Local\Requests\helpers\IBlockHelper;

final class RequestManager
{
    public static function onBeforeRequestAdd(&$arFields)
    {
        if ($arFields["IBLOCK_ID"] != IBlockHelper::getID('requests')) {
            return true;
        }
        //fixme: вынести в функцию
        $propId = null;
        $res = \CIBlockProperty::GetList([], [
            "IBLOCK_ID" => IBlockHelper::getID('requests'),
            "CODE" => "MEET_DATE"
        ]);

        if ($prop = $res->Fetch()) {
            $propId = $prop["ID"];
        }
        $userDate = new \Bitrix\Main\Type\DateTime(
            $arFields["PROPERTY_VALUES"][$propId]["n0"]["VALUE"] ??
            $arFields["PROPERTY_VALUES"][$propId]["VALUE"]
        );

        $currentTime = new \Bitrix\Main\Type\DateTime();
        if ($userDate->getTimestamp() < $currentTime->getTimestamp()) {
            global $APPLICATION;
            $APPLICATION->throwException("Ошибка: Дата встречи не может быть в прошлом!");
            return false;
        }
        //fixme: вынести в функцию
        $propId = null;
        $res = \CIBlockProperty::GetList([], [
            "IBLOCK_ID" => IBlockHelper::getID('requests'),
            "CODE" => "STATUS"
        ]);

        if ($prop = $res->Fetch()) {
            $propId = $prop["ID"];
        }
        if (!$arFields["PROPERTY_VALUES"][$propId][0]["VALUE"]) {
            $res = \CIBlockPropertyEnum::GetList([], [
                "IBLOCK_ID" => IBlockHelper::getID('requests'),
                "PROPERTY_CODE" => "STATUS",
                "VALUE" => "Новая"
            ]);
            if ($enum = $res->Fetch()) {
                $arFields["PROPERTY_VALUES"][$propId][0]["VALUE"] = $enum["ID"];
            }
        }
        return true;
    }
    public static function onAfterRequestAdd(&$arFields)
    {
        if ($arFields["ID"] > 0 && $arFields["IBLOCK_ID"] == IBlockHelper::getID('requests')) {

            $propId = null;
            $res = \CIBlockProperty::GetList([], [
                "IBLOCK_ID" => IBlockHelper::getID('requests'),
                "CODE" => "AUTHOR"
            ]);

            if ($prop = $res->Fetch()) {
                $propId = $prop["ID"];
            }

            $userId = $arFields["PROPERTY_VALUES"][$propId]["n0"]["VALUE"] ?? $arFields["PROPERTY_VALUES"][$propId];

            if ($userId > 0) {
                $dbUser = \CUser::GetList(
                    "ID", "ASC",
                    ["ID" => $userId],
                    ["SELECT" => ["UF_REQUESTS_COUNT"]]
                );

                if ($user = $dbUser->Fetch()) {
                    $count = (int)$user["UF_REQUESTS_COUNT"];

                    $count++;

                    $userObject = new \CUser;
                    $userObject->Update($userId, [
                        "UF_REQUESTS_COUNT" => $count
                    ]);
                }
            }
        }
    }
    public static function onBeforeRequestUpdate(&$arFields) {
        if ($arFields["IBLOCK_ID"] != IBlockHelper::getID('requests')) {
            return true;
        }

        $statusCurrentValue = null;
        $statusCurrentEnumId = null;

        $statusRes = \CIBlockElement::GetList(
            arFilter: ["ID" => $arFields["ID"]],
            arSelectFields: ["PROPERTY_STATUS"]
        );
        if ($status = $statusRes->Fetch()) {
            $statusCurrentValue = $status["PROPERTY_STATUS_VALUE"];
            $statusCurrentEnumId = $status["PROPERTY_STATUS_ENUM_ID"];
        }

        //fixme: вынести DESCRIPTION в языквой файл потом
        if (in_array($statusCurrentValue, ["Завершена", "Отклонена"])) {
            $propId = null;
            $res = \CIBlockProperty::GetList([], [
                "IBLOCK_ID" => IBlockHelper::getID('requests'),
                "CODE" => "STATUS"
            ]);

            if ($prop = $res->Fetch()) {
                $propId = $prop["ID"];
            }
            if ($arFields["PROPERTY_VALUES"][$propId][0]["VALUE"] != $statusCurrentEnumId) {
                $itemId = $arFields["ID"];
                register_shutdown_function(function() use ($itemId) {
                    \CEventLog::Add([
                        "SEVERITY" => "WARNING",
                        "AUDIT_TYPE_ID" => "requests",
                        "MODULE_ID" => "local.requests",
                        "ITEM_ID" => $itemId,
                        "DESCRIPTION" => "Попытка изменения статуса закрытой заявки",
                    ]);
                });

                global $APPLICATION;
                $APPLICATION->throwException('Ошибка: нельзя перевести статус из "Завершена" или "Отклонена"');
                return false;
            }
        }
    }
    public static function onAfterRequestUpdate(&$arFields) {

    }
    public static function onBeforeRequestDoneMailSend(&$arFields) {

    }
}