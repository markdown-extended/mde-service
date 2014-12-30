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
     * @param $name
     * @param $object
     * @throws \Exception
     */
    public static function set($name, $object)
    {
        try {
            self::getInstance()->_set($name, $object);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     */
    public static function delete($name)
    {
        self::getInstance()->_delete($name);
    }

    /**
     * @param $name
     * @return null
     */
    public static function get($name)
    {
        return self::getInstance()->_get($name);
    }

    /**
     * @param $name
     * @return bool
     */
    public static function exists($name)
    {
        return self::getInstance()->_exists($name);
    }

// ------------------------------
// container internals
// ------------------------------

    /**
     * @var array
     */
    private $_registry = array();

    /**
     * @param $name
     * @param $object
     * @throws \Exception
     */
    public function _set($name, $object)
    {
        $name = strtolower($name);
        try {
            if (!isset($this->_registry[$name])) {
                $this->_registry[$name] = $object;
            } else {
                throw new \Exception(
                    sprintf('A container entry can not be override! (for "%s")', $name)
                );
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @return null
     */
    public function _get($name)
    {
        $name = strtolower($name);
        return (isset($this->_registry[$name]) ? $this->_registry[$name] : null);
    }

    /**
     * @param $name
     * @return bool
     */
    public function _exists($name)
    {
        $name = strtolower($name);
        return (bool) (isset($this->_registry[$name]));
    }

    /**
     * @param $name
     */
    public function _delete($name)
    {
        $name = strtolower($name);
        if (isset($this->_registry[$name])) {
            unset($this->_registry[$name]);
        }
    }

}
