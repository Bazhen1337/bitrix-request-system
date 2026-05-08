<?
$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . "/../../..");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

if (!Loader::includeModule("local.requests"))
{
    return;
}

Local\Requests\Agents\RequestAgents::archiveExpired();