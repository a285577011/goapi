<?php
if(YII_ENV_DEV){
    return [
            'class' => 'yii\redis\Connection',
            'hostname' => '211.149.205.201',
            'password' => 'goodluck',
            'port' => 63790,
            'database' => 0,
        ];
}else{
    return [
            'class' => 'yii\redis\Connection',
            'hostname' => '10.155.105.165',
            'password' => 'gula_lottery_redis',
            'port' => 63790,
            'database' => 0,
        ];
}


