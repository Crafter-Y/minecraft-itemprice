<?php

/**
 * Contains the class-registry
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2009 mnt@codeninja.de, gameforge ag
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata_internal
 */

/**
 * Class-registry, a pseudo-singleton wrapper. Used to memorize and return all classes we did already instanciate.
 * @package kata_internal
 */
class classRegistry
{

    /**
     * array to save objects of any classed created
     * @var array
     */
    static protected $objects = array();

    /**
     * return an instance of the class given in $name. If the class does not exist yet, create it.
     *
     * @param string $name name of the class to return an instance of
     * @param string $key to create the same class more than once. uses simple caching to return consistency
     */
    static function &getObject($name, $key = '')
    {
        $objname = $name . (empty($key) ? '' : '/') . $key;

        if (!isset(self::$objects[$objname])) {
            self::$objects[$objname] = new $name;
        }
        return self::$objects[$objname];
    }

    /**
     * check if we already registered given classname
     *
     * @param string $name name of the class to return an instance of
     * @param string $key to create the same class more than once
     * @return bool
     */
    static function hasObject($name, $key = '')
    {
        $objname = $name . (empty($key) ? '' : '/') . $key;
        return isset(self::$objects[$objname]);
    }

    /**
     * return all classes in the cache. used for debugging
     * @return array cache-array
     */
    static function getLoadedClasses()
    {
        return self::$objects;
    }

    /**
     * stores an Object of the given name in the repository. used for mocking in unittests
     *
     * @param string $objname the name of the $obj to store, including optional $key (see ::hasObject() )
     * @param object $obj the instance to store
     * @param bool $replaceExisting
     * @throws Exception
     */
    static function storeObject($objname, $obj, $replaceExisting = false)
    {
        if (defined('CALLED_BY_TEST') && constant('CALLED_BY_TEST') === true) {
            if (isset(self::$objects[$objname])) {
                if ($replaceExisting) {
                    unset(self::$objects[$objname]);
                } else {
                    throw new Exception('classRegistry::storeObject(): object already exists', E_USER_ERROR);
                }
            }
            self::$objects[$objname] = $obj;
        }
    }

    /**
     * clears the class cache for unittest tearDown()
     *
     */
    static function clear()
    {
        if (defined('CALLED_BY_TEST') && constant('CALLED_BY_TEST') === true) {
            foreach (self::$objects as $objname => $obj) {
                unset(self::$objects[$objname]);
            }
            self::$objects = array();
        }
    }

}
