<?php

namespace app\modules\agents\models;

use Yii;

/**
 * This is the model class for table "agents_ip".
 *
 * @property integer $agents_ip_id
 * @property integer $agents_id
 * @property string $ip_address
 * @property integer $status
 */
class AgentsIp extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'agents_ip';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['agents_id', 'ip_address'], 'required'],
            [['agents_id', 'status'], 'integer'],
            [['ip_address'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'agents_ip_id' => 'Agents Ip ID',
            'agents_id' => 'Agents ID',
            'ip_address' => 'Ip Address',
            'status' => 'Status',
        ];
    }
}
