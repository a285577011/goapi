<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "schedule_technic".
 *
 * @property integer $schedule_technic_id
 * @property string $schedule_mid
 * @property integer $home_ball_rate
 * @property integer $visit_ball_rate
 * @property integer $home_shoot_num
 * @property integer $visit_shoot_num
 * @property integer $home_shoot_right_num
 * @property integer $visit_shoot_right_num
 * @property integer $home_corner_num
 * @property integer $visit_corner_num
 * @property integer $home_foul_num
 * @property integer $visit_foul_num
 * @property integer $home_red_num
 * @property integer $home_yellow_num
 * @property integer $visit_red_num
 * @property integer $visit_yellow_num
 * @property string $odds_3006
 * @property string $odds_3007
 * @property string $odds_3008
 * @property string $odds_3009
 * @property string $odds_3010
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class ScheduleTechnic extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'schedule_technic';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['home_ball_rate', 'visit_ball_rate', 'home_shoot_num', 'visit_shoot_num', 'home_shoot_right_num', 'visit_shoot_right_num', 'home_corner_num', 'visit_corner_num', 'home_foul_num', 'visit_foul_num', 'home_red_num', 'home_yellow_num', 'visit_red_num', 'visit_yellow_num'], 'integer'],
            [['odds_3006', 'odds_3007', 'odds_3008', 'odds_3009', 'odds_3010'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 11],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'schedule_technic_id' => 'Schedule Technic ID',
            'schedule_mid' => 'Schedule Mid',
            'home_ball_rate' => 'Home Ball Rate',
            'visit_ball_rate' => 'Visit Ball Rate',
            'home_shoot_num' => 'Home Shoot Num',
            'visit_shoot_num' => 'Visit Shoot Num',
            'home_shoot_right_num' => 'Home Shoot Right Num',
            'visit_shoot_right_num' => 'Visit Shoot Right Num',
            'home_corner_num' => 'Home Corner Num',
            'visit_corner_num' => 'Visit Corner Num',
            'home_foul_num' => 'Home Foul Num',
            'visit_foul_num' => 'Visit Foul Num',
            'home_red_num' => 'Home Red Num',
            'home_yellow_num' => 'Home Yellow Num',
            'visit_red_num' => 'Visit Red Num',
            'visit_yellow_num' => 'Visit Yellow Num',
            'odds_3006' => 'Odds 3006',
            'odds_3007' => 'Odds 3007',
            'odds_3008' => 'Odds 3008',
            'odds_3009' => 'Odds 3009',
            'odds_3010' => 'Odds 3010',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
