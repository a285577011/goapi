<?php

namespace app\modules\welfare\models;

use Yii;

/**
 * This is the model class for table "ssq_trend_chart".
 *
 * @property integer $ssq_trend_chart_id
 * @property string $lottery_name
 * @property string $periods
 * @property string $open_code
 * @property string $blue_omission
 * @property string $red_omission
 * @property string $modify_time
 * @property string $create_time
 * @property integer $opt_id
 * @property string $update_time
 */
class SsqTrendChart extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ssq_trend_chart';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_name', 'periods', 'open_code', 'blue_omission', 'red_omission'], 'required'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['opt_id'], 'integer'],
            [['lottery_name', 'periods'], 'string', 'max' => 25],
            [['open_code', 'blue_omission'], 'string', 'max' => 50],
            [['red_omission'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ssq_trend_chart_id' => 'Ssq Trend Chart ID',
            'lottery_name' => 'Lottery Name',
            'periods' => 'Periods',
            'open_code' => 'Open Code',
            'blue_omission' => 'Blue Omission',
            'red_omission' => 'Red Omission',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'opt_id' => 'Opt ID',
            'update_time' => 'Update Time',
        ];
    }
    
    /**
     * 说明: 生成双色球走势图数据
     * @author  kevi
     * @date 2017年6月7日 下午3:26:04
     * @param 
     * @return $blue_omission,$red_omission
     */
    public static function createOmission($periods,$openCode){
        $lastTrend = static::find()->where([])->orderBy('periods desc')->asArray()->one();
        if(!empty($lastTrend)){
            $openCodeArr = explode('|', $openCode);
            $redArr = explode(',', $openCodeArr[0]);
//             $redArr = explode(',', $openCodeArr[1]);
            $blue =  $openCodeArr[1];
            $lastRedOmissionArr = explode(',', $lastTrend['red_omission']);
            $lastBlueOmissionArr = explode(',', $lastTrend['blue_omission']);
            
            $newBlueOmissionArr = [];
            $newRedOmissionArr = [];
            
            foreach ($lastRedOmissionArr as $k=>$v){
                if(in_array($k+1, $redArr)){
                    $newRedOmissionArr[$k]=0;
                }else{
                    $newRedOmissionArr[$k]=$v+1;
                }
            }
            $newRedOmission = implode($newRedOmissionArr, ',');
            
            foreach ($lastBlueOmissionArr as $k=>$v){
                if(($k+1)==$blue){
                    $newBlueOmissionArr[$k]=0;
                }else{
                    $newBlueOmissionArr[$k]=$v+1;
                }
            }
            $newBlueOmission = implode($newBlueOmissionArr, ',');
            
        }
        
        $newSsqTrendChart = new SsqTrendChart();
        $newSsqTrendChart->lottery_name = '双色球';
        $newSsqTrendChart->periods = $periods;
        $newSsqTrendChart->open_code = $openCode;
        $newSsqTrendChart->blue_omission = $newBlueOmission;
        $newSsqTrendChart->red_omission = $newRedOmission;
        $newSsqTrendChart->modify_time = date('Y-m-d H:i:s');
        $newSsqTrendChart->create_time = date('Y-m-d H:i:s');
        
        if(!$newSsqTrendChart->save()){
            return $newSsqTrendChart->errors;
        }
        
        return $newSsqTrendChart->attributes;
        
    }
    
}
