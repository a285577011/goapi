<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_5002".
 *
 * @property integer $odds_5002_id
 * @property string $open_mid
 * @property integer $update_nums
 * @property string $odds_0
 * @property integer $trend_0
 * @property string $odds_1
 * @property integer $trend_1
 * @property string $odds_2
 * @property integer $trend_2
 * @property string $odds_3
 * @property integer $trend_3
 * @property string $odds_4
 * @property integer $trend_4
 * @property string $odds_5
 * @property integer $trend_5
 * @property string $odds_6
 * @property integer $trend_6
 * @property string $odds_7
 * @property integer $trend_7
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds5002 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_5002';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['open_mid'], 'required'],
            [['update_nums', 'trend_0', 'trend_1', 'trend_2', 'trend_3', 'trend_4', 'trend_5', 'trend_6', 'trend_7'], 'integer'],
            [['odds_0', 'odds_1', 'odds_2', 'odds_3', 'odds_4', 'odds_5', 'odds_6', 'odds_7'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['open_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_5002_id' => 'Odds 5002 ID',
            'open_mid' => 'Open Mid',
            'update_nums' => 'Update Nums',
            'odds_0' => 'Odds 0',
            'trend_0' => 'Trend 0',
            'odds_1' => 'Odds 1',
            'trend_1' => 'Trend 1',
            'odds_2' => 'Odds 2',
            'trend_2' => 'Trend 2',
            'odds_3' => 'Odds 3',
            'trend_3' => 'Trend 3',
            'odds_4' => 'Odds 4',
            'trend_4' => 'Trend 4',
            'odds_5' => 'Odds 5',
            'trend_5' => 'Trend 5',
            'odds_6' => 'Odds 6',
            'trend_6' => 'Trend 6',
            'odds_7' => 'Odds 7',
            'trend_7' => 'Trend 7',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
