<?php

namespace app\modules\cron\models;

use Yii;

/**
 * This is the model class for table "check_lottery_result_record".
 *
 * @property integer $check_lottery_result_record_id
 * @property string $lottery_code
 * @property string $periods
 * @property string $open_num
 * @property string $remark
 * @property string $create_time
 */
class CheckLotteryResultRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'check_lottery_result_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_code', 'periods', 'open_num'], 'required'],
            [['create_time'],'default','value'=>date('Y-m-d H:i:s')],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'check_lottery_result_record_id' => 'Check Lottery Result Record ID',
            'lottery_code' => 'Lottery Code',
            'periods' => 'Periods',
            'open_num' => 'Open Num',
            'remark' => 'Remark',
            'create_time' => 'Create Time',
        ];
    }
    
    public static function tosave($att){
        $newModel = new CheckLotteryResultRecord();
        foreach ($att as $k =>$v){
            $newModel->$k = $v;
        }
        if(!$newModel->save()){
            $errMsg = $newModel->errors;
            return $errMsg;
        }
        return $newModel->attributes;
    }
}
