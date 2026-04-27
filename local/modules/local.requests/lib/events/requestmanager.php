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
    public static string $oldStatusId = "";
    public static function onBeforeRequestUpdate(&$arFields) {

        if ($arFields["IBLOCK_ID"] != IBlockHelper::getID('requests') || (is_null($arFields['PROPERTY_VALUES']))) {
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
        self::$oldStatusId = $statusCurrentEnumId;
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
        return true;
    }
    public static function onAfterRequestUpdate(&$arFields) {
//        **`OnAfterIBlockElementUpdate`** — при смене статуса на `Завершена`:
//
//        - Отправить почтовое уведомление через собственное почтовое событие `LOCAL_REQUEST_DONE`.
//        - Программно **добавить агент** на ближайший запуск для архивации данной заявки.
//        - Записать в **журнал событий**.
        if ($arFields["IBLOCK_ID"] != IBlockHelper::getID('requests')) {
            return true;
        }
        $propId = null;
        $res = \CIBlockProperty::GetList([], [
            "IBLOCK_ID" => IBlockHelper::getID('requests'),
            "CODE" => "STATUS"
        ]);

        if ($prop = $res->Fetch()) {
            $propId = $prop["ID"];
        }
        if ($arFields["PROPERTY_VALUES"][$propId][0]["VALUE"] != self::$oldStatusId) {
            $res = \CIBlockPropertyEnum::GetList([], [
                "IBLOCK_ID" => IBlockHelper::getID('requests'),
                "PROPERTY_CODE" => "STATUS",
                "VALUE" => "Завершена"
            ]);
            if ($enum = $res->Fetch()) {
                if ($arFields["PROPERTY_VALUES"][$propId][0]["VALUE"] == $enum["ID"]) {
                    $agentName = "\\Local\\Requests\\Agents\\RequestAgents::archiveRequest(" . $arFields["ID"] . ");";

                    // Проверяем, не добавлен ли уже такой агент, чтобы не дублировать
                    $dbAgents = \CAgent::GetList([], ["NAME" => $agentName]);
                    if (!$dbAgents->Fetch()) {
                        \CAgent::AddAgent(
                            $agentName,        // имя функции
                            "local.requests",  // ID модуля
                            "N",               // агент не критичен к количеству запусков
                            60,                // интервал запуска (сек) - через минуту
                            "",                // дата первой проверки (пусто - сейчас)
                            "Y",               // активен
                            date("d.m.Y H:i:s", strtotime("+1 minute")) // запуск через минуту
                        );
                    }
                    \CEventLog::Add([
                        "SEVERITY" => "INFO",
                        "AUDIT_TYPE_ID" => "requests",
                        "MODULE_ID" => "local.requests",
                        "ITEM_ID" => $arFields["ID"],
                        "DESCRIPTION" => "заявка завершена",
                    ]);
                }
            }
        }
    }
    public static function onBeforeRequestDoneMailSend(&$arFields) {

    }
}