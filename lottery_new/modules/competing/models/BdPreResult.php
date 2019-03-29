<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_pre_result".
 *
 * @property integer $pre_result_id
 * @property string $schedule_mid
 * @property string $pre_result_title
 * @property string $pre_result_3010
 * @property string $pre_result_3007
 * @property double $confidence_index
 * @property double $average_home_percent
 * @property double $average_visit_percent
 * @property string $json_data
 * @property string $expert_analysis
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class BdPreResult extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_pre_result';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['confidence_index', 'average_home_percent', 'average_visit_percent'], 'number'],
            [['json_data', 'expert_analysis'], 'string'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 11],
            [['pre_result_title'], 'string', 'max' => 100],
            [['pre_result_3010', 'pre_result_3007'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pre_result_id' => 'Pre Result ID',
            'schedule_mid' => 'Schedule Mid',
            'pre_result_title' => 'Pre Result Title',
            'pre_result_3010' => 'Pre Result 3010',
            'pre_result_3007' => 'Pre Result 3007',
            'confidence_index' => 'Confidence Index',
            'average_home_percent' => 'Average Home Percent',
            'average_visit_percent' => 'Average Visit Percent',
            'json_data' => 'Json Data',
            'expert_analysis' => 'Expert Analysis',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
