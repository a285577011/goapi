<?php

use app\modules\common\helpers\Commonfun;
use app\modules\openapi\services\PlayOrderService;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @auther GL zyl
 * @param
 * @return 
 */
class third_order_pay_job {

    public function perform() {
        Commonfun::updateQueue($this->args['queueId'], 2);
        try {
            $playOrderService = new PlayOrderService();
            $result = $playOrderService->orderPay($this->args['custNo'], $this->args['userId'], $this->args['orderCode']);
            Commonfun::updateQueue($this->args['queueId'], 3);
            return $result;
        } catch (\yii\db\Exception $ex) {
            Commonfun::updateQueue($this->args['queueId'], 4);
            return json_encode($ex);
        }
    }

}