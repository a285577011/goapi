<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "betting_detail".
 *
 * @property integer $betting_detail_id
 * @property integer $lottery_order_id
 * @property string $lottery_order_code
 * @property string $betting_detail_code
 * @property integer $lottery_id
 * @property string $lottery_name
 * @property string $cust_no
 * @property integer $user_id
 * @property string $agent_id
 * @property string $periods
 * @property string $bet_val
 * @property string $odds
 * @property string $play_name
 * @property string $play_code
 * @property integer $schedule_nums
 * @property integer $deal_nums
 * @property string $deal_schedule
 * @property integer $bet_double
 * @property integer $is_bet_add
 * @property string $win_amount
 * @property integer $status
 * @property integer $deal_status
 * @property integer $win_level
 * @property string $one_money
 * @property string $back_order
 * @property string $bet_money
 * @property string $opt_id
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class BettingDetail extends \yii\db\ActiveRecord
{
	use SyncCommon;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'betting_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_order_id', 'lottery_order_code', 'betting_detail_code', 'lottery_id', 'lottery_name', 'cust_no', 'agent_id', 'bet_val', 'one_money', 'bet_money'], 'required'],
            [['lottery_order_id', 'lottery_id', 'user_id', 'schedule_nums', 'deal_nums', 'bet_double', 'is_bet_add', 'status', 'deal_status', 'win_level'], 'integer'],
            [['win_amount', 'one_money', 'bet_money'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['lottery_order_code', 'betting_detail_code', 'play_name'], 'string', 'max' => 50],
            [['lottery_name', 'agent_id', 'periods', 'opt_id'], 'string', 'max' => 25],
            [['cust_no'], 'string', 'max' => 15],
            [['bet_val', 'odds'], 'string', 'max' => 1000],
            [['play_code'], 'string', 'max' => 20],
            [['deal_schedule'], 'string', 'max' => 200],
            [['back_order'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'betting_detail_id' => 'Betting Detail ID',
            'lottery_order_id' => 'Lottery Order ID',
            'lottery_order_code' => 'Lottery Order Code',
            'betting_detail_code' => 'Betting Detail Code',
            'lottery_id' => 'Lottery ID',
            'lottery_name' => 'Lottery Name',
            'cust_no' => 'Cust No',
            'user_id' => 'User ID',
            'agent_id' => 'Agent ID',
            'periods' => 'Periods',
            'bet_val' => 'Bet Val',
            'odds' => 'Odds',
            'play_name' => 'Play Name',
            'play_code' => 'Play Code',
            'schedule_nums' => 'Schedule Nums',
            'deal_nums' => 'Deal Nums',
            'deal_schedule' => 'Deal Schedule',
            'bet_double' => 'Bet Double',
            'is_bet_add' => 'Is Bet Add',
            'win_amount' => 'Win Amount',
            'status' => 'Status',
            'deal_status' => 'Deal Status',
            'win_level' => 'Win Level',
            'one_money' => 'One Money',
            'back_order' => 'Back Order',
            'bet_money' => 'Bet Money',
            'opt_id' => 'Opt ID',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
