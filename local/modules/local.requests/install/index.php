<?php
Class local_requests extends CModule
{
    var $MODULE_ID = "local.requests";
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

    public function DoInstall(): bool
    {
        try {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        } catch (Exception $e) {
            global $APPLICATION;
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnBeforeIBlockElementAdd',
            $this->MODULE_ID,
            '\Local\Requests\Events\RequestManager',
            'onBeforeRequestAdd'
        );
        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            '\Local\Requests\Events\RequestManager',
            'onAfterRequestAdd'
        );

        return true;
    }

    public function DoUninstall(): bool
    {
        try {
            $eventManager = \Bitrix\Main\EventManager::getInstance();

            $eventManager->unRegisterEventHandler(
                'iblock',
                'OnBeforeIBlockElementAdd',
                $this->MODULE_ID,
                '\Local\Requests\Events\RequestManager',
                'onBeforeRequestAdd'
            );
            $eventManager->unRegisterEventHandler(
                'iblock',
                'OnAfterIBlockElementAdd',
                $this->MODULE_ID,
                '\Local\Requests\Events\RequestManager',
                'onAfterRequestAdd'
            );

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