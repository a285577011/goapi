<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "api_order".
 *
 * @property integer $api_order_id
 * @property string $api_order_code
 * @property string $third_order_code
 * @property string $message_id
 * @property integer $user_id
 * @property string $lottery_code
 * @property string $periods
 * @property string $play_code
 * @property string $bet_val
 * @property integer $bet_money
 * @property integer $multiple
 * @property integer $is_add
 * @property string $end_time
 * @property integer $status
 * @property integer $major_type
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class ApiOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['api_order_code', 'third_order_code', 'user_id', 'lottery_code', 'play_code', 'bet_val'], 'required'],
            [['user_id', 'bet_money', 'multiple', 'is_add', 'status', 'major_type'], 'integer'],
            [['end_time', 'create_time', 'modify_time', 'update_time'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'api_order_id' => 'Api Order ID',
            'api_order_code' => 'Api Order Code',
            'third_order_code' => 'Third Order Code',
            'message_id' => 'Message Id',
            'user_id' => 'User ID',
            'lottery_code' => 'Lottery Code',
            'periods' => 'Periods',
            'play_code' => 'Play Code',
            'bet_val' => 'Bet Val',
            'bet_money' => 'Bet Money',
            'multiple' => 'Multiple',
            'is_add' => 'Is Add',
            'end_time' => 'End Time',
            'status' => 'Status',
            'major_type' => 'Major Type',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
