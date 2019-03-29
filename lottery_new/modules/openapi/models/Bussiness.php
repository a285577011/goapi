<?php

namespace app\modules\openapi\models;

use Yii;

/**
 * This is the model class for table "bussiness".
 *
 * @property integer $bussiness_id
 * @property string $name
 * @property string $bussiness_appid
 * @property string $secret_key
 * @property integer $user_id
 * @property string $cust_no
 * @property integer $status
 * @property string $des_key
 * @property string $create_time
 */
class Bussiness extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bussiness';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bussiness_appid', 'secret_key'], 'required'],
            [['user_id', 'status'], 'integer'],
            [['create_time'], 'safe'],
            [['name'], 'string', 'max' => 45],
            [['bussiness_appid'], 'string', 'max' => 16],
            [['secret_key'], 'string', 'max' => 32],
            [['cust_no'], 'string', 'max' => 15],
            [['des_key'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bussiness_id' => 'Bussiness ID',
            'name' => 'Name',
            'bussiness_appid' => 'Bussiness Appid',
            'secret_key' => 'Secret Key',
            'user_id' => 'User ID',
            'cust_no' => 'Cust No',
            'status' => 'Status',
            'des_key' => 'Des Key',
            'create_time' => 'Create Time',
        ];
    }
}
