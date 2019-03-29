<?php

require __DIR__ . '/init.php';

use app\modules\common\helpers\Commonfun;

date_default_timezone_set('Asia/Shanghai');

class LotteryQueue {

    /**
     * 说明: 加入队列 
     * @author  kevi
     * @date 2017年6月6日 下午4:29:50
     * @param $queueName string 队列名称
     * @param $job string  任务
     * @param $args array 任务参数
     * @return 队列任务 jobId
     */
    public function pushQueue($job, $queueName = 'default', $args = []) {
        $ret = Commonfun::addQueue($job, $queueName, $args);
        if ($ret == false) {
            return false;
        } else {
            $args["queueId"] = $ret;
        }
        if (YII_ENV_DEV) {
            Resque::setBackend('redis://goodluck@211.149.205.201:6379');
        } else {
            Resque::setBackend('redis://user:gula_lottery_redis@27.155.105.165:63790');
        }
        $jobId = Resque::enqueue($queueName, $job, $args, true);
        return $jobId;
    }

}
