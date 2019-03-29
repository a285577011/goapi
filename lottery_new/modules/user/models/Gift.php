<?php

namespace app\modules\user\models;

use Yii;
use yii\db\Expression;
/**
 * This is the model class for table "gift".
 *
 * @property integer $gift_id
 * @property string $gift_code
 * @property string $gift_name
 * @property integer $gift_category
 * @property integer $type
 * @property string $batch
 * @property integer $gift_level
 * @property string $gift_glcoin
 * @property string $gift_picture
 * @property string $gift_picture2
 * @property integer $in_stock
 * @property integer $exchange_nums
 * @property string $start_date
 * @property string $end_date
 * @property integer $status
 * @property string $agent_code
 * @property string $agent_name
 * @property string $gift_remark
 * @property integer $opt_id
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class Gift extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'gift';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['gift_code', 'gift_name', 'gift_category', 'gift_glcoin', 'start_date', 'end_date', 'gift_remark'], 'required'],
            [['gift_category', 'type', 'gift_level', 'in_stock', 'exchange_nums', 'status', 'opt_id'], 'integer'],
            [['gift_glcoin'], 'number'],
            [['start_date', 'end_date', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['gift_code'], 'string', 'max' => 25],
            [['gift_name', 'agent_code', 'agent_name'], 'string', 'max' => 50],
            [['batch'], 'string', 'max' => 20],
            [['gift_picture', 'gift_picture2'], 'string', 'max' => 100],
            [['gift_remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'gift_id' => 'Gift ID',
            'gift_code' => 'Gift Code',
            'gift_name' => 'Gift Name',
            'gift_category' => 'Gift Category',
            'type' => 'Type',
            'batch' => 'Batch',
            'gift_level' => 'Gift Level',
            'gift_glcoin' => 'Gift Glcoin',
            'gift_picture' => 'Gift Picture',
            'gift_picture2' => 'Gift Picture2',
            'in_stock' => 'In Stock',
            'exchange_nums' => 'Exchange Nums',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'status' => 'Status',
            'agent_code' => 'Agent Code',
            'agent_name' => 'Agent Name',
            'gift_remark' => 'Gift Remark',
            'opt_id' => 'Opt ID',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 获取礼品列表
     * @param $page     当前分页
     * @param $size     每页条数
     * @return array
     */
    public function getGiftLists($page, $size){

        $date = date('Y-m-d H:i:s');
        $total = self::find()->where(['status'=>1])->andWhere(['>','in_stock',0])->andWhere(['>','end_date',$date])->count(1);
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $fields = 'gift_id, gift_code, gift_name, gift_glcoin, gift_level, in_stock, exchange_nums, gift_picture, in_stock, gift_remark, start_date, end_date';
        $data = self::find()->select($fields)
            ->where(['status'=>1])
            ->andWhere(['>','in_stock',0])
            ->andWhere(['>','end_date',$date])
            ->asArray()
            ->offset($offset)
            ->limit($size)
            ->orderBy('create_time desc')
            ->all();
        $date = date('Y-m-d H:i:s');
        foreach($data as &$v){
            //1个月内是新品
            $start_date = date('Y-m-d H:i:s', strtotime('+1 months',strtotime($v['start_date'])));
            if($date < $start_date){
                $v['type'] = 1;
            } else {
                $v['type'] = 0;
            }
        }
        return ['page' => $page, 'pages' => $pages, 'size' => count($data), 'total' => $total, 'data' => $data ];
    }

    /**
     * 单个礼品详情
     */
    public function getGiftById($gift_id){
        $fields = 'gift_id, gift_code, gift_name, subtitle, gift_glcoin, gift_level, gift_picture, gift_picture2, in_stock, exchange_nums, gift_remark, create_time, start_date, end_date ';
        $gift = self::find()->select($fields)
            ->where('gift_id=:gift_id',['gift_id'=>$gift_id])
            ->asArray()
            ->one();
        $gift['gift_glcoin'] = number_format($gift['gift_glcoin']);
        return $gift;
    }

    /**
     * 兑换礼品
     * @param $user_id  用户id
     * @param $gift_id  礼品码
     * @param $giftNum  礼品数量
     */
    public function exchange($user_id, $gift_id, $giftNum=1){
        $date = date('Y-m-d H:i:s');
        //查用户是否有兑换资格
        $user = User::find()->select('user.level_id, f.user_glcoin, user.user_name, user.cust_no, user.user_tel')
            ->innerJoin('user_funds f', ' f.user_id = user.user_id ')
            ->where(['user.user_id'=> $user_id])
            ->asArray()
            ->one();
        $gift = self::find()
            ->select('gift_level, gift_glcoin, gift_name, gift_code, in_stock, type, batch, start_date, end_date')
            ->where('gift_id=:gift_id',['gift_id'=>$gift_id])->asArray()->one();
        if(!$user || !$gift){
            return ['code'=>109, 'msg'=>'系统错误！'];
        }
        if($gift['start_date'] > $date){
            return ['code'=>109, 'msg'=>'活动时间未开始！'];
        }
        if($gift['end_date'] < $date){
            return ['code'=>109, 'msg'=>'活动时间已结束！'];
        }
        if($gift['gift_level'] > $user['level_id']){
            return ['code'=>109, 'msg'=>'您的等级不够！'];
        }
        if($gift['gift_glcoin'] > $user['user_glcoin']){
            return ['code'=>109, 'msg'=>'您的咕啦币不够！'];
        }
        if($gift['in_stock'] <= 0){
            return ['code'=>109, 'msg'=>'该礼品已经兑换完了！'];
        }
        //事物
        $tran = Yii::$app->db->beginTransaction();
        //生成订单号,保存至礼品兑换表
        $exch_code = 'GLGIFT'.date('YmdHis').substr(time(),-2).substr(microtime(),2,4);
        $exchange = [
            'user_id' => $user_id,
            'cust_no' => $user['cust_no'],
            'user_tel' => $user['user_tel'],
            'user_name' => $user['user_name'],
            'exch_code' => $exch_code,
            'exch_glcoin' => $gift['gift_glcoin'] * $giftNum,
            'exch_nums' => $giftNum,
            'exch_time' => date('Y-m-d H:i:s'),
            'create_time' => date('Y-m-d H:i:s'),
            'modify_time' => date('Y-m-d H:i:s'),
            'review_status' => 2,
            'review_name' => '会员俱乐部兑换',
        ];
        $res1 = \Yii::$app->db->createCommand()->insert('exchange_record', $exchange)->execute();
        if(!$res1){
            $tran->rollBack();
            return ['code' => 109, 'msg' => '兑换失败，礼品兑换保存错误！'];
        }
        $exchange_id = \Yii::$app->db->getLastInsertID();
        //保存至礼品兑换记录表
        $exgift = [
            'exchange_id' => $exchange_id,
            'exch_code' => $exch_code,
            'gift_code' => $gift['gift_code'],
            'gift_name' => $gift['gift_name'],
            'gift_nums' => $giftNum,                        //礼品数量
            'exch_int' => $gift['gift_glcoin'],             //单价所需咕啦币
            'all_int' => $gift['gift_glcoin'] * $giftNum,   //总共需要的咕啦币
            'create_time' => date('Y-m-d H:i:s'),
        ];
        //添加礼品兑换记录表子表
        $res2 = \Yii::$app->db->createCommand()->insert('exgift_record', $exgift)->execute();
        if(!$res2){
            $tran->rollBack();
            return ['code' => 109, 'msg' => '兑换失败，礼品兑换记录保存错误！'];
        }
        //更新咕啦币明细表、资金表
        $userGlCoin = new UserGlCoinRecord();
        $data = [
            'coin_source'=> 4,
            'order_code'=>$exch_code,
            'coin_value' => $exchange['exch_glcoin'],   //咕啦币总支出
            'type' => 2,
            'order_source' => 7,
            'remark' => '兑换：'.$gift['gift_name'],
        ];
        $res3 = $userGlCoin -> updateGlCoin($user['cust_no'], $data);
        if($res3['code'] != 600){
            $tran->rollBack();
            return ['code' => 109, 'msg' => $res3['msg']];
        }
        //更新礼品表库存
        $data = [
            'in_stock' => $gift['in_stock'] - $giftNum,
            'exchange_nums'=>new Expression('exchange_nums+'.$giftNum)
        ];
        $res4 = \Yii::$app->db->createCommand()->update('gift', $data, ['gift_id' => $gift_id])->execute();
        if(!$res4){
            $tran->rollBack();
            return ['code' => 109, 'msg' => '兑换失败，更新礼品错误！'];
        }
        //发放礼品(目前只有优惠券) type:礼品类型：1=优惠券，2=礼品卡，3=实物
        if($gift['type'] == 1){
            $res5 = $this -> giveCoupons($gift['batch'], $user['cust_no'], $giftNum);
        } else {
            $res5 = false;
        }
        if(!$res5){
            $tran->rollBack();
            return ['code' => 109, 'msg' => '兑换错误，发放优惠券失败！'];
        }
        $tran->commit();
        return ['code' => 600, 'msg' => '兑换成功！'];
    }

    /**
     * 发放优惠券到用户账户
     * @param $batch     优惠券批次
     * @param $cust_no   用户编号
     * @param $giftNum   发放礼品数
     * return bool
     */
    public function giveCoupons($batch, $cust_no, $giftNum){
        //查兑换的优惠券
        $where = [
            'send_user'=> NULL,
            'use_status'=> 0,                  //使用状态: 0:未领取:1领取未使用 2领取已使用
            'send_status'=> 1,                 //发送状态:1未发送
            'coupons_batch'=> $batch,          //优惠券批次
            'status'=> 1,                      //1=激活
        ];
        $coupons = CouponsDetail::find()->select('coupons_detail_id')
            ->where($where)
            ->asArray()
            ->orderBy('coupons_detail_id')
            ->one();
        //给用户发券
        $data = ['send_status'=>2,'send_user' => $cust_no, 'send_time' => date('Y-m-d H:i:s'),'use_status'=>1];
        $where = ['coupons_detail_id' => $coupons['coupons_detail_id'],];
        $bool = \Yii::$app->db->createCommand()->update('coupons_detail', $data, $where)->execute();
        //发放优惠券总数自增1
        $map=['batch'=> $batch];
        $res = \Yii::$app->db->createCommand()->update('coupons', ['send_num'=>new Expression('send_num+'.$giftNum)], $map)->execute();
        if($bool && $res){
            return 1;
        } else {
            return 0;
        }
    }

}
