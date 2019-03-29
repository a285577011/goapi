<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "plan".
 *
 * @property integer $plan_id
 * @property string $plan_code
 * @property integer $store_id
 * @property string $store_name
 * @property string $store_tel
 * @property string $title
 * @property integer $settlement_type
 * @property integer $settlement_periods
 * @property string $plan_buy_min
 * @property string $incr_money
 * @property string $plan_remark
 * @property integer $buy_nums
 * @property string $buy_amount
 * @property string $plan_time
 * @property integer $status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class Plan extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'plan';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['plan_code', 'store_id', 'title', 'plan_buy_min'], 'required'],
            [['store_id', 'settlement_type', 'settlement_periods', 'buy_nums', 'status'], 'integer'],
            [['plan_buy_min', 'incr_money', 'buy_amount'], 'number'],
            [['plan_remark'], 'string'],
            [['plan_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['plan_code', 'store_name', 'title'], 'string', 'max' => 45],
            [['store_tel'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'plan_id' => 'Plan ID',
            'plan_code' => 'Plan Code',
            'store_id' => 'Store ID',
            'store_name' => 'Store Name',
            'store_tel' => 'Store Tel',
            'title' => 'Title',
            'settlement_type' => 'Settlement Type',
            'settlement_periods' => 'Settlement Periods',
            'plan_buy_min' => 'Plan Buy Min',
            'incr_money' => 'Incr Money',
            'plan_remark' => 'Plan Remark',
            'buy_nums' => 'Buy Nums',
            'buy_amount' => 'Buy Amount',
            'plan_time' => 'Plan Time',
            'status' => 'Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
