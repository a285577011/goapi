<?php

use app\modules\common\services\ProgrammeService;
use app\modules\common\helpers\Commonfun;

/**
 * 说明: 
 * @author  GL ctx
 * @date 2017年6月12日 上午10:20:03
 * @param
 * @return 
 */
class programme_job {

    public function perform() {
        Commonfun::updateQueue($this->args['queueId'], 2);
        try {
            $proSer = new ProgrammeService();
            $ret = $proSer->playProgramme($this->args['programmeId']);
            Commonfun::updateQueue($this->args['queueId'], 3);
            return $ret;
        } catch (\yii\db\Exception $e) {
            Commonfun::updateQueue($this->args['queueId'], 4);
            return json_encode($e);
        }
    }

}
