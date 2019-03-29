<?php

namespace app\modules\user\models;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "user_sgin_record".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $create_time
 * @property integer $continuous_num
 * @property integer $prize
 */
class UserSginRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_sgin_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'continuous_num', 'prize'], 'integer'],
            [['create_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'create_time' => 'Create Time',
            'continuous_num' => 'Continuous Num',
            'prize' => 'Prize',
        ];
    }

    /**
     * 今天是否签到
     * @param $userId
     * @return int  0 = 未签到 1 = 已签到
     */
    public function todaySginData($userId){
        $start_stime  = date('Y-m-d 00:00:00');
        $end_stime = date('Y-m-d 23:59:59');
        $sgin = (new Query())->select('create_time')->from('user_sgin_record')
            ->Where(['user_id' =>$userId])
            ->orderBy('create_time desc')
            ->one();
        if($sgin['create_time'] >= $start_stime && $sgin['create_time'] <= $end_stime ){
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 用户上次签到记录
     * @return array 上次签到信息
     */
    public function lastSginDate($userId){
        $query = new Query();
        $res = $query -> from('user_sgin_record') -> select('create_time, continuous_num') -> where(['user_id'=>$userId]) -> orderBy('create_time desc') -> one();
        return $res;
    }

    /**
     * 连续签到判断
     * $user_id
     * last_sign_time  上次签到日期
     * continuous_num  连续签到次数
     * return string 返回数字就是签到次数，否则为空
     */
    public function keepSginDate($last_sign_time,$continuous_num){
        $last_sign_time = strtotime($last_sign_time);
        /*
        //不是同一个月，不能连续签到
        $sign_month = date('m',$last_sign_time);
        $new_month = date('m');
        if($sign_month != $new_month) return 1;
        */
        /* 处理上次签到为0点，加上2天的时间戳 */
        $keepTime = strtotime(date('Y-m-d',$last_sign_time)) + 172799;
        /* 当前签到时间的零点 */
        $nowTime = strtotime(date('Y-m-d',time()));
        /* 如果当前时间 大于 连续签到时间域，就重置签到 */
        if($keepTime < $nowTime) {
            $data = 1;
        } else {
            $data = $continuous_num + 1;
        }
        return $data;
    }

    /**
     * 获取当月天数
     * $date 日期
     */
    public function getMonthDat($date){
        $days = date('t', strtotime($date));
        $date = [];
        for($i=1; $i<=$days; $i++){
            $date[] = ['day'=>$i];
        }
        return $date;
    }

    /**
     * 当月签到数据
     * @param $userId
     * @return array
     */
    public function monthSginDate($userId){
        $start_date = date('Y-m').'-01'.' 00:00:00';
        $end_time = date('Y-m').'-'.date('t',strtotime($start_date)).' 23:59:59';
        $query = new Query();
        $res = $query -> from('user_sgin_record')
            -> select('create_time')
            -> where(['user_id'=>$userId])
            -> andWhere([">=", "create_time", $start_date])
            -> andWhere(["<=", "create_time", $end_time])
            -> orderBy('id')
            -> all();
        $days = $this -> getMonthDat($start_date);
        if(empty($res)){
            return $days;
        }
        //月天数+签到数合并
        $sgin = array_column($res, 'create_time');
        foreach($sgin as &$sv){
            $sv = (int)substr($sv,8,2);
        }
        foreach($days as $k => &$v){
            if(in_array($v['day'],$sgin)){
                $v['mark'] = 1;
            } else {
                $v['mark'] = 0;
            }
        }
        return $days;
    }

    /**
     * 写入签到表
     * @param $userId
     * @param $continuous_num 连续签到次数
     * @return array
     */
    public function addSgin($userId, $continuous_num=1){
        $data['user_id'] = $userId;
        $data['continuous_num'] = $continuous_num;
        $data['create_time'] = date('Y-m-d H:i:s');
        $res = \Yii::$app->db->createCommand()->insert("user_sgin_record", $data)->execute();
        if(!$res){
            return ['code'=>109,'msg'=>'签到失败，请重新再试'];
        } else {
            return ['code'=>600,'msg'=>'签到表写入成功'];
        }
    }


}
