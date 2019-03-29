<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "user_token".
 *
 * @property integer $user_token_id
 * @property string $cust_no
 * @property string $token
 * @property string $expire_time
 * @property string $create_time
 */
class UserToken extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cust_no', 'token', 'expire_time', 'create_time'], 'required'],
            [['expire_time', 'create_time'], 'safe'],
            [['token'], 'string', 'max' => 70],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_token_id' => 'User Token ID',
            'cust_no' => 'Cust_no',
            'token' => 'Token',
            'expire_time' => 'Expire Time',
            'create_time' => 'Create Time',
        ];
    }
    
}
