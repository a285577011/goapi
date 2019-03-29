<?php

namespace app\modules\store\models;

use Yii;

/**
 * This is the model class for table "store_token".
 *
 * @property integer $store_token_id
 * @property string $cust_no
 * @property string $token
 * @property string $expire_time
 * @property string $create_time
 * @property string $update_time
 */
class StoreToken extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cust_no', 'token', 'expire_time', 'create_time'], 'required'],
            [['expire_time', 'create_time', 'update_time'], 'safe'],
            [['cust_no'], 'string', 'max' => 50],
            [['token'], 'string', 'max' => 70],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_token_id' => 'Store Token ID',
            'cust_no' => 'Cust No',
            'token' => 'Token',
            'expire_time' => 'Expire Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
