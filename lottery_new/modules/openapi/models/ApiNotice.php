<?php

namespace app\modules\openapi\models;

use Yii;

/**
 * This is the model class for table "api_notice".
 *
 * @property integer $notice_id
 * @property integer $user_id
 * @property string $name
 * @property string $periods
 * @property string $param
 * @property integer $lose_num
 * @property string $url
 * @property string $third_order_code
 * @property string $lottery_order_code
 * @property integer $type
 * @property string $create_time
 * @property string $update_time
 * @property string $response_msg
 * @property integer $status
 */
class ApiNotice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_notice';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'lose_num', 'type', 'status'], 'integer'],
            [['create_time'], 'required'],
            [['create_time', 'update_time'], 'safe'],
            [['name', 'periods'], 'string', 'max' => 25],
            [['param', 'url', 'response_msg'], 'string', 'max' => 255],
            [['third_order_code', 'lottery_order_code'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'notice_id' => 'Notice ID',
            'user_id' => 'User ID',
            'name' => 'Name',
            'periods' => 'Periods',
            'param' => 'Param',
            'lose_num' => 'Lose Num',
            'url' => 'Url',
            'third_order_code' => 'Third Order Code',
            'lottery_order_code' => 'Lottery Order Code',
            'type' => 'Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'response_msg' => 'Response Msg',
            'status' => 'Status',
        ];
    }
}
