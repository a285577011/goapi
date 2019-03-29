<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "activity_receive_recode".
 *
 * @property integer $receive_recode_id
 * @property string $open_id
 * @property string $cust_no
 * @property string $qb_order_code
 * @property string $active_code
 * @property string $content_code
 * @property string $content_name
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class ActivityReceiveRecode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_receive_recode';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['cust_no'], 'string', 'max' => 50],
            [['qb_order_code'], 'string', 'max' => 150],
            [['active_code', 'content_code'], 'string', 'max' => 25],
            [['content_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'receive_recode_id' => 'Receive Recode ID',
            'open_id' => 'Open Id',
            'cust_no' => 'Cust No',
            'qb_order_code' => 'Qb Order Code',
            'active_code' => 'Active Code',
            'content_code' => 'Content Code',
            'content_name' => 'Content Name',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
