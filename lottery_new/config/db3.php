<?php
if (YII_ENV == "dev")
{
	
	return [ 
		'class' => 'yii\db\Connection' , 
		'dsn' => 'mysql:host=211.149.205.201:9696;dbname=gl_lottery_php' , 
		'username' => 'kingshard' , 
		'password' => 'kingshard' , 
		'charset' => 'utf8mb4'
	];
}else
{
	
	return [ 
		'class' => 'yii\db\Connection' , 
		'dsn' => 'mysql:host=211.149.205.201:9696;dbname=gl_lottery_php' , 
		'username' => 'kingshard' , 
		'password' => 'kingshard' , 
		'charset' => 'utf8mb4'
	];
}
