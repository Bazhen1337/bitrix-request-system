<?php
//3. Обработчики событий (регистрация в модуле)
namespace Local\Requests\Events;

use Local\Requests\helpers\IBlockHelper;

final class RequestManager
{
    public static string $oldStatusId = "";
    public static string $requestId = "";
    public static function onBeforeRequestAdd(&$arFields)
    {
        if ($arFields["IBLOCK_ID"] != IBlockHelper::getID('requests')) {
            return true;
        }

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
            $APPLICATION->throwException(GetMessage("REQUEST_PAST_DATE_ERROR"));
            return false;
        }

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
                        "DESCRIPTION" => GetMessage("REQUEST_CLOSED_WARNING_LOG"),
                    ]);
                });

                global $APPLICATION;
                $APPLICATION->throwException(GetMessage("REQUEST_CLOSED_ERROR"));
                return false;
            }
        }
        return true;
    }
    public static function onAfterRequestUpdate(&$arFields) {
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
                        "DESCRIPTION" => GetMessage("REQUEST_ARCHIVE_INFO_LOG"),
                    ]);

                    $userFieldId = \CIBlockProperty::GetList([], [
                        "IBLOCK_ID" => IBlockHelper::getID('requests'),
                        "CODE" => "AUTHOR"
                    ])->GetNext()['ID'];

                    $rsUser = \CUser::GetByID(current($arFields["PROPERTY_VALUES"][$userFieldId])["VALUE"]);

                    if ($arUser = $rsUser->Fetch()) {
                        $userName = !empty($arUser['NAME']) ? $arUser['NAME'] : $arUser['LOGIN'];
                        $userEmail = $arUser['EMAIL'];
                    }

                    $dateFieldId = \CIBlockProperty::GetList([], [
                        "IBLOCK_ID" => IBlockHelper::getID('requests'),
                        "CODE" => "MEET_DATE"
                    ])->GetNext()['ID'];

                    $managerCommentFieldId = \CIBlockProperty::GetList([], [
                        "IBLOCK_ID" => IBlockHelper::getID('requests'),
                        "CODE" => "MANAGER_COMMENT"
                    ])->GetNext()['ID'];

                    if (isset($userName) && isset($userEmail)) {
                        self::$requestId = $arFields["ID"];
                        \CEvent::SendImmediate(
                            "LOCAL_REQUEST_DONE",
                            's1',
                            [
                                "REQUEST_DATE" => current($arFields["PROPERTY_VALUES"][$dateFieldId])["VALUE"],
                                "USER_NAME" => $userName,
                                "USER_EMAIL" => $userEmail,
                                "MANAGER_COMMENT" => current($arFields["PROPERTY_VALUES"][$managerCommentFieldId])["VALUE"]
                            ],
                        );
                    } else {
                        \CEventLog::Add([
                            "SEVERITY" => "WARNING",
                            "AUDIT_TYPE_ID" => "requests",
                            "MODULE_ID" => "local.requests",
                            "ITEM_ID" => $arFields["ID"],
                            "DESCRIPTION" => GetMessage("REQUEST_MISSED_USER_DATA_WARNING_LOG"),
                        ]);
                    }
                }
            }
        }
        return true;
    }
    public static function onBeforeRequestDoneMailSend(&$arFields)
    {
        if ($arFields['HEADER']['X-EVENT_NAME'] === 'LOCAL_REQUEST_DONE') {

            $extraInfo = "\n\n---";
            if (self::$requestId != "") {
                $extraInfo .= "\nАйди заявки: " . self::$requestId;
            }

            $date = date('d.m.Y H:i:s');
            $extraInfo .= "\nДата отправки: " . $date;

            $arFields['BODY'] .= $extraInfo;
            \CEventLog::Add([
                "SEVERITY" => "INFO",
                "AUDIT_TYPE_ID" => "requests",
                "MODULE_ID" => "local.requests",
                "ITEM_ID" => "",
                "DESCRIPTION" => print_r($arFields, true),
            ]);
        }
    }
}