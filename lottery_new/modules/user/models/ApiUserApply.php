<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "api_user_apply".
 *
 * @property integer $api_user_apply_id
 * @property string $apply_code
 * @property integer $user_id
 * @property string $cust_no
 * @property string $type
 * @property string $money
 * @property string $voucher_pic
 * @property string $remark
 * @property string $refuse_reson
 * @property integer $status
 * @property integer $api_user_bank_id
 * @property integer $opt_id
 * @property string $create_time
 * @property string $modify_time
 */
class ApiUserApply extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_user_apply';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'cust_no', 'type', 'money'], 'required'],
            [['user_id', 'status', 'opt_id'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['cust_no'], 'string', 'max' => 45],
            [['voucher_pic'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'api_user_apply_id' => 'Api User Apply ID',
            'apply_code' => 'Apply Code',
            'user_id' => 'User ID',
            'cust_no' => 'Cust No',
            'type' => 'Type',
            'money' => 'Money',
            'voucher_pic' => 'Voucher Pic',
            'status' => 'Status',
            'api_user_bank_id' => '绑定的银行卡id',
            'remark' => 'Remark',
            'refuse_reson' => 'Refuse Reson',
            'opt_id' => 'Opt ID',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
        ];
    }
}
