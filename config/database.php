<?php

/**
 * all database configs used by kata. you can use a different database-connection per model.
 * 
 * driver: name of the database to use (or in other words: name of the dbo to use to access the database), selected by connection-property of the model
 * subdriver: name of the subdriver, to tell the dbo which driver you want (e.g. if you use PDO or ADODB. can be left empty for mysql and mssql)
 * host: where the database runs (normally localhost) 
 * login: user used to access the database
 * password: password used to access database
 * prefix: you can use a fixed prefix for all tables (but you have to obey model->getPrefix() if you write your own queries)
 * encoding: LEAVE EMPTY if all works well, ONLY set this to the characterset of the client if you encouter encoding problems! (MySQL only!!!)
 * 
 * @package kata
 */
define('DBDRIVER', getenv('DB_DRIVER') ? getenv('DB_DRIVER') : 'mysqli');
define("DBHOST", getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost');
define("DBLOGIN", getenv('DB_LOGIN') ? getenv('DB_LOGIN') : 'root');
define("DBPASSWORD", getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : '');
define("DBDATABASE", getenv('DB_DATABASE') ? getenv('DB_DATABASE') : 'minecraftItemrice');
define("DBPREFIX", getenv('DB_PREFIX') ? getenv('DB_PREFIX') : '');
define("DBENCODING", getenv('DB_ENCODING') ? getenv('DB_ENCODING') : '');
class DATABASE_CONFIG
{
	public static $default = array(
		'driver' => DBDRIVER,
		'host' => DBHOST,
		'login' => DBLOGIN,
		'password' => DBPASSWORD,
		'database' => DBDATABASE,
		'prefix' => DBPREFIX,
		'encoding' => DBENCODING
	);
}
