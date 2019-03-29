<?php

namespace app\modules\common\models;

use Yii;
use yii\db\Query;
/**
 * This is the model class for table "ice_record".
 *
 * @property integer $ice_record_id
 * @property string $cust_no
 * @property integer $cust_type
 * @property string $order_code
 * @property string $money
 * @property string $ice_balance
 * @property string $body
 * @property integer $type
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class IceRecord extends \yii\db\ActiveRecord
{
	use SyncCommon;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ice_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cust_no', 'cust_type', 'order_code', 'money', 'ice_balance', 'type'], 'required'],
            [['cust_type', 'type'], 'integer'],
            [['money', 'ice_balance'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['cust_no'], 'string', 'max' => 20],
            [['order_code'], 'string', 'max' => 50],
            [['body'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ice_record_id' => 'Ice Record ID',
            'cust_no' => 'Cust No',
            'cust_type' => 'Cust Type',
            'order_code' => 'Order Code',
            'money' => 'Money',
            'ice_balance' => 'Ice Balance',
            'body' => 'Body',
            'type' => 'Type',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
