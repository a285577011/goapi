<?php

namespace app\modules\openapi\models;

use Yii;

/**
 * This is the model class for table "api_list".
 *
 * @property integer $api_list_id
 * @property string $api_name
 * @property string $api_url
 * @property integer $status
 */
class ApiList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_list';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['api_name', 'api_url'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'api_list_id' => 'Api List ID',
            'api_name' => 'Api Name',
            'api_url' => 'Api Url',
            'status' => 'Status',
        ];
    }
}
