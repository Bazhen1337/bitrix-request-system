<?php
//5. Агент
namespace Local\Requests\Agents;

use Local\Requests\helpers\IBlockHelper;

class RequestAgents
{
    public static function archiveRequest($elementId)
    {
        if ($elementId <= 0) return "";
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
            "DESCRIPTION" => GetMessage("AGENT_DONE") . $res,
        ]);
        return "";
    }

    public static function archiveExpired()
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return __METHOD__ . "();";
        }

        $deadline = (new \Bitrix\Main\Type\DateTime())->add("-7 days");

        $res = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => IBlockHelper::getID('requests'),
                'PROPERTY_STATUS_VALUE' => 'В работе',
                '<=PROPERTY_MEET_DATE' => $deadline->format("Y-m-d H:i:s"),
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'PROPERTY_MEET_DATE']
        );
        $declineId = \CIBlockPropertyEnum::GetList([], [
            "IBLOCK_ID" => IBlockHelper::getID('requests'),
            "PROPERTY_CODE" => "STATUS",
            "VALUE" => "Отклонена"
        ])->Fetch()["ID"];

        while ($item = $res->Fetch()) {
            \CIBlockElement::SetPropertyValuesEx($item['ID'], $item['IBLOCK_ID'], [
                'STATUS' => [
                    'VALUE' => $declineId
                ]
            ]);
        }
        return __METHOD__ . "();";
    }
}