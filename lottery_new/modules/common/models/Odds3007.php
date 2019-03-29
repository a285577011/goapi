<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "odds_3007".
 *
 * @property integer $odds_score_id
 * @property integer $schedule_id
 * @property string $schedule_mid
 * @property integer $updates_nums
 * @property string $score_wins_10
 * @property string $score_wins_20
 * @property string $score_wins_21
 * @property string $score_wins_30
 * @property string $score_wins_31
 * @property string $score_wins_32
 * @property string $score_wins_40
 * @property string $score_wins_41
 * @property string $score_wins_42
 * @property string $score_wins_50
 * @property string $score_wins_51
 * @property string $score_wins_52
 * @property string $score_wins_90
 * @property string $score_level_00
 * @property string $score_level_11
 * @property string $score_level_22
 * @property string $score_level_33
 * @property string $score_level_99
 * @property string $score_negative_01
 * @property string $score_negative_02
 * @property string $score_negative_12
 * @property string $score_negative_03
 * @property string $score_negative_13
 * @property string $score_negative_23
 * @property string $score_negative_04
 * @property string $score_negative_14
 * @property string $score_negative_24
 * @property string $score_negative_05
 * @property string $score_negative_15
 * @property string $score_negative_25
 * @property string $score_negative_09
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class Odds3007 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3007';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_id', 'schedule_mid'], 'required'],
            [['schedule_id', 'updates_nums'], 'integer'],
            [['score_wins_10', 'score_wins_20', 'score_wins_21', 'score_wins_30', 'score_wins_31', 'score_wins_32', 'score_wins_40', 'score_wins_41', 'score_wins_42', 'score_wins_50', 'score_wins_51', 'score_wins_52', 'score_wins_90', 'score_level_00', 'score_level_11', 'score_level_22', 'score_level_33', 'score_level_99', 'score_negative_01', 'score_negative_02', 'score_negative_12', 'score_negative_03', 'score_negative_13', 'score_negative_23', 'score_negative_04', 'score_negative_14', 'score_negative_24', 'score_negative_05', 'score_negative_15', 'score_negative_25', 'score_negative_09'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_score_id' => 'Odds Score ID',
            'schedule_id' => 'Schedule ID',
            'schedule_mid' => 'Schedule Mid',
            'updates_nums' => 'Updates Nums',
            'score_wins_10' => 'Score Wins 10',
            'score_wins_20' => 'Score Wins 20',
            'score_wins_21' => 'Score Wins 21',
            'score_wins_30' => 'Score Wins 30',
            'score_wins_31' => 'Score Wins 31',
            'score_wins_32' => 'Score Wins 32',
            'score_wins_40' => 'Score Wins 40',
            'score_wins_41' => 'Score Wins 41',
            'score_wins_42' => 'Score Wins 42',
            'score_wins_50' => 'Score Wins 50',
            'score_wins_51' => 'Score Wins 51',
            'score_wins_52' => 'Score Wins 52',
            'score_wins_90' => 'Score Wins 90',
            'score_level_00' => 'Score Level 00',
            'score_level_11' => 'Score Level 11',
            'score_level_22' => 'Score Level 22',
            'score_level_33' => 'Score Level 33',
            'score_level_99' => 'Score Level 99',
            'score_negative_01' => 'Score Negative 01',
            'score_negative_02' => 'Score Negative 02',
            'score_negative_12' => 'Score Negative 12',
            'score_negative_03' => 'Score Negative 03',
            'score_negative_13' => 'Score Negative 13',
            'score_negative_23' => 'Score Negative 23',
            'score_negative_04' => 'Score Negative 04',
            'score_negative_14' => 'Score Negative 14',
            'score_negative_24' => 'Score Negative 24',
            'score_negative_05' => 'Score Negative 05',
            'score_negative_15' => 'Score Negative 15',
            'score_negative_25' => 'Score Negative 25',
            'score_negative_09' => 'Score Negative 09',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
