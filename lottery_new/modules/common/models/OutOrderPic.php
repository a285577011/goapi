<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "out_order_pic".
 *
 * @property integer $out_order_pic_id
 * @property integer $user_id
 * @property integer $order_id
 * @property string $order_img1
 * @property string $order_img2
 * @property string $order_img3
 * @property string $order_img4
 * @property string $create_time
 * @property string $modfiy_time
 * @property string $update_time
 */
class OutOrderPic extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'out_order_pic';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'order_id'], 'required'],
            [['user_id', 'order_id'], 'integer'],
            [['create_time', 'modfiy_time', 'update_time'], 'safe'],
            [['order_img1', 'order_img2', 'order_img3', 'order_img4'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'out_order_pic_id' => 'Out Order Pic ID',
            'user_id' => 'User ID',
            'order_id' => 'Order ID',
            'order_img1' => 'Order Img1',
            'order_img2' => 'Order Img2',
            'order_img3' => 'Order Img3',
            'order_img4' => 'Order Img4',
            'create_time' => 'Create Time',
            'modfiy_time' => 'Modfiy Time',
            'update_time' => 'Update Time',
        ];
    }
}
