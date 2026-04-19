<?php
Class local_requests extends CModule
{
    var $MODULE_ID = "dv_module";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    function __construct()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->MODULE_NAME = "local_requests – модуль системы звявок";
        $this->MODULE_DESCRIPTION = "Модуль создан для реализации системы заявок";
    }
//    function InstallFiles()
//    {
//        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/dv_module/install/components",
//            $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
//        return true;
//    }
//    function UnInstallFiles()
//    {
//        DeleteDirFilesEx("/local/components/dv");
//        return true;
//    }
//    function DoInstall()
//    {
//        global $DOCUMENT_ROOT, $APPLICATION;
//        $this->InstallFiles();
//        RegisterModule("dv_module");
//        $APPLICATION->IncludeAdminFile("Установка модуля local_requests", $DOCUMENT_ROOT."/local/modules/dv_module/install/step.php");
//    }
//    function DoUninstall()
//    {
//        global $DOCUMENT_ROOT, $APPLICATION;
//        $this->UnInstallFiles();
//        UnRegisterModule("dv_module");
//        $APPLICATION->IncludeAdminFile("Деинсталляция модуля local_requests", $DOCUMENT_ROOT."/local/modules/dv_module/install/unstep.php");
//    }
    public function DoInstall(): bool
    {
        try {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        } catch (Exception $e) {
            global $APPLICATION;
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }

    public function DoUninstall(): bool
    {
        try {
            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        } catch (Exception $e) {
            global $APPLICATION;
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }
}
?>