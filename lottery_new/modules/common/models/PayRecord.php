<?php

namespace app\modules\common\models;

use Yii;
use app\modules\common\helpers\Commonfun;

/**
 * This is the model class for table "pay_record".
 *
 * @property integer $pay_record_id
 * @property string $order_code
 * @property string $cust_no
 * @property integer $cust_type
 * @property integer $user_id
 * @property string $user_name
 * @property integer $store_id
 * @property string $agent_code
 * @property integer $agent_id
 * @property string $agent_name
 * @property string $pay_no
 * @property string $outer_no
 * @property string $refund_no
 * @property string $pay_name
 * @property string $way_name
 * @property string $way_type
 * @property integer $pay_way
 * @property string $pay_money
 * @property string $pay_pre_money
 * @property string $balance
 * @property string $pay_type_name
 * @property integer $pay_type
 * @property string $body
 * @property integer $status
 * @property string $pay_time
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property string $discount_money
 * @property string $discount_detail
 * @property string $total_money
 */
class PayRecord extends \yii\db\ActiveRecord
{
	use SyncCommon;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pay_record';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cust_type', 'user_id', 'store_id', 'agent_id', 'pay_way', 'pay_type', 'status'], 'integer'],
            [['pay_money', 'pay_pre_money', 'balance'], 'number'],
            [['pay_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['order_code', 'cust_no', 'user_name', 'agent_code', 'agent_name', 'outer_no', 'refund_no', 'pay_type_name'], 'string', 'max' => 50],
            [['pay_no'], 'string', 'max' => 32],
            [['pay_name', 'way_name', 'body'], 'string', 'max' => 200],
            [['way_type'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pay_record_id' => 'Pay Record ID',
            'order_code' => 'Order Code',
            'cust_no' => 'Cust No',
            'cust_type' => 'Cust Type',
            'user_id' => 'User ID',
            'user_name' => 'User Name',
            'store_id' => 'Store ID',
            'agent_code' => 'Agent Code',
            'agent_id' => 'Agent ID',
            'agent_name' => 'Agent Name',
            'pay_no' => 'Pay No',
            'outer_no' => 'Outer No',
            'refund_no' => 'Refund No',
            'pay_name' => 'Pay Name',
            'way_name' => 'Way Name',
            'way_type' => 'Way Type',
            'pay_way' => 'Pay Way',
            'pay_money' => 'Pay Money',
            'pay_pre_money' => 'Pay Pre Money',
            'balance' => 'Balance',
            'pay_type_name' => 'Pay Type Name',
            'pay_type' => 'Pay Type',
            'body' => 'Body',
            'status' => 'Status',
            'pay_time' => 'Pay Time',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        	'discount_money'=>'discount_money',
        	'discount_detail'=>'discount_detail',
        	'total_money'=>'total_money',
        ];
    }
}
