<?php

/**
 * NethGui
 *
 * @package Modules
 */

/**
 * TODO: describe class
 *
 * @package Modules
 */
final class NethGui_Module_RemoteAccess extends NethGui_Core_Module_Composite implements NethGui_Core_TopModuleInterface
{

    public function getTitle()
    {
        return "Remote access";

    }

    public function getParentMenuIdentifier()
    {
        return "Security";

    }

    public function initialize()
    {
        parent::initialize();
        // TODO: implement child autoloading in Composite.
        foreach (array('Pptp', 'RemoteManagement', 'Ssh', 'Ftp') as $dependency) {
            $childModuleClass = 'NethGui_Module_RemoteAccess_' . $dependency;
            $childModule = new $childModuleClass();
            $childModule->setHostConfiguration($this->getHostConfiguration());
            $this->addChild($childModule);
        }
    }

    public function prepareView(NethGui_Core_ViewInterface $response)
    {
        parent::prepareView($response);
        // TODO: cleanup
        log_message('info', 'Format: ' . $response->getFormat());

        if($response->getFormat() === NethGui_Core_ViewInterface::HTML)
        {
            log_message('info', '$response->setViewName(\'NethGui_Core_View_form\');');
            $response->setViewName('NethGui_Core_View_form');
            $response->setData(array('save' => 1));
        }
    }

}