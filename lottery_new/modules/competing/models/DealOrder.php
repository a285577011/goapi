<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "deal_order".
 *
 * @property integer $deal_order_id
 * @property integer $order_id
 * @property string $lottery_code
 * @property string $play_code
 * @property string $bet_val
 * @property string $odds
 * @property string $bet_money
 * @property integer $bet_double
 * @property string $win_amount
 * @property integer $status
 * @property integer $deal_status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class DealOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'deal_order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'lottery_code', 'play_code', 'bet_val', 'bet_money'], 'required'],
            [['order_id', 'bet_double', 'status', 'deal_status'], 'integer'],
            [['bet_money', 'win_amount'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['lottery_code'], 'string', 'max' => 50],
            [['play_code'], 'string', 'max' => 20],
            [['bet_val'], 'string', 'max' => 500],
            [['odds'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'deal_order_id' => 'Deal Order ID',
            'order_id' => 'Order ID',
            'lottery_code' => 'Lottery Code',
            'play_code' => 'Play Code',
            'bet_val' => 'Bet Val',
            'odds' => 'Odds',
            'bet_money' => 'Bet Money',
            'bet_double' => 'Bet Double',
            'win_amount' => 'Win Amount',
            'status' => 'Status',
            'deal_status' => 'Deal Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
