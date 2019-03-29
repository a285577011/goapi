<?php

use app\modules\common\services\AdditionalService;
use app\modules\common\helpers\Commonfun;

/**
 * 说明: 
 * @author  GL ctx
 * @date 2017年6月12日 上午10:20:03
 * @param
 * @return 
 */
class order_trace_job {

    public function perform() {
        Commonfun::updateQueue($this->args['queueId'], 2);
        try {
            $trace = new AdditionalService();
            $ret = $trace->doTrace($this->args['traceInfo'], $this->args['periods'], $this->args['endTime']);
            Commonfun::updateQueue($this->args['queueId'], 3);
            return $ret;
        } catch (\yii\db\Exception $e) {
            Commonfun::updateQueue($this->args['queueId'], 4);
            return json_encode($e);
        }
    }

}
