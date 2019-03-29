<?php

namespace app\modules\tools\kafka;


use app\modules\common\helpers\Commonfun;
use app\modules\user\models\UserFollow;
use app\modules\common\models\Store;

class NewuserFollow implements Kafka {


    public function run($params) {
        return true;
        Commonfun::updateQueue($params['queueId'], 2);
        $custNo=$params['custNo'];
        $userFollow = UserFollow::find()->select(['store_id'])->where(['cust_no' => $custNo]);
        $companyStores = Store::find()->select(['store.store_code', 'store.cust_no'])
        ->where(['store.cert_status' => 3, 'company_id' => 1, 'store.status' => 1])
        ->andWhere(['not in', 'store_code', $userFollow])
        ->asArray()
        ->all();
        $format = date('Y-m-d H:i:s');
        $data = [];
        foreach ($companyStores as $val) {
        	$data[] = [$custNo, $val['cust_no'], $val['store_code'], $format];
        }
        $follows = \Yii::$app->db->createCommand()->batchInsert('user_follow', ['cust_no', 'store_no', 'store_id', 'create_time'], $data)->execute();
        if ($follows === false) {
        	Commonfun::updateQueue($params['queueId'], 4);
        }
        Commonfun::updateQueue($params['queueId'], 3);
    }

}
