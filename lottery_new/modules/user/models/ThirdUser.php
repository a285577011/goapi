<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "third_user".
 *
 * @property integer $id
 * @property integer $uid
 * @property integer $uid_source
 * @property string $third_uid
 * @property string $union_id
 * @property integer $type
 * @property string $icon
 * @property string $nickname
 * @property integer $sex
 * @property string $create_time
 */
class ThirdUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'third_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['third_uid', 'nickname', 'create_time'], 'required'],
            [['uid', 'type', 'sex'], 'integer'],
            [['create_time'], 'safe'],
            [['third_uid', 'union_id', 'icon'], 'string', 'max' => 200],
            [['nickname'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'uid_source' => 'Uid Source',
            'third_uid' => 'Third Uid',
            'union_id' => 'Union ID',
            'type' => 'Type',
            'icon' => 'Icon',
            'nickname' => 'Nickname',
            'sex' => 'Sex',
            'create_time' => 'Create Time',
        ];
    }
}
