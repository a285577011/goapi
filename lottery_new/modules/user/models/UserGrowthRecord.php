<?php

namespace app\modules\user\models;

use Yii;
use yii\db\Query;
/**
 * This is the model class for table "user_growth_record".
 *
 * @property integer $user_growth_id
 * @property integer $type
 * @property string $growth_source
 * @property integer $growth_value
 * @property integer $totle_balance
 * @property string $growth_remark
 * @property integer $user_id
 * @property integer $levels
 * @property string $create_time
 */
class UserGrowthRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_growth_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'growth_value', 'totle_balance', 'user_id', 'levels'], 'integer'],
            [['growth_source'], 'required'],
            [['create_time'], 'safe'],
            [['growth_source'], 'string', 'max' => 25],
            [['growth_remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_growth_id' => 'User Growth ID',
            'type' => 'Type',
            'growth_source' => 'Growth Source',
            'growth_value' => 'Growth Value',
            'totle_balance' => 'Totle Balance',
            'growth_remark' => 'Growth Remark',
            'user_id' => 'User ID',
            'levels' => 'Levels',
            'create_time' => 'Create Time',
        ];
    }


    /**
     * 获取成长值记录明细
     * @param $user_id  用户id
     * @param $page     当前分页
     * @param $size     每页条数
     * @param $condition其他筛选条件
     * @return array
     */
    public static function getUserGrowthRecord($user_id, $page, $size, $type){
        $where = ['user_id'=>$user_id];
        if($type != 0){
            $where['type'] = $type;
        }
        $total = self::find()->where(['user_id'=>$user_id])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $fields = 'user_growth_id, growth_source, growth_value, growth_remark, levels, create_time';
        $data = self::find()->select($fields)
            ->where($where)->asArray()
            ->offset($offset)
            ->limit($size)
            ->orderBy('create_time desc')
            ->all();
        return ['page' => $page, 'pages' => $pages, 'size' => count($total), 'total' => $total, 'data' => $data];
    }

    /**
     * 增加或减少成长值：
     * 两种方式：
     * 1：填写$data
     * @param int $custNo
     * @param int $user_growth_id  成长值表id，获取相对应成长值,
     * @param array $data = [
                    'type' =>  操作类型：1=增加，2=减少: 必填
                    'growth_value' => 增加或减少的成长值, 必须是正数: 必填
                    'growth_remark' => 操作备注: 必填
                    'growth_source' => 操作来源  user_growth表id 必填,
                    'order_code' => string 订单编号,
                    'order_source' => int 订单表的来源,
                    ]
     * @param int $growth_remark   备注
     * @return bool
     */
    public function updateGrowth($custNo, $data='', $user_growth_id=''){
        //成长值取整
        if(isset($data['growth_value'])){
            $data['growth_value'] = floor($data['growth_value']);
        }
        //如果有指定成长值类型，则取成长类型表数据
        if ($user_growth_id){
            if(empty($data)){
                $data = [];
            }
            $growth = $this ->getGrowthValue($user_growth_id);
            if($growth['growth_value'] == 0) return ['code' => 109, 'msg' => '操作失败,成长值为空'];
            $data['growth_value'] = $growth['growth_value'];
            $data['growth_source'] = $user_growth_id;
            $data['growth_remark'] = $growth['growth_source'];
            $data['type'] = $growth['type'];
        }
        //查等级和成长值
        $user = User::find()->select('level_id') -> where(['cust_no' => $custNo]) ->asArray() -> one();
        $userFunds = (new Query())->select('user_growth,user_id')->from('user_funds')->where(['cust_no' => $custNo])->one();
        $user_id = $userFunds['user_id'];
        if($data['type'] == 1){
            $totle_balance = ($data['growth_value'] + $userFunds['user_growth']);   //新增后总成长值
        } elseif($data['type'] == 2) {
            $totle_balance = ($userFunds['user_growth'] - $data['growth_value'] );  //减少后总成长值
        }
        $newLevel = $this -> getLevels($totle_balance); //成长值更改后的等级
        //事物
        $tran = Yii::$app->db->beginTransaction();
        //更新用户等级
        if($user['level_id'] != $newLevel['user_level_id']){
            $userDate = [
                'level_id' => $newLevel['user_level_id'],
                'level_name' => $newLevel['level_name'],
            ];
            $res1 = \Yii::$app->db->createCommand()->update('user', $userDate, ['user_id' => $user_id])->execute();
            if($res1 == false){
                $tran->rollBack();
                return false;
            }
        }
        //更新成长值记录表
        $UserGrowthRecord = [
            'user_id' => $user_id,
            'levels' => $newLevel['user_level_id'],
            'totle_balance' => $totle_balance,
            'growth_source' => $data['growth_source'],
            'growth_value' => abs($data['growth_value']),
            'growth_remark' => isset($data['growth_remark']) ? $data['growth_remark'] : '',
            'order_code' => isset($data['order_code']) ? $data['order_code'] : '',
            'order_source' => isset($data['order_source']) ? $data['order_source'] : '',
            'create_time' => date('Y-m-d H:i:s'),
            'type' => $data['type'],
        ];
        //更新资金表 - 总成长值
        $userFunds = ['user_growth' => $totle_balance,];
        $res1 = \Yii::$app->db->createCommand()->insert(self::tableName(),$UserGrowthRecord)->execute();
        $res2 = \Yii::$app->db->createCommand()->update('user_funds', $userFunds, ['cust_no' => $custNo])->execute();
        if(!$res1 || !$res2){
            $tran->rollBack();
            return ['code' => 109, 'msg' => '操作失败！'];
        }
        $tran->commit();
        return ['code' => 600, 'msg' => '操作成功！'];
    }

    /**
     * 获取对应成长值的等级
     * @param $totle_balance 总成长值
     * @return array
     */
    public function getLevels($totle_balance){
        //获取全部等级
        $levels = (new Query())->select('user_level_id,level_growth,level_name')->from('user_levels')->all();
        foreach($levels as $k => $v){
            if($totle_balance < $v['level_growth']){
                if($totle_balance <= 0){
                    $newLevel = $v;
                } else {
                    $newLevel = $levels[$k-1];
                }
                break;
            } else {
                $newLevel  = $v;
            }
        }
        return $newLevel;
    }

    /**
     * 获取成长值表对应的成长数值
     * @param $user_growth_id   user_growth表id
     * @return mixed
     */
    public function getGrowthValue($user_growth_id){
        $res = (new Query())->select('growth_value, growth_source, type')->from('user_growth')->where(['user_growth_id' => $user_growth_id])->one();
        return $res;
    }

    /**
     * 用户资料完善添加
     * @param $userId
     * @return array
     */
    public function addInfoPerfect($userId){
        //查找是否领取过完善资料成长值
        $num = self::find()->where(['user_id'=>$userId, 'growth_source'=>5])->count(1);
        if($num > 0){
            return ['code' => 109, 'msg' => '已经领取过完善资料成长值！'];
        }
        //判断是否完善资料
        $fields = 'user_name, user_pic, address, city, province, area, cust_no';
        $user = User::find()->select($fields)->where(['user_id' => $userId])->one();
        if(strrpos($user->user_name,'gl')===false && $user->user_name && $user->user_pic && $user->address && $user->city && $user->province && $user->area){
            //5=完善资料送成长值
            $res = $this->updateGrowth($user->cust_no, '', 5);
            return ['code' => $res['code'], 'msg' => $res['msg']];
        } else {
            return ['code' => 110, 'msg' => '资料不完善！'];
        }
    }

    /**
     * 用户实名认证送成长值
     * @param $userId
     * @param $authen_status  0：未认证 1:认证成功 2：待审核 3：审核失败
     * @return array|bool
     */
    public function addRealName($userId, $authen_status=''){
        if($authen_status != 1){
            return false;
        }
        //查找是否领取过实名认证成长值
        $num = self::find()->where(['user_id'=>$userId, 'growth_source'=>6])->count(1);
        if($num > 0){
            return ['code' => 109, 'msg' => '已经领取过实名认证成长值！'];
        }
        //6=实名认证
        $user = (new Query())->select('cust_no')->from('user')->where(['user_id'=>$userId])->one();
        $res = $this->updateGrowth($user['cust_no'], '', 6);
        return $res;
    }

}
