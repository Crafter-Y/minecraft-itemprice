<?php

/**
 * include this file inside your config.php to get a profiling-overview of your webapp.
 * note: you need the xdebug extension
 *
 * @author mnt@codeninja.de
 * @author timo.huber (Erweiterung)
 * @package kata_debugging
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2009 mnt@codeninja.de, gameforge ag
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 */
if (defined('XHPROF_ENABLED') && extension_loaded('xhprof')) {
    if (XHPROF_ENABLED === true) {
        /* XHPROF pathes */
        define('XHPROF_CONFIG_PATH', '/home/www/xhprof/xhprof-gui/xhprof_lib/config.php');
        define('XHPROF_LIB_PATH', '/home/www/xhprof/xhprof-gui/xhprof_lib/utils/xhprof_lib.php');
        define('XHPROF_RUNS_PATH', '/home/www/xhprof/xhprof-gui/xhprof_lib/utils/xhprof_runs.php');
        define('XHPROF_LIB_ROOT', '/home/www/xhprof/xhprof-gui/xhprof_lib/');

        // require xprof files. use defined variables in config
        require_once XHPROF_CONFIG_PATH;
        require_once XHPROF_LIB_PATH;
        require_once XHPROF_RUNS_PATH;
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

        register_shutdown_function('kataXhprofShutdown');
    }
}

function kataXhprofShutdown()
{
    $xhprof_data = xhprof_disable();
    $xhprof_runs = new XHProfRuns_Default();

    if (CLI) {
        global $argv;
        $tempArgv = $argv;
        array_shift($tempArgv); // we don't need the filename
        $_SERVER['REQUEST_URI'] = '/CLI/' . implode('/', $tempArgv);
    }
    $run_id = $xhprof_runs->save_run($xhprof_data, CACHE_IDENTIFIER);

    // no debug output. otherwise the debug will also be visible in ajax, api requests, etc.
}
