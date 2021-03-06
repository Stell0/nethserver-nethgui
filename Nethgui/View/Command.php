<?php
namespace Nethgui\View;

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
 * @internal
 */
class Command implements \Nethgui\View\CommandInterface
{

    /**
     *
     * @var \Nethgui\View\CommandReceiverInterface
     */
    private $receiver;

    /**
     *
     * @var string
     */
    private $methodName;

    /**
     *
     * @var array
     */
    private $arguments;

    /**
     *
     * @var boolean
     */
    private $executed;

    /**
     *
     * @var \Nethgui\View\ViewInterface
     */
    private $origin;

    /**
     *
     * @var string
     */
    private $selector;

    /**
     *
     * @param \Nethgui\View\ViewInterface $origin
     * @param string $selector
     * @param string $methodName
     * @param array $arguments
     */
    public function __construct(\Nethgui\View\ViewInterface $origin, $selector, $methodName, $arguments)
    {
        $this->origin = $origin;
        $this->selector = $selector;
        $this->methodName = $methodName;
        $this->arguments = $arguments;
        $this->executed = FALSE;
    }

    public function execute()
    {
        if ($this->executed === TRUE) {
            throw new \LogicException(sprintf('%s: command was already executed', get_class($this)), 1322148828);
        }

        if ( ! $this->receiver instanceof \Nethgui\View\CommandReceiverInterface) {
            throw new \LogicException(sprintf('%s: invalid receiver object', get_class($this)), 1323170262);
        }

        $this->executed = TRUE;

        $this->receiver->executeCommand($this->origin, $this->selector, $this->methodName, $this->arguments);

        return $this;
    }

    public function getOrigin()
    {
        return $this->origin;
    }

    public function getSelector()
    {
        return $this->selector;
    }

    public function setSelector($selector)
    {
        $this->selector = $selector;
    }

    public function setReceiver(\Nethgui\View\CommandReceiverInterface $receiver)
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function isExecuted()
    {
        return $this->executed === TRUE;
    }

}
