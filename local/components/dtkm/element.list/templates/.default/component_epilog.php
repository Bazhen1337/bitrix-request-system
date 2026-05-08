<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("iblock")) return;

/** @var $arParams */
/** @var $arResult */

$iblockId = $arParams["IBLOCK_ID"];
$propertyCode = "STATUS";
$propertyAuthorCode = $arParams["ELEMENT_ASSOC_PROPERTY"];

if ($iblockId > 0) {
    $propertyEnums = CIBlockPropertyEnum::GetList(
        ["SORT" => "ASC", "VALUE" => "ASC"],
        ["IBLOCK_ID" => $iblockId, "CODE" => $propertyCode]
    );

    $stats = [];

    while ($enum = $propertyEnums->Fetch()) {

        $arFilter = [
            "IBLOCK_ID" => $iblockId,
            "PROPERTY_" . $propertyCode . "_VALUE" => $enum["VALUE"],
        ];

        global $USER;
        if (!$arResult["SHOW_ALL"]) {
            $arFilter["PROPERTY_" . $propertyAuthorCode . "_VALUE"] = $USER->GetID();
        }

        $count = CIBlockElement::GetList(
            [],
            $arFilter,
            []
        );

        $stats[] = [
            "NAME" => $enum["VALUE"],
            "COUNT" => $count
        ];
    }

    if (!empty($stats)) {
        echo '<div class="status-counters" style="margin-top: 20px; padding: 10px; border-top: 1px solid #eee;">';
        echo '<strong>Всего по статусам:</strong>';
        echo '<ul>';
        foreach ($stats as $stat) {
            echo '<li>' . htmlspecialcharsbx($stat["NAME"]) . ': ' . (int)$stat["COUNT"] . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}