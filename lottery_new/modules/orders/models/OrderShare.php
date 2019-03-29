<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "order_share".
 *
 * @property integer $order_share_id
 * @property integer $organiz_id
 * @property string $organiz_no
 * @property integer $order_id
 * @property integer $with_nums
 * @property string $recom_remark
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class OrderShare extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_share';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['organiz_id', 'organiz_no', 'order_id', 'recom_remark'], 'required'],
            [['organiz_id', 'order_id', 'with_nums'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['organiz_no'], 'string', 'max' => 25],
            [['recom_remark'], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_share_id' => 'Order Share ID',
            'organiz_id' => 'Organiz ID',
            'organiz_no' => 'Organiz No',
            'order_id' => 'Order ID',
            'with_nums' => 'With Nums',
            'recom_remark' => 'Recom Remark',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
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
