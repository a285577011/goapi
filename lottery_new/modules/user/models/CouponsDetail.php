<?php

namespace app\modules\user\models;

use Yii;
use yii\db\Expression;
use yii\db\Query;
use app\modules\user\helpers\UserTool;
use app\modules\common\models\CouponsActivity;
use app\modules\common\models\Activity;

/**
 * 用户优惠券
 *
 * @property integer $coupons_detail_id
 * @property string $coupons_no
 * @property string $coupons_batch
 * @property integer $send_status
 * @property string $send_user
 * @property string $send_time
 * @property integer $use_status
 * @property string $use_time
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
class CouponsDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'coupons_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['coupons_no', 'coupons_batch'], 'required'],
            [['send_status', 'use_status', 'status'], 'integer'],
            [['send_time', 'use_time', 'create_time', 'update_time'], 'safe'],
            [['coupons_no', 'send_user'], 'string', 'max' => 20],
            [['coupons_batch'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'coupons_detail_id' => 'Coupons Detail ID',
            'coupons_no' => 'Coupons No',
            'coupons_batch' => 'Coupons Batch',
            'send_status' => 'Send Status',
            'send_user' => 'Send User',
            'send_time' => 'Send Time',
            'use_status' => 'Use Status',
            'use_time' => 'Use Time',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    //优惠券编码兑换 (会员俱乐部用）
    public function getCouponsOn($conversion_code, $custNo){
        //查兑换的优惠券
        $where = [
            'send_user'=> NULL,
            'use_status'=> 0,                  //使用状态:1未使用
            'send_status'=> 1,                 //发送状态:1未发送
            'status'=> 1,                      //1=激活
            'conversion_code'=> $conversion_code,         //优惠券编码
        ];
        $coupons = self::find()->select('coupons_batch,coupons_detail_id')->where($where)->one();
        if(empty($coupons)) return ['code' => 109, 'msg' => '该优惠券号无效！'];
        //给用户发券
        $data = ['send_status'=>2,'send_user' => $custNo, 'send_time' => date('Y-m-d H:i:s'), 'use_status'=>1];
        $where = ['coupons_detail_id' => $coupons['coupons_detail_id'],];
        $bool = \Yii::$app->db->createCommand()->update('coupons_detail', $data, $where)->execute();
        //发放优惠券总数自增1
        $map=['batch'=> $coupons['coupons_batch']];
        $res = \Yii::$app->db->createCommand()->update('coupons', ['send_num'=>new Expression('send_num+1')], $map)->execute();
        if($bool && $res){
            return ['code' => 600, 'msg' => '兑换成功'];
        } else {
            return ['code' => 109, 'msg' => '优惠券领取错误，请稍候再试！'];
        }
    }

    //用户优惠券数量(会员俱乐部用）
    public function userCouponNum($custNo){
        $query = new Query();
        $coupons = $query->select('coupons_detail.use_status,c.end_date ')->from('coupons_detail')
            ->innerJoin('coupons c', ' c.batch = coupons_detail.coupons_batch ')
            ->where(['coupons_detail.send_user' => $custNo, 'coupons_detail.status'=>1])
            ->all();
//        if(empty($coupons)){
//            return ['code' => 109, 'msg' => '暂无优惠券'];
//        }
        $unused = 0;   //未使用
        $used = 0;     //已使用
        $overdue = 0;  //过期的
        $date = date('Y-m-d H:i:s');
        foreach($coupons as $k=>$v){
            if($date >= $v['end_date'] && in_array($v['use_status'], [0,1])){
                $overdue++;
                continue;
            }
            if($v['use_status'] == 2){
                $used++;
                continue;
            }
            if($v['use_status'] == 1) $unused++;
        }
        $unused = $unused > 99 ? '99+' : $unused ;
        $used = $used > 99 ? '99+' : $used ;
        $overdue = $overdue > 99 ? '99+' : $overdue ;
        return ['unusedNum' => $unused, 'usedNum' => $used, 'overdueNum' => $overdue ];
    }

    /**
     * 用户优惠券列表(会员俱乐部用）
     * @param $custNo
     * @param $page
     * @param $size
     * @param $type  1=未使用，2=使用记录，3=已过期
     * @return array
     */
    public function userCouponLists($custNo, $page, $size, $type){
        $where = ['coupons_detail.status'=>1, 'coupons_detail.send_user'=>$custNo];
        $date = date('Y-m-d H:i:s');
        $andWhere='';
        switch ($type){
            case 1:
                $where['coupons_detail.use_status'] = 1;
                break;
            case 2:
                $where['coupons_detail.use_status'] = 2;
                break;
            case 3:
                $where['coupons_detail.use_status'] = [0,1];
                break;
        }
        $query= new Query();
        $query->from('coupons_detail')
//            ->innerJoin('coupons c', ' c.batch = coupons_detail.coupons_batch ')
//            ->leftJoin('lottery_category l1', ' l1.lottery_category_id = c.use_range ')
            ->where($where);
        if($andWhere){
            $query->andWhere(['<=', 'c.end_date', $date]);
        }
        $total = $query->count(1);      //总数

        $query= new Query();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $fields = 'coupons_detail.coupons_detail_id, ';
        $fields .= 'c.start_date,c.end_date,c.less_consumption,c.reduce_money,c.use_range,c.coupons_name,c.type,';
        $fields .= 'l.cp_category_name';
        $query->select($fields)->from('coupons_detail')
            ->innerJoin('coupons c', ' c.batch = coupons_detail.coupons_batch ')
            ->leftJoin('lottery_category l', ' l.lottery_category_id = c.use_range ')
            ->where($where);

        switch ($type){
            case 1:
                $query->andWhere(['>', 'c.end_date', $date]);
                break;
            case 3:
                $query->andWhere(['<', 'c.end_date', $date]);
                break;
        }
        $coupons = $query->orderBy('coupons_detail.send_time desc')->offset($offset)->limit($size)->all();    //数据
        foreach($coupons as &$v){
            if($v['use_range'] == 0){
                $v['cp_category_name'] = '购彩使用';
            }elseif($v['use_range'] == 100){
                $v['cp_category_name'] = '购买文章';
            }elseif($v['use_range'] == 101){
                $v['cp_category_name'] = '全场通用';
            }
            if($type == 1){         //优惠券是否可用
                $v['usable'] = 1;
            } else {
                $v['usable'] = 0;
            }
        }
        return ['page' => $page, 'pages' => $pages, 'size' => count($coupons), 'total' => $total, 'data' => $coupons ];
    }


    /**
     * 订单验证优惠券是否可用(支付接口用）
     * @param string $custNo  用户编号
     * @param array $coupons  优惠券编码(一维:conversion_code)
     * @param $lottery_code   彩种表编码（查lottery_category_id 关联 lottery_category表）100购买文章 其他购买彩票
     * @param $price   付款金额
     */
    public function checkCoupon($custNo ,array $coupons, $lottery_code, $price){
        if(empty($coupons) || empty($custNo) || !isset($lottery_code) || empty($price)){
            return ['code'=>109,'msg'=>'参数错误！'];
        }

        //得到优惠券id
        $query = new Query();
        $date = date('Y-m-d H:i:s');
        //查优惠券是不是该用户的,和使用的彩种
        $fields = 'c.use_range, d.conversion_code, c.end_date, c.less_consumption, c.reduce_money, c.stack_use, c.days_num, c.batch, c.start_date, c.coupons_name';
        $query -> from('coupons_detail d')->select($fields)
            ->innerJoin('coupons c', ' c.batch = d.coupons_batch ')
            ->where(['d.send_user'=>$custNo, 'd.conversion_code'=>$coupons, 'd.use_status'=>1]);
        if(count($coupons)>=2){
            $query -> andWhere(['c.stack_use'=>1]);
        }
        $row = $query->all();
        if(!$row){
            return ['code'=>109,'msg'=>'优惠券已使用！'];
        }
        $batch = array_unique(array_column($row, 'batch'));
        if(count($batch)>1){
            return ['code'=>109,'msg'=>'不同种优惠券不能同时使用！'];
        }
        //查优惠券是否可以叠加使用
        if(count($coupons) != count($row)){
            return ['code'=>109,'msg'=>'该种优惠券不能同时使用！'];
        }
        //当日可用的张数
        $can_use_num = $row[0]['days_num'];
        $batch = $row[0]['batch'];
        //判断当日已使用张数
        if($can_use_num != 0){
            $start_date = date('Y-m-d 00:00:00');
            $end_date = date('Y-m-d 23:59:59');
            //当日使用的张数
            $day_use_num = (new Query()) -> from('coupons_detail')
                -> where(['send_user'=>$custNo,'coupons_batch' => $batch])
                -> andWhere(['>=','use_time',$start_date])
                -> andWhere(['<=','use_time',$end_date])
                -> count(1);
            //总使用张数（当日已使用+即将使用的）
            $totle_use_num = $day_use_num + count($row);
            if($can_use_num < $totle_use_num){
                return ['code'=>109,'msg'=>'该种优惠券每日最多只能使用'.$can_use_num.'张！'];
            }
        }
        $use_coupons = [];      //无限制的券
        $unuse_coupons = [];    //还需要查是否可用的券
        foreach($row as $k=>$v){
            //时间
            if($v['start_date'] > $date){
                return ['code'=>109,'msg'=>'优惠券使用时间未开始！'];
            }
            if($v['end_date'] <= $date){
                return ['code'=>109,'msg'=>'优惠券已过期！'];
            }
            //价格
            if($v['less_consumption'] > $price){
                return ['code'=>109,'msg'=>'优惠券使用金额条件不足！'];
            }
            //使用限制
            if($v['use_range'] == 101 ){
                $use_coupons[] = [          //可以用的券
                    'conversion_code'=>$v['conversion_code'],
                    'use_range'=>(int)$v['use_range'],
                    'less_consumption'=>$v['less_consumption'],
                    'reduce_money'=>$v['reduce_money'],
                    'start_date'=>$v['start_date'],
                    'end_date'=>$v['end_date'],
                    'stack_use'=>$v['stack_use'],
                    'coupons_name'=>$v['coupons_name'],
                    'batch'=>$v['batch'],
                ];
            } else {
                $unuse_coupons[] = [
                    'conversion_code'=>$v['conversion_code'],
                    'use_range'=>(int)$v['use_range'],
                    'less_consumption'=>$v['less_consumption'],
                    'reduce_money'=>$v['reduce_money'],
                    'start_date'=>$v['start_date'],
                    'end_date'=>$v['end_date'],
                    'stack_use'=>$v['stack_use'],
                    'coupons_name'=>$v['coupons_name'],
                    'batch'=>$v['batch'],
                ];
            }
        }
        if(empty($unuse_coupons)){
            return ['code'=>600,'msg'=>'成功！', 'data'=>$use_coupons];
        }
        //判断购买彩票还是文章
        if($lottery_code!=100){
            //查彩种的分类id,父id
            $cateDate = self::getLotteryCategory($lottery_code);
            if(empty($cateDate)){
                return ['code'=>109,'msg'=>'彩种错误！'];
            }
            //对比这个分类id
            foreach($unuse_coupons as $u_k=>$u_v){
                if($u_v['use_range'] === 0 || $u_v['use_range'] == $cateDate['lottery_category_id'] || $u_v['use_range'] == $cateDate['parent_id']|| $u_v['use_range'] ==101  ){
                    $use_coupons[] = $u_v;
                } else{
                    return ['code'=>109,'msg'=>'彩种错误！'];
                }
            }
        }else{
            foreach($unuse_coupons as $u_k=>$u_v){
                if($u_v['use_range'] == 100 || $u_v['use_range'] == 101){
                    $use_coupons[] = $u_v;
                } else{
                    return ['code'=>109,'msg'=>'优惠券不可用于购买文章！'];
                }
            }
        }

        return ['code'=>600,'msg'=>'成功！', 'data'=>$use_coupons];
    }

    /**
     * 下单前优惠券总数(支付接口用）
     * @param $custNo
     * @param $lottery_code 100购买文章  其他购买彩票
     * @param $price
     * @param $type  1=可用的 2=不可用 3=可用 + 不可用
     * @return array
     */
    public function couponsNum($custNo, $lottery_code, $price, $type=3){
        //查彩种的分类id,父id
        $cateDate = self::getLotteryCategory($lottery_code);
        $cateDate[] = 0;
        $cateDate[] = 101;
        $date = date('Y-m-d H:i:s');
        $unUse = false;
        $canUse = false;
        if($type == 1 || $type ==3){
            $query = new Query();
            $query -> from('coupons_detail d')
                ->innerJoin('coupons c', ' c.batch = d.coupons_batch ')
                ->where(['d.send_user'=>$custNo, 'd.use_status'=>1]);
            //购买文章
            if($lottery_code!=100){
                $query -> andWhere(['in','c.use_range',$cateDate]);
            }elseif($lottery_code==100){
                $query -> andWhere(['in','c.use_range',[100,101]]);
            }
            $query -> andWhere(['<=', 'c.less_consumption', $price]);
            $query -> andWhere(['<','c.start_date',$date]);
            $query -> andWhere(['>','c.end_date',$date]);
            $canUse = $query -> count();
        }
        if($type == 2 || $type ==3) {
            $query = new Query();
            $query -> from('coupons_detail d')
                ->innerJoin('coupons c', ' c.batch = d.coupons_batch ')
                ->where(['d.send_user'=>$custNo, 'd.use_status'=>1 ]);
            if($lottery_code!=100){
                $query->andWhere(
                    array('or',"c.use_range not in({$cateDate['lottery_category_id']},{$cateDate['parent_id']},0,101)","c.less_consumption>{$price}","c.start_date>'{$date}'")
                );
            }elseif($lottery_code==100){
                $query->andWhere(
                    array('or', "c.use_range not in (100,101)","c.less_consumption>{$price}","c.start_date>'{$date}'")
                );
            }
            //$query -> andWhere(['<>','c1.use_range',$cateDate['lottery_category_id']]);
            //$query -> andWhere(['<>','c1.use_range',$cateDate['parent_id']]);
            //$query -> andWhere(['<','c1.start_date',$date]);
            $query -> andWhere(['>','c.end_date',$date]);
            //$query -> orWhere(['>', 'c1.less_consumption', $price]);
            $unUse = $query-> count();
        }
        return ['code'=>600, 'data'=>['canUse' => $canUse, 'unUse' => $unUse]];
    }

    /**
     * 下单前可使用优惠券列表(支付接口用）
     * @param $custNo       用户编号
     * @param $lottery_code 彩种编码  100购买文章 其他购买彩票
     * @param $price        价格
     * @param $type         筛选类型：1=可用，2=不可用
     * @param $page         当前页
     */
    public function orderUseCoupon($custNo, $lottery_code, $price, $type=1, $page, $size=10){
        if(empty($custNo) || empty($lottery_code) || empty($price)){
            return ['code'=>109,'msg'=>'参数错误！'];
        }
        //查彩种的分类id,父id
        $cateDate = self::getLotteryCategory($lottery_code);
        $cateDate[] = 0;
        $cateDate[] = 101;
        $date = date('Y-m-d H:i:s');
        $query = new Query();
        //分页：统计数量
        $query -> from('coupons_detail d')
            ->innerJoin('coupons c', ' c.batch = d.coupons_batch ')
            ->where(['d.send_user'=>$custNo, 'd.use_status'=>1 ]);
        if($type == 1){
            if($lottery_code==100){
                $query -> andWhere(['in','c.use_range',[100,101]]);
            }else{
                $query -> andWhere(['in','c.use_range',$cateDate]);
            }
            $query -> andWhere(['<=', 'c.less_consumption', $price]);
            $query -> andWhere(['<','c.start_date',$date]);
        } else {
            if($lottery_code==100){
                $query->andWhere(
                    array('or', "c.use_range not in (100,101)","c.less_consumption>{$price}","c.start_date>'{$date}'")
                );
            }else{
                $query->andWhere(array('or', "c.use_range not in({$cateDate['lottery_category_id']},{$cateDate['parent_id']},0,101)","c.less_consumption>{$price}","c.start_date>'{$date}'"));
            }
        }
        $query -> andWhere(['>','c.end_date',$date]);
        $total = $query -> count(1);

        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        if(!$total){
            return ['code'=>600, 'data'=>['page' => $page, 'pages' => 0, 'size' => $size , 'total' => $total, 'data' => '' ]];
        }
        //数据列表
        $query = new Query();
        $fields = 'c.coupons_name, d.conversion_code,c.use_range, c.start_date, c.end_date, c.less_consumption, c.reduce_money, c.stack_use, c.batch, c.stack_use ';
        $query -> from('coupons_detail d')->select($fields)
            ->innerJoin('coupons c', ' c.batch = d.coupons_batch ')
            ->where(['d.send_user'=>$custNo, 'd.use_status'=>1]);
        if($type == 1){
            if($lottery_code==100){
                $query -> andWhere(['in','c.use_range',[100,101]]);
            }else{
                $query -> andWhere(['in','c.use_range',$cateDate]);
            }
            $query -> andWhere(['<=', 'c.less_consumption', $price]);
            $query -> andWhere(['<','c.start_date',$date]);
        } else {
            if($lottery_code==100){
                $query->andWhere(array('or', "c.use_range not in (100,101)","c.less_consumption>{$price}","c.start_date>'{$date}'"));
            }else{
                $query->andWhere(array('or', "c.use_range not in({$cateDate['lottery_category_id']},{$cateDate['parent_id']},0,101)","c.less_consumption>{$price}","c.start_date>'{$date}'"));
            }
        }
        $query -> andWhere(['>','c.end_date',$date]);
        $query -> orderBy('coupons_detail_id desc') -> offset($offset) ->limit($size);
        $row = $query -> all();
        if($row){
            //获得彩种分类名
            $lottery_category = (new Query()) -> from('lottery_category') -> select('lottery_category_id, cp_category_name') -> all();
            foreach($row as $rk => &$rv){
                if($rv['use_range'] == 0){
                    $rv['cp_category_name'] = '全部彩种';
                    continue;
                }elseif($rv['use_range'] == 100){
                    $rv['cp_category_name'] = '文章购买';
                    continue;
                }elseif($rv['use_range'] == 101){
                    $rv['cp_category_name'] = '全场通用';
                    continue;
                } else {
                    foreach($lottery_category as $lk => $lv){
                        if($lv['lottery_category_id'] == $rv['use_range']) {
                            $rv['cp_category_name'] = $lv['cp_category_name'];
                        }
                    }
                }
            }
        }
        return ['code'=>600, 'data'=>['page' => $page, 'pages' => $pages, 'size' => count($row) , 'total' => $total, 'data' => $row ] ];
    }

    /**
     * 根据彩种编码查彩种分类id,父id
     * @param $lottery_code 彩种编码
     */
    public static function getLotteryCategory($lottery_code){
        $cateDate = (new Query())->from('lottery')->select('lottery_category.lottery_category_id, lottery_category.parent_id')
            ->innerJoin('lottery_category', ' lottery_category.lottery_category_id = lottery.lottery_category_id')
            ->where(['lottery.lottery_code'=>$lottery_code])->one();
        return $cateDate;
    }

    /**
     * 退换星星币 or 优惠券(支付接口用）
     * @param $order_code       订单编号
     * @param $custNo
     * @param string $glCion    星星币
     * @param array $coupons   优惠券
     * @return array
     */
    public function refundPrep($order_code,$custNo, $glCion='', $coupons='', $remark='退款'){
        if(empty($glCion) && empty($coupons) || empty($order_code) || empty($custNo)){
            return ['code'=>109,'msg'=>'参数错误！'];
        }
        $query = new Query();
        //退星星币
//        if($glCion){
//            $nowYear = date('Y');
//            $order = $query -> from('lottery_order')->select('create_time,source')->where(['lottery_order_code'=>$order_code])->one();
//            $orderYear = substr($order['create_time'],0,4);
//            if(($nowYear-$orderYear >= 2)){
//                return ['code' => 600, 'msg' => '订单超过2年！'];
//            }
//            $data = [
//                'coin_source'=> 5,
//                'order_code' => $order_code,
//                'coin_value' => (int)$glCion ,     //星星币
//                'type' => 1,                       //1=收入，2=支出
//                'order_source' => $order['source'],
//                'remark' => $remark,
//            ];
//            $UserGlCoinRecord = new UserGlCoinRecord();
//            $res = $UserGlCoinRecord->updateGlCoin($custNo, $data);
//            if($res['code']===600){
//                return ['code' => 600, 'msg' => '操作成功！'];
//            } else {
//                return ['code' => 109, 'msg' => $res['msg']];
//            }
//        }
        //退优惠券
        if($coupons){
            //减少使用数量
            $batch = $query -> from('coupons_detail')->select('coupons_batch')->where(['conversion_code' => $coupons])->one();
            $condition = ['batch'=>$batch['coupons_batch']];
            $update = ['use_num'=>new Expression('use_num-'.count($coupons))];
            $ret = \Yii::$app->db->createCommand()->update('coupons', $update, $condition)->execute();

            $update = [
                'use_status' => 1,      //1领取未使用
                'use_order_code' => '',
                'use_order_source'=>'',
                'use_time'=>'',
                'status'=>1,
            ];
            $condition = ['conversion_code' => $coupons,'send_user'=>$custNo];
            $res = \Yii::$app->db->createCommand()->update('coupons_detail', $update, $condition)->execute();
            if($res){
                return ['code' => 600, 'msg' => '退还优惠券成功！'];
            } else {
                return ['code' => 109, 'msg' => '退还优惠券失败！'];
            }
        }
    }

    /**
     * 订单使用了优惠券后改变优惠券状态(支付接口用）
     * @param $order_code           订单id
     * @param $custNo
     * @param array $coupons        优惠券(一维)
     * @param $type  1 文章购买 空则为购彩
     */
    public function changeCouponsStatus($order_code, $custNo, array $coupons,$type=''){
        if(empty($order_code) || empty($coupons) || empty($custNo)){
            return ['code'=>109,'msg'=>'参数错误！'];
        }
        $query = new Query();
        //区分购彩还是购买文章
        if($type==''){
            $order = $query -> from('lottery_order')->select('source')->where(['lottery_order_code'=>$order_code])->one();
        }else{
            $order['source'] = 7;
        }

        //增加使用数量
        $batch = $query -> from('coupons_detail')->select('coupons_batch')->where(['conversion_code' => $coupons])->one();
        $condition = ['batch'=>$batch['coupons_batch']];
        $update = ['use_num'=>new Expression('use_num+'.count($coupons))];
        $ret = \Yii::$app->db->createCommand()->update('coupons', $update, $condition)->execute();
        //修改优惠券状态
        $update = [
            'use_status' => 2,
            'use_order_code' => $order_code,
            'use_time'=> date('Y-m-d H:i:s'),
            'use_order_source'=> $order['source'],
        ];
        $condition = ['conversion_code' => $coupons,'send_user'=>$custNo];
        $res = \Yii::$app->db->createCommand()->update('coupons_detail', $update, $condition)->execute();
        if($res){
            return ['code' => 600, 'msg' => '优惠券更新成功'];
        } else {
            return ['code' => 109, 'msg' => '优惠券更新失败'];
        }
    }

    /**
     * 根据代理商赠送给用户对应的活动优惠券
     * @param $agentCode          代理商码
     * @param $type               活动类型 1注册
     * @param $cust_no            用户编号
     */
    public static function activitySendCoupons($agentCode,$type,$cust_no){
        $now = date('Y-m-d H:i:s');
        $couponsAry = Activity::find()
            ->where(["use_agents"=>$agentCode,"type_id"=>$type,"status"=>1])
            ->andWhere(['and',["<=","start_date",$now],[">","end_date",$now]])
            ->asArray()
            ->one();
        if(!empty($couponsAry)){
            $detailAry = CouponsActivity::find()->where(["activity_id"=>$couponsAry["activity_id"],"status"=>1])->asArray()->all();
            if(!empty($detailAry)){
                foreach ($detailAry as $k =>$v){
                    $userAry = UserTool::getUserAry($cust_no,$v["send_num"]);
                    $res = UserTool::regSendCoupons($v["batch"],$userAry);
                    if($res["code"]!=600){
                        KafkaService::addLog("sendCoupons-error",$cust_no.$res["msg"]);
                    }
                }
            }
            return $res;
        }
        return true;
    }

    /**
     * 统计某个批次某个用户已领取的张数
     */
    public static function getUserCouponsNum($batch,$cust_no){
        $start_time = date("Y-m-d")." 00:00:00";
        $end_time = date("Y-m-d")." 23:59:59";
        $sendNum = (new Query()) -> from('coupons_detail')
            -> where(['send_user'=>$cust_no,'coupons_batch' => $batch])
            -> andWhere(['>=','create_time',$start_time])
            -> andWhere(['<=','create_time',$end_time])
            -> count();
        return $sendNum;
    }
    /**
     * 根据代理商、活动类型获取优惠券批次
     * @param $agentCode          代理商码
     * @param $type               活动类型 1注册
     */
    public static function getActivityBatch($agentCode,$type){
        $now = date('Y-m-d H:i:s');
        $couponsAry = Activity::find()
            ->where(["use_agents"=>$agentCode,"type_id"=>$type,"status"=>1])
            ->andWhere(['and',["<=","start_date",$now],[">","end_date",$now]])
            ->asArray()
            ->one();
        if(!empty($couponsAry)){
            $detailAry = CouponsActivity::find()->where(["activity_id"=>$couponsAry["activity_id"],"status"=>1])->asArray()->all();
        }else{
            $detailAry=[];
        }
        return $detailAry;
    }
}
