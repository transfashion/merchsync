<?php

require_once implode('/', [__DIR__, 'vendor', 'autoload.php']);


use AgungDhewe\PhpLogger\Logger;
use AgungDhewe\PhpLogger\LoggerOutput;

use TransFashion\MerchSync\Sync;
use TransFashion\MerchSync\Configuration;

try {

	if (php_sapi_name() !== 'cli') {
		throw new Exception("Script harus dijalankan di CLI");
	} 

	echo "Starting Script...\n";

	$short_options = "c:";
	$long_options = ["config:"];
	$options = getopt($short_options, $long_options);

	$configfile = 'config-development.php';
	if(isset($options["c"]) || isset($options["config"])) {
		$configfile = isset($options["c"]) ? $options["c"] : $options["config"];
	}

	$configpath = implode('/', [__DIR__, $configfile]);
	echo "read config file '$configfile'\n";
	if (!is_file($configpath)) {
		throw new Exception("File '$configfile' tidak ditemukan");
	}

	require_once $configpath;
	Configuration::setRootDir(__DIR__);

	$logfilename = Configuration::Get("Logger.filename");
	$logfilepath = implode('/', [Configuration::getRootDir(), $logfilename]);
	$clearlog = Configuration::Get("Logger.ClearOnStart");
	$output = Configuration::Get("Logger.output");
	$debugmode = Configuration::Get("Logger.debug");

	echo "log to '$logfilename'\n";
	if ($clearlog) {
		echo "clearing log file\n";
		file_put_contents(Configuration::Get("Logger.filename"), "");
	}	

	if ($debugmode) {
		echo "set debug mode\n";
		Logger::SetDebugMode(true);
	}


	if ($output == "file") {
		Logger::SetOutput(LoggerOutput::FILE);
	} 

	echo "executing module...";
	Sync::main();
	sleep(2);
	echo "DONE.";
} catch (Exception $ex) {
	echo "\e[1;31;40mERROR\e[0m\n";
	echo $ex->getMessage();
} finally {
	echo "\n\n";
}

