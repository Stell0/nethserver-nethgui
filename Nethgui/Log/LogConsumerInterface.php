<?php
namespace Nethgui\Log;

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
 * Implementor produces some log messages
 *
 * NOTE: when implementing this interface remind that getLog() must return always
 * a valid LogInterface object: instantiate Nullog as last resort.
 *
 * @see \Nethgui\Log\Nullog
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 * @api 
 */
interface LogConsumerInterface
{
    /**
     * Change the log attached to the interface implementor
     *
     * @api
     * @param \Nethgui\Log\LogInterface $log
     * @return \Nethgui\Log\LogConsumerInterface
     */
    public function setLog(\Nethgui\Log\LogInterface $log);

    /**
     * The log attached to the interface implementor
     *
     * @api
     * @return \Nethgui\Log\LogInterface
     */
    public function getLog();
}
