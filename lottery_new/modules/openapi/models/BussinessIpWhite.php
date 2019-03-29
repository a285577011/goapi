<?php

namespace app\modules\openapi\models;

use Yii;

/**
 * This is the model class for table "bussiness_ip_white".
 *
 * @property integer $bussiness_ip_white_id
 * @property integer $bussiness_id
 * @property string $ip
 * @property integer $status
 */
class BussinessIpWhite extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bussiness_ip_white';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bussiness_id', 'ip'], 'required'],
            [['bussiness_id', 'status'], 'integer'],
            [['ip'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bussiness_ip_white_id' => 'Bussiness Ip White ID',
            'bussiness_id' => 'Bussiness ID',
            'ip' => 'Ip',
            'status' => 'Status',
        ];
    }
}
