<?php
namespace Nethgui\Controller;

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
 * Default implementation of RequestInterface
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 * @internal
 */
class Request implements \Nethgui\Controller\RequestInterface, \Nethgui\Utility\SessionConsumerInterface, \Nethgui\Log\LogConsumerInterface
{
    /**
     *
     * @var \Nethgui\Utility\SessionInterface
     */
    private $session;

    /**
     *
     * @var array
     */
    private $attributes = array(
        'format' => 'xhtml',
        'languageCode' => '',
        'languageCodeDefault' => 'en',
        'isValidated' => FALSE,
        'isMutation' => FALSE,
        'originalRequest' => FALSE,
    );

    /**
     *
     * @var array
     */
    private $data = array();

    /**
     *
     * @var \Nethgui\Log\LogInterface
     */
    private $log;

    public function __construct($data = array())
    {
        $this->data = $data;
        $this->setAttribute('originalRequest', $this);
    }

    public function setSession(\Nethgui\Utility\SessionInterface $session)
    {
        $this->session = $session;
        return $this;
    }

    public function setParameter($name, $value)
    {
        if ( ! isset($this->data[$name])) {
            $this->data[$name] = $value;
        }        
        return $this;
    }

    public function hasParameter($parameterName)
    {
        return array_key_exists($parameterName, $this->data);
    }

    public function isEmpty()
    {
        return empty($this->data);
    }

    public function isMutation()
    {
        return $this->getAttribute('isMutation') === TRUE;
    }

    public function getParameterNames()
    {
        return array_keys($this->data);
    }

    public function getParameter($parameterName)
    {
        if ( ! isset($this->data[$parameterName])) {
            return NULL;
        }
        return $this->data[$parameterName];
    }

    public function spawnRequest($subsetName, $path = array())
    {
        $parameterSubset = $this->getParameter($subsetName);
        if ( ! is_array($parameterSubset)) {
            $parameterSubset = array();
        }

        $instance = new static($parameterSubset);
        $instance->attributes = &$this->attributes;

        if (isset($this->session)) {
            $instance->setSession($this->session);
        }

        if (isset($this->log)) {
            $instance->setLog($this->getLog());
        }

        if (count($path) > 0) {
            $this->getLog()->deprecated("%s: %s, \$path argument is DEPRECATED");
        }

        return $instance;
    }

    public function getUser()
    {
        $key = \Nethgui\Authorization\UserInterface::ID;

        $user = $this->session->retrieve($key);

        if ( ! $user instanceof \Nethgui\Authorization\UserInterface) {
            $user = \Nethgui\Authorization\User::getAnonymousUser();
        }

        if ($user instanceof \Nethgui\Log\LogConsumerInterface) {
            $user->setLog($this->getLog());
        }

        return $user;
    }

    public function getPath()
    {
        $arr = &$this->data;
        $path = array();
        while (TRUE) {
            reset($arr);
            $part = key($arr);
            if ($part === NULL || ! is_array($arr[$part])) {
                break;
            }
            $path[] = $part;
            $arr = &$arr[$part];
        };

        return $path;
    }

    public function getAttribute($name)
    {
        if ( ! isset($this->attributes[$name])) {
            return NULL;
        }

        return $this->attributes[$name];
    }

    public function setAttribute($name, $value)
    {
        if ( ! isset($this->attributes[$name])) {
            throw new \LogicException(sprintf("%s: Cannot change the unknown attribute `%s`", __CLASS__, $name), 1325237327);
        }

        $this->attributes[$name] = $value;
        return $this;
    }

    public function getExtension()
    {
        return $this->getFormat();
    }

    public function getFormat()
    {
        return $this->getAttribute('format');
    }

    public function getLanguageCode()
    {
        $lang = $this->getAttribute('languageCode');
        if ( ! $lang) {
            $lang = $this->getUser()->getLanguageCode();
        }
        if ( ! $lang) {
            $lang = $this->getAttribute('languageCodeDefault');
        }
        return $lang;
    }

    public function isValidated()
    {
        return $this->getAttribute('isValidated') === TRUE;
    }

    /**
     * Experimental method that returns the original request path
     *
     * @return array
     */
    public function getOriginalPath()
    {
        return $this->getAttribute('originalRequest')->getPath();
    }

    public function getArgument($argumentName)
    {
        $this->getLog()->deprecated();
        if ( ! isset($this->data[$argumentName])) {
            return NULL;
        }
        return $this->data[$argumentName];
    }

    public function getArgumentNames()
    {
        $this->getLog()->deprecated();
        return array_keys($this->data);
    }

    public function hasArgument($argumentName)
    {
        $this->getLog()->deprecated();
        return array_key_exists($argumentName, $this->data);
    }

    public function getLog()
    {
        if ( ! isset($this->log)) {
            return new \Nethgui\Log\Nullog();
        }
        return $this->log;
    }

    public function setLog(\Nethgui\Log\LogInterface $log)
    {
        $this->log = $log;
        return $this;
    }

    public function toArray()
    {
        return $this->data;
    }

}
