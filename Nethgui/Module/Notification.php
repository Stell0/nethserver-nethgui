<?php
namespace Nethgui\Module;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
 * 
 * This script is part of NethServer.
 * 
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Carries notification messages to the User.
 * 
 * Keeps persistent messages into User session through the NotificationManager object
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Notification extends \Nethgui\Controller\AbstractController implements \Nethgui\View\CommandReceiverInterface, \Nethgui\Utility\SessionConsumerInterface
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        $attributes = new SystemModuleAttributesProvider();
        $attributes->initializeFromModule($this);
        return $attributes;
    }    
    
    public function __construct($identifier = NULL)
    {
        parent::__construct($identifier);
        $this->notifications = new \ArrayObject();
    }

    public function setSession(\Nethgui\Utility\SessionInterface $session)
    {
        $key = get_class($this);

        $s = $session->retrieve($key);

        if ($s instanceof $this->notifications) {
            $this->notifications = $s;
        } else {
            $session->store($key, $this->notifications);
        }

        // Update notification state after retrieving objects from session:
        foreach (new \ArrayIterator($this->notifications) as $index => $notification) {
            if ( ! $notification instanceof \Nethgui\Module\Notification\AbstractNotification) {
                throw new \UnexpectedValueException(sprintf('%s: notifications must be instances of class \Nethgui\Module\Notification\AbstractNotification', get_class($this)), 1323168952);
            }

            // Transient notifications are dismissed:
            if ($notification->isTransient()) {
                $notification->dismiss();
            }

            // Dismissed notifications are dropped:
            if ($notification->isDismissed()) {
                unset($this->notifications[$index]);
            }
        }
        return $this;
    }

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('dismiss', '/^[a-zA-Z0-9]+$/');
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        if ($view->getTargetFormat() === $view::TARGET_XHTML) {
            $view->setTemplate(array($this, 'render'));
        } else {
            $view->setTemplate(FALSE);
        }

        // Sends a dismiss command to itself:
        if ($this->parameters['dismiss']) {
            unset($view['dismiss']);
            $view->getCommandList()->dismissNotification($this->parameters['dismiss']);
        }

        $this->view = $view;

        // Transfer notification attributes into the view
        foreach ($this->notifications as $notification) {
            $this->updateViewData($notification);
        }
    }

    public function render(\Nethgui\Renderer\Xhtml $renderer)
    {
        $renderer->includeFile('Nethgui/Js/jquery.nethgui.notification.js');

        $panel = $renderer->panel()->setAttribute('name', 'Pane')->setAttribute('receiver', '');

        foreach ($renderer as $offset => $innerView) {
            if ( ! $innerView instanceof \Nethgui\View\ViewInterface
                || $innerView['dismissed'] === TRUE) {
                continue;
            }

            $panel->insert($renderer->inset($offset));
        }

        return (String) $panel;
    }

    public function executeCommand(\Nethgui\View\ViewInterface $origin, $selector, $name, $arguments)
    {
        if ($name === 'showNotification') {
            $this->showNotification($arguments[0]);
        } elseif ($name === 'showMessage') {
            if ( ! isset($arguments[1])) {
                $arguments[1] = \Nethgui\Module\Notification\AbstractNotification::NOTIFY_SUCCESS;
            }

            $notification = new \Nethgui\Module\Notification\TextNotification($origin->getModule(), $arguments[0], $arguments[1]);

            $this->showNotification($notification);
        } elseif ($name === 'dismissNotification') {
            $this->dismissNotification($arguments[0]);
        }
    }

    protected function dismissNotification($notificationId)
    {
        if (isset($this->notifications[$notificationId])) {
            $this->notifications[$notificationId]->dismiss();
            $this->updateViewData($this->notifications[$notificationId]);
        }
    }

    protected function showNotification(\Nethgui\Module\Notification\AbstractNotification $notification)
    {
        $id = $notification->getIdentifier();
        $this->notifications[$id] = $notification;
        $this->updateViewData($notification);
    }

    protected function updateViewData(\Nethgui\Module\Notification\AbstractNotification $notification)
    {
        if ( ! isset($this->view)) {
            return;
        }
        $innerView = $this->view->spawnView($this);
        $notification->prepareView($innerView);
        $this->view[$notification->getIdentifier()] = $innerView;
    }

}
