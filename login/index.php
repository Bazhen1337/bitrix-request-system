<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (is_string($_REQUEST["backurl"]) && mb_strpos($_REQUEST["backurl"], "/") === 0)
{
	LocalRedirect($_REQUEST["backurl"]);
}

$APPLICATION->SetTitle("Вход на сайт");
$userId = $USER->GetID();
?><?$APPLICATION->IncludeComponent(
	"bitrix:blog.user", 
	".default", 
	array(
		"BLOG_VAR" => "",
		"DATE_TIME_FORMAT" => "d.m.Y H:i:s",
		"ID" => $userId,
		"PAGE_VAR" => "",
		"PATH_TO_BLOG" => "",
		"PATH_TO_SEARCH" => "",
		"PATH_TO_USER" => "",
		"PATH_TO_USER_EDIT" => "",
		"SET_TITLE" => "Y",
		"USER_CONSENT" => "N",
		"USER_CONSENT_ID" => "0",
		"USER_CONSENT_IS_CHECKED" => "Y",
		"USER_CONSENT_IS_LOADED" => "N",
		"USER_PROPERTY" => array(
			0 => "UF_REQUESTS_COUNT",
		),
		"USER_VAR" => "",
		"COMPONENT_TEMPLATE" => ".default"
	),
	false
);?>
    <h2>Мои заявки</h2>
<?$APPLICATION->IncludeComponent(
	"dtkm:element.list",
	".default",
	Array(
		"ALLOW_DELETE" => "N",
		"ALLOW_EDIT" => "N",
		"COMPONENT_TEMPLATE" => ".default",
		"EDIT_URL" => "",
		"ELEMENT_ASSOC" => "PROPERTY_ID",
		"ELEMENT_ASSOC_PROPERTY" => "12",
		"GROUPS" => array(0=>"2",),
		"IBLOCK_ID" => "5",
		"IBLOCK_TYPE" => "requests",
		"MAX_USER_ENTRIES" => "100000",
		"NAV_ON_PAGE" => "10",
		"SEF_MODE" => "N",
		"STATUS" => "ANY"
	)
);?>
<!--<a href="/requests/">Мои заявки</a>--><?$APPLICATION->IncludeComponent(
	"bitrix:iblock.element.add.form", 
	".default", 
	array(
		"CUSTOM_TITLE_DATE_ACTIVE_FROM" => "",
		"CUSTOM_TITLE_DATE_ACTIVE_TO" => "",
		"CUSTOM_TITLE_DETAIL_PICTURE" => "",
		"CUSTOM_TITLE_DETAIL_TEXT" => "",
		"CUSTOM_TITLE_IBLOCK_SECTION" => "",
		"CUSTOM_TITLE_NAME" => "",
		"CUSTOM_TITLE_PREVIEW_PICTURE" => "",
		"CUSTOM_TITLE_PREVIEW_TEXT" => "",
		"CUSTOM_TITLE_TAGS" => "",
		"DEFAULT_INPUT_SIZE" => "30",
		"DETAIL_TEXT_USE_HTML_EDITOR" => "N",
		"ELEMENT_ASSOC" => "PROPERTY_ID",
		"GROUPS" => array(
			0 => "2",
		),
		"IBLOCK_ID" => "5",
		"IBLOCK_TYPE" => "requests",
		"LEVEL_LAST" => "Y",
		"LIST_URL" => "",
		"MAX_FILE_SIZE" => "0",
		"MAX_LEVELS" => "100000",
		"MAX_USER_ENTRIES" => "100000",
		"PREVIEW_TEXT_USE_HTML_EDITOR" => "N",
		"PROPERTY_CODES" => array(
			0 => "10",
			1 => "11",
			2 => "NAME",
		),
		"PROPERTY_CODES_REQUIRED" => array(
			0 => "10",
			1 => "NAME",
		),
		"RESIZE_IMAGES" => "N",
		"SEF_MODE" => "N",
		"STATUS" => "ANY",
		"STATUS_NEW" => "N",
		"USER_MESSAGE_ADD" => "",
		"USER_MESSAGE_EDIT" => "",
		"USE_CAPTCHA" => "N",
		"COMPONENT_TEMPLATE" => ".default",
		"ELEMENT_ASSOC_PROPERTY" => "12"
	),
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>