<?php

namespace app\modules\openapi\models;

use Yii;

/**
 * This is the model class for table "api_ip".
 *
 * @property integer $api_ip_id
 * @property integer $bussiness_ip_id
 * @property integer $api_list_id
 * @property integer $limit_time
 * @property integer $limit_num
 * @property string $create_time
 */
class ApiIp extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_ip';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bussiness_ip_id', 'api_list_id'], 'required'],
            [['bussiness_ip_id', 'api_list_id', 'limit_time', 'limit_num'], 'integer'],
            [['create_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'api_ip_id' => 'Api Ip ID',
            'bussiness_ip_id' => 'Bussiness Ip ID',
            'api_list_id' => 'Api List ID',
            'limit_time' => 'Limit Time',
            'limit_num' => 'Limit Num',
            'create_time' => 'Create Time',
        ];
    }
}
