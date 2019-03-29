<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_5001".
 *
 * @property integer $odds_5001_id
 * @property string $open_mid
 * @property integer $update_nums
 * @property string $odds_3
 * @property integer $trend_3
 * @property string $odds_1
 * @property integer $trend_1
 * @property string $odds_0
 * @property integer $trend_0
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds5001 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_5001';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['update_nums', 'trend_3', 'trend_1', 'trend_0'], 'integer'],
            [['odds_3', 'odds_1', 'odds_0'], 'number'],
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
            'odds_5001_id' => 'Odds 5001 ID',
            'open_mid' => 'Open Mid',
            'update_nums' => 'Update Nums',
            'odds_3' => 'Odds 3',
            'trend_3' => 'Trend 3',
            'odds_1' => 'Odds 1',
            'trend_1' => 'Trend 1',
            'odds_0' => 'Odds 0',
            'trend_0' => 'Trend 0',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
