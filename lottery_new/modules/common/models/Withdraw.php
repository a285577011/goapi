<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "withdraw".
 *
 * @property integer $withdraw_id
 * @property string $cust_no
 * @property integer $cust_type
 * @property string $withdraw_code
 * @property string $outer_no
 * @property string $bank_info
 * @property string $withdraw_money
 * @property string $actual_money
 * @property string $fee_money
 * @property integer $status
 * @property string $remark
 * @property string $toaccount_time
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 * @property string $cardholder
 * @property string $bank_name
 */
class Withdraw extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'withdraw';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cust_no', 'withdraw_code'], 'required'],
            [['cust_type', 'status'], 'integer'],
            [['withdraw_money', 'actual_money', 'fee_money'], 'number'],
            [['toaccount_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['cust_no', 'cardholder'], 'string', 'max' => 50],
            [['withdraw_code', 'outer_no', 'bank_info', 'bank_name'], 'string', 'max' => 100],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'withdraw_id' => 'Withdraw ID',
            'cust_no' => 'Cust No',
            'cust_type' => 'Cust Type',
            'withdraw_code' => 'Withdraw Code',
            'outer_no' => 'Outer No',
            'bank_info' => 'Bank Info',
            'withdraw_money' => 'Withdraw Money',
            'actual_money' => 'Actual Money',
            'fee_money' => 'Fee Money',
            'status' => 'Status',
            'remark' => 'Remark',
            'toaccount_time' => 'Toaccount Time',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
            'cardholder' => 'Cardholder',
            'bank_name' => 'Bank Name',
        ];
    }
}
