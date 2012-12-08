<?php
/**
 * The development database settings.
 */

return array(
	'default' => array(
		'type'        => 'pdo',
		'connection'  => array(
			'dsn'        => 'mysql:host=localhost;dbname=fuel_prod',
			'username'   => 'fuel_app',
			'password'   => 'super_secret_password',
		),		
	),
);