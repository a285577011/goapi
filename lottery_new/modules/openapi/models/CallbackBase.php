<?php

namespace app\modules\openapi\models;

use Yii;

/**
 * This is the model class for table "callback_base".
 *
 * @property integer $id
 * @property string $url
 * @property integer $times
 * @property string $name
 * @property integer $agent_id
 * @property string $remark
 * @property integer $c_time
 */
class CallbackBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'callback_base';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url', 'name', 'agent_id', 'remark','code'], 'required'],
            [['times', 'agent_id', 'c_time'], 'integer'],
            [['url', 'remark','code'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'Url',
            'times' => 'Times',
            'name' => 'Name',
            'agent_id' => 'Agent ID',
            'remark' => 'Remark',
            'c_time' => 'C Time',
        	'code' => 'code',
        ];
    }
}
