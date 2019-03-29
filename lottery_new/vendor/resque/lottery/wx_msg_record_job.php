<?php

use app\modules\common\helpers\Commonfun;


/**
 * 说明：
 * @author  龚伟平
 * @date 2017年11月22日
 * @param
 * @return
 */
class wx_msg_record_job {

    public function perform() {

        Commonfun::updateQueue($this->args['queueId'], 2);

        $data = $this->args['data'];
        $type = $this->args['type'];
        $status = $this->args['status'];
        $user_open_id = $this->args['user_open_id'];
        $order_code = $this->args['order_code'];
        $res = \Yii::$app->db->createCommand()->insert("wx_msg_record", [
            'msg_data' => $data,
            'type' => $type,
            'status' => $status,
            'user_open_id' => $user_open_id,
            'order_code' => $order_code,
            'create_time' => date('Y-m-d H:i:s')
        ])->execute();

        if (!$res) {
            return false;
        }
        Commonfun::updateQueue($this->args['queueId'], 3);
    }
}
