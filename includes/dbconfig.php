<?php
	define('HOST','localhost');
	define('DB_USERNAME','root');
	define('DB_PASSWORD','.?');
	define('DB_NAME','stratek');
	define('FNAME','includes');

	# Defining php path and runtime for automatic backups

	## Cron file path


	## Linux
	define('PHP_PATH_LINUX', '/usr/bin/php');
	define('MYSQL_PATH_LINUX', '/usr/bin/mysql');
	define('MYSQLDUMP_PATH_LINUX', '/usr/bin/mysqldump');
	define('CRON_FILE_PATH_LINUX', '/var/www/stratek/includes/cron.php');
	define('ROOT_PATH_LINUX', '/var/www/gcr/');

	## Windows
	define('PHP_PATH_WINDOWS', 'C:\xampp\php\php.exe');
	define('MYSQL_PATH_WINDOWS', 'C:\xampp\mysql\bin\mysql.exe');
	define('MYSQLDUMP_PATH_WINDOWS', 'C:\xampp\mysql\bin\mysqldump.exe');
	define('CRON_FILE_PATH_WINDOWS', 'C:\xampp\htdocs\includes\cron.php');
	define('ROOT_PATH_WINDOWS', 'C:\xampp\htdocs\\');