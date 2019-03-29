<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_3001".
 *
 * @property integer $odds_3001_id
 * @property string $schedule_mid
 * @property integer $update_nums
 * @property string $wins_3001
 * @property integer $wins_trend
 * @property string $lose_3001
 * @property integer $lose_trend
 * @property integer $opt_id
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds3001 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3001';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_mid', 'wins_3001', 'lose_3001'], 'required'],
            [['update_nums', 'wins_trend', 'lose_trend', 'opt_id'], 'integer'],
            [['wins_3001', 'lose_3001'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_3001_id' => 'Odds 3001 ID',
            'schedule_mid' => 'Schedule Mid',
            'update_nums' => 'Update Nums',
            'wins_3001' => 'Wins 3001',
            'wins_trend' => 'Wins Trend',
            'lose_3001' => 'Lose 3001',
            'lose_trend' => 'Lose Trend',
            'opt_id' => 'Opt ID',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
