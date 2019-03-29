<?php

namespace app\modules\store\models;

use Yii;
require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';
/**
 * This is the model class for table "pay_record".
 *
 * @property integer $pay_record_id
 * @property string $order_code
 * @property string $cust_no
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
 * @property string $pay_type_name
 * @property integer $pay_type
 * @property string $body
 * @property integer $status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class PayRecord extends \yii\db\ActiveRecord
{
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
            [['user_id', 'store_id', 'agent_id', 'pay_way', 'pay_type', 'status'], 'integer'],
            [['pay_money', 'pay_pre_money'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
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
            'pay_type_name' => 'Pay Type Name',
            'pay_type' => 'Pay Type',
            'body' => 'Body',
            'status' => 'Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
	/**
	 * 保存数据
	 */
	public function saveData(){
		$res=$this->save();
		if ($res)
		{
			$lotteryqueue = new \LotteryQueue();
			$data = array_filter($this->attributes, function ($v) {
				return $v !== null;
			});
			if (isset($data['pay_record_id']))
			{ // 有主键更新
				$update=$data;
				$where['pay_record_id']=$data['pay_record_id'];
				unset($update['pay_record_id']);
				$lotteryqueue->pushQueue('backupOrder_job', 'sync#'.self::tableName(), ['tablename' => self::tableName(),'type' => 'update','data'=>['update'=>$update,'where'=>$where],'pk'=>['pay_record_id'=>$data['pay_record_id']]]);//主键更新
			}else
			{
				$data['pay_record_id']=\Yii::$app->db->getLastInsertID();
				$lotteryqueue->pushQueue('backupOrder_job', 'sync#'.self::tableName(), ['tablename' => self::tableName(),'type' => 'insert','data' => $data,'pkField'=>'pay_record_id']);
			}
		}
		return $res;
	}
}
