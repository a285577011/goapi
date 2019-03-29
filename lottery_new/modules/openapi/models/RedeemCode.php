<?php

namespace app\modules\openapi\models;

use Yii;

/**
 * This is the model class for table "redeem_code".
 *
 * @property integer $redeem_code_id
 * @property string $redeem_code
 * @property string $value_amount
 * @property string $out_trade_no
 * @property integer $platform_source
 * @property integer $status
 * @property integer $store__id
 * @property string $store_cust_no
 * @property integer $settle_status
 * @property string $redeem_time
 * @property string $settle_date
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class RedeemCode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'redeem_code';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['redeem_code', 'value_amount', 'out_trade_no'], 'required'],
            [['value_amount'], 'number'],
            [['platform_source', 'status', 'store__id', 'settle_status'], 'integer'],
            [['redeem_time', 'settle_date', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['redeem_code'], 'string', 'max' => 32],
            [['out_trade_no', 'store_cust_no'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'redeem_code_id' => 'Redeem Code ID',
            'redeem_code' => 'Redeem Code',
            'value_amount' => 'Value Amount',
            'out_trade_no' => 'Out Trade No',
            'platform_source' => 'Platform Source',
            'status' => 'Status',
            'store__id' => 'Store  ID',
            'store_cust_no' => 'Store Cust No',
            'settle_status' => 'Settle Status',
            'redeem_time' => 'Redeem Time',
            'settle_date' => 'Settle Date',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
