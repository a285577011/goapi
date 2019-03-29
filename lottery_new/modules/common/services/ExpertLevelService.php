<?php

namespace app\modules\common\services;

use app\modules\common\models\ExpertLevel;
use Yii;
use app\modules\common\models\Programme;

class ExpertLevelService {
    
    public function createUpdate($userId, $custNo) {
        $model = ExpertLevel::findOne(['user_id' => $userId]);
        if(empty($model)) {
            $model = new ExpertLevel;
            $model->issue_nums = 1;
        }  else {
            $model->issue_nums = $model->issue_nums + 1;
        }
        $model->user_id = $userId;
        $model->cust_no = $custNo;
        if(!$model->validators){
            return ['code' => 109, 'msg' => '验证失败'];
        }
        if(!$model->save()) {
            return ['code' => 109, 'msg' => '存储失败']; 
        }
        return ['code' => 600, 'msg' => '添加成功'];
    }
    
    /**
     * 每天凌晨2:00 更新
     * @auther GL zyl
     * @return boolean
     */
    public function updateTable(){
        $expertData = ExpertLevel::find()->select(['expert_level_id', 'user_id', 'cust_no'])->asArray()->all();
        $updateStr = '';
        $format = date('Y-m-d H:i:s');
        foreach ($expertData as $val) {
            $winAmount = 0;
            $winNums = 0;
            $value = 0;
            $data = Programme::find()->select(['programme_id','user_id', 'expert_no', 'status', 'win_amount', 'bet_money'])->where(['user_id' => $val['user_id'], 'level_deal' => 0])->andWhere(['in', 'status', [5, 6]])->asArray()->all();
            $succIssueNums = count($data);
            foreach ($data as $v) {
                if($v['status'] == 6){
                    $winNums += 1;
                    $winAmount += $v['win_amount'];
                    $profit = bcsub(floatval($v['win_amount']), floatval($v['bet_money']), 2);
                    if($profit >= 200){
                        if($profit < 2000 && ($profit * 4) >= floatval($v['bet_money'])){
                            $value += 100;
                        }  elseif($profit >= 2000 && $profit < 20000) {
                            $value += 100;
                        }elseif ($profit >= 20000 && $profit < 100000) {
                            $value += 200;
                        }elseif ($profit >= 100000 && $profit < 500000) {
                            $value += 500;
                        }  else {
                            $value += 1000;
                        }
                    }
                }
                $updateStr .= "update programme set level_deal = 1, modify_time = '" . $format . "' where programme_id = {$v['programme_id']};"; 
            }
            $updateStr .= "update expert_level set value = value + {$value}, win_amount = win_amount + {$winAmount}, win_nums = win_nums + {$winNums}, succ_issue_nums = succ_issue_nums + {$succIssueNums}, modify_time = '" . $format . "' where  expert_level_id = {$val['expert_level_id']}; ";
        }
        $db = Yii::$app->db;
        $updataIds = $db->createCommand($updateStr)->execute();
        if($updataIds === false) {
            return ['code' => 109, 'data' => $format];
        }
        return ['code' => 600, 'data' => $format];
    }
    
}

