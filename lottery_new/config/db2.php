<?php

if (YII_ENV == "dev") {

    return [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=211.149.162.241:9697;dbname=lottery_log',
        'username' => 'gula',
        'password' => 'guLA_27EcKelE9',
        'charset' => 'utf8mb4',
    ];
} else {

    return [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=211.149.162.241:9697;dbname=lottery_log',
        'username' => 'gula',
        'password' => 'guLA_27EcKelE9',
        'charset' => 'utf8mb4',
    ];
}
