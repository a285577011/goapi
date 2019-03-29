<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "worldcup_fnl".
 *
 * @property integer $worldcup_fnl_id
 * @property integer $open_mid
 * @property integer $home_code
 * @property string $home_name
 * @property string $home_img
 * @property integer $visit_code
 * @property string $visit_name
 * @property string $visit_img
 * @property string $team_odds
 * @property string $team_chance
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class WorldcupFnl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'worldcup_fnl';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['home_code', 'visit_code', 'status', 'open_mid'], 'integer'],
            [['team_odds', 'team_chance'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['open_mid'], 'string', 'max' => 25],
            [['home_name', 'visit_name'], 'string', 'max' => 100],
            [['home_img', 'visit_img'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'worldcup_fnl_id' => 'Worldcup Fnl ID',
            'open_mid' => 'Open Mid',
            'home_code' => 'Home Code',
            'home_name' => 'Home Name',
            'home_img' => 'Home Img',
            'visit_code' => 'Visit Code',
            'visit_name' => 'Visit Name',
            'visit_img' => 'Visit Img',
            'team_odds' => 'Team Odds',
            'team_chance' => 'Team Chance',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
