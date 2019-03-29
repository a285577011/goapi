<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "lottery".
 *
 * @property integer $lottery_id
 * @property string $lottery_code
 * @property string $lottery_name
 * @property string $description
 * @property integer $lottery_category_id
 * @property integer $status
 * @property integer $sale_status
 * @property string $lottery_pic
 * @property integer $lottery_sort
 * @property integer $result_status
 * @property integer $opt_id
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class Lottery extends \yii\db\ActiveRecord
{
    
    const CODE_SSQ = '1001'; //双色球
    const CODE_FC_3D = '1002'; //福彩3D
    const CODE_QLC = '1003'; //七乐彩
    const CODE_DLT = '2001'; //大乐透
    const CODE_PL3 = '2002'; //排列三
    const CODE_PL5 = '2003'; //排列五
    const CODE_QXC = '2004'; //七星彩
    const CODE_GD11X5 = '2005'; //广东11选5
    const CODE_JX11X5 = '2006'; //江西11选5
    const CODE_SD11X5 = '2007'; //山东11选5
    const CODE_RQSPF = '3006'; //让球胜平负
    const CODE_QCBF = '3007'; //全场比分
    const CODE_JQ = '3008'; //
    const CODE_BQC = '3009'; //半全场
    const CODE_SPF = '3010'; //胜平负
    const CODE_HH = '3011'; //混合过关
    const CODE_FOOT = '3000'; // 竞彩总code
    const CODE_FOURTEEN = '4001'; // 任选14
    const CODE_NINE = '4002'; // 任选九
    const CODE_OPTIONAL = '4000'; //任选总code
    const CODE_SF = '3001';// 胜负
    const CODE_RFSF = '3002'; // 让分胜负
    const CODE_SFC = '3003'; // 胜分差
    const CODE_DXF = '3004'; // 大小分
    const CODE_HHTZ = '3005'; // 混合投注
    const CODE_BDSPF = '5001'; //北单胜平负
    const CODE_BDZJQ = '5002'; //北单总进球
    const CODE_BDBQC = '5003'; //北单半全场
    const CODE_BDSXDS = '5004'; //北单上下单双
    const CODE_BDBF = '5005'; //北单全场比分
    const CODE_BDSF = '5005'; //北单胜负

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lottery';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_code', 'lottery_name'], 'required'],
            [['lottery_category_id', 'status', 'sale_status', 'lottery_sort', 'result_status', 'opt_id'], 'integer'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['lottery_code'], 'string', 'max' => 10],
            [['lottery_name'], 'string', 'max' => 20],
            [['description'], 'string', 'max' => 200],
            [['lottery_pic'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'lottery_id' => 'Lottery ID',
            'lottery_code' => 'Lottery Code',
            'lottery_name' => 'Lottery Name',
            'description' => 'Description',
            'lottery_category_id' => 'Lottery Category ID',
            'status' => 'Status',
            'sale_status' => 'Sale Status',
            'lottery_pic' => 'Lottery Pic',
            'lottery_sort' => 'Lottery Sort',
            'result_status' => 'Result Status',
            'opt_id' => 'Opt ID',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
    
    public function getNewPeriods()
    {
        $periods = [];
        $maxPeriods = LotteryRecord::find()->select(["CONCAT(lottery_code, '_' , max(periods)) periods"])->where(['status' => 2])->groupBy('lottery_code')->asArray()->all();
        foreach ($maxPeriods as $item) {
            $periods[] = $item['periods']; 
        }
        return $this->hasOne(LotteryRecord::className(), ['lottery_code' => 'lottery_code'])
        ->select('lottery_name,lottery_code,periods,lottery_time,week,lottery_numbers,pool')
        ->where(['status'=>2])
        ->andWhere(['in', "CONCAT(`lottery_code`,'_' ,`periods`)", $periods])
        ->orderBy('periods desc');
    }
    public static function upData($update,$where){
    	 
    	return \Yii::$app->db->createCommand()->update(self::tableName(),$update,$where)->execute();
    }
}
