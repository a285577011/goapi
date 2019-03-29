<?php

$master_slave_dev =  [
    'class' => 'yii\db\Connection',
    // 主库的配置
    'dsn' => 'mysql:host=211.149.205.201;dbname=gl_lottery_php',
    'username' => 'coder',
    'password' => 'gula_lottery_coder',
    'charset' => 'utf8mb4',

//     // 从库的通用配置
//    'slaveConfig' => [
//        'username' => 'root',
//        'password' => 'chenqiwei',
//        'charset' => 'utf8mb4',
//        'attributes' => [
//            // use a smaller connection timeout
//            PDO::ATTR_TIMEOUT => 10,
//        ],
//    ],
    // 从库配置列表
//    'slaves' => [
//        ['dsn' => 'mysql:host=27.155.105.178;dbname=gl_lottery_php'],
//    ],
];

$master_slave_prod =  [
    'class' => 'yii\db\Connection',
    // 主库的配置
    'dsn' => 'mysql:host=10.155.105.164;dbname=gl_lottery_php',
    'username' => 'coder',
    'password' => 'gula_lottery_coder',
    'charset' => 'utf8mb4',

//    // 从库的通用配置
//    'slaveConfig' => [
//        'username' => 'root',
//        'password' => 'chenqiwei',
//        'charset' => 'utf8',
//        'attributes' => [
//            // use a smaller connection timeout
//            PDO::ATTR_TIMEOUT => 10,
//        ],
//    ],
//    // 从库配置列表
//    'slaves' => [
//        ['dsn' => 'mysql:host=27.155.105.178;dbname=gl_lottery_php'],
//    ],
];
if(YII_ENV_DEV){
    return $master_slave_dev;
}else{
    return $master_slave_prod;
}

