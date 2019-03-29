<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "worldcup_schedule".
 *
 * @property integer $worldcup_schedule_id
 * @property string $game_city
 * @property string $game_field
 * @property integer $game_level_id
 * @property string $game_level_name
 * @property string $schedule_date
 * @property string $start_time
 * @property string $sort
 * @property string $group_id
 * @property string $group_name
 * @property string $home_team_name
 * @property string $home_img
 * @property string $visit_team_name
 * @property string $visit_img
 * @property string $bifen
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class WorldcupSchedule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'worldcup_schedule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['game_level_id'], 'integer'],
            [['start_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['game_city', 'game_field', 'game_level_name', 'schedule_date', 'home_team_name', 'visit_team_name', 'bifen'], 'string', 'max' => 50],
            [['sort', 'group_id', 'group_name'], 'string', 'max' => 25],
            [['home_img', 'visit_img'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'worldcup_schedule_id' => 'Worldcup Schedule ID',
            'game_city' => 'Game City',
            'game_field' => 'Game Field',
            'game_level_id' => 'Game Level ID',
            'game_level_name' => 'Game Level Name',
            'schedule_date' => 'Schedule Date',
            'start_time' => 'Start Time',
            'sort' => 'Sort',
            'group_id' => 'Group ID',
            'group_name' => 'Group Name',
            'home_team_name' => 'Home Team Name',
            'home_img' => 'Home Img',
            'visit_team_name' => 'Visit Team Name',
            'visit_img' => 'Visit Img',
            'bifen' => 'Bifen',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
