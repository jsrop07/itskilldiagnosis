<?php

$_SERVER['SITE_URL'] = $_SERVER['HTTP_HOST'];

return array(
	"db" => array(
		"driver"         => "Pdo",
		"dsn"            => "mysql:dbname=itds_db;host=18.181.4.65",
		"username" => "root",
		"password" => "gngs1234",
		"dbname" => "itds_db",
		"host" => "18.181.4.65",
		"driver_options" => array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
			"buffer_results" => true,
		),
	),
        'service_manager' => array(
                'factories' => array(
                        'Zend\Db\Adapter\Adapter'
                        => 'Zend\Db\Adapter\AdapterServiceFactory',
                ),
                'invokables' => array(
                        'Zend\Authentication\AuthenticationService' => 'Zend\Authentication\AuthenticationService',
                ),
        ),
       

);
