<?php

namespace app\modules\openapi\models;

use Yii;
use app\modules\common\services\KafkaService;

/**
 * This is the model class for table "callback_detail".
 *
 * @property integer $id
 * @property integer $callback_base_id
 * @property integer $exec_status
 * @property integer $callback_status
 * @property integer $exec_times
 * @property string $params
 * @property integer $c_time
 */
class CallbackDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'callback_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['callback_base_id', 'params','url'], 'required'],
            [['callback_base_id', 'exec_status', 'callback_status', 'exec_times', 'c_time','u_time'], 'integer'],
            [['params'], 'string', 'max' => 1000],
        	[['url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'callback_base_id' => 'Callback Base ID',
            'exec_status' => 'Exec Status',
            'callback_status' => 'Callback Statuc',
            'exec_times' => 'Exec Times',
            'params' => 'Params',
            'c_time' => 'C Time',
        	'u_time' => 'U Time',
        	'url' => 'Url',
        ];
    }
    /**
     * 
     * @param unknown $code 请求唯一表示
     *  param unknown $params 请求参数
     */
    public static function add($code,$params){
    	$data=['code'=>$code,'params'=>$params];
    	KafkaService::addQue('CallbackThird', $data);
    }
}
