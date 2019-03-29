<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_europe_odds".
 *
 * @property integer $europe_odds_id
 * @property string $schedule_mid
 * @property string $company_name
 * @property string $country
 * @property integer $handicap_type
 * @property string $handicap_name
 * @property string $odds_3
 * @property integer $odds_3_trend
 * @property string $odds_1
 * @property integer $odds_1_trend
 * @property string $odds_0
 * @property integer $odds_0_trend
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class BdEuropeOdds extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_europe_odds';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['handicap_type', 'odds_3_trend', 'odds_1_trend', 'odds_0_trend'], 'integer'],
            [['odds_3', 'odds_1', 'odds_0'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 11],
            [['company_name', 'country', 'handicap_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'europe_odds_id' => 'Europe Odds ID',
            'schedule_mid' => 'Schedule Mid',
            'company_name' => 'Company Name',
            'country' => 'Country',
            'handicap_type' => 'Handicap Type',
            'handicap_name' => 'Handicap Name',
            'odds_3' => 'Odds 3',
            'odds_3_trend' => 'Odds 3 Trend',
            'odds_1' => 'Odds 1',
            'odds_1_trend' => 'Odds 1 Trend',
            'odds_0' => 'Odds 0',
            'odds_0_trend' => 'Odds 0 Trend',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
