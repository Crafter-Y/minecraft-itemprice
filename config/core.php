<?php

/**
 * framework core configuration
 * @package kata
 */

/**
 * which debug-level to use: 
 * 3 = as 2, but turn off any caching used
 * 2 = show all errors, debug() output and timing for view rendering and query execution
 * 1 = show all errors and debug() output, but be silent otherwise
 * 0 = dont show any error or debug information
 * -1 = als 0, but dont even log KATA_DEBUG
 */
define('DEBUG',1);

/**
 * how many seconds the session-component should wait until it expires a session
 */
define('SESSION_TIMEOUT',3000);

/**
 * the name of the cookie that saves the session token in the uses browser
 */
define('SESSION_COOKIE','SID');

/**
 * which method to use for session storage. can currently be:
 * FILE (=normal filsystem php sessions)
 * CLIENT (=session-data resides as cookie on clients browser)
 * MEMCACHED (=use memcached for session)
 */
define('SESSION_STORAGE','FILE');

/**
 * salt to use to ensure no one is fiddeling with our session token.
 * is also used some components for having an uniqe caching-identifier
 */
define('SESSION_STRING','kataDefault');

/**
* If a user changes his ip, the session is destroyed (for security reasons)
* kata does an educated guess of the users ip, even if he uses a proxy (AOL,etc)
* If you don't want the session to be destroyed when the user changes ip
* you have to set SESSION_UNSAFE to true. (absolutely not recommended!)
* (http://de.wikipedia.org/wiki/Session_Fixation#des_Dienstanbieters)
*/
define('SESSION_UNSAFE', false);

/**
 * true  = session-cookie is set for the base domain ([*.]example.com)
 * false = session-cookie is set only for whatever subdomain we are 
           under (foo.bar.baz.example.com)
 */
define('SESSION_BASEDOMAIN',false);

/**
 * cache-identifier. prepended to any cache-id to ensure
 * we dont overwrite data from other kata-installations
 */
define('CACHE_IDENTIFIER','kataDefault');

/**
 * use this language for the locale-component. 
 * can be: 2-letter isocode of the language like "de" for germany
 *         VHOST to use the the top-level part or the third-level part of the domain to try to find a suitable language
 *         BROWSER to use the primary language of the users browser.
 * 		   NULL dont do anything automatically, select language yourself via setCode()
 */
define('LANGUAGE','de');

/**
  * set to true if you want your locale-strings auto-h()ed. default ist false.
  */
//define('LANGUAGE_ESCAPE',false);

/**
 * timezone to use, or a strict error will raise its ugly head
 */
define('TZ','Europe/Berlin');

 /**
 * change locale key behaviour
 * true: __('keyname',array(1,2,3)) when key is 'some text %s bla %s bla %s' (DONT USE!)
 * false: __('keyname',array('url'=>'bla.htm','title'=>'wow!')) when key is 'please visit <a href="%url%">%title%</a>'
 */
define('LANGUAGE_PRINTF',false);

//insert warn message into empty keys
//define('LANGUAGE_WARNEMPTY,1);
//fall back to english if key nonexistant or empty
//define('LANGUAGE_FALLBACK',1);

/**
 * set all available memcached-server
 * format is: ip:port,ip,ip,ip
 * 
 */
//define('MEMCACHED_SERVERS','localhost,server2.com:999');

/**
 * tell cache-utility not to autoselect caching-method, but use the given one.
 * see cacheUtility-doku for possible values
 */
//define('CACHE_USEMETHOD','file');

/* // routes. can rewrite the current url to a new one
$routes = array(
        'foocontroller/fooaction' => 'bla/blubb',
        'bla/foo.php' => 'foo/index/',
        '' => 'notmain/index' // make notmain new default controller
);
*/
/**
 * The path to the redis socket file
 * @name REDIS_SOCKET
 */
//define('REDIS_SOCKET', '/var/run/redis/redis.sock');

//set a special subpath
// instead of using /controller/action
// if URL_PATH is set its /URL_PATH/controller/action

if(!defined('URL_PATH')){
    define('URL_PATH','');
}

// set to true to let the cookie not expire
define('USE_SESSION_COOKIE',false);

/**
 * Comma separated list of Nagios/Icinga ips
 *
 * @name NAGIOS_IPS
 */
/*
define('NAGIOS_IPS', []);
*/

/*
 * we setup the graylog here in our app directly, not needed to have it somewhere esle
 *
 */
/*
define('GRAYLOG_HOST', 'graylog.dummy.dummy');
define('GRAYLOG_PORT', 12201);
define('GRAYLOG_FACILITY', 'APP_dummy');

// ab welchem level wird geloggt
//If the $logLevel <= the $thresholdLevel it will send the message
define('GRAYLOG_THRESHOLD_LEVEL', 4);
*/
//----------YOUR STUFF----------------------------------------------------------

/* use a custom dispatcher
   if you want to use a custom dispatcher u can now
   extend the dispatcher and override/add methods
   The dispatcher name is the class name in folder custom
 */
define('USE_CUSTOM_DISPATCHER',false);
//define('CUSTOM_DISPATCHER_NAME','');
//define('CUSTOM_DISPATCHER_ARGS',[]);

/*** GF_SPECIFIC ***/
//spezial-hack f�r nicht abgesprochene gameinstaller-�nderung:
//define('KATATMP','#+#TMPPATH#+#');
//define('KATALOG','#+#LOGPATH#+#');
/*** /GF_SPECIFIC ***/

//include(LIB.'boot_firephp.php'); // fb.php must be available in include path
//include(LIB.'boot_coverage.php');
//include(LIB.'boot_profile.php');
//include(LIB.'boot_strict.php');
//include(LIB.'boot_dbug.php'); // dbug.php must be available in include path

