<?php
/**
 * This file is part of MDE-Service
 * <http://github.com/piwi/mde-service>
 *
 * Copyright 2014 Pierre Cassat
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace MdeService;

/**
 * Class Container
 *
 * This is the global service provider of the MdeService namespace.
 * It acts like a registry of objects indexed by a simple string.
 * All access methods are static.
 *
 * To register an object, use:
 *
 *      Container::set( name , object instance )
 *
 * To retrieve it, use:
 *
 *      Container::get( name )
 *
 * @package MdeService
 */
final class Container
    implements SingletonInterface
{

// ------------------------------
// singleton object instance
// ------------------------------

    /**
     * @var self The singleton instance
     */
    private static $_instance;

    /**
     * Get the container instance
     * @return \MdeService\Container
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * Avoid direct construction
     */
    private function __construct() {}

// ------------------------------
// static access to container
// ------------------------------

    /**
     * use this flag to throw errors on failures
     */
    const FAIL_WITH_ERROR = 0;

    /**
     * use this flag to NOT throw error on failures
     */
    const FAIL_GRACEFULLY = 1;

    /**
     * @param   string  $name
     * @param   object  $object
     * @param   int     $on_failure
     * @throws  \Exception
     * @return  bool
     * @see     self::_set()
     */
    public static function set($name, $object, $on_failure = self::FAIL_WITH_ERROR)
    {
        try {
            return self::getInstance()->_set($name, $object, $on_failure);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param   string  $name
     * @param   int     $on_failure
     * @throws  \Exception
     * @return  object
     * @see     self::_get()
     */
    public static function get($name, $on_failure = self::FAIL_WITH_ERROR)
    {
        try {
            return self::getInstance()->_get($name, $on_failure);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param   string  $name
     * @return  bool
     * @see     self::_exists()
     */
    public static function exists($name)
    {
        return self::getInstance()->_exists($name);
    }

// ------------------------------
// container internals
// ------------------------------

    /**
     * @var     array   This is the container registry of objects, indexed by their names in lowercase
     */
    private $_registry = array();

    /**
     * @param   string    $name         the name of the object to store in the container
     * @param   object    $object       the instance of the object to store
     * @param   int       $on_failure   flag to set the behavior on error
     * @throws  \Exception if the object already exists or is not an object
     * @return  bool
     */
    public function _set($name, $object, $on_failure = self::FAIL_WITH_ERROR)
    {
        $name = strtolower($name);
        if (!$this->_exists($name)) {
            if (is_object($object)) {
                $this->_registry[$name] = $object;
                return true;
            } elseif (($on_failure & self::FAIL_WITH_ERROR)) {
                throw new \Exception(
                    sprintf('A "%s" container entry must be an object!', $name)
                );
            }
        } elseif (($on_failure & self::FAIL_WITH_ERROR)) {
            throw new \Exception(
                sprintf('The "%s" container entry can not be override!', $name)
            );
        }
        return false;
    }

    /**
     * @param   string    $name         the name of the object to get from the container
     * @param   int       $on_failure   flag to set the behavior on error
     * @throws  \Exception if the object does not exist
     * @return  object
     */
    public function _get($name, $on_failure = self::FAIL_WITH_ERROR)
    {
        $name = strtolower($name);
        if ($this->_exists($name)) {
            return (isset($this->_registry[$name]) ? $this->_registry[$name] : null);
        } elseif (($on_failure & self::FAIL_WITH_ERROR)) {
            throw new \Exception(
                sprintf('Trying to access an unknown container entry "%s"!', $name)
            );
        }
        return null;
    }

    /**
     * @param   string  $name
     * @return  bool
     */
    public function _exists($name)
    {
        $name = strtolower($name);
        return (bool) (isset($this->_registry[$name]));
    }

}
