<?php

/**
 * never change this file!
 *
 * @package kata_internal
 * @author mnt@codeninja.de
 */
/**
 * setup always needed paths
 */
require('..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'defines.php');

/**
 * include needed files
 */
require(LIB . "boot.php");

/**
 * call dispatcher to handle the rest
 */
if (!defined('USE_CUSTOM_DISPATCHER') || !USE_CUSTOM_DISPATCHER) {
    $dispatcher = new dispatcher();
} else {
    require('..' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . CUSTOM_DISPATCHER_NAME . '.php');
    $class = CUSTOM_DISPATCHER_NAME;
    $dispatcher = new $class();
}
echo $dispatcher->dispatch(isset($_GET['kata']) ? $_GET['kata'] : '', isset($routes) ? $routes : null);