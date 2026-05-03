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
        //fixme: подумать как сократить можно
        //add
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
        //update
        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnBeforeIBlockElementUpdate',
            $this->MODULE_ID,
            '\Local\Requests\Events\RequestManager',
            'onBeforeRequestUpdate'
        );
        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            '\Local\Requests\Events\RequestManager',
            'onAfterRequestUpdate'
        );
        //mail
        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnBeforeMailSend',
            $this->MODULE_ID,
            '\Local\Requests\Events\RequestManager',
            'onBeforeRequestDoneMailSend'
        );
        //agent
        $this->installAgent();

        return true;
    }

    public function DoUninstall(): bool
    {
        try {
            $eventManager = \Bitrix\Main\EventManager::getInstance();

            //add
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
            //update
            $eventManager->unRegisterEventHandler(
                'iblock',
                'OnBeforeIBlockElementUpdate',
                $this->MODULE_ID,
                '\Local\Requests\Events\RequestManager',
                'onBeforeRequestUpdate'
            );
            $eventManager->unRegisterEventHandler(
                'iblock',
                'OnAfterIBlockElementUpdate',
                $this->MODULE_ID,
                '\Local\Requests\Events\RequestManager',
                'onAfterRequestUpdate'
            );
            //mail
            $eventManager->unRegisterEventHandler(
                'iblock',
                'OnBeforeMailSend',
                $this->MODULE_ID,
                '\Local\Requests\Events\RequestManager',
                'onBeforeRequestDoneMailSend'
            );
            //agent
            $this->uninstallAgent();

            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        } catch (Exception $e) {
            global $APPLICATION;
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }

    private function installAgent(): void
    {
        $agentName = '\\Local\\Requests\\Agents\\RequestAgents::archiveExpired();';

        // Проверяем, существует ли уже агент
        $existingAgent = \CAgent::GetList(
            ['ID' => 'DESC'],
            ['NAME' => $agentName]
        )->Fetch();

        if (!$existingAgent) {
            \CAgent::AddAgent(
                $agentName,                // имя агента
                'local.requests',          // модуль
                'Y',                       // не периодический (свой интервал)
                60,                     // период: 24 часа
                '',                        // дата первого запуска (сейчас)
                'Y',                       // активен
                date("d.m.Y H:i:s", strtotime("+1 minute")), // запуск через минуту
                100                        // сортировка
            );
        }
    }
    private function uninstallAgent(): void
    {
        \CAgent::RemoveAgent('\\Local\\Requests\\Agents\\RequestAgents::archiveExpired();', 'local.requests');
    }
}
?>