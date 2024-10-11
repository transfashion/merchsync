<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;


class Database {

	public static object $DbMain;
	public static object $DbReport;
	

	public static function Connect() : void { 
		try {
			// connecting to mainDb
			Log::info("Connecting to main database...");
			$cfgname = Configuration::GetUsedConfig(Configuration::DB_MAIN);
			$dsn = Configuration::Get("$cfgname.DSN") ;
			$user = Configuration::Get("$cfgname.user") ;
			$pass = Configuration::Get("$cfgname.pass") ;
			$param = Configuration::DB_PARAM;
			self::$DbMain = new \PDO($dsn, $user, $pass, $param);


			// connecting to reportDb
			Log::info("Connecting to report database...");
			$cfgname = Configuration::GetUsedConfig(Configuration::DB_REPORT);
			$dsn = Configuration::Get("$cfgname.DSN") ;
			$user = Configuration::Get("$cfgname.user") ;
			$pass = Configuration::Get("$cfgname.pass") ;
			$param = Configuration::DB_PARAM;
			self::$DbReport = new \PDO($dsn, $user, $pass, $param);


		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}

	}
}