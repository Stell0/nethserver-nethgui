<?php
namespace Nethgui\System;

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
 * Read and write parameters into SME DB
 *
 * Ths class implements an interface to SME database executing the command /sbin/e-smith/db with sudo.
 * The class needs /etc/sudoers configurazione. In the sudoers file you must have something like this:
 * <code>
 * Cmnd_Alias SME = /sbin/e-smith/db, /sbin/e-smith/signal-event
 * www ALL=NOPASSWD: SME
 * </code>
 *
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 * @internal
 */
class EsmithDatabase implements \Nethgui\System\DatabaseInterface, \Nethgui\Authorization\PolicyEnforcementPointInterface, \Nethgui\Utility\PhpConsumerInterface, \Nethgui\Authorization\AuthorizationAttributesProviderInterface, \Nethgui\Log\LogConsumerInterface
{
    const PERM_READ = 'READ';
    const PERM_WRITE = 'WRITE';

    /**
     *
     * @var \Nethgui\Utility\PhpWrapper
     */
    private $phpWrapper;

    /**
     * @var PolicyDecisionPointInterface;
     */
    private $policyDecisionPoint;

    /**
     * @var SME DB database command
     * */
    private $command = "/usr/bin/sudo /sbin/e-smith/db";

    /**
     * @var $db Database name, it's translated into the db file path. For example: /home/e-smith/db/testdb
     * */
    private $db;

    /**
     * @var \Nethgui\Authorization\AccessControlResponseInterface
     * */
    private $readPermission;

    /**
     * @var \Nethgui\Authorization\AccessControlResponseInterface
     * */
    private $writePermission;

    /**
     * Keeps User object acting on this database. 
     * @var \Nethgui\Authorization\UserInterface
     */
    private $user;

    /**
     * This static class variable shares a socket connection to smwingsd
     *
     * @var resource
     */
    private static $socket;

    /**
     *
     * @var \Nethgui\Log\LogInterface
     */
    private $log;

    /**
     * setPolicyDecisionPoint 
     * 
     * @param \Nethgui\Authorization\PolicyDecisionPointInterface $pdp 
     * @access public
     * @return \Nethgui\Authorization\PolicyEnforcementPointInterface
     */
    public function setPolicyDecisionPoint(\Nethgui\Authorization\PolicyDecisionPointInterface $pdp)
    {
        $this->policyDecisionPoint = $pdp;
        $this->authorizeDbAccess();
        return $this;
    }

    /**
     * Construct an object to access a SME Configuration database file
     * with $user's privileges.
     * 
     * @param string $database Database name
     */
    public function __construct($database, \Nethgui\Authorization\UserInterface $user)
    {
        if ( ! $database)
            throw new \InvalidArgumentException(sprintf("%s: You must provide a valid database name.", get_class($this)), 1322148910);

        $this->db = $database;
        $this->user = $user;
        $this->log = new \Nethgui\Log\Nullog();
        $this->readPermission = \Nethgui\Authorization\LazyAccessControlResponse::createDenyResponse();
        $this->writePermission = \Nethgui\Authorization\LazyAccessControlResponse::createDenyResponse();        
    }

    private function authorizeDbAccess()
    {
        $resource = __CLASS__ . ':' . $this->db;
        $this->readPermission = $this->policyDecisionPoint->authorize($this->getUser(), $resource, self::PERM_READ);
        $this->writePermission = $this->policyDecisionPoint->authorize($this->getUser(), $resource, self::PERM_WRITE);
    }

    /**
     * @return \Nethgui\Authorization\UserInterface
     */
    private function getUser()
    {
        return $this->user;
    }

    public function getAll($type = NULL)
    {
        if ($this->readPermission->isDenied()) {
            throw $this->readPermission->asException(1322149164);
        }

        $output = "";

        $ret = $this->dbRead('getjson', array(), $output);
        if ($ret !== 0) {
            throw new \UnexpectedValueException(sprintf("%s: internal database command failed!", __CLASS__), 1350896938);
        }

        $data = json_decode($output, TRUE);
        if ( ! is_array($data)) {
            throw new \UnexpectedValueException(sprintf("%s: unexpected json string `%s`", __CLASS__, substr($output, 0, 8)), 1350896939);
        }

        $result = array();

        foreach ($data as $item) {
            // Apply type check filter:
            if (isset($type) && $type !== $item['type']) {
                continue;
            }
            $props = isset($item['props']) ? $item['props'] : array();
            $result[$item['name']] = array_merge($props, array('type' => $item['type']));
        }

        return $result;
    }

    public function getKey($key)
    {
        if ($this->readPermission->isDenied()) {
            throw $this->readPermission->asException(1322149166);
        }

        $output = '';

        $ret = $this->dbRead('getjson', array($key), $output);
        if ($ret !== 0) {
            throw new \UnexpectedValueException(sprintf("%s: internal database command failed", __CLASS__), 1350909145);
        }

        $data = json_decode($output, TRUE);

        if ($data === 1) {
            // Key has not been found
            return array();
        } elseif ( ! is_array($data)) {
            throw new \UnexpectedValueException(sprintf("%s: unexpected json string `%s`", __CLASS__, substr($output, 0, 8)), 1350909146);
        } elseif ( ! isset($data['props'])) {
            // No props set
            return array();
        }

        return $data['props'];
    }

    public function setKey($key, $type, $props)
    {
        if ($this->writePermission->isDenied()) {
            throw $this->writePermission->asException(1322149167);
        }

        $output = NULL;
        $ret = $this->dbExec('set', $this->prepareArguments($key, $type, $props), $output);
        return ($ret == 0);
    }

    public function deleteKey($key)
    {
        if ($this->writePermission->isDenied()) {
            throw $this->writePermission->asException(1322149168);
        }

        $output = NULL;
        $output = NULL;
        $ret = $this->dbExec('delete', $this->prepareArguments($key), $output);
        return ($ret == 0);
    }

    /**
     * Return the type of a key
     * Act like: /sbin/e-smith/db dbfile gettype key
     * 
     * @param string $key the key to retrieve
     * @access public
     * @return string the type of the key
     */
    public function getType($key)
    {
        if ($this->readPermission->isDenied()) {
            throw $this->readPermission->asException(1322149169);
        }

        $output = NULL;
        $ret = $this->dbRead('gettype', array($key), $output);
        return trim($output);
    }

    /**
     * Set the type of a key 
     * Act like: /sbin/e-smith/db dbfile settype key type
     * 
     * @param string $key the key to change
     * @param string $type the new type
     * @access public
     * @return bool true on success, FALSE otherwise
     */
    public function setType($key, $type)
    {
        if ($this->writePermission->isDenied()) {
            throw $this->writePermission->asException(1322149193);
        }

        $output = NULL;
        $ret = $this->dbExec('settype', $this->prepareArguments($key, $type), $output);
        return ($ret == 0);
    }

    public function getProp($key, $prop)
    {
        if ($this->readPermission->isDenied()) {
            throw $this->readPermission->asException(1322149194);
        }

        $output = NULL;
        $ret = $this->dbRead('getprop', array($key, $prop), $output);
        return trim($output);
    }

    public function setProp($key, $props)
    {
        if ($this->writePermission->isDenied()) {
            throw $this->writePermission->asException(1322149191);
        }

        $output = NULL;
        $ret = $this->dbExec('setprop', $this->prepareArguments($key, $props), $output);
        return ($ret == 0);
    }

    public function delProp($key, $props)
    {
        if ($this->writePermission->isDenied()) {
            throw $this->writePermission->asException(1322149192);
        }

        $output = NULL;
        $ret = $this->dbExec('delprop', array_merge(array($key), array_values($props)), $output);
        return ($ret == 0);
    }

    public function setPhpWrapper(\Nethgui\Utility\PhpWrapper $object)
    {
        $this->phpWrapper = $object;
        return $this;
    }

    /**
     * Execute the db command
     * @param string $command The command to invoke
     * @param array $args The command arguments
     * @param string &$output The output from the command process
     * @return int  The command exit code
     */
    private function dbExec($command, $args, &$output)
    {
        // prepend the database name and command
        array_unshift($args, $this->db, $command);
        $p = new Process($this->command . ' ${@}', $args);
        if (isset($this->phpWrapper)) {
            $p->setPhpWrapper($this->phpWrapper);
        }
        $p->exec();
        $output = $p->getOutput();
        return $p->getExitCode();
    }

    /**
     * Read db data from memory cache daemon
     * @param string $command The command to invoke
     * @param array $args The command arguments
     * @param string &$output The output from the read socket
     * @return int  The command exit code
     */
    private function dbRead($command, $args, &$output)
    {
        if ( ! isset(self::$socket) ) {
            $socketPath = '/var/run/smwingsd.sock';
            $errno = 0;
            $errstr = '';
            self::$socket = $this->phpWrapper->fsockopen('unix://' . $socketPath, -1, $errno, $errstr);
            if( ! is_resource(self::$socket)) {
                $this->getLog()->warning(sprintf("Invalid socket (%d): %s. Fall back to exec().", $errno, $errstr));
            }
        }
         
        if ( ! is_resource(self::$socket)) {
            return $this->dbExec($command, call_user_func_array(array($this, 'prepareArguments'), $args), $output);            
        }

        // prepend the database name and command
        array_unshift($args, $this->db, $command);

        try {
            $this->sendMessage(self::$socket, 0x10, $args);
            if ($command === 'getjson') {
                $output = $this->recvMessage(self::$socket);
            } else {
                $output = json_decode($this->recvMessage(self::$socket), TRUE);
            }
        } catch (\Exception $ex) {
            $this->log->exception($ex);
            return 1;
        }

        return 0;
    }

    private function sendMessage($socket, $type, $args = array())
    {
        $payload = json_encode($args);
        $data = pack('CN', (int) $type, strlen($payload)) . $payload;
        $written = $this->phpWrapper->fwrite($socket, $data);
        if ($written !== strlen($data)) {
            throw new \RuntimeException('Socket write error', 1383145263);
        }
        return TRUE;
    }

    private function recvMessage($socket)
    {
        $buf = $this->safeRead($socket, 5);
        if ($buf === FALSE) {
            throw new \RuntimeException('Socket read error', 1383145266);
        }

        $header = unpack('Ctype/Nsize', $buf);
        if ( ! is_array($header)) {
            throw new \RuntimeException('Invalid message header', 1383145264);
        }

        $message = NULL;
        $message = $this->safeRead($socket, $header['size']);
        if ($message === FALSE) {
            throw new \RuntimeException('Socket read error', 1383145265);
        }

        if ($header['type'] & 0x02) {
            return NULL;
        }

        return $message;
    }

    private function safeRead($socket, $size) 
    {
        $buffer = "";
        $count = 0;
        while($count < $size) {
            if(feof($socket)) {
                return FALSE;
            }
            $chunk = $this->phpWrapper->fread($socket, $size - $count);
            $count += strlen($chunk);
            if($chunk === FALSE) {
                return FALSE;
            }
            $buffer .= $chunk;
        }
        return $buffer;
    }


    /**
     * Take arbitrary arguments and flattenize to an array
     *
     * @param mixed $_
     * @return array
     */
    private function prepareArguments()
    {
        $args = array();

        foreach (func_get_args() as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $propName => $propValue) {
                    $args[] = $propName;
                    $args[] = $propValue;
                }
            } else {
                $args[] = (String) $arg;
            }
        }

        return $args;
    }

    public function asAuthorizationString()
    {
        return get_class($this) . ':' . $this->db;
    }

    public function getAuthorizationAttribute($attributeName)
    {
        if ($attributeName === 'dbname') {
            return $this->db;
        }

        return NULL;
    }

    public function setLog(\Nethgui\Log\LogInterface $log)
    {
        $this->log = $log;
        return $this;
    }

    public function getLog()
    {
        return $this->log;
    }

}