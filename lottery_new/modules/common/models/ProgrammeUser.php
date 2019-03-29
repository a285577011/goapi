<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "programme_user".
 *
 * @property integer $programme_user_id
 * @property integer $programme_id
 * @property string $programme_user_code
 * @property string $programme_code
 * @property integer $record_type
 * @property integer $store_id
 * @property string $expert_no
 * @property integer $user_id
 * @property string $cust_no
 * @property integer $cust_type
 * @property string $user_name
 * @property string $lottery_code
 * @property string $lottery_name
 * @property string $periods
 * @property string $bet_money
 * @property integer $buy_number
 * @property string $win_amount
 * @property integer $buy_type
 * @property integer $deal_status
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class ProgrammeUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'programme_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['programme_id', 'record_type', 'store_id', 'user_id', 'cust_type', 'buy_number', 'buy_type', 'deal_status', 'status'], 'integer'],
            [['bet_money', 'win_amount'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['programme_user_code', 'programme_code', 'user_name', 'lottery_name'], 'string', 'max' => 50],
            [['expert_no', 'cust_no'], 'string', 'max' => 15],
            [['lottery_code'], 'string', 'max' => 10],
            [['periods'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'programme_user_id' => 'Programme User ID',
            'programme_id' => 'Programme ID',
            'programme_user_code' => 'Programme User Code',
            'programme_code' => 'Programme Code',
            'record_type' => 'Record Type',
            'store_id' => 'Store ID',
            'expert_no' => 'Expert No',
            'user_id' => 'User ID',
            'cust_no' => 'Cust No',
            'cust_type' => 'Cust Type',
            'user_name' => 'User Name',
            'lottery_code' => 'Lottery Code',
            'lottery_name' => 'Lottery Name',
            'periods' => 'Periods',
            'bet_money' => 'Bet Money',
            'buy_number' => 'Buy Number',
            'win_amount' => 'Win Amount',
            'buy_type' => 'Buy Type',
            'deal_status' => 'Deal Status',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
