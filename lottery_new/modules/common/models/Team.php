<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "team".
 *
 * @property integer $team_id
 * @property string $team_code
 * @property integer $team_type
 * @property string $team_position
 * @property string $team_short_name
 * @property string $team_long_name
 * @property string $country_name
 * @property string $country_code
 * @property string $team_img
 * @property string $team_remarks
 * @property integer $team_status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property integer $opt_id
 */
class Team extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'team';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['team_code', 'team_type', 'team_long_name'], 'required'],
            [['team_type', 'team_status', 'opt_id'], 'integer'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['team_code', 'team_short_name'], 'string', 'max' => 25],
            [['team_position', 'country_code'], 'string', 'max' => 50],
            [['team_long_name', 'team_img'], 'string', 'max' => 100],
            [['country_name'], 'string', 'max' => 150],
            [['team_remarks'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'team_id' => 'Team ID',
            'team_code' => 'Team Code',
            'team_type' => 'Team Type',
            'team_position' => 'Team Position',
            'team_short_name' => 'Team Short Name',
            'team_long_name' => 'Team Long Name',
            'country_name' => 'Country Name',
            'country_code' => 'Country Code',
            'team_img' => 'Team Img',
            'team_remarks' => 'Team Remarks',
            'team_status' => 'Team Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'opt_id' => 'Opt ID',
        ];
    }
}
