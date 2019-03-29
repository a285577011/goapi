<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "auto_out_order".
 *
 * @property integer $out_order_id
 * @property string $out_order_code
 * @property string $order_code
 * @property string $ticket_code
 * @property string $free_type
 * @property string $lottery_code
 * @property string $play_code
 * @property string $periods
 * @property string $bet_val
 * @property integer $bet_add
 * @property integer $multiple
 * @property string $amount
 * @property integer $count
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class AutoOutOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auto_out_order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bet_add', 'multiple', 'count', 'status'], 'integer'],
            [['bet_val'], 'string'],
            [['amount'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['out_order_code', 'order_code'], 'string', 'max' => 100],
            [['ticket_code', 'periods'], 'string', 'max' => 50],
            [['lottery_code', 'play_code', 'free_type'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'out_order_id' => 'Out Order ID',
            'out_order_code' => 'Out Order Code',
            'order_code' => 'Order Code',
            'ticket_code' => 'Ticket Code',
            'free_type' => 'Free Type',
            'lottery_code' => 'Lottery Code',
            'play_code' => 'Play Code',
            'periods' => 'Periods',
            'bet_val' => 'Bet Val',
            'bet_add' => 'Bet Add',
            'multiple' => 'Multiple',
            'amount' => 'Amount',
            'count' => 'Count',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
