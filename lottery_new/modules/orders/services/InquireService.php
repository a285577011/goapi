<?php

namespace app\modules\orders\services;

use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Constants;
use app\modules\competing\helpers\CompetConst;
use yii\db\Query;
use app\modules\common\models\LotteryAdditional;
use app\modules\common\services\ScheduleService;
use app\modules\common\services\PayService;
use app\modules\common\models\Lottery;
use app\modules\common\models\LotteryRecord;

class InquireService {

    /**
     * 获取投注订单列表 
     * @param type $post 前端传的参数
     * @param type $custNo 会员编号
     * @param type $pn 当前页码
     * @param type $pageSize 当前页条数
     * @return type
     */
    public static function getOrderList($post, $custNo, $pn, $pageSize, $sourceType) {
        $status1 = [
            '1' => '未支付',
            '2' => '处理中',
            '3' => '待开奖',
            '4' => '中奖',
            '5' => '未中奖',
            '6' => '出票失败',
            '9' => '出票失败',
            '10' => '出票失败',
            '11' => '等待出票'
        ];
        $status2 = [
            '1' => '未支付',
            '2' => '招募中',
            '3' => '处理中',
            '4' => '待开奖',
            '5' => '未中奖',
            '6' => '中奖',
            '7' => '未满员撤单',
            '8' => '方案失败',
            '9' => '出票失败',
            '10' => '出票失败',
            '11' => '出票失败',
            '12' => '等待出票'
        ];
        $source1 = [
            '1' => '自购',
            '2' => '追号',
            '3' => '赠送',
            '5' => '分享',
            '6' => '计划',
            '7' => '接口'
        ];
        $source2 = [
            '1' => '合买',
            '2' => '定制合买'
        ];
        $competing = Constants::MADE_FOOTBALL_LOTTERY;
        $basketballs = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdMade = CompetConst::MADE_BD_LOTTERY;
        $where = ['cust_no' => $custNo];
        $jWhere = [];
        if (isset($post["order_status"]) && $post["order_status"] != 0) {
            if ($post["order_status"] == 6) {
                $andwhere1 = ["in", "status", [7, 8, 9, 10, 11]];
                $andwhere = ["in", "status", [6, 9, 10, 11]];
            } else {
                $andwhere["status"] = $post["order_status"];
                if ($post["order_status"] == 4) {
                    $andwhere1["status"] = 6;
                } else if ($post["order_status"] > 1) {
                    $andwhere1["status"] = $post["order_status"] + 1;
                } else {
                    $andwhere1["status"] = $post["order_status"];
                }
            }
        } else {
            $andwhere = ["!=", "status", 1];
            $andwhere1 = ["!=", "status", 1];
        }
        $qWhere = [];
        if(!empty($sourceType)){
            if ($sourceType == 4) {
                $qWhere = ['!=', 'q.record_type', 1];
            } elseif ($sourceType == 12) {//电视投注记录特有
                $qWhere = ['and', ['q.record_type' => 1], ['in', 'q.source', [1,2]]];
            } else {
                $qWhere['q.source'] = $sourceType;
                $qWhere['q.record_type'] = 1;
            }
        }
        $query = LotteryOrder::find();
        $queryOrder = $query->select(['lottery_id', 'lottery_order_code', 'lottery_name', 'periods', 'bet_money', 'status', 'source', 'record_type', 'create_time', 'win_amount', 'deal_status', 'bet_val', 'play_name'])
                ->where($where)
                ->andWhere($andwhere)
                ->andWhere(["!=", "source", 4]);
        $queryProUser = (new Query())->select(['lottery_code lottery_id', 'programme_code lottery_order_code', 'lottery_name', 'periods', 'bet_money', 'status', 'buy_type source', 'record_type', 'create_time', 'win_amount', 'deal_status', 'buy_number', 'create_time'])
                ->from("programme_user")
                ->where($where)
                ->andWhere($andwhere1);
        $queryAll = (new Query())->select("q.*,l.lottery_pic")->from("(({$queryOrder->createCommand()->getRawSql()}) union {$queryProUser->createCommand()->getRawSql()}) q")->leftJoin('lottery as l', 'l.lottery_code = q.lottery_id');
        if ($post['order_status'] == 1) {
            $queryAll->leftJoin('pay_record pr', 'pr.order_code = q.lottery_order_code');
            $jWhere = ['pr.status' => 0];
        }
        $count = $queryAll->andWhere($jWhere)->andWhere($qWhere)->count();
        $pageCount = ceil($count / $pageSize);
        $offset = ($pn - 1) * $pageSize;
        $order = $queryAll->andWhere($jWhere)
                ->andWhere($qWhere)
                ->limit($pageSize)
                ->offset($offset)
                ->orderBy('q.create_time desc')
                ->all();
        foreach ($order as &$val) {
            $codeStr = '';
            $playStr = '';
            if (in_array($val['lottery_id'], $competing)) {
                $val['lottery_name'] = '竞彩足球';
                if ($val['record_type'] == 1) {
                    $scheduleService = new ScheduleService();
                    $codeStr = $scheduleService->getScheduleCode($val['lottery_id'], $val['bet_val'], '3000');
                    $playStr = $val['play_name'];
                }
            } elseif (in_array($val['lottery_id'], $basketballs)) {
                $val['lottery_name'] = '竞彩篮球';
                if ($val['record_type'] == 1) {
                    $scheduleService = new ScheduleService();
                    $codeStr = $scheduleService->getScheduleCode($val['lottery_id'], $val['bet_val'], '3100');
                    $playStr = $val['play_name'];
                }
            } elseif (in_array($val['lottery_id'], $bdMade)) {
                $val['lottery_name'] = '北京单场';
                $playStr = $val['play_name'];
            }
            if ($val["record_type"] == 1) {
                if ($val['deal_status'] == 3) {
                    $val['deal_status_name'] = '已派奖';
                } elseif ($val['deal_status'] == 1 && $val['status'] == 4) {
                    $val['deal_status_name'] = '未派奖';
                }
                $val['status'] = isset($status1[$val['status']]) ? $status1[$val['status']] : "未知状态";
                $val['source'] = isset($source1[$val['source']]) ? $source1[$val['source']] : "未知来源";
            } else {
                if ($val['deal_status'] == 6) {
                    $val['deal_status_name'] = '已派奖';
                } elseif ($val['deal_status'] == 2 && $val['status'] == 4) {
                    $val['deal_status_name'] = '未派奖';
                }
                $val['status'] = isset($status2[$val['status']]) ? $status2[$val['status']] : "未知状态";
                $val['source'] = isset($source2[$val['source']]) ? $source2[$val['source']] : "未知来源";
            }

            $val['sche_code'] = $codeStr;
            $val['play_name'] = $playStr;
        }
        $orderList = ['page_num' => $pn, 'records' => $order, 'size' => $pageSize, 'pages' => $pageCount, 'total' => $count];
        return $orderList;
    }

    /**
     * 获取追期列表
     * @param type $custNo 会员编号
     * @param type $pn 当前页码
     * @param type $pageSize 当前页条数
     * @return type
     */
    public static function getTraceList($custNo, $pn, $pageSize) {
        $traceStatus = [
            '0' => '停止',
            '1' => '未追',
            '2' => '正在追',
            '3' => '已结束'
        ];

        $where['cust_no'] = $custNo;
        $where['pay_status'] = 1;
        $query = LotteryAdditional::find();
        $count = $query->where($where)->andWhere([">", "periods_total", 1])->count();
        $pageCount = ceil($count / $pageSize);
        $offset = ($pn - 1) * $pageSize;
        $trace = $query->select(['lottery_additional_code', 'lottery_name', 'lottery_id', 'total_money', 'periods_total', 'chased_num', 'is_random', 'pay_status', 'status', 'create_time'])
                ->where($where)
                ->andWhere([">", "periods_total", 1])
                ->limit($pageSize)
                ->offset($offset)
                ->orderBy('create_time desc')
                ->asArray()
                ->all();
        foreach ($trace as &$val) {
            $pic = Lottery::find()->where(['lottery_code' => $val['lottery_id']])->select(['lottery_pic'])->asArray()->one();
            $val['picture'] = $pic['lottery_pic'];
            $val['is_random'] = $val['is_random'] == 0 ? '追固定号码' : '随机号码';
            $val['pay_status'] == 0 ? '未支付' : '支付';
            $val['status'] = $val['pay_status'] == 0 ? '未支付' : $traceStatus[$val['status']];
        }
        $traceList = ['pn' => $pn, 'records' => $trace, 'size' => $pageSize, 'pages' => $pageCount, 'total' => $count];
        return $traceList;
    }

    /**
     * 获取订单详情
     * @param type $custNo 会员编号
     * @param type $orderCode 订单编号
     * @return type
     */
    public static function getOrderDetail($custNo, $orderCode, $orderId = '') {
        $status = [
            "1" => "未支付",
            "2" => "处理中",
            "3" => "待开奖",
            "4" => "中奖",
            "5" => "未中奖",
            "6" => "出票失败",
            '9' => '过点撤销',
            '10' => '拒绝出票',
            '11' => '等待出票'
        ];
        $source = [
            '1' => '自购',
            '2' => '追号',
            '3' => '赠送',
            '5' => '分享',
            '6' => '计划'
        ];
        $where['lottery_order.lottery_order_code'] = $orderCode;
        if(!empty($custNo)) {
            $where['lottery_order.cust_no'] = $custNo;
        }
        if(!empty($orderId)) {
            $where['lottery_order.lottery_order_id'] = $orderId; 
        }
        
        $field = ['lottery_order.lottery_order_id', 'lottery_order.lottery_name', 'lottery_order.refuse_reason', 'lottery_order.lottery_additional_id', 'lottery_order.lottery_order_code', 'lottery_order.play_name', 'lottery_order.lottery_id', 'lottery_order.periods', 'lottery_order.end_time',
            'lottery_order.bet_double', 'lottery_order.is_bet_add', '.lottery_order.bet_money', 'lottery_order.count', 'lottery_order.win_amount', 'lottery_order.status', 'lottery_order.bet_val', 'lottery_order.chased_num',
            'lottery_order.source', 'lottery_order.create_time', 'l.lottery_pic', 's.store_name', 's.store_code', 's.telephone phone_num', 'lr.lottery_time', 'lr.lottery_numbers', 'lottery_order.play_code', 'lottery_order.source_id', 'lottery_order.deal_status'];
        $orderDet = LotteryOrder::find()->select($field)
                ->leftJoin('lottery as l', 'l.lottery_code = lottery_order.lottery_id')
                ->leftJoin('store as s', 's.store_code = lottery_order.store_no and s.status = 1')
                ->leftJoin('lottery_record as lr', 'lr.lottery_code = lottery_order.lottery_id and lr.periods = lottery_order.periods')
                ->where($where)
                ->asArray()
                ->one();
        if ($orderDet == null || $orderDet == false) {
            return \Yii::jsonError('5', '查询结果不存在');
        }
        $orderDet['lottery_numbers'] = $orderDet['lottery_numbers'] == null ? '' : $orderDet['lottery_numbers'];
        $orderDet['is_bet_add'] = $orderDet['is_bet_add'] == 0 ? '未追加' : '追加';
        $orderDet['status_name'] = $status[$orderDet['status']];
        $orderDet['source'] = $source[$orderDet['source']];
        $orderDet['award_time'] = date('Y-m-d H:i:s', strtotime($orderDet['end_time'] . '+4 hours'));
        $orderDet['periods_total'] = 0;
        if ($orderDet['source'] == 2) {
            $total = LotteryAdditional::find()->where(['lottery_additional_id' => $orderDet['source_id']])->select('periods_total')->asArray()->one();
            if ($total == null || $total == false) {
                return $this->jsonResult(40007, '查询结果不存在', '');
            }
            $orderDet['periods_total'] = $total['periods_total'];
        }

        $eleven = Constants::ELEVEN_TREND;
        $elevenPlayName = Constants::ELEVEN_PLAYNAME;
        $playName = '';
        if (in_array($orderDet['lottery_id'], $eleven)) {
            $playCode = explode(',', $orderDet['play_code']);
            foreach ($playCode as $v) {
                $playName .= $elevenPlayName[$v] . ',';
            }

            $orderDet['play_name'] = $playName;
        }
        $orderDet['discount_data'] = PayService::getDiscount(['order_code' => $orderCode]); //优惠信息
        return $orderDet;
    }

    /**
     * 获取追期详情
     * @param type $custNo 会员编号
     * @param type $orderCode 订单编号
     * @return type
     */
    public static function getTraceDetail($custNo, $orderCode) {
        $status = [
            "1" => "未支付",
            "2" => "处理中",
            "3" => "待开奖",
            "4" => "中奖",
            "5" => "未中奖",
            "6" => "出票失败",
            '9' => '过点撤销',
            '10' => '拒绝出票',
            '11' => '等待出票'
        ];
        $traceStatus = [
            '0' => '停止',
            '1' => '未追',
            '2' => '正在追',
            '3' => '已结束'
        ];
        $traceDet = LotteryAdditional::find()->where(['cust_no' => $custNo, 'lottery_additional_code' => $orderCode])->andWhere([">", "periods_total", 1])->asArray()->one();
//        var_dump($traceDet,$orderCode);exit();
        if ($traceDet == null || $traceDet == false) {
            return \Yii::jsonError('5', '查询结果不存在');
        }

        $orderDet = LotteryOrder::find()->where(['cust_no' => $custNo, 'source_id' => $traceDet['lottery_additional_id']])->orderBy('periods desc')->asArray()->all();
        if ($traceDet == null || $traceDet == false) {
            return \Yii::jsonError('5', '查询结果不存在');
        }
        $pic = Lottery::find()->where(['lottery_code' => $traceDet['lottery_id']])->select(['lottery_pic'])->asArray()->one();
        $traceDet['picture'] = $pic['lottery_pic'];
        $amount = 0;
        foreach ($orderDet as &$val) {
            $lotteryResult = LotteryRecord::find()->where(['periods' => $val['periods'], 'lottery_code' => $val['lottery_id']])->select('lottery_time, lottery_numbers')->asArray()->one();
            if ($lotteryResult == null || $lotteryResult == false) {
                return \Yii::jsonError(40007, '查询结果不存在');
            }
            $val['picture'] = $pic['lottery_pic'];
            $val['status'] = $status[$val['status']];
            $amount += $val['win_amount'];
            $val['lottery_time'] = $lotteryResult['lottery_time'];
            $val['lottery_result'] = $lotteryResult['lottery_numbers'] == null ? '' : $lotteryResult['lottery_numbers'];
        }
        $traceDet['status_name'] = $traceDet['pay_status'] == 0 ? '未支付' : $traceStatus[$traceDet['status']];
        $traceDet['is_random'] = $traceDet['is_random'] == 0 ? '追固定号码' : '随机号码';
        $traceDet['is_bet_add'] = $traceDet['is_bet_add'] == 0 ? '未追加' : '追加';
        $traceDet['is_limit'] = $traceDet['is_limit'] == 0 ? '不限制' : '限制';
        $traceDet['chase_chord'] = $orderDet;
        $traceDet['win_money'] = $amount;
        return $traceDet;
    }
}
