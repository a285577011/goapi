<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_asian_handicap".
 *
 * @property integer $asian_handicap_id
 * @property string $schedule_mid
 * @property string $company_name
 * @property string $country
 * @property integer $handicap_type
 * @property string $handicap_name
 * @property string $home_discount
 * @property integer $home_discount_trend
 * @property string $let_index
 * @property string $visit_discount
 * @property integer $visit_discount_trend
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class BdAsianHandicap extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_asian_handicap';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['handicap_type', 'home_discount_trend', 'visit_discount_trend'], 'integer'],
            [['home_discount', 'visit_discount'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 11],
            [['company_name', 'country', 'handicap_name', 'let_index'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'asian_handicap_id' => 'Asian Handicap ID',
            'schedule_mid' => 'Schedule Mid',
            'company_name' => 'Company Name',
            'country' => 'Country',
            'handicap_type' => 'Handicap Type',
            'handicap_name' => 'Handicap Name',
            'home_discount' => 'Home Discount',
            'home_discount_trend' => 'Home Discount Trend',
            'let_index' => 'Let Index',
            'visit_discount' => 'Visit Discount',
            'visit_discount_trend' => 'Visit Discount Trend',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
