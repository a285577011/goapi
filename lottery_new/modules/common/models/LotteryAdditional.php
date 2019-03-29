<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "lottery_additional".
 *
 * @property integer $lottery_additional_id
 * @property string $lottery_name
 * @property integer $lottery_id
 * @property string $play_name
 * @property string $play_code
 * @property string $lottery_additional_code
 * @property integer $chased_num
 * @property integer $periods_total
 * @property string $periods
 * @property integer $user_id
 * @property string $cust_no
 * @property integer $cust_type
 * @property integer $store_id
 * @property string $store_no
 * @property string $agent_id
 * @property integer $user_plan_id
 * @property string $programme_code
 * @property string $bet_val
 * @property integer $bet_double
 * @property integer $is_bet_add
 * @property string $bet_money
 * @property string $total_money
 * @property integer $count
 * @property string $opt_id
 * @property integer $is_random
 * @property integer $is_limit
 * @property string $win_limit
 * @property integer $pay_status
 * @property integer $status
 * @property string $remark
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class LotteryAdditional extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lottery_additional';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_name', 'lottery_id', 'play_name', 'play_code', 'lottery_additional_code', 'cust_no', 'agent_id', 'bet_val', 'bet_money', 'total_money'], 'required'],
            [['lottery_id', 'chased_num', 'periods_total', 'user_id', 'cust_type', 'store_id', 'user_plan_id', 'bet_double', 'is_bet_add', 'count', 'is_random', 'is_limit', 'pay_status', 'status'], 'integer'],
            [['bet_money', 'total_money', 'win_limit'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['lottery_name'], 'string', 'max' => 50],
            [['play_name'], 'string', 'max' => 1500],
            [['play_code'], 'string', 'max' => 700],
            [['lottery_additional_code'], 'string', 'max' => 200],
            [['periods', 'opt_id'], 'string', 'max' => 25],
            [['cust_no', 'store_no'], 'string', 'max' => 15],
            [['programme_code', 'remark'], 'string', 'max' => 100],
            [['bet_val'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'lottery_additional_id' => 'Lottery Additional ID',
            'lottery_name' => 'Lottery Name',
            'lottery_id' => 'Lottery ID',
            'play_name' => 'Play Name',
            'play_code' => 'Play Code',
            'lottery_additional_code' => 'Lottery Additional Code',
            'chased_num' => 'Chased Num',
            'periods_total' => 'Periods Total',
            'periods' => 'Periods',
            'user_id' => 'User ID',
            'cust_no' => 'Cust No',
            'cust_type' => 'Cust Type',
            'store_id' => 'Store ID',
            'store_no' => 'Store No',
            'agent_id' => 'Agent ID',
            'user_plan_id' => 'User Plan ID',
            'programme_code' => 'Programme Code',
            'bet_val' => 'Bet Val',
            'bet_double' => 'Bet Double',
            'is_bet_add' => 'Is Bet Add',
            'bet_money' => 'Bet Money',
            'total_money' => 'Total Money',
            'count' => 'Count',
            'opt_id' => 'Opt ID',
            'is_random' => 'Is Random',
            'is_limit' => 'Is Limit',
            'win_limit' => 'Win Limit',
            'pay_status' => 'Pay Status',
            'status' => 'Status',
            'remark' => 'Remark',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
    public static function upData($update,$where){
    
    	return \Yii::$app->db->createCommand()->update(self::tableName(),$update,$where)->execute();
    }
}
