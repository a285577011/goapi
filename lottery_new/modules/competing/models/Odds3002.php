<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_3002".
 *
 * @property integer $odds_3002_id
 * @property string $schedule_mid
 * @property integer $update_nums
 * @property string $rf_nums
 * @property string $wins_3002
 * @property integer $wins_trend
 * @property string $lose_3002
 * @property integer $lose_trend
 * @property integer $opt_id
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds3002 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3002';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_mid', 'wins_3002', 'lose_3002'], 'required'],
            [['update_nums', 'wins_trend', 'lose_trend', 'opt_id'], 'integer'],
            [['rf_nums', 'wins_3002', 'lose_3002'], 'number'],
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
            'odds_3002_id' => 'Odds 3002 ID',
            'schedule_mid' => 'Schedule Mid',
            'update_nums' => 'Update Nums',
            'rf_nums' => 'Rf Nums',
            'wins_3002' => 'Wins 3002',
            'wins_trend' => 'Wins Trend',
            'lose_3002' => 'Lose 3002',
            'lose_trend' => 'Lose Trend',
            'opt_id' => 'Opt ID',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
