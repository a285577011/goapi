<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "api_user_bank".
 *
 * @property integer $api_user_bank_id
 * @property integer $bussiness_id
 * @property integer $user_id
 * @property string $user_name
 * @property string $bank_open
 * @property string $branch
 * @property string $card_number
 * @property string $province
 * @property string $city
 * @property integer $is_default
 * @property integer $status
 */
class ApiUserBank extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_user_bank';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bussiness_id', 'user_id'], 'integer'],
            [['user_name', 'bank_open', 'card_number', 'province', 'city'], 'string', 'max' => 45],
            [['branch'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'api_user_bank_id' => 'Api User Bank ID',
            'bussiness_id' => 'Bussiness ID',
            'user_id' => 'User ID',
            'user_name' => 'User Name',
            'bank_open' => 'Bank Open',
            'branch' => 'Branch',
            'card_number' => 'Card Number',
            'province' => 'Province',
            'city' => 'City',
            'is_default' => 'Is Default',
            'status' => 'Status',
        ];
    }
}
