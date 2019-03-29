<?php

namespace app\modules\openapi\services;

use app\modules\common\models\LotteryRecord;
use app\modules\common\models\FootballFourteen;

class LotteryResultService {

    public function getLotteryResult($lotteryCode, $periods, $startDate, $endDate) {
        $where= ['and', ['lottery_code' => $lotteryCode]]; 
        if(!empty($periods)){
            $where[] = ['periods' => $periods];
        }  else {
            $where[] = ['>=', 'lottery_time', $startDate . ' 00:00:00'];
            $where[] = ['<=', 'lottery_time', $endDate . ' 23:59:59'];
        }
        
        $data = LotteryRecord::find()->select(['lottery_code', 'lottery_name', 'lottery_numbers', 'periods', 'total_sales', 'pool'])->where($where)->asArray()->all();
        
        return $data;
    }

    public function getOptionalResult($periods, $startDate, $endDate) {
        $where= ['and', ['status' => 3]]; 
        if(!empty($periods)){
            $where[] = ['periods' => $periods];
        }  else {
            $where[] = ['>=', 'endsale_time', $startDate . ' 00:00:00'];
            $where[] = ['<=', 'endsale_time', $endDate . ' 23:59:59'];
        }
        
        $data = FootballFourteen::find()->select(['periods', 'schedule_results', 'first_prize', 'second_prize', 'nine_prize'])->where($where)->asArray()->all();
        return $data;
    }
}
