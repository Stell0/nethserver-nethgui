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
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 * @api
 */
abstract class AbstractModule implements \Nethgui\Module\ModuleInterface, \Nethgui\View\ViewableInterface, \Nethgui\Log\LogConsumerInterface, \Nethgui\Utility\PhpConsumerInterface, \Nethgui\Authorization\AuthorizationAttributesProviderInterface, \Nethgui\System\PlatformConsumerInterface, \Nethgui\Authorization\PolicyEnforcementPointInterface
{
    /**
     * @var string
     */
    private $identifier;

    /**
     *
     * @var \Nethgui\Module\ModuleInterface;
     */
    private $parent;
    /*
     * @var bool
     */
    private $initialized = FALSE;

    /**
     *
     * @var \Nethgui\Log\LogInterface
     */
    private $log;

    /**
     *
     * @var \Nethgui\Utility\PhpWrapper
     */
    private $php;

    /**
     *
     * @var \Nethgui\Module\SimpleModuleAttributesProvider
     */
    private $descriptor;

    /**
     * Template applied to view, if different from NULL
     *
     * @see \Nethgui\View\ViewInterface::setTemplate()
     * @var string|callable
     */
    private $viewTemplate;

    /**
     *
     * @var \Nethgui\System\PlatformInterface
     */
    private $platform;

    public function __construct($identifier = NULL)
    {
        $this->php = new \Nethgui\Utility\PhpWrapper(get_class($this));
        $this->viewTemplate = NULL;
        if (isset($identifier)) {
            $this->identifier = $identifier;
        } else {
            $this->identifier = \Nethgui\array_end(explode('\\', get_class($this)));
        }
    }

    public function setPlatform(\Nethgui\System\PlatformInterface $platform)
    {
        $this->platform = $platform;
        if ($platform instanceof \Nethgui\Log\LogConsumerInterface) {
            $this->setLog($platform->getLog());
        }
        return $this;
    }

    public function getPlatform()
    {
        return $this->platform;
    }

    public function hasPlatform()
    {
        return $this->getPlatform() instanceof \Nethgui\System\PlatformInterface;
    }

    /**
     *  Overriding methods can read current state from model.
     */
    public function initialize()
    {
        if ($this->initialized === FALSE) {
            $this->initialized = TRUE;
        } else {
            throw new \LogicException(sprintf("%s: module re-initialization is forbidden in class `%s`.", __CLASS__, get_class($this)), 1322148737);
        }
    }

    public function isInitialized()
    {
        return $this->initialized;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setParent(\Nethgui\Module\ModuleInterface $parentModule)
    {
        $this->parent = $parentModule;
        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        $template = $this->getViewTemplate();
        if ( ! is_null($template)) {
            $view->setTemplate($template);
        }
    }

    /**
     *
     * @param string|callable $template
     * @return \Nethgui\Module\AbstractModule
     */
    protected function setViewTemplate($template)
    {
        $this->viewTemplate = $template;
        return $this;
    }

    /**
     *
     * @return string|callable
     */
    protected function getViewTemplate()
    {
        return $this->viewTemplate;
    }

    public function setLog(\Nethgui\Log\LogInterface $log)
    {
        $this->log = $log;
        return $this;
    }

    public function getLog()
    {
        if ( ! isset($this->log)) {
            return new \Nethgui\Log\Nullog;
        }
        return $this->log;
    }

    /**
     * Called after a default attributes provider object is created
     *
     * @see getAttributesProvider()
     * @param \Nethgui\Module\ModuleAttributesInterface $descriptor
     * @return \Nethgui\Module\ModuleAttributesInterface
     */
    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return $attributes;
    }

    public function getAttributesProvider()
    {
        if ( ! isset($this->descriptor)) {
            $attributes = new \Nethgui\Module\SimpleModuleAttributesProvider();
            $this->descriptor = $this->initializeAttributes($attributes->initializeFromModule($this));
        }
        return $this->descriptor;
    }

    public function setPhpWrapper(\Nethgui\Utility\PhpWrapper $object)
    {
        $this->php = $object;
        return $this;
    }

    /**
     *
     * @return \Nethgui\Utility\PhpWrapper
     */
    protected function getPhpWrapper()
    {
        return $this->php;
    }

    public function asAuthorizationString()
    {
        return get_class($this) . ':' . $this->getIdentifier();
    }

    public function getAuthorizationAttribute($attributeName)
    {
        if ($attributeName === 'identifier') {
            return $this->getIdentifier();
        } elseif ($attributeName === 'title') {
            return $this->getAttributesProvider()->getTitle();
        } elseif ($attributeName === 'category') {
            return $this->getAttributesProvider()->getCategory();
        }

        return NULL;
    }

    /**
     *
     * @var \Nethgui\Authorization\PolicyDecisionPointInterface
     */
    private $pdp;

    /**
     * Access the associated PDP object. If no PDP has been associated to
     * this object, an always-pass PDP is returned by default.
     *
     * @return \Nethgui\Authorization\PolicyDecisionPointInterface
     */
    protected function getPolicyDecisionPoint()
    {
        if ( ! isset($this->pdp)) {
            $this->pdp = new \Nethgui\Test\Tool\StaticPolicyDecisionPoint(TRUE);
        }
        return $this->pdp;
    }

    public function setPolicyDecisionPoint(\Nethgui\Authorization\PolicyDecisionPointInterface $pdp)
    {
        $this->pdp = $pdp;
        return $this;
    }

}
