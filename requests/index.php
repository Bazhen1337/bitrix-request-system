<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (is_string($_REQUEST["backurl"]) && mb_strpos($_REQUEST["backurl"], "/") === 0)
{
    LocalRedirect($_REQUEST["backurl"]);
}

$APPLICATION->SetTitle("Вход на сайт");
?>
<?//$APPLICATION->IncludeComponent(
//	"bitrix:iblock.element.add",
//	".default",
//	Array(
//		"AJAX_MODE" => "Y",
//		"AJAX_OPTION_ADDITIONAL" => "",
//		"AJAX_OPTION_HISTORY" => "N",
//		"AJAX_OPTION_JUMP" => "N",
//		"AJAX_OPTION_STYLE" => "Y",
//		"ALLOW_DELETE" => "N",
//		"ALLOW_EDIT" => "N",
//		"COMPONENT_TEMPLATE" => ".default",
//		"CUSTOM_TITLE_DATE_ACTIVE_FROM" => "",
//		"CUSTOM_TITLE_DATE_ACTIVE_TO" => "",
//		"CUSTOM_TITLE_DETAIL_PICTURE" => "",
//		"CUSTOM_TITLE_DETAIL_TEXT" => "",
//		"CUSTOM_TITLE_IBLOCK_SECTION" => "",
//		"CUSTOM_TITLE_NAME" => "",
//		"CUSTOM_TITLE_PREVIEW_PICTURE" => "",
//		"CUSTOM_TITLE_PREVIEW_TEXT" => "",
//		"CUSTOM_TITLE_TAGS" => "",
//		"DEFAULT_INPUT_SIZE" => "30",
//		"DETAIL_TEXT_USE_HTML_EDITOR" => "N",
//		"ELEMENT_ASSOC" => "PROPERTY_ID",
//		"ELEMENT_ASSOC_PROPERTY" => "12",
//		"GROUPS" => array(0=>"2",),
//		"IBLOCK_ID" => "5",
//		"IBLOCK_TYPE" => "requests",
//		"LEVEL_LAST" => "Y",
//		"MAX_FILE_SIZE" => "0",
//		"MAX_LEVELS" => "100000",
//		"MAX_USER_ENTRIES" => "100000",
//		"NAV_ON_PAGE" => "10",
//		"PREVIEW_TEXT_USE_HTML_EDITOR" => "N",
//		"PROPERTY_CODES" => array(0=>"10",1=>"11",2=>"NAME",),
//		"PROPERTY_CODES_REQUIRED" => array(0=>"10",1=>"NAME",),
//		"RESIZE_IMAGES" => "N",
//		"SEF_MODE" => "N",
//		"STATUS" => "ANY",
//		"STATUS_NEW" => "N",
//		"USER_MESSAGE_ADD" => "",
//		"USER_MESSAGE_EDIT" => "",
//		"USE_CAPTCHA" => "N"
//	),
//false,
//Array(
//	'ACTIVE_COMPONENT' => 'Y'
//)
//);?>

<?$APPLICATION->IncludeComponent(
    "bitrix:iblock.element.add.list",
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

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>