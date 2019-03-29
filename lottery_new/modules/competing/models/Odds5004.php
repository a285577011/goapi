<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_5004".
 *
 * @property integer $odds_5004_id
 * @property string $open_mid
 * @property integer $update_nums
 * @property string $odds_1
 * @property integer $trend_1
 * @property string $odds_2
 * @property integer $trend_2
 * @property string $odds_3
 * @property integer $trend_3
 * @property string $odds_4
 * @property integer $trend_4
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds5004 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_5004';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['update_nums', 'trend_1', 'trend_2', 'trend_3', 'trend_4'], 'integer'],
            [['odds_1', 'odds_2', 'odds_3', 'odds_4'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['open_mid'], 'string', 'max' => 24],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_5004_id' => 'Odds 5004 ID',
            'open_mid' => 'Open Mid',
            'update_nums' => 'Update Nums',
            'odds_1' => 'Odds 1',
            'trend_1' => 'Trend 1',
            'odds_2' => 'Odds 2',
            'trend_2' => 'Trend 2',
            'odds_3' => 'Odds 3',
            'trend_3' => 'Trend 3',
            'odds_4' => 'Odds 4',
            'trend_4' => 'Trend 4',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
