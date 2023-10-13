<?php

use Bitrix\Main\ModuleManager;

class boilerplate_tools extends CModule
{
    function __construct()
    {
        $this->MODULE_ID = 'boilerplate.tools';
        $this->MODULE_GROUP_RIGHTS = 'N';

        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = 'Boilerplate Tools';
        $this->MODULE_DESCRIPTION = 'Project module';

        $this->PARTNER_NAME = 'Uplab';
        $this->PARTNER_URI = 'https://uplab.ru';
    }

    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
