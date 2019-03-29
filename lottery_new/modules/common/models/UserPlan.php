<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "user_plan".
 *
 * @property integer $user_plan_id
 * @property string $user_plan_code
 * @property integer $user_id
 * @property string $user_name
 * @property string $user_tel
 * @property integer $plan_id
 * @property integer $store_id
 * @property integer $win_type
 * @property integer $bet_scale
 * @property string $buy_money
 * @property string $able_funds
 * @property string $betting_funds
 * @property string $win_amount
 * @property string $total_profit
 * @property string $end_time
 * @property integer $status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class UserPlan extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_plan';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_plan_code', 'user_id', 'plan_id', 'store_id', 'buy_money'], 'required'],
            [['user_id', 'plan_id', 'store_id', 'win_type', 'bet_scale', 'status'], 'integer'],
            [['buy_money', 'able_funds', 'betting_funds', 'win_amount', 'total_profit'], 'number'],
            [['end_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['user_plan_code'], 'string', 'max' => 50],
            [['user_name', 'user_tel'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_plan_id' => 'User Plan ID',
            'user_plan_code' => 'User Plan Code',
            'user_id' => 'User ID',
            'user_name' => 'User Name',
            'user_tel' => 'User Tel',
            'plan_id' => 'Plan ID',
            'store_id' => 'Store ID',
            'win_type' => 'Win Type',
            'bet_scale' => 'Bet Scale',
            'buy_money' => 'Buy Money',
            'able_funds' => 'Able Funds',
            'betting_funds' => 'Betting Funds',
            'win_amount' => 'Win Amount',
            'total_profit' => 'Total Profit',
            'end_time' => 'End Time',
            'status' => 'Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
    /**
     * 更新数据
     * @param unknown $update
     * @param unknown $where
     */
    public static function upData($update,$where){
    	 
    	return \Yii::$app->db->createCommand()->update(self::tableName(),$update,$where)->execute();
    }
}
