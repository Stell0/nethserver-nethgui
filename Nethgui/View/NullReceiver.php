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
 * A Receiver that does nothing
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class NullReceiver implements \Nethgui\View\CommandReceiverInterface
{

    /**
     * Get the NullReceiver singleton
     * @staticvar NullReceiver $singleton
     * @return NullReceiver
     */
    static public function getNullInstance()
    {
        static $singleton;

        if ( ! isset($singleton)) {
            $singleton = new self();
        }

        return $singleton;
    }

    protected function __construct()
    {
        // constructor is not publicly available
    }

    public function executeCommand(\Nethgui\View\ViewInterface $origin, $selector, $name, $arguments)
    {
        // DO NOTHING
    }

}
