<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "lan_league_team".
 *
 * @property integer $lan_league_team
 * @property integer $lan_league_id
 * @property integer $lan_team_id
 * @property string $update_time
 */
class LanLeagueTeam extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lan_league_team';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lan_league_id', 'lan_team_id'], 'integer'],
            [['update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'lan_league_team' => 'Lan League Team',
            'lan_league_id' => 'Lan League ID',
            'lan_team_id' => 'Lan Team ID',
            'update_time' => 'Update Time',
        ];
    }
}
