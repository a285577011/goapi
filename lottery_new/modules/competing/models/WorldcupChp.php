<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "worldcup_chp".
 *
 * @property integer $worldcup_chp_id
 * @property integer $open_mid
 * @property integer $team_code
 * @property string $team_name
 * @property string $team_img
 * @property string $team_odds
 * @property integer $status
 * @property string $team_chance
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class WorldcupChp extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'worldcup_chp';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['team_code', 'status'], 'integer'],
            [['team_odds', 'team_chance'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['open_mid'], 'string', 'max' => 25],
            [['team_name'], 'string', 'max' => 150],
            [['team_img'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'worldcup_chp_id' => 'Worldcup Chp ID',
            'open_mid' => 'Open Mid',
            'team_code' => 'Team Code',
            'team_name' => 'Team Name',
            'team_img' => 'Team Img',
            'team_odds' => 'Team Odds',
            'status' => 'Status',
            'team_chance' => 'Team Chance',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
