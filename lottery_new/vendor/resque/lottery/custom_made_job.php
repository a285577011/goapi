<?php

use app\modules\common\helpers\Made;
use app\modules\common\helpers\Commonfun;

/**
 * @auther GL zyl
 * @date 2017年7月25日 
 * @param
 * @return 
 */
class custom_made_job {

    public function perform() {
        Commonfun::updateQueue($this->args['queueId'], 2);
        try {
            $made = new Made();
            $result = $made->CustomMade($this->args['expert_no'], $this->args['lottery_code'], $this->args['bet_nums'], $this->args['programme_id'], $this->args['programme_price']);
            Commonfun::updateQueue($this->args['queueId'], 3);
            return $result;
        } catch (\yii\db\Exception $ex) {
            Commonfun::updateQueue($this->args['queueId'], 4);
            return json_encode($ex);
        }
    }

}
