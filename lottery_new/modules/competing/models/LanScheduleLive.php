<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "lan_schedule_live".
 *
 * @property integer $live_id
 * @property integer $sort_id
 * @property integer $schedule_mid
 * @property string $live_person
 * @property string $text_sub
 * @property string $game_time
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class LanScheduleLive extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lan_schedule_live';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sort_id', 'schedule_mid', 'live_person', 'text_sub', 'game_time'], 'required'],
            [['sort_id', 'schedule_mid'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['live_person'], 'string', 'max' => 50],
            [['text_sub'], 'string', 'max' => 500],
            [['game_time'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'live_id' => 'Live ID',
            'sort_id' => 'Sort ID',
            'schedule_mid' => 'Schedule Mid',
            'live_person' => 'Live Person',
            'text_sub' => 'Text Sub',
            'game_time' => 'Game Time',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
