<?php

namespace app\modules\common\helpers;

use Yii;
use app\modules\common\models\LotteryRecord;
use app\modules\common\models\BettingDetail;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\Lottery;
use yii\base\Exception;
use yii\db\Query;
use app\modules\common\services\FundsService;
use app\modules\common\models\PayRecord;
use app\modules\common\models\ScheduleResult;
use app\modules\common\models\Programme;
use app\modules\common\models\ProgrammeUser;
use app\modules\common\models\Store;
use app\modules\common\models\UserFunds;
use app\modules\common\models\FootballFourteen;
use app\modules\common\helpers\Constants;
use app\modules\common\models\TaxRecord;
use app\modules\user\models\User;
use app\modules\user\models\ThirdUser;
use app\modules\user\helpers\WechatTool;
use app\modules\common\models\UserPlan;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use app\modules\sports\helpers\Guangdong;
use app\modules\cron\models\CheckLotteryResultRecord;
use app\modules\common\services\SyncApiRequestService;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\services\SyncService;
use app\modules\common\services\KafkaService;
use yii\db\Expression;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class Winning {

    /**
     * 备用接口（无效）
     * @auther GL zyl
     * 
     * @param string $code
     *            彩种编号
     * @param string $periods
     *            期数
     */
    public function getWinning($code, $periods = '') {
        if ($code == Lottery::CODE_SSQ) {
            $this->getSsqWinning($code, $periods);
        }
        if ($code == Lottery::CODE_FC_3D) {
            $this->getFcTdWinning($code, $periods);
        }
        if ($code == Lottery::CODE_QLC) {
            $this->getQlcWinning($code, $periods);
        }
        if ($code == Lottery::CODE_DLT) {
            $this->getDltWinning($code, $periods);
        }
        if ($code == Lottery::CODE_PL3) {
            $this->getPltWinning($code, $periods);
        }
        if ($code == Lottery::CODE_PL5) {
            $this->getPlfWinning($code, $periods);
        }
        if ($code == Lottery::CODE_QXC) {
            $this->getQxcWinning($code, $periods);
        }
        if ($code == Lottery::CODE_FOOT) {
            $this->getCompetingWinning();
        }
        if ($code == Lottery::CODE_FOURTEEN) {
            $this->getFourteenWinning();
        }
        if ($code == Lottery::CODE_GD11X5) {
            $this->getGd11x5Winning($code, $periods);
        }
    }

    /**
     * 说明: 大乐透的兑奖
     * 
     * @author kevi
     *         @date 2017年10月10日 下午8:50:16
     * @param string $lotteryCode
     *            彩种编号
     * @param string $periods
     *            期数
     * @param string $openNumber
     *            开奖号码
     * @return
     *
     */
    public function lottery2001Level($periods, $openNumber) {
        $openNumber = str_replace('|', ',', $openNumber);
        $sql = "call CheckDLT('{$openNumber}',4000000,3000000,2000000,'{$periods}'); ";

        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "超级大乐透 - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => 2001,
                'periods' => $periods,
                'open_num' => $openNumber,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $openNumber, 'jj1' => '4000000', 'jj2' => '3000000', 'jj3' => '2000000'];
            SyncApiRequestService::awardLottery(2001, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 说明: 排列三的兑奖
     * 
     * @author kevi
     *         @date 2017年10月10日 下午8:50:16
     * @param string $lotteryCode
     *            彩种编号
     * @param string $periods
     *            期数
     * @param string $openNumber
     *            开奖号码
     * @return
     *
     */
    public function lottery2002Level($periods, $openNumber) {
        // $numArr = explode(',', $openNumber);
        $openNumber = str_replace('|', ',', $openNumber);
        $sql = "call CheckPL3 ('{$openNumber}', '{$periods}');";

        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "排列三 - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => 2002,
                'periods' => $periods,
                'open_num' => $openNumber,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $openNumber];
            SyncApiRequestService::awardLottery(2002, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 说明: 排列五的兑奖
     * @author kevi 
     * @date 2017年10月10日 下午8:50:16
     * @param string $lotteryCode 彩种编号
     * @param string $periods 期数
     * @param string $openNumber 开奖号码
     */
    public function lottery2003Level($periods, $openNumber) {
        $sql = "call CheckPL5 ('{$openNumber}','{$periods}');";

        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "排列五 - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => 2003,
                'periods' => $periods,
                'open_num' => $openNumber,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $openNumber];
            SyncApiRequestService::awardLottery(2003, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 说明: 七星彩的兑奖
     * 
     * @author kevi
     *         @date 2017年10月10日 下午8:50:16
     * @param string $periods
     *            期数
     * @param string $openNumber
     *            开奖号码
     * @return
     *
     */
    public function lottery2004Level($periods, $openNumber) {
        $sql = "call CheckQXC ('{$openNumber}','{$periods}');";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "七星彩 - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => 2004,
                'periods' => $periods,
                'open_num' => $openNumber,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $openNumber];
            SyncApiRequestService::awardLottery(2004, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 说明:双色球对奖
     * 
     * @author kevi
     *         @date 2017年10月10日 下午8:50:16
     * @param string $lotteryCode
     *            彩种编号
     * @param string $periods
     *            期数
     * @param string $openNumber
     *            开奖号码
     * @return
     *
     */
    public function lottery1001Level($periods, $openNumber) {
        $openNumber = str_replace('|', ',', $openNumber);
        $sql = "call CheckSSQ('{$openNumber}',4000000,3000000,'{$periods}'); ";

        $connection = \Yii::$app->db;
        try { // 执行 打日志
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "双色球 - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => 1001,
                'periods' => $periods,
                'open_num' => $openNumber,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $openNumber, 'jj1' => '4000000', 'jj2' => '3000000'];
            SyncApiRequestService::awardLottery(1001, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 说明:福彩3D对奖
     * 
     * @author kevi
     *         @date 2017年10月10日 下午8:50:16
     * @param string $lotteryCode
     *            彩种编号
     * @param string $periods
     *            期数
     * @param string $openNumber
     *            开奖号码
     * @return
     *
     */
    public function lottery1002Level($periods, $openNumber) {
        $sql = "call Check3D('{$openNumber}','{$periods}'); ";
        $connection = \Yii::$app->db;
        try { // 执行 打日志
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "福彩3D - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => 1002,
                'periods' => $periods,
                'open_num' => $openNumber,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $openNumber];
            SyncApiRequestService::awardLottery(1002, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 说明:七乐彩对奖
     * 
     * @author kevi
     *         @date 2017年10月10日 下午8:50:16
     * @param string $lotteryCode
     *            彩种编号
     * @param string $periods
     *            期数
     * @param string $openNumber
     *            开奖号码
     * @return
     *
     */
    public function lottery1003Level($periods, $openNumber) {
        $sql = "call CheckQLC('{$openNumber}',4000000,3000000,2000000,'{$periods}'); ";
        $connection = \Yii::$app->db;
        try { // 执行 打日志
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "七乐彩 - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => 1003,
                'periods' => $periods,
                'open_num' => $openNumber,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $openNumber, 'jj1' => '4000000', 'jj2' => '3000000', 'jj3' => '2000000'];
            SyncApiRequestService::awardLottery(1003, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 广东11选5兑奖
     * auther GL ctx
     * 
     * @param string $code
     *            彩种编号
     * @param string $periods
     *            期数
     */
    public function getGd11x5Winning($code, $periods) {
        $result = LotteryRecord::find()->where([
                    'lottery_code' => $code,
                    'periods' => $periods
                ])->one();
        $res = [
            'code' => 2,
            'data' => ''
        ];
        if (!empty($result)) {
            $condition = [];
            $allBet = BettingDetail::find()->select('betting_detail_id,lottery_order_id,bet_val,play_code,bet_double')
                    ->where([
                        'lottery_id' => $result['lottery_code'],
                        'periods' => $result['periods'],
                        'deal_status' => 0,
                        'status' => 3
                    ])
                    ->limit(10000)
                    ->orderBy('betting_detail_id')
                    ->asArray()
                    ->all();
            if (!empty($allBet)) {
                foreach ($allBet as $val) {
                    $fun = "gdWinning_" . $val["play_code"];
                    $oneWinMoney = Guangdong::$fun($val['bet_val'], $result['lottery_numbers']);
                    $winAmount = 0;
                    $isWin = 5;
                    $winLevel = 0;
                    if ($oneWinMoney != false) {
                        $winAmount = floatval($val['bet_double']) * $oneWinMoney;
                        $winLevel = 1;
                        $isWin = 4;
                    }
                    $condition[] = [
                        'bet_id' => $val['betting_detail_id'],
                        'win_amount' => $winAmount,
                        'win_level' => $winLevel,
                        'is_win' => $isWin
                    ];
                }
                $this->getUpdateDetail($condition);
                $finish = BettingDetail::find()->select('betting_detail_id')
                        ->where([
                            'lottery_id' => $result['lottery_code'],
                            'periods' => $result['periods'],
                            'deal_status' => 0
                        ])
                        ->asArray()
                        ->one();
                $idStr = $allBet[0]['betting_detail_id'] . '-' . $allBet[count($allBet) - 1]['betting_detail_id'];
                if (empty($finish)) {
                    $result->win_status = LotteryRecord::WIN_STATUS_WON;
                    $result->modify_time = date('Y-m-d H:i:s');
                    $id = $result->save();
                    if ($id == false) {
                        $res = [
                            'code' => 0,
                            'data' => $idStr
                        ];
                    } else {
                        $res = [
                            'code' => 1,
                            'data' => $idStr
                        ];
                    }
                } else {
                    $result->win_status = LotteryRecord::WIN_STATUS_WINNING;
                    $result->modify_time = date('Y-m-d H:i:s');
                    $id = $result->save();
                    $res = [
                        'code' => 0,
                        'data' => $idStr
                    ];
                }
            } else {
                $result->win_status = LotteryRecord::WIN_STATUS_WON;
                $result->modify_time = date('Y-m-d H:i:s');
                $id = $result->save();
                if ($id == false) {
                    $res = [
                        'code' => 0,
                        'data' => '开奖结果表更新失败'
                    ];
                } else {
                    $res = [
                        'code' => 1,
                        'data' => '详情单已兑奖完'
                    ];
                }
            }
        }
        return $res;
    }

    /**
     * 数据写入
     * auther GL ZYL
     * 
     * @param array $betArr
     *            计算结果
     * @return boolean
     * @throws Exception
     */
    public function getUpdateDetail($betArr) {
        $updateIds = '';
        $db = Yii::$app->db;
        set_time_limit(0);
        foreach ($betArr as $val) {
            $format = date('Y-m-d H:i:s');
            $updateIds .= "update betting_detail set win_amount ={$val['win_amount']} ,win_level = {$val['win_level']}, status = {$val['is_win']}, deal_status = 1, modify_time = '" . $format . "' where  betting_detail_id = {$val['bet_id']}; ";
            BettingDetail::addQueSync(BettingDetail::$syncUpdateType, 'win_amount,win_level,status,deal_status,modify_time', ['betting_detail_id' => $val['bet_id']]);
        }
        $detailIds = $db->createCommand($updateIds)->execute();
        if ($detailIds == FALSE) {
            return FALSE;
        }
    }

    /**
     * 订单表数据写入
     * @auther GL zyl
     * 
     * @param type $code
     *            彩种编号
     * @param type $periods
     *            期数
     * @return string
     */
    public static function getUpdateOrder($code, $periods) {
        $numsArr = Constants::MADE_NUMS_LOTTERY;
        $db = Yii::$app->db;
        $updateIds = '';
        $res = [
            'code' => 0,
            'data' => ''
        ];
        $isFinish = BettingDetail::find()->where([
                    'lottery_id' => $code,
                    'periods' => $periods
                ])
                ->andWhere([
                    'deal_status' => 0,
                    'status' => 3
                ])
                ->count();
        if ($isFinish != 0) {
            $res = [
                'code' => 0,
                'data' => '详细单还未处理完'
            ];
        }
        if (in_array($code, $numsArr)) {
            $codeWhere['o.lottery_id'] = $code;
        } else {
            $codeWhere = [
                'in',
                'o.lottery_id',
                [
                    4001,
                    4002
                ]
            ];
        }
        $query = new Query();
        $allWin = $query->select('o.lottery_order_id,sum(b.win_amount) as win_amount')
                ->from('lottery_order as o')
                ->leftJoin('betting_detail as b', 'b.lottery_order_id = o.lottery_order_id')
                ->groupBy('o.lottery_order_id')
                ->where([
                    'o.periods' => $periods,
                    'o.deal_status' => 0,
                    'o.status' => 3
                ])
                ->andWhere($codeWhere)
                ->limit(5000)
                ->all();
        if (!empty($allWin)) {
            foreach ($allWin as $val) {
                if (intval($val['win_amount']) != 0) {
                    $orderWin = 4;
                } elseif ($val['win_amount'] == '') {
                    $orderWin = 1;
                    $val['win_amount'] = 0;
                } else {
                    $orderWin = 5;
                }
                $format = date('Y-m-d H:i:s');
                $updateIds .= "update lottery_order set status = {$orderWin}, win_amount = {$val['win_amount']} ,deal_status = 1, modify_time = '" . $format . "' where lottery_order_id = {$val['lottery_order_id']} ;";
                $updateIds .= "update betting_detail set deal_status = 2, modify_time = '" . $format . "' where lottery_order_id = {$val['lottery_order_id']} ;";
                //BettingDetail::addQueUpdate(['betting_detail_id' => $val['bet_id']]);
                BettingDetail::addQueSync(BettingDetail::$syncUpdateType, 'deal_status,modify_time', ['lottery_order_id' => $val['lottery_order_id']]);
                BettingDetail::addQueSync(LotteryOrder::$syncUpdateType, 'win_amount,status,deal_status,modify_time', ['lottery_order_id' => $val['lottery_order_id']]);
                //LotteryOrder::addQueUpdate(['lottery_order_id' => $val['lottery_order_id']]);
            }
            $orderUpdate = $db->createCommand($updateIds)->execute();
            $idStr = $allWin[0]['lottery_order_id'] . '-' . $allWin[count($allWin) - 1]['lottery_order_id'];
            if ($orderUpdate == false) {
                $res = [
                    'code' => 0,
                    'data' => $idStr
                ];
            } else {
                $res = [
                    'code' => 1,
                    'data' => $idStr
                ];
            }
        } else {
            if (in_array($code, $numsArr)) {
                $model = LotteryRecord::find()->where([
                            'lottery_code' => $code,
                            'periods' => $periods
                        ])->one();
                $model->win_status = LotteryRecord::WIN_STATUS_ORDER_WON;
            } else {
                $model = FootballFourteen::find()->where([
                            'periods' => $periods
                        ])->one();
                $model->win_status = FootballFourteen::WIN_STATUS_ORDER_WON;
            }
            $model->modify_time = date('Y-m-d H:i:s');
            $id = $model->save();
            if ($id == false) {
                $res = [
                    'code' => 0,
                    'data' => '开奖结果表更新失败'
                ];
            } else {
                $res = [
                    'code' => 1,
                    'data' => '订单已兑奖完'
                ];
            }
        }
        return $res;
    }

    /**
     * 中奖订单派奖
     * @auther GL zyl
     * 
     * @return string|int
     * @throws Exception
     */
    public function getAwardsFunds() {
        $order = LotteryOrder::find()->select([
                    'lottery_order.lottery_order_id',
                    'lottery_order.cust_no',
                    'lottery_order.lottery_order_code',
                    'lottery_order.store_id',
                    'lottery_order.win_amount',
                    's.store_type',
                    's.cust_no as store_no'
                ])
                ->leftJoin('store as s', 's.user_id = lottery_order.store_id')
                ->where([
                    'lottery_order.deal_status' => 1,
                    'lottery_order.status' => 4
                ])
                ->andWhere([
                    'in',
                    'lottery_order.source',
                    [
                        1,
                        2,
                        3
                    ]
                ])
                ->andWhere([
                    '<=',
                    'win_amount',
                    10000
                ])
                ->limit(5000)
                ->asArray()
                ->all();
        if (empty($order)) {
            $result = [
                'code' => 0,
                'data' => '暂无已兑奖且中奖的订单'
            ];
            return $result;
        }
        $updateStr = '';
        $format = date('Y-m-d H:i:s');
        $fundsService = new FundsService();
        $db = Yii::$app->db;
        foreach ($order as $val) {
            $trans = $db->beginTransaction();
            try {
                LotteryOrder::upData([
                    "award_amount" => $val['win_amount']
                        ], [
                    "lottery_order_code" => $val["lottery_order_code"]
                ]);
                $user = User::findOne([
                            "cust_no" => $val['cust_no']
                ]);
                if (empty($user)) {
                    return \Yii::jsonResult(109, "未找到会员", "");
                }
                $storeNo = $val['store_no'];
                $storePay = new PayRecord();
                $storePay->order_code = $val['lottery_order_code'];
                $storePay->cust_no = $storeNo;
                $storePay->cust_type = 2;
                $storePay->pay_no = Commonfun::getCode('PAY', 'L');
                $storePay->pay_pre_money = $val['win_amount'];
                $storePay->pay_money = $val['win_amount'];
                $storePay->pay_name = '余额';
                $storePay->way_name = '余额';
                $storePay->way_type = 'YE';
                $storePay->pay_way = 3;
                $storePay->pay_type_name = '奖金发放';
                $storePay->pay_type = 11;
                $storePay->body = '奖金发放-' . (substr($user->user_tel, - 4)) . "({$user->user_name})";
                $storePay->status = 0;
                $storePay->pay_time = date('Y-m-d H:i:s');
                $storePay->create_time = date('Y-m-d H:i:s');
                if (!$storePay->validate()) {
                    throw new Exception('B明细表验证失败');
                }
                if (!$storePay->saveData()) {
                    throw new Exception('B保存失败');
                }

                $userPay = new PayRecord();
                $userPay->order_code = $val['lottery_order_code'];
                $userPay->cust_no = $val['cust_no'];
                $userPay->cust_type = 1;
                $userPay->pay_no = Commonfun::getCode('PAY', 'L');
                $userPay->pay_pre_money = $val['win_amount'];
                $userPay->pay_money = $val['win_amount'];
                $userPay->pay_name = '余额';
                $userPay->way_name = '余额';
                $userPay->way_type = 'YE';
                $userPay->pay_way = 3;
                $userPay->pay_type_name = '奖金';
                $userPay->pay_type = 15;
                $userPay->body = '奖金';
                $userPay->status = 0;
                $userPay->pay_time = date('Y-m-d H:i:s');
                $userPay->create_time = date('Y-m-d H:i:s');
                if (!$userPay->validate()) {
                    throw new Exception('C明细表验证失败');
                }
                if (!$userPay->saveData()) {
                    throw new Exception('C保存失败');
                }

                $storeFunds = $fundsService->operateUserFunds($storeNo, - $val['win_amount'], - $val['win_amount'], 0, true, "兑奖");
                if ($storeFunds['code'] != 0) {
                    // if($storeFunds['code'] == 407){
                    // 提醒门店金额不够兑奖
                    // }
                    throw new Exception($storeFunds['msg']);
                }
                $storeBalance = \app\modules\common\models\UserFunds::find()->select([
                            'all_funds'
                        ])
                        ->where([
                            'cust_no' => $storeNo
                        ])
                        ->asArray()
                        ->one();
                $storePay->balance = $storeBalance['all_funds'];
                $storePay->status = 1;
                $storePay->modify_time = date('Y-m-d H:i:s');
                if (!$storePay->saveData()) {
                    throw new Exception('B修改失败');
                }

                $userFunds = $fundsService->operateUserFunds($val['cust_no'], $val['win_amount'], $val['win_amount'], 0, false, '兑奖');
                if ($userFunds['code'] != 0) {
                    throw new Exception($userFunds['msg']);
                }
                $userBalance = \app\modules\common\models\UserFunds::find()->select([
                            'all_funds'
                        ])
                        ->where([
                            'cust_no' => $val['cust_no']
                        ])
                        ->asArray()
                        ->one();
                $userPay->balance = $userBalance['all_funds'];
                $userPay->status = 1;
                $userPay->modify_time = date('Y-m-d H:i:s');
                if (!$userPay->saveData()) {
                    throw new Exception('C修改失败');
                }
                $trans->commit();
                $updateStr .= "update lottery_order set deal_status = 3, award_time = '" . $format . "', modify_time = '" . $format . "' where lottery_order_id = {$val['lottery_order_id']} ;";
                LotteryOrder::addQueSync(LotteryOrder::$syncUpdateType, 'deal_status,award_time,modify_time', ['lottery_order_id' => $val['lottery_order_id']]);
            } catch (Exception $ex) {
                $trans->rollBack();
                continue;
            }
        }
        $orderIds = $db->createCommand($updateStr)->execute();
        $idStr = $order[0]['lottery_order_id'] . '-' . $order[count($order) - 1]['lottery_order_id'];
        if ($orderIds == FALSE) {
            $result = [
                'code' => 0,
                'data' => $idStr
            ];
        }
        $result = [
            'code' => 1,
            'data' => $idStr
        ];
        return $result;
    }

    /**
     * 手动派奖
     * 
     * @param type $orderCode            
     * @param type $storeId            
     * @return json
     */
    public function playAwardsFunds($orderCode, $storeId, $awardAmount = "") {
        $field = ['lottery_order.lottery_order_id','lottery_order.source','lottery_order.cust_no','lottery_order.lottery_order_code', 'lottery_order.source_id', 'lottery_order.store_id','lottery_order.win_amount',
                    'lottery_order.bet_money', 's.store_type', 's.cust_no as store_no', 'lottery_order.lottery_id'];
        $order = LotteryOrder::find()->select($field)
                ->leftJoin('store as s', 's.user_id = lottery_order.store_id')
                ->where(["lottery_order.lottery_order_code" => $orderCode,'lottery_order.store_id' => $storeId,'lottery_order.deal_status' => 1,'lottery_order.status' => 4 ])
                -> asArray()
                ->one();
        $bettingDetails = BettingDetail::find()->select([ 'win_amount', 'bet_double'])->where(["lottery_order_id" => $order["lottery_order_id"]])->asArray()->all();
        if (empty($order) || empty($bettingDetails)) {
            \Yii::info(LotteryOrder::find()->where([ "lottery_order.lottery_order_code" => $orderCode, 'lottery_order.store_id' => $storeId,'lottery_order.deal_status' => 1, 'lottery_order.status' => 4])->createCommand()->getRawSql(), 'backuporder_log');
            \Yii::info(BettingDetail::find()->where(["lottery_order_id" => $order["lottery_order_id"] ])->createCommand()->getRawSql(), 'backuporder_log');
            return \Yii::jsonResult(109, "未找到对应订单", "");
        }

        $format = date('Y-m-d H:i:s');
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        $third_check = \Yii::$app->params['third_check'];
        if (in_array($order['lottery_id'], CompetConst::MADE_BD_LOTTERY)) {//北单
            $third_check = 0;
        }
        if ($third_check) {
            $chekc = SyncApiRequestService::getWinAmount($order['lottery_order_code']);
            if (!$chekc || $chekc['code'] != 600) {
                return \Yii::jsonResult(109, "第三方系统出错", "");
            }
            if ($order["win_amount"] != $chekc['win_amount']) {
                KafkaService::addLog('paijiangMoneyError', $order);
                return \Yii::jsonResult(110, "派奖金额不匹配第三方", "");
            }
        }
        try {
            if (empty($awardAmount)) {
                $awardAmount = 0;
                foreach ($bettingDetails as $val) {
                    if (($val['win_amount'] / $val['bet_double']) >= 10000) {
                        $awardAmount += sprintf("%.2f", $val['win_amount'] * 0.8);
                    } else {
                        $awardAmount += $val['win_amount'];
                    }
                }
                $taxMoney = $order["win_amount"] - $awardAmount;
                if ($taxMoney > 0) {
                    $taxRecord = TaxRecord::findOne([
                                "order_code" => $order["lottery_order_code"]
                    ]);
                    if ($taxRecord != null) {
                        return \Yii::jsonResult(109, "订单出错，请联系客服处理", "");
                    }
                    $taxUser = User::findOne([
                                "cust_no" => $order["cust_no"]
                    ]);
                    if (empty($taxUser)) {
                        return \Yii::jsonResult(109, "未找到会员", "");
                    }
                    $taxRecord = new TaxRecord();
                    $taxRecord->order_code = $order["lottery_order_code"];
                    $taxRecord->tax_record_code = Commonfun::getCode("TAX", "S");
                    $taxRecord->user_id = $taxUser->user_id;
                    $taxRecord->cust_no = $order["cust_no"];
                    $taxRecord->tax_money = $taxMoney;
                    $taxRecord->create_time = date("Y-m-d H:i:s");
                    if ($taxRecord->validate()) {
                        $ret = $taxRecord->save();
                        if ($ret == false) {
                            return \Yii::jsonResult(109, "税务处理失败", "");
                        }
                    } else {
                        return \Yii::jsonResult(109, "税务处理失败", $taxRecord->getFirstErrors());
                    }
                }
            }
            LotteryOrder::upData(["award_amount" => $awardAmount ], ["lottery_order_code" => $order["lottery_order_code"]]);
            if (in_array($order["source"], [1, 2, 3, 5, 7])) {
                $fundsService = new FundsService();

                $user = User::findOne(["cust_no" => $order['cust_no']]);
                if (empty($user)) {
                    return \Yii::jsonResult(109, "未找到会员", "");
                }
                $storeNo = $order['store_no'];
                $storePay = new PayRecord();
                $storePay->order_code = $order['lottery_order_code'];
                $storePay->cust_no = $storeNo;
                $storePay->cust_type = 2;
                $storePay->pay_no = Commonfun::getCode('PAY', 'L');
                $storePay->pay_pre_money = $awardAmount; // $order['win_amount'];
                $storePay->pay_money = $awardAmount; // $order['win_amount'];
                $storePay->pay_name = '余额';
                $storePay->way_name = '余额';
                $storePay->way_type = 'YE';
                $storePay->pay_way = 3;
                $storePay->pay_type_name = '奖金发放';
                $storePay->pay_type = 11;
                $storePay->body = '奖金发放-' . (substr($user->user_tel, - 4)) . "({$user->user_name})";
                $storePay->status = 0;
                $storePay->pay_time = $format;
                $storePay->create_time = $format;
                if (!$storePay->validate()) {
                    return \Yii::jsonResult(109, "明细表验证失败", "");
                }
                if (!$storePay->saveData()) {
                    return \Yii::jsonResult(109, "派奖处理失败", "");
                }

                $userPay = new PayRecord();
                $userPay->order_code = $order['lottery_order_code'];
                $userPay->cust_no = $order['cust_no'];
                $userPay->cust_type = 1;
                $userPay->pay_no = Commonfun::getCode('PAY', 'L');
                $userPay->pay_pre_money = $awardAmount; // $order['win_amount'];
                $userPay->pay_money = $awardAmount; // $order['win_amount'];
                $userPay->pay_name = '余额';
                $userPay->way_name = '余额';
                $userPay->way_type = 'YE';
                $userPay->pay_way = 3;
                $userPay->pay_type_name = '奖金';
                $userPay->pay_type = 15;
                $userPay->body = '奖金';
                $userPay->status = 0;
                $userPay->pay_time = $format;
                $userPay->create_time = $format;
                if (!$userPay->validate()) {
                    return \Yii::jsonResult(109, "明细表验证失败", "");
                }
                if (!$userPay->saveData()) {
                    return \Yii::jsonResult(109, "派奖处理失败", "");
                }
                $storeFunds = $fundsService->operateUserFunds($storeNo, - $awardAmount, - $awardAmount, 0, true, "兑奖");
                if ($storeFunds['code'] != 0) {
                    // if($storeFunds['code'] == 407){
                    // 提醒门店金额不够兑奖
                    // }
                    return \Yii::jsonResult($storeFunds["code"], $storeFunds["msg"], "");
                }
                $storeBalance = \app\modules\common\models\UserFunds::find()->select([ 'all_funds'])->where(['cust_no' => $storeNo]) ->asArray()->one();
                $storePay->balance = $storeBalance['all_funds'];
                $storePay->status = 1;
                $storePay->modify_time = date('Y-m-d H:i:s');
                if (!$storePay->saveData()) {
                    return \Yii::jsonResult(109, "派奖处理失败", "");
                }

                $userFunds = $fundsService->operateUserFunds($order['cust_no'], $awardAmount, $awardAmount, 0, false, '兑奖');
                if ($userFunds['code'] != 0) {
                    return \Yii::jsonResult(109, $userFunds['msg'], "");
                }
                $userBalance = \app\modules\common\models\UserFunds::find()->select(['all_funds'])->where(['cust_no' => $order['cust_no']])->asArray()->one();
                $userPay->balance = $userBalance['all_funds'];
                $userPay->status = 1;
                $userPay->modify_time = date('Y-m-d H:i:s');
                if (!$userPay->saveData()) {
                    return \Yii::jsonResult(109, "派奖处理失败", "");
                }
            } elseif ($order["source"] == 4) {
                $programme = Programme::find()->where(['programme_id' => $order["source_id"],'status' => 6, 'bet_status' => 6])->one();
                if (empty($programme)) {
                    \Yii::info(Programme::find()->where([ 'programme_id' => $order["source_id"],'status' => 6,'bet_status' => 6])->createCommand()->getRawSql(), 'backuporder_log');
                    return \Yii::jsonResult(109, "未找到对应订单", "");
                }
                $proUser = ProgrammeUser::find()->select(['programme_user_id','cust_no','bet_money','buy_number','cust_type','win_amount']) ->where(['programme_id' => $programme->programme_id, 'deal_status' => 1])->andWhere(['!=', 'status', 1]) ->asArray()->all();
                if (empty($proUser)) {
                    $programme->bet_status = 9;
                    $programme->modify_time = $format;
                    if (!$programme->save()) {
                        return \Yii::jsonResult(109, "保存失败", "");
                    }
                    $order = LotteryOrder::findOne([ 'source_id' => $programme->programme_id]);
                    $order->deal_status = 3;
                    $order->modify_time = $format;
                    if (!$order->saveData()) {
                        return ['code' => 0,'data' => '订单表保存失败' ];
                    }
                    return \Yii::jsonResult(109, "该合买方案已全部派奖", "");
                }
                $errData = [];
                $storeNo = \app\modules\common\models\Store::find()->select(['cust_no'])->where(['user_id' => $programme->store_id])->asArray()->one();
                $storeBalance = \app\modules\common\models\UserFunds::find()->select(['able_funds'])->where(['cust_no' => $storeNo['cust_no']])->asArray()->one();
                if ($storeBalance['able_funds'] < $programme['bet_status']) {
                    return \Yii::jsonResult(109, "该方案所属出票门店资金暂时不足以支付奖金", "");
                }
                $royalty = floatval($awardAmount) * floatval($programme->royalty_ratio / 100);
                if ($royalty != 0) {
                    $expertRoyalty = $this->Prizes($programme->programme_code, $storeNo['cust_no'], $programme->expert_no, $royalty, $programme->cust_type, 14, '提成');
                    if ($expertRoyalty['code'] != 1) {
                        if ($userWin['code'] == 109) {
                            return \Yii::jsonResult(109, "该方案所属出票门店资金暂时不足以支付奖金", "");
                        }
                    }
                }
                $laveMoney = floatval($awardAmount) - $royalty;
                foreach ($proUser as $value) {
                    $ratio = floatval($value['bet_money']) / floatval($programme->bet_money);
                    $winMoney = round($laveMoney * $ratio, 2);
                    $userWin = $this->Prizes($programme->programme_code, $storeNo['cust_no'], $value['cust_no'], $winMoney, $value['cust_type']);
                    if ($userWin['code'] != 1) {
                        if ($userWin['code'] == 109) {
                            return \Yii::jsonResult(109, "该方案所属出票门店资金暂时不足以支付奖金", "");
                        }
                        $errData[] = $value['programme_user_id'];
                        continue;
                    }
                    $ret = ProgrammeUser::updateAll([ "win_amount" => $winMoney,"deal_status" => 6], ['and', ["programme_user_id" => $value['programme_user_id'],"deal_status" => 1], ['!=', 'status', 1]]);
                    if ($ret == false) {
                        $trans->rollBack();
                        return \Yii::jsonResult(109, "兑奖失败,请重试", "");
                    }
                }
                $userPro = ProgrammeUser::find()->select(['programme_user_id'])->where(['programme_id' => $order['source_id'],'deal_status' => 1])->andWhere(['!=', 'status', 1])->all();
                if (empty($userPro)) {
                    $programme->bet_status = 7;
                    $programme->save();
                }
            } elseif ($order["source"] == 6) {
                $winType = UserPlan::find()->select(['win_type'])->where(['user_plan_id' => $order['source_id']])->asArray()->one();
                $profit = $order['win_amount'] - $order['bet_money'];
                if ($profit < 0) {
                    $profit = 0;
                }
                if ($winType['win_type'] == 1) {
                    $ret = $db->createCommand("update user_plan set able_funds = able_funds + {$awardAmount}, win_amount = win_amount + {$awardAmount}, total_profit = total_profit + {$profit}, modify_time = '" . $format . "' where user_plan_id = {$order['user_plan_id']} ;")->execute();
                } else {
                    $storeNo = Store::find()->select(['cust_no'])->where(['user_id' => $order['store_id']])->asArray()->one();
                    $storeBalance = UserFunds::find()->select(['able_funds'])->where(['cust_no' => $storeNo['cust_no']])->asArray()->one();
                    if ($storeBalance['able_funds'] < $order['win_amount']) {
                        return ['code' => 0,'data' => '该方案所属出票门店资金暂时不足以支付奖金'];
                    }
                    $userWin = $this->Prizes($order['lottery_order_code'], $storeNo['cust_no'], $order['cust_no'], $order['win_amount']);
                    if ($userWin['code'] == 0) {
                        // 日志
                        return \Yii::jsonResult(109, $userWin["msg"], "");
                    }
                    $ret = $db->createCommand("update user_plan set win_amount = win_amount + {$awardAmount}, total_profit = total_profit + {$profit}, modify_time = '" . $format . "' where user_plan_id = {$order['source_id']} ;")->execute();
                    if ($ret == false) {
                        $trans->rollBack();
                        return \Yii::jsonResult(109, "派奖处理失败，请重试", "");
                    }
                }
            } else {
                return \Yii::jsonResult(109, "该下单投注类型有问题", "");
            }
            $ret = LotteryOrder::upData(["deal_status" => 3,"award_time" => $format, "modify_time" => $format], ["deal_status" => 1,"lottery_order_id" => $order['lottery_order_id']]);
            if ($ret == false) {
                $trans->rollBack();
                return \Yii::jsonResult(109, "派奖处理失败，请重试", "");
            }
            $trans->commit();
            SyncService::syncFromHttp();
            return \Yii::jsonResult(600, "派奖成功", "");
        } catch (Exception $ex) {
            $trans->rollBack();
            return \Yii::jsonResult(109, "兑奖失败", $ex);
        }
    }

    /**
     * 竞彩详情单兑奖
     * 
     * @param int $mid
     *            赛程MID
     * @return type
     */
    public function getWinningCompeting($mid) {
        $result = ScheduleResult::find()->where([
                    'schedule_mid' => $mid,
                    'status' => 2,
                    'deal_status' => 0
                ])
                ->orderBy('schedule_date')
                ->one();
        if (empty($result)) {
            return [
                'code' => 1,
                'data' => '暂无需兑奖场次'
            ];
        }
        $format = date('Y-m-d H:i:s');
        set_time_limit(0);
        $str = ',' . $mid;
        $data = BettingDetail::find()->select([
                    'betting_detail_id',
                    'lottery_order_id',
                    'lottery_id',
                    'bet_val',
                    'odds',
                    'bet_double',
                    'win_amount',
                    'schedule_nums',
                    'deal_nums',
                    'deal_schedule'
                ])
                ->where([
                    'status' => 3,
                    'deal_status' => 0
                ])
                ->andWhere([
                    'in',
                    'lottery_id',
                    [
                        3006,
                        3007,
                        3008,
                        3009,
                        3010,
                        3011
                    ]
                ])
                ->andWhere([
                    'like',
                    'bet_val',
                    $result->schedule_mid
                ])
                ->andWhere([
                    'not like',
                    'deal_schedule',
                    $str
                ])
                ->limit(10000)
                ->asArray()
                ->all();
        if (empty($data)) {
            $result->deal_status = 1;
            $result->modify_time = $format;
            if (!$result->save()) {
                return [
                    'code' => 0,
                    'data' => '处理状态数据更新失败'
                ];
            }
            return [
                'code' => 1,
                'data' => '有此场次的详情，已处理完'
            ];
        }
        $detailUp = '';
        foreach ($data as $val) {
            $code = $val['lottery_id'];
            $bet = $val['bet_val'];
            $odds = json_decode($val['odds'], true);
            $ret = $this->getCalculWinning($code, $bet, $odds, $result);
            if ($ret['is_win'] == 4) {
                $detailUp .= "update betting_detail set win_amount = TRUNCATE(win_amount * {$ret['win_amount']}, 2), deal_nums = deal_nums + 1, deal_schedule = concat(deal_schedule, ',{$mid}'), modify_time = '" . $format . "' where  betting_detail_id = {$val['betting_detail_id']}; ";
                $filed = 'win_amount,deal_nums,deal_schedule,modify_time,modify_time';
            } else {
                $filed = 'win_amount,deal_nums,deal_schedule,modify_time,modify_time,status,deal_status';
                $detailUp .= "update betting_detail set win_amount = 0, deal_nums = deal_nums + 1, deal_schedule = concat(deal_schedule, ',{$mid}'), status = {$ret['is_win']}, deal_status = 1, modify_time = '" . $format . "' where  betting_detail_id = {$val['betting_detail_id']};";
            }
            BettingDetail::addQueSync(BettingDetail::$syncUpdateType, $filed, ['betting_detail_id' => $val['betting_detail_id']]);
        }
        $db = Yii::$app->db;
        $detailIds = $db->createCommand($detailUp)->execute();
        if ($detailIds == false) {
            return [
                'code' => 0,
                'data' => $data[0]['betting_detail_id'] . '-' . $data[count($data) - 1]['betting_detail_id']
            ];
        }
        return [
            'code' => 1,
            'data' => $data[0]['betting_detail_id'] . '-' . $data[count($data) - 1]['betting_detail_id']
        ];
    }

    /**
     * 竞彩详情单兑奖计算
     * 
     * @param type $code
     *            彩种编号
     * @param type $bet
     *            投注内容
     * @param type $odds
     *            投注内容赔率
     * @param type $result
     *            开奖结果
     * @return type
     */
    public function getCalculWinning($code, $bet, $odds, $result) {
        $bifen = Constants::BIFEN_ARR;
        $betArr = explode('|', $bet);
        if ($code != '3011') {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $schedule = [];
        if ($code == 3011) {
            $r = [];
            foreach ($betArr as $val) {
                preg_match($pattern, $val, $schedule);
                if ($schedule[1] == $result['schedule_mid']) {
                    $str = explode('*', $schedule[2]);
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                    $oneOdds = $odds[$r[1]][$schedule[1]][$r[2]];
                    $field = 'schedule_result_' . $r[1];
                    if ($field == 'schedule_result_3007') {
                        $result[$field] = str_replace(':', '', $result[$field]);
                        if ($result['schedule_result_3010'] == 0) {
                            if (!in_array($result[$field], $bifen[0])) {
                                $result[$field] = '09';
                            }
                        } elseif ($result['schedule_result_3010'] == 1) {
                            if (!in_array($result[$field], $bifen[1])) {
                                $result[$field] = '99';
                            }
                        } elseif ($result['schedule_result_3010'] == 3) {
                            if (!in_array($result[$field], $bifen[2])) {
                                $result[$field] = '90';
                            }
                        }
                    } elseif ($field == 'schedule_result_3008') {
                        if ($result[$field] > 7) {
                            $result[$field] = 7;
                        }
                    }
                    if ($result[$field] == $r[2]) {
                        $winAmount = $oneOdds;
                    } else {
                        $winAmount = 0;
                    }
                    break;
                }
                continue;
            }
        } else {
            foreach ($betArr as $val) {
                preg_match($pattern, $val, $schedule);
                if ($schedule[1] == $result['schedule_mid']) {
                    $field = 'schedule_result_' . $code;
                    if ($field == 'schedule_result_3007') {
                        $result[$field] = str_replace(':', '', $result[$field]);
                        if ($result['schedule_result_3010'] == 0) {
                            if (!in_array($result[$field], $bifen[0])) {
                                $result[$field] = '09';
                            }
                        } elseif ($result['schedule_result_3010'] == 1) {
                            if (!in_array($result[$field], $bifen[1])) {
                                $result[$field] = '99';
                            }
                        } elseif ($result['schedule_result_3010'] == 3) {
                            if (!in_array($result[$field], $bifen[2])) {
                                $result[$field] = '90';
                            }
                        }
                    } elseif ($field == 'schedule_result_3008') {
                        if ($result[$field] > 7) {
                            $result[$field] = 7;
                        }
                    }
                    $oneOdds = $odds[$code][$schedule[1]][$schedule[2]];
                    if ($result[$field] == $schedule[2]) {
                        $winAmount = $oneOdds;
                    } else {
                        $winAmount = 0;
                    }
                    break;
                }
                continue;
            }
        }
        if ($winAmount == 0) {
            $isWin = 5;
        } else {
            $isWin = 4;
        }
        return [
            'code' => 600,
            'win_amount' => $winAmount,
            'is_win' => $isWin
        ];
    }

    /**
     * 竞彩订单兑奖
     * 
     * @return type
     */
    public function getComptingOrder() {
        $redis = \Yii::$app->redis;
        $orderIds = $redis->executeCommand('SRANDMEMBER', [
            "dealed_with:footballs",
            1
        ]);
        if (empty($orderIds)) {
            return [
                'code' => 0,
                'data' => '暂无需兑奖订单'
            ];
        }
        $format = date('Y-m-d H:i:s');
        $orderUp = '';
        $db = Yii::$app->db;
        $query = new Query();
        $allWin = $query->select([
                    'o.lottery_order_id',
                    'sum(b.win_amount) as win_amount'
                ])
                ->from('lottery_order as o')
                ->leftJoin('betting_detail as b', 'b.lottery_order_id = o.lottery_order_id')
                ->where([
                    'o.deal_status' => 0
                ])
                ->andWhere([
                    'in',
                    'o.lottery_order_id',
                    $orderIds
                ])
                ->groupBy('o.lottery_order_id')
                ->all();
        if (empty($allWin)) {
            array_unshift($orderIds, 'dealed_with:footballs');
            $orderIds = $redis->executeCommand('SREM', $orderIds);
            return [
                'code' => 0,
                'data' => '已无订单可兑奖'
            ];
        }
        foreach ($allWin as $val) {
            if (intval($val['win_amount']) != 0) {
                $orderWin = 4;
            } elseif ($val['win_amount'] == '') {
                $orderWin = 1;
                $val['win_amount'] = 0;
            } else {
                $orderWin = 5;
            }
            $format = date('Y-m-d H:i:s');
            $orderUp .= "update lottery_order set status = {$orderWin}, win_amount = {$val['win_amount']} ,deal_status = 1, modify_time = '" . $format . "' where lottery_order_id = {$val['lottery_order_id']} ;";
        }
        $orderUpdate = $db->createCommand($orderUp)->execute();
        if ($orderUpdate == false) {
            return [
                'code' => 0,
                'data' => $allWin[0]['lottery_order_id'] . '-' . $allWin[count($allWin) - 1]['lottery_order_id']
            ];
        }
        array_unshift($orderIds, 'dealed_with:footballs');
        $redis->executeCommand('SREM', $orderIds);
        return [
            'code' => 1,
            'data' => $allWin[0]['lottery_order_id'] . '-' . $allWin[count($allWin) - 1]['lottery_order_id']
        ];
    }

    /**
     * 合买派奖
     * @auther GL zyl
     * 
     * @return string
     */
    public function programmeAwardFunds() {
        $programme = Programme::find()->where([
                    'status' => 6,
                    'bet_status' => 7
                ])
                ->andWhere([
                    '<',
                    'win_amount',
                    10000
                ])
                ->one();
        if (empty($programme)) {
            $result = [
                'code' => 0,
                'data' => '暂无已兑奖且中奖的合买订单'
            ];
            return $result;
        }
        $format = date('Y-m-d H:i:s');
        $proUser = ProgrammeUser::find()->select([
                    'programme_user_id',
                    'cust_no',
                    'bet_money',
                    'buy_number',
                    'cust_type',
                    'win_amount'
                ])
                ->where([
                    'programme_id' => $programme->programme_id,
                    'deal_status' => 2
                ])
                ->asArray()
                ->all();
        if (empty($proUser)) {
            $programme->bet_status = 9;
            $programme->modify_time = $format;
            if (!$programme->save()) {
                return [
                    'code' => 0,
                    'data' => '合买表保存失败'
                ];
            }
            $order = LotteryOrder::find()->where([
                        'programme_code' => $programme->programme_code
                    ])->one();
            $order->deal_status = 3;
            $order->modify_time = $format;
            if (!$order->save()) {
                return [
                    'code' => 0,
                    'data' => '订单表保存失败'
                ];
            }
            return [
                'code' => 1,
                'data' => '该合买方案已全部派奖'
            ];
        }
        $updateStr = '';
        $errData = [];
        $storeNo = Store::find()->select([
                    'cust_no'
                ])
                ->where([
                    'user_id' => $programme->store_id
                ])
                ->asArray()
                ->one();
        $storeBalance = UserFunds::find()->select([
                    'able_funds'
                ])
                ->where([
                    'cust_no' => $storeNo['cust_no']
                ])
                ->asArray()
                ->one();
        if ($storeBalance['able_funds'] < $programme['bet_status']) {
            return [
                'code' => 0,
                'data' => '该方案所属出票门店资金暂时不足以支付奖金'
            ];
        }
        $royalty = floatval($programme->win_amount) * floatval($programme->royalty_ratio / 100);
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            LotteryOrder::upData([
                "award_amount" => $programme->win_amount
                    ], [
                "programme_code" => $programme->programme_code
            ]);
            if ($royalty != 0) {
                $expertRoyalty = $this->Prizes($programme->programme_code, $storeNo['cust_no'], $programme->expert_no, $royalty, $programme->cust_type, 14, '提成');
                if ($expertRoyalty['code'] != 1) {
                    if ($userWin['code'] == 109) {
                        throw new Exception('该方案所属出票门店资金暂时不足以支付奖金');
                    }
                }
            }
            foreach ($proUser as $value) {
                $winMoney = $value['win_amount'];
                $userWin = $this->Prizes($programme->programme_code, $storeNo['cust_no'], $value['cust_no'], $winMoney, $value['cust_type']);
                if ($userWin['code'] != 1) {
                    if ($userWin['code'] == 109) {
                        throw new Exception('该方案所属出票门店资金暂时不足以支付奖金');
                    }
                    $errData[] = $value['programme_user_id'];
                    continue;
                }
                $updateStr .= "update programme_user set deal_status = 6 where programme_user_id = {$value['programme_user_id']};";
            }
            $updateStr .= "update lottery_order set deal_status = 3, award_time = '" . $format . "', modify_time = '" . $format . "' where lottery_order_id = {$val['lottery_order_id']} ;";
            $tran->commit();
        } catch (Exception $ex) {
            $tran->rollBack();
            return [
                'code' => 0,
                'data' => $ex->getMessage()
            ];
        }
        $programmeIds = $db->createCommand($updateStr)->execute();
        $idStr = $proUser[0]['programme_user_id'] . '-' . $proUser[count($proUser) - 1]['programme_user_id'];
        if ($programmeIds == FALSE) {
            $result = [
                'code' => 0,
                'data' => $idStr
            ];
        }
        $result = [
            'code' => 1,
            'data' => $idStr
        ];
        return $result;
    }

    /**
     * 合买派奖
     * 
     * @param type $code
     *            编码
     * @param type $storeNo
     *            门店cust_no
     * @param type $userNo
     *            会员cust_no
     * @param type $money
     *            中奖金额
     * @param type $custType
     *            类型
     * @param type $payType            
     * @param type $payTypeName            
     * @return type
     * @throws Exception
     */
    public function Prizes($code, $storeNo, $userNo, $money, $custType = 1, $payType = 15, $payTypeName = '奖金') {
        $format = date('Y-m-d H:i:s');
        $fundsService = new FundsService();
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            $user = User::findOne([
                        "cust_no" => $userNo
            ]);
            if (empty($user)) {
                return \Yii::jsonResult(109, "未找到会员", "");
            }
            $storePay = new PayRecord();
            $storePay->order_code = $code;
            $storePay->cust_no = $storeNo;
            $storePay->cust_type = 2;
            $storePay->pay_no = Commonfun::getCode('PAY', 'L');
            $storePay->pay_pre_money = $money;
            $storePay->pay_money = $money;
            $storePay->pay_name = '余额';
            $storePay->way_name = '余额';
            $storePay->way_type = 'YE';
            $storePay->pay_way = 3;
            $storePay->pay_type_name = '奖金发放';
            $storePay->pay_type = 11;
            $storePay->body = '奖金发放-' . (substr($user->user_tel, - 4)) . "({$user->user_name})";
            $storePay->status = 0;
            $storePay->create_time = $format;
            if (!$storePay->validate()) {
                throw new Exception('B明细表验证失败');
            }
            if (!$storePay->saveData()) {
                throw new Exception('B保存失败');
            }

            $userPay = new PayRecord();
            $userPay->order_code = $code;
            $userPay->cust_no = $userNo;
            $userPay->cust_type = $custType;
            $userPay->pay_no = Commonfun::getCode('PAY', 'L');
            $userPay->pay_pre_money = $money;
            $userPay->pay_money = $money;
            $userPay->pay_name = '余额';
            $userPay->way_name = '余额';
            $userPay->way_type = 'YE';
            $userPay->pay_way = 3;
            $userPay->pay_type_name = $payTypeName;
            $userPay->pay_type = $payType;
            $userPay->body = $payTypeName;
            $userPay->status = 0;
            $userPay->create_time = $format;
            if (!$userPay->validate()) {
                throw new Exception('C明细表验证失败');
            }
            if (!$userPay->saveData()) {
                throw new Exception('C保存失败');
            }

            $storeFunds = $fundsService->operateUserFunds($storeNo, - $money, - $money, 0, true, "兑奖");
            if ($storeFunds['code'] != 0) {
                $trans->rollBack();
                return [
                    'code' => 109,
                    'msg' => $storeFunds['msg']
                ];
            }
            $storeBalance = UserFunds::find()->select([
                        'all_funds'
                    ])
                    ->where([
                        'cust_no' => $storeNo
                    ])
                    ->asArray()
                    ->one();
            $storePay->balance = $storeBalance['all_funds'];
            $storePay->status = 1;
            $storePay->pay_time = $format;
            $storePay->modify_time = date('Y-m-d H:i:s');
            if (!$storePay->saveData()) {
                throw new Exception('B修改失败');
            }

            $userFunds = $fundsService->operateUserFunds($userNo, $money, $money, 0, false, '兑奖');
            if ($userFunds['code'] != 0) {
                throw new Exception($userFunds['msg']);
            }
            $userBalance = UserFunds::find()->select([
                        'all_funds'
                    ])
                    ->where([
                        'cust_no' => $userNo
                    ])
                    ->asArray()
                    ->one();
            $userPay->balance = $userBalance['all_funds'];
            $userPay->status = 1;
            $userPay->pay_time = date('Y-m-d H:i:s');
            $userPay->modify_time = date('Y-m-d H:i:s');
            if (!$userPay->saveData()) {
                throw new Exception('C修改失败');
            }
            $trans->commit();
            $result = [
                'code' => 1,
                'msg' => '成功'
            ];
        } catch (Exception $ex) {
            $trans->rollBack();
            $result = [
                'code' => 0,
                'msg' => $ex->getMessage()
            ];
        }
        return $result;
    }

//    /**
//     * 计划派奖
//     * @auther GL zyl
//     *
//     * @return string
//     */
//    public function planAwardsFunds() {
//        $order = LotteryOrder::find()->select([
//                    'lottery_order.lottery_order_id',
//                    'lottery_order.lottery_order_code',
//                    'lottery_order.win_amount',
//                    'lottery_order.bet_money',
//                    'lottery_order.cust_no',
//                    'up.user_plan_id',
//                    'up.user_plan_code',
//                    'up.store_id',
//                    'up.win_type'
//                ])
//                ->leftJoin('user_plan as up', 'up.user_plan_id = lottery_order.user_plan_id')
//                ->where([
//                    'lottery_order.deal_status' => 1,
//                    'lottery_order.status' => 4,
//                    'lottery_order.source' => 6
//                ])
//                ->andWhere([
//                    '<',
//                    'lottery_order.win_amount',
//                    10000
//                ])
//                ->limit(5000)
//                ->asArray()
//                ->all();
//        if (empty($order)) {
//            $result = [
//                'code' => 0,
//                'data' => '暂无已兑奖且中奖的计划订单'
//            ];
//            return $result;
//        }
//        $upPlan = '';
//        $format = date('Y-m-d H:i:s');
//        foreach ($order as $val) {
//            $profit = $val['win_amount'] - $val['bet_money'];
//            if ($profit < 0) {
//                $profit = 0;
//            }
//            $upPlan .= "update lottery_order set award_amount = {$val['win_amount']} where lottery_order_id = {$val['lottery_order_id']} ;";
//            if ($val['win_type'] == 1) {
//                $upPlan .= "update user_plan set able_funds = able_funds + {$val['win_amount']}, win_amount = win_amount + {$val['win_amount']}, total_profit = total_profit + {$profit}, modify_time = '" . $format . "' where user_plan_id = {$val['user_plan_id']} ;";
//            } else {
//                $storeNo = Store::find()->select([
//                            'cust_no'
//                        ])
//                        ->where([
//                            'user_id' => $val['store_id']
//                        ])
//                        ->asArray()
//                        ->one();
//                $storeBalance = UserFunds::find()->select([
//                            'able_funds'
//                        ])
//                        ->where([
//                            'cust_no' => $storeNo['cust_no']
//                        ])
//                        ->asArray()
//                        ->one();
//                if ($storeBalance['able_funds'] < $val['win_amount']) {
//                    return [
//                        'code' => 0,
//                        'data' => '该方案所属出票门店资金暂时不足以支付奖金'
//                    ];
//                }
//                $userWin = $this->Prizes($val['lottery_order_code'], $storeNo['cust_no'], $val['cust_no'], $val['win_amount']);
//                if ($userWin['code'] == 0) {
//                    // 日志
//                    continue;
//                }
//                $upPlan .= "update user_plan set win_amount = win_amount + {$val['win_amount']}, total_profit = total_profit + {$profit}, modify_time = '" . $format . "' where user_plan_id = {$val['user_plan_id']} ;";
//            }
//            $upPlan .= "update lottery_order set deal_status = 3, award_time = '" . $format . "', modify_time = '" . $format . "' where lottery_order_id = {$val['lottery_order_id']} ;";
//        }
//        $db = Yii::$app->db;
//        $orderIds = $db->createCommand($upPlan)->execute();
//        $idStr = $order[0]['lottery_order_id'] . '-' . $order[count($order) - 1]['lottery_order_id'];
//        if ($orderIds == FALSE) {
//            $result = [
//                'code' => 0,
//                'data' => $idStr
//            ];
//        }
//        $result = [
//            'code' => 1,
//            'data' => $idStr
//        ];
//        return $result;
//    }

    /**
     * 任选详情单的兑奖
     * @auther GL zyl
     * 
     * @param type $periods
     *            需兑奖期数
     * @return int
     */
    public function getOptionalWinning($periods) {
        $result = FootballFourteen::find()->where([
                    'periods' => $periods,
                    'status' => 3
                ])->one();
        $code = [
            4001,
            4002
        ];
        $res = [
            'code' => 2,
            'data' => ''
        ];
        if (!empty($result)) {
            $condition = [];
            $allBet = BettingDetail::find()->select('betting_detail_id,lottery_order_id,bet_val,lottery_id,bet_double')
                    ->where([
                        'periods' => $result['periods'],
                        'deal_status' => 0,
                        'status' => 3
                    ])
                    ->andWhere([
                        'in',
                        'lottery_id',
                        $code
                    ])
                    ->limit(10000)
                    ->orderBy('betting_detail_id')
                    ->asArray()
                    ->all();
            if (!empty($allBet)) {
                $arr = explode(',', $result['schedule_results']);
                $firstPrize = $result['first_prize'];
                $secondPrize = $result['second_prize'];
                $ninePrize = $result['nine_prize'];
                foreach ($allBet as $val) {
                    $betArr = explode(',', $val['bet_val']);
                    if ($val['lottery_id'] == 4001) {
                        $winData = $this->getFourteenCalcul($betArr, $arr, $firstPrize, $secondPrize);
                    } elseif ($val['lottery_id'] == 4002) {
                        $winData = $this->getNineCalcul($betArr, $arr, $ninePrize);
                    }
                    $winLevel = $winData['win_level'];
                    $isWin = $winData['is_win'];
                    $winAmount = floatval($winData['win_amount']) * floatval($val['bet_double']);
                    $condition[] = [
                        'bet_id' => $val['betting_detail_id'],
                        'win_amount' => $winAmount,
                        'win_level' => $winLevel,
                        'is_win' => $isWin
                    ];
                }

                $this->getUpdateDetail($condition);
                $idStr = $allBet[0]['betting_detail_id'] . '-' . $allBet[count($allBet) - 1]['betting_detail_id'];
                $result->win_status = FootballFourteen::WIN_STATUS_WINNING;
                $result->modify_time = date('Y-m-d H:i:s');
                $id = $result->save();
                if ($id == false) {
                    $res = [
                        'code' => 0,
                        'data' => $idStr
                    ];
                } else {
                    $res = [
                        'code' => 1,
                        'data' => $idStr
                    ];
                }
            } else {
                $result->win_status = FootballFourteen::WIN_STATUS_WON;
                $result->modify_time = date('Y-m-d H:i:s');
                $id = $result->save();
                if ($id == false) {
                    $res = [
                        'code' => 0,
                        'data' => '开奖结果表更新失败'
                    ];
                } else {
                    $res = [
                        'code' => 1,
                        'data' => '详情单已兑奖完'
                    ];
                }
            }
        }
        return $res;
    }

    /**
     * 任选14的奖金计算
     * 
     * @param array $betArr
     *            投注内容
     * @param array $resultArr
     *            开奖结果
     * @param float $firstPrize
     *            任选14一等奖奖金
     * @param float $secondPrize
     *            任选14二等奖奖金
     * @return array
     */
    public function getFourteenCalcul($betArr, $resultArr, $firstPrize, $secondPrize) {
        $winLevel = 0;
        $winAmount = 0;
        $isWin = 5;
        $ii = 0;
        foreach ($betArr as $key => $val) {
            if ($val == $resultArr[$key]) {
                $ii ++;
            }
        }
        if ($ii == 14) {
            $winLevel = 1;
            $winAmount = $firstPrize;
            $isWin = 4;
        } elseif ($ii == 13) {
            $isWin = 4;
            $winAmount = $secondPrize;
            $winLevel = 2;
        } else {
            $isWin = 5;
            $winAmount = 0;
            $winLevel = 0;
        }
        $result = [
            'win_level' => $winLevel,
            'win_amount' => $winAmount,
            'is_win' => $isWin
        ];
        return $result;
    }

    /**
     * 任选九奖金计算
     * 
     * @param array $betArr
     *            投注内容
     * @param array $resultArr
     *            开奖结果
     * @param float $ninePrize
     *            任选九奖金
     * @return array
     */
    public function getNineCalcul($betArr, $resultArr, $ninePrize) {
        $winLevel = 0;
        $winAmount = 0;
        $isWin = 5;
        $ii = 0;
        foreach ($betArr as $key => $val) {
            if ($val == $resultArr[$key]) {
                $ii ++;
            }
        }
        if ($ii == 9) {
            $isWin = 4;
            $winAmount = $ninePrize;
            $winLevel = 1;
        } else {
            $isWin = 5;
            $winAmount = 0;
            $winLevel = 0;
        }
        $result = [
            'win_level' => $winLevel,
            'win_amount' => $winAmount,
            'is_win' => $isWin
        ];
        return $result;
    }

    /**
     * 合买子单兑奖
     * @auther GL zyl
     * 
     * @return string|int
     */
    public function programmeUserAwardFunds() {
        $programme = Programme::find()->where([
                    'status' => 6,
                    'bet_status' => 6
                ])->one();
        if (empty($programme)) {
            $result = [
                'code' => 0,
                'data' => '暂无已兑奖且中奖的合买订单'
            ];
            return $result;
        }
        $detailWin = LotteryOrder::find()->select([
                    'b.win_amount',
                    'b.bet_double'
                ])
                ->leftJoin('betting_detail as b', 'b.lottery_id = lottery_order.lottery_id')
                ->where([
                    'source_id' => $programme->programme_id,
                    'b.status' => 4
                ])
                ->asArray()
                ->all();
        $format = date('Y-m-d H:i:s');
        $winAmount = 0;
        foreach ($detailWin as $val) {
            $win = floatval($val['win_amount']);
            $sigin = $win / floatval($val['bet_double']);
            if ($sigin > 10000) {
                $win *= 0.2;
            }
            $winAmount += $win;
        }
        $proUser = ProgrammeUser::find()->select([
                    'programme_user_id',
                    'cust_no',
                    'user_id',
                    'bet_money',
                    'buy_number',
                    'cust_type',
                    'create_time'
                ])
                ->where([
                    'programme_id' => $programme->programme_id,
                    'deal_status' => 1
                ])
                ->asArray()
                ->all();
        if (empty($proUser)) {
            $programme->bet_status = 6;
            $programme->modify_time = $format;
            if (!$programme->save()) {
                return [
                    'code' => 0,
                    'data' => '合买表保存失败'
                ];
            }
            return [
                'code' => 1,
                'data' => '该合买方案子单已全部兑奖'
            ];
        }
        $updateStr = '';
        $ratio = floatval($programme->royalty_ratio) / 100;
        $royalty = $programme->win_amount * $ratio;
        $laveMoney = floatval($programme->win_amount) - $royalty;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        foreach ($proUser as $value) {
            $ratio = floatval($value['bet_money']) / floatval($programme->bet_money);
            $winMoney = round($laveMoney * $ratio, 2);
            $updateStr .= "update programme_user set win_amount = {$winMoney}, deal_status = 2 where programme_user_id = {$value['programme_user_id']};";

            $title = '恭喜您，中奖啦！';
            $resultMsg = '中奖 ' . $winMoney . ' 元';
            $remark = '奖金稍后发至您的账户余额内，请注意查收！';

            if (in_array($programme->lottery_code, $football)) {
                $betMsg = '竞彩足球';
            } else {
                $betMsg = $programme->lottery_name . $programme->periods . ' 期';
            }
            $userMsg = ThirdUser::find()->select('third_uid')
                    ->where([
                        'uid' => $value['user_id']
                    ])
                    ->asArray()
                    ->one();
            $betMoney = $value['bet_money'];
            $betTime = $value['create_time'];

            if ($userMsg['third_uid']) {
                $wechatTool = new WechatTool();
                $wechatTool->sendTemplateMsgAwards($title, $userMsg['third_uid'], $resultMsg, $betMsg, $betMoney, $betTime, $remark, $programme->lottery_order_code);
            }
        }
        $db = Yii::$app->db;
        $programmeIds = $db->createCommand($updateStr)->execute();
        $idStr = $proUser[0]['programme_user_id'] . '-' . $proUser[count($proUser) - 1]['programme_user_id'];
        if ($programmeIds == FALSE) {
            $result = [
                'code' => 0,
                'data' => $idStr
            ];
        }
        $result = [
            'code' => 1,
            'data' => $idStr
        ];
        return $result;
    }

    /**
     * 粤11X5 兑奖
     * 
     * @param type $periods            
     * @param type $openNums            
     * @return type
     * @throws Exception
     */
    public function lottery11X5Level($lotteryCode, $periods, $openNums) {
        $lottery = Constants::LOTTERY;
        if ($lotteryCode == 2005) {
            $sql = "call CheckGD11X5('{$openNums}', '{$periods}'); ";
        } elseif ($lotteryCode == 2006) {
            $sql = "call CheckJX11X5('{$openNums}', '{$periods}'); ";
        } elseif ($lotteryCode == 2007) {
            $sql = "call CheckYDJ11X5('{$openNums}', '{$periods}'); ";
        } elseif ($lotteryCode == 2010) {
            $sql = "call CheckHB11X5('{$openNums}', '{$periods}'); ";
        } elseif ($lotteryCode == 2011) {
            $sql = "call CheckFJ11X5('{$openNums}', '{$periods}'); ";
        } else {
            return false;
        }
        $lotteryName = $lottery[$lotteryCode];
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1); // 1:返回条数 日志所需
            $remark = $lotteryName . " - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => $lotteryCode,
                'periods' => $periods,
                'open_num' => $openNums,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $openNums];
            SyncApiRequestService::awardLottery($lotteryCode, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 竞彩篮球兑奖
     * 
     * @param type $mid
     *            赛程MID
     * @param type $visitFen
     *            客队得分
     * @param type $homeFen
     *            主负得分
     * @return type
     * @throws Exception
     */
    public function basketballLevel($mid, $visitFen, $homeFen) {
        $sql = "call CheckBskBall('{$mid}', $visitFen, $homeFen); call CheckLQ_Deal('{$mid}', $visitFen, $homeFen);";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "篮球 - 兑奖完成!成功执行:{$ret['Update_Row_Count']}条";
            $data = [
                'lottery_code' => 3100,
                'periods' => $mid,
                'open_num' => $visitFen . ':' . $homeFen,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $mid, 'FKe' => $visitFen, 'FZhu' => $homeFen];
            SyncApiRequestService::awardLottery(3100, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 胜负彩的对奖
     * 
     * @param type $result
     *            开奖结果
     * @param type $periods
     *            期数
     * @param type $firstP
     *            胜负彩一等奖
     * @param type $secondP
     *            胜负彩二等奖
     * @param type $nineP
     *            任选九奖金
     * @return type
     * @throws Exception
     */
    public function optionalLevel($result, $periods, $firstP, $secondP, $nineP) {
        $sql = "call CheckZQ_SFC('{$result}', '{$periods}', $firstP, $secondP, $nineP); ";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "足球任选 - 兑奖完成!成功执行:{$ret['UpdateRowCount']}条";
            $data = [
                'lottery_code' => 4001,
                'periods' => $periods,
                'open_num' => $result,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $periods, 'zj' => $result, 'jj1' => $firstP, 'jj2' => $secondP, 'jj3' => $nineP];
            SyncApiRequestService::awardLottery(4000, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 足球竞彩对奖
     * 
     * @param type $mid
     *            赛程MID
     * @param type $res3006
     *            3006开奖结果
     * @param type $res3007
     *            3007开奖结果
     * @param type $res3008
     *            3008开奖结果
     * @param type $res3009
     *            3009开奖结果
     * @param type $res3010
     *            3010开奖结果
     * @return type
     * @throws Exception
     */
    public function footballLevel($mid, $res3006, $res3007, $res3008, $res3009, $res3010) {
        $sql = "call CheckZQ('{$mid}', '{$res3006}', '{$res3007}', '{$res3008}', '{$res3009}', '{$res3010}'); ";
        $sql .= "call CheckZQ_Deal('{$mid}', '{$res3006}', '{$res3007}', '{$res3008}', '{$res3009}', '{$res3010}'); ";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "足球 - 兑奖完成!成功执行:{$ret['Update_Row_Count']}条";
            $data = [
                'lottery_code' => 4001,
                'periods' => $mid,
                'open_num' => $res3007,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $mid, 'f3006' => $res3006, 'f3007' => $res3007, 'f3008' => $res3008, 'f3009' => $res3009, 'f3010' => $res3010];
            SyncApiRequestService::awardLottery(3000, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 北单订单对奖
     * @param type $mid  赛程编号
     * @param type $r5001 让球胜平负
     * @param type $r5002 总进球数
     * @param type $r5003 半全场胜负
     * @param type $r5004 上下单双
     * @param type $r5005 比分
     * @param type $r5006 胜负过关
     * @param type $odds5001 让球胜平负赔率
     * @param type $odds5002 总进球赔率
     * @param type $odds5003 半全场胜负赔率
     * @param type $odds5004 上下单双赔率
     * @param type $odds5005 比分赔率
     * @param type $odds5006 胜负过关赔率
     * @return type
     * @throws Exception
     */
    public function bdLevel($mid, $r5001, $r5002, $r5003, $r5004, $r5005, $r5006, $odds5001, $odds5002, $odds5003, $odds5004, $odds5005, $odds5006) {
//        print_r($odds5006);die;
        $sql = "call CheckBd('{$mid}', '{$r5001}', '{$r5002}', '{$r5003}', '{$r5004}', '{$r5005}', '{$r5006}', '{$odds5001}', '{$odds5002}', '{$odds5003}', '{$odds5004}', '{$odds5005}', '{$odds5006}'); ";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "北单 - 兑奖完成!成功执行:{$ret['Update_Row_Count']}条";
            $data = [
                'lottery_code' => 5000,
                'periods' => $mid,
                'open_num' => $r5005,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $mid, 'f5001' => $r5001, 'f5002' => $r5002, 'f5003' => $r5003, 'f5004' => $r5004, 'f5005' => $r5005, 'f5006' => $r5006, 'pl5001' => $odds5001, 'pl5002' => $odds5002,
                'pl5003' => $odds5003, 'pl5004' => $odds5004, 'pl5005' => $odds5005, 'pl5006' => $odds5006];
            SyncApiRequestService::awardLottery(5000, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    //北单取消赛程对奖
    public function bdCancelLevel($mid) {
        $sql = "call CheckBd_Cancel('{$mid}'); ";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "北单 - 兑奖完成!成功执行:{$ret['Update_Row_Count']}条";
            $data = [
                'lottery_code' => 5000,
                'periods' => $mid,
                'open_num' => '',
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $mid];
            SyncApiRequestService::awardLottery('BD_Cancel', $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 取消赛程对奖
     * @param type $scheduleMid 赛程MID
     * @return type
     * @throws \app\modules\orders\helpers\Exception
     */
    public function cancelLevel($scheduleMid, $code) {
        $str = '';
        if ($code == 3000) {
            $str = '足球';
            $sql = "call CheckZQ_Cancel('{$scheduleMid}'); ";
            $sql .= "call CheckZQCancel_Deal('{$scheduleMid}');";
            $backStr = 'ZQ_CANCEL';
        } elseif ($code == 3100) {
            $str = '篮球';
            $sql = "call CheckLQ_Cancel('{$scheduleMid}'); ";
            $sql .= "call CheckLQCancel_Deal('{$scheduleMid}');";
            $backStr = 'LQ_CANCEL';
        }
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = $str . " - 兑奖完成!成功执行:{$ret['Update_Row_Count']}条";
            $data = [
                'lottery_code' => 4001,
                'periods' => $scheduleMid,
                'open_num' => '',
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
            $retdata = ['periods' => $scheduleMid];
            SyncApiRequestService::awardLottery($backStr, $retdata);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }
    
    /**
     * 流量单派奖
     * @param type $orderId
     * @return type
     * @throws Exception
     */
    public static function doAwardThird($orderCode) {
        $field = ['lottery_order.lottery_order_id', 'lottery_order.lottery_order_code', 'lottery_order.win_amount', 'lottery_order.zmf_award_money',
            'lottery_order.deal_status', 'lottery_order.status', 'lottery_order.cust_no', 'lottery_order.lottery_type', 'lottery_order.lottery_id', 'lottery_order.user_id'];
        $orderData = LotteryOrder::find()->select($field)
                ->innerJoin('api_order a', 'a.api_order_id = lottery_order.source_id')
                ->andWhere(['lottery_order.source' => 7, 'lottery_order.status' => 4, 'lottery_order.lottery_order_code' => $orderCode])
                ->andWhere(['in', 'lottery_order.deal_status', [1, 2]])
                ->groupBy('lottery_order.lottery_order_id')
                ->asArray()
                ->one();
        $db = \Yii::$app->db;
        if(!$orderData){
        	KafkaService::addLog('doAwardThird-nodata', $orderCode);
        	return;
        }
        $trans = $db->beginTransaction();
        try {
            $thirdWinAmount = SyncApiRequestService::getWinAmount($orderData['lottery_order_code']);
            if ($thirdWinAmount['code'] != 600) {
            }
                throw new Exception('获取第三方中奖金额失败, 请稍后再试！！');
            if (empty($orderData['zmf_award_money'])) {
            	throw new Exception('获取出票方中奖金额失败, 请稍后再试');
            }
            if (bccomp($thirdWinAmount['win_amount'], $orderData['zmf_award_money'], 2) != 0) {
            	KafkaService::addLog('doAwardThird-money', ['money1'=>$thirdWinAmount['win_amount'],'money2'=>$orderData['zmf_award_money']]);
                throw new Exception('订单:' . $orderData['lottery_order_code'] . '中奖金额与第三方不匹配<br/>');
            }
            $winAmount = $orderData['zmf_award_money'];
            if ($orderData['lottery_type'] == 1 && $winAmount > 10000) {
                throw new Exception('订单:' . $orderData['lottery_order_code'] . '中奖金额大于10000,请核对再派奖<br/>');
            }
            $orderUpdate = ['award_amount' => $winAmount, 'deal_status' => 3, 'award_time' => date('Y-m-d H:i:s'), 'modify_time' => date('Y-m-d H:i:s')];
            $orderWhere = ['lottery_order_id' => $orderData['lottery_order_id'], 'deal_status' => $orderData['deal_status']];
            $order = LotteryOrder::updateAll($orderUpdate, $orderWhere);
            if ($order === false) {
                throw new Exception('订单:' . $orderData['lottery_order_code'] . '派奖失败, 订单状态更新失败<br/>');
            }
            $fundUpdate = ['all_funds' => new Expression('all_funds+' . $winAmount), 'able_funds' => new Expression('able_funds+' . $winAmount), 'modify_time' => date('Y-m-d H:i:s')];
            $fundWhere = ['user_id' => $orderData['user_id'], 'cust_no' => $orderData['cust_no']];
            $userFund = UserFunds::updateAll($fundUpdate, $fundWhere);
            if ($userFund === false) {
                throw new Exception('订单:' . $orderData['lottery_order_code'] . '派奖失败, 余额更新失败<br/>');
            }
            $funds = UserFunds::find()->select(['all_funds'])->where(['cust_no' => $orderData['cust_no']])->asArray()->one();
            $userPay = new PayRecord();
            $userPay->order_code = $orderData['lottery_order_code'];
            $userPay->cust_no = $orderData['cust_no'];
            $userPay->cust_type = 1;
            $userPay->pay_no = Commonfun::getCode('PAY', 'L');
            $userPay->pay_pre_money = $winAmount;
            $userPay->pay_money = $winAmount;
            $userPay->pay_name = '余额';
            $userPay->way_name = '余额';
            $userPay->way_type = 'YE';
            $userPay->pay_way = 3;
            $userPay->pay_type_name = '奖金';
            $userPay->pay_type = 15;
            $userPay->body = '奖金';
            $userPay->status = 1;
            $userPay->balance = $funds['all_funds'];
            $userPay->pay_time = date('Y-m-d H:i:s');
            $userPay->create_time = date('Y-m-d H:i:s');
            $userPay->modify_time = date('Y-m-d H:i:s');
            if (!$userPay->save()) {
                throw new Exception('订单:' . $orderData['lottery_order_code'] . '派奖失败, 交易处理明细写入失败<br/>');
            }
            $trans->commit();
            return ['code' => 600, 'msg' => '成功', 'data' => '订单:' . $orderData['lottery_order_code'] . '派奖成功<br/>'];
//                    $errorArr[] = ;
        } catch (Exception $ex) {
            $trans->rollBack();
//                    $errorArr[] = $ex->getMessage();
            return ['code' => 109, 'msg' => '失败', 'data' => $ex->getMessage()];
        }
//            }
//            return $this->jsonResult(600, '操作成功', $errorArr);
    }

}
