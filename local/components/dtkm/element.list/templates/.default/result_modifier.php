<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Data\Cache;

if ($arParams["SHOW_AUTHOR_DATA"] === "Y" && !empty($arResult["ELEMENTS"])) {
    $userIDs = [];
    foreach ($arResult["ELEMENTS"] as $arItem) {
        $authorId = $arItem["USER_PROPERTY"]["PROPERTY_" . $arParams["ELEMENT_ASSOC_PROPERTY"] . "_VALUE"];
        if ($authorId > 0) {
            $userIDs[] = $authorId;
        }
    }
    $userIDs = array_unique($userIDs);


    if (!empty($userIDs)) {
        global $USER;
        $currentUserGroups = $USER->GetUserGroupArray();

        $isExpert = $USER->IsAdmin() || count(array_intersect($currentUserGroups, (array)$arParams["EXPERT_MANAGER_GROUPS"])) > 0;
        $isManager = count(array_intersect($currentUserGroups, (array)$arParams["MANAGER_GROUPS"])) > 0;

        if ($isExpert || $isManager) {
            $cacheId = "user_data_" . ($isExpert ? "expert" : "mgr") . "_" . md5(serialize($userIDs));
            $cacheDir = "/custom_author_info";
            $cache = Cache::createInstance();

            if ($cache->initCache(3600, $cacheId, $cacheDir)) {
                $userData = $cache->getVars();
            } elseif ($cache->startDataCache()) {
                $userData = [];
                $selectFields = ["ID", "NAME"];
                if ($isExpert) {
                    $selectFields[] = "EMAIL";
                }

                $rsUsers = CUser::GetList("ID", "ASC", ["ID" => implode("|", $userIDs)], ["FIELDS" => $selectFields]);
                while ($user = $rsUsers->Fetch()) {
                    $userData[$user["ID"]] = $user["NAME"] . ($user["EMAIL"] ? " | " . $user["EMAIL"] : "");
                }
                $cache->endDataCache($userData);
            }

            foreach ($arResult["ELEMENTS"] as &$arItem) {
                $authorId = $arItem["USER_PROPERTY"]["PROPERTY_" . $arParams["ELEMENT_ASSOC_PROPERTY"] . "_VALUE"];

                if (isset($userData[$authorId])) {
                    $arItem["PROPERTIES"]["AUTHOR_INFO_VALUE"] = $userData[$authorId];
                }
            }
            unset($arItem);
        }
    }
}

$this->__component->SetResultCacheKeys(['SHOW_ALL']);