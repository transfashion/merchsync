<?php namespace TransFashion\MerchSync;

class Configuration
{
	const string SPARATOR = ".";
	const string DB_MAIN = "DbMain";	
	const string DB_REPORT = "DbRpt";

	const array DB_PARAM = [
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
		\PDO::ATTR_PERSISTENT=>true
	];

	private static array $_config;
	private static array $_usedConfig;
	private static string $_rootDir;

	public static function Set(array $config) : void {
		self::$_config = $config;
	}

	public static function Get(?string $keypath = null) : mixed {
		try {
			if ($keypath!==null) {
				$value = self::getValueByPath(self::$_config, $keypath);
				return $value;
			} else {
				return self::$_config;
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}


	public static function GetUsedConfig(string $name) : string {
		if (!array_key_exists($name, self::$_usedConfig)) {
			throw new \Exception("Config '$name' tidak ditemukan");
		}
		return self::$_usedConfig[$name];
	}

	public static function UseConfig(array $usedconfig) : void {
		self::$_usedConfig = $usedconfig;
	}

	private static function getValueByPath(array $array, string $path, ?string $separator = self::SPARATOR) : mixed {
		$keys = explode($separator, $path);
		foreach ($keys as $key) {
			if (!isset($array[$key])) {
				return null; // Kunci tidak ditemukan
			}
			$array = $array[$key]; // Melangkah lebih dalam ke array
		}
		return $array;
	}


	public static function setRootDir($dir) : void {
		if (!defined('__ROOT_DIR__')) {
			define('__ROOT_DIR__', $dir);
		}

		self::$_rootDir = $dir;
	}

	public static function getRootDir() : string {
		return self::$_rootDir;
	}

}