<?php
    
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
// echo "Connection to server sucessfully";
$redis->incrBy('chenqiwei',1);

// $redis->auth('Gsz17041@)!^');
