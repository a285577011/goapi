<?php

namespace app\modules\agents\models;

use Yii;

/**
 * This is the model class for table "open_id".
 *
 * @property integer $id
 * @property string $open_id
 * @property string $type
 * @property string $tel
 * @property integer $agent_id
 * @property string $tmp_name
 * @property string $tmp_avatar
 * @property string $create_time
 */
class OpenId extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'open_id';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['open_id'], 'required'],
            [['agent_id'], 'integer'],
            [['create_time'], 'default','value'=>date('Y-m-d H:i:s')],
            [['tel'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'open_id' => 'Open ID',
            'type' => 'Type',
            'tel' => 'Tel',
            'agent_id' => 'Agent ID',
            'tmp_name' => 'Tmp Name',
            'tmp_avatar' => 'Tmp Avatar',
            'create_time' => 'Create Time',
        ];
    }
}
