<?php

use TransFashion\MerchSync\Configuration;


Configuration::Set([
	'TransBrowserUrl' => 'http://ws.transfashion.id/crossroads/frontend',
	
	'DbMain' => [
		'DSN' => "mysql:host=127.0.0.1;dbname=tfidblocal",
		'user' => "root",
		'pass' => "rahasia123!"
	],

	'DbReport' => [
		'DSN' => "mysql:host=127.0.0.1;dbname=tfirptdblocal",
		'user' => "root",
		'pass' => "rahasia123!"
	],


	'Logger' => [
		'output' => 'file',
		'filename' => 'log.txt',
		'ClearOnStart' => true
	]
]);

Configuration::UseConfig([
	Configuration::DB_MAIN => 'DbMain',
	Configuration::DB_REPORT => 'DbReport'
]);

