<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "user_statistics".
 *
 * @property string $id
 * @property string $cust_no
 * @property string $order_money
 * @property string $pro_order_money
 * @property integer $order_num
 * @property integer $pro_order_num
 * @property integer $u_time
 * @property integer $c_time
 */
class UserStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_statistics';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cust_no'], 'required'],
            [['order_money', 'pro_order_money'], 'number'],
            [['order_num', 'pro_order_num', 'u_time', 'c_time'], 'integer'],
            [['cust_no'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cust_no' => 'Cust No',
            'order_money' => 'Order Money',
            'pro_order_money' => 'Pro Order Money',
            'order_num' => 'Order Num',
            'pro_order_num' => 'Pro Order Num',
            'u_time' => 'U Time',
            'c_time' => 'C Time',
        ];
    }
	/**
	 * 获取统计数据
	 * @param unknown $cusNo
	 */
	public static function getStatis($cusNo)
	{
		$data = UserStatistics::find()->where([ 
			'cust_no' => $cusNo 
		])->asArray()->one();
		if (! $data)
		{
			$data['order_money'] = $data['pro_order_money'] = $data['order_num'] = $data['pro_order_num'] = $data['total_money'] = $data['total_order'] = 0;
		}
		$data['total_money'] = $data['order_money'] + $data['pro_order_money'];
		$data['total_order'] = $data['order_num'] + $data['pro_order_num'];
		return $data;
	}
}
