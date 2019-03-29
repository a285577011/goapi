<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "odds_3006".
 *
 * @property integer $odds_let_id
 * @property string $schedule_mid
 * @property integer $updates_nums
 * @property string $let_ball_nums
 * @property string $let_wins
 * @property integer $let_wins_trend
 * @property string $let_level
 * @property integer $let_level_trend
 * @property string $let_negative
 * @property integer $let_negative_trend
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property integer $schedule_id
 * @property integer $opt_id
 */
class Odds3006 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3006';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['updates_nums', 'let_wins_trend', 'let_level_trend', 'let_negative_trend', 'schedule_id', 'opt_id'], 'integer'],
            [['let_wins', 'let_level', 'let_negative'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_id'], 'required'],
            [['schedule_mid'], 'string', 'max' => 25],
            [['let_ball_nums'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_let_id' => 'Odds Let ID',
            'schedule_mid' => 'Schedule Mid',
            'updates_nums' => 'Updates Nums',
            'let_ball_nums' => 'Let Ball Nums',
            'let_wins' => 'Let Wins',
            'let_wins_trend' => 'Let Wins Trend',
            'let_level' => 'Let Level',
            'let_level_trend' => 'Let Level Trend',
            'let_negative' => 'Let Negative',
            'let_negative_trend' => 'Let Negative Trend',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'schedule_id' => 'Schedule ID',
            'opt_id' => 'Opt ID',
        ];
    }
}
