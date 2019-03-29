<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "user_follow".
 *
 * @property integer $user_follow_id
 * @property string $cust_no
 * @property string $store_no
 * @property integer $store_id
 * @property string $ticket_amount
 * @property integer $ticket_count
 * @property integer $default_status
 * @property integer $follow_status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class UserFollow extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_follow';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cust_no', 'store_id'], 'required'],
            [['store_id', 'ticket_count', 'default_status', 'follow_status'], 'integer'],
            [['ticket_amount'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['cust_no', 'store_no'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_follow_id' => 'User Follow ID',
            'cust_no' => 'Cust No',
            'store_no' => 'Store No',
            'store_id' => 'Store ID',
            'ticket_amount' => 'Ticket Amount',
            'ticket_count' => 'Ticket Count',
            'default_status' => 'Default Status',
            'follow_status' => 'Follow Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
