<?php
/**
 * @package Core
 * @author Davide Principi <davide.principi@nethesis.it>
 */

/**
 * @package Core
 */

/**
 * Carries notification messages from modules to the User
 *  
 * A module 
 */
interface NethGui_Core_NotificationCarrierInterface
{                  
    public function showDialog(NethGui_Core_ModuleInterface $module, $message, $actions = array(), $type = self::NOTIFY_SUCCESS);
        
    public function addRedirectOrder(NethGui_Core_ModuleInterface $module, $path = array());
}