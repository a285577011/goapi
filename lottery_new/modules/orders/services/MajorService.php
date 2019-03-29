<?php

namespace app\modules\orders\services;

use app\modules\orders\models\MajorData;
use app\modules\common\helpers\Constants;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\BettingDetail;

class MajorService {

    /**
     * 写入奖金优化字表
     * @auther GL zyl
     * @param type $orderId 订单ID
     * @param type $majorData 优化数据
     * @param type $majorType 优化类型
     * @param type $source 来源
     * @return type
     */
    public function createMajor($orderId, $majorData, $majorType, $source = 1) {
        $majorModel = new MajorData;
        $majorModel->order_id = $orderId;
        $majorModel->major = $majorData;
        $majorModel->major_type = $majorType;
        $majorModel->source = $source;
        $majorModel->create_time = date('Y-m-d H:i:s');
        if (!$majorModel->validate()) {
            return ['code' => 109, 'msg' => '数据验证失败'];
        }
        if (!$majorModel->save()) {
            return ['code' => 109, 'msg' => '数据存储失败'];
        }
        return ['code' => 600, 'msg' => '成功'];
    }

    /**
     * 奖金优化生成子单
     * @auther  GL zyl
     * @param type $model 订单信息
     * @return type
     */
    public function proSubOrder($model) {
        $infos = [];
        $keys = ['agent_id', 'bet_double', 'bet_money', 'bet_val', 'betting_detail_code', 'create_time', 'is_bet_add', 'lottery_id', 'lottery_name', 'lottery_order_code', 'lottery_order_id', 'modify_time', 'one_money',
            'periods', 'play_code', 'schedule_nums', 'status', 'cust_no', 'play_name', 'odds', 'win_amount', 'fen_json'];
        $infos["agent_id"] = $model->agent_id;
        $infos["bet_double"] = $model->bet_double;
        $infos["bet_money"] = $model->bet_money;
        $infos["is_bet_add"] = $model->is_bet_add;
        $infos["lottery_id"] = $model->lottery_id;
        $infos["lottery_name"] = $model->lottery_name;
        $infos["lottery_order_code"] = $model->lottery_order_code;
        $infos["lottery_order_id"] = $model->lottery_order_id;
        $infos["opt_id"] = $model->opt_id;
        $infos["periods"] = $model->periods;
        $infos["status"] = $model->status;
        $infos["cust_no"] = $model->cust_no;
        $infos["count"] = $model->count;
        $infos["odds"] = $model->odds;
        $lotteryCode = $infos['lottery_id'];
        $playName = Constants::MANNER;
        $price = Constants::PRICE;
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $footballCode = Constants::MADE_FOOTBALL_LOTTERY;
        $basketballCode = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdCode = CompetConst::MADE_BD_LOTTERY;
//        $chuan = Constants::CHUAN_CODE;
        $chuan = CompetConst::AUTO_CHANG;
        $source = $model->source;
        if ($source == 4) {
            $orderId = $model->source_id;
            $source = 2;
        } elseif ($source == 7) {
            $orderId = $model->source_id;
            $source = 7;
        } else {
            $source = 1;
            $orderId = $model->lottery_order_id;
        }
        $major = MajorData::find()->select(['major'])->where(['order_id' => $orderId, 'source' => $source])->asArray()->one();
        if (empty($major)) {
            return ['code' => 2, 'msg' => '优化数据不存在'];
        }
        $majorData = json_decode($major['major'], true);
        if ($lotteryCode != "3005" && $lotteryCode != '3011') {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $odds = json_decode($infos['odds'], true);
        $oddStr = '';
        $jsonFen = '';
        $winAmount = 0;
        $format = date('Y-m-d H:i:s');
        $db = \Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            foreach ($majorData as &$vm) {
                $count = ceil($vm['mul'] / 99);
                $vm['sub'] = rtrim($vm['sub'], '|');
                $allBetDouble = $vm['mul'];
                for ($num = 1; $num <= $count; $num++) {
                    if ($allBetDouble > 99) {
                        $betDouble = 99;
                    } else {
                        $betDouble = $allBetDouble;
                    }
                    $allBetDouble = $allBetDouble - $betDouble;
                    if(in_array($lotteryCode, $footballCode) || in_array($lotteryCode, $basketballCode)){
                        $dealNums = $chuan[$vm['subplay']];
                        $betArr = explode('|', $vm['sub']);
                        $oddsAmount = 1;
                        $fenData = [];
                        foreach ($betArr as $it) {
                            preg_match($pattern, $it, $res);
                            if ($lotteryCode != 3005 && $lotteryCode != 3011) {
                                $oddsAmount *= $odds[$lotteryCode][$res[1]][$res[2]];
                                if ($lotteryCode == 3002) {
                                    $fenData[$res[1]] = $odds[$lotteryCode][$res[1]]['rf_nums'];
                                } elseif ($lotteryCode == 3004) {
                                    $fenData[$res[1]] = $odds[$lotteryCode][$res[1]]['fen_cutoff'];
                                }
                            } else {
                                $str = explode('*', $res[2]);
                                preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                                $oddsAmount *= $odds[$r[1]][$res[1]][$r[2]];
                                if ($r[1] == 3002) {
                                    $fenData[$res[1]] = $odds[$r[1]][$res[1]]['rf_nums'];
                                } elseif ($r[1] == 3004) {
                                    $fenData[$res[1]] = $odds[$r[1]][$res[1]]['fen_cutoff'];
                                }
                            }
                        }
                        $oddStr = $oddsAmount;
                        $jsonFen = json_encode($fenData);
                    } elseif (in_array($infos['lottery_id'], $bdCode)) {
                        $dealNums = $chuan[$vm['subplay']];
                        $oddStr = '';
                    } else {
                        $dealNums = 1;
                    }
                    $vals[] = [$infos["agent_id"], $betDouble, $betDouble * $price, $vm["sub"], Commonfun::getCode($lotteryType[$infos["lottery_id"]], "X"), $format, $infos["is_bet_add"], $infos["lottery_id"], $infos["lottery_name"],
                        $infos["lottery_order_code"], $infos["lottery_order_id"], $format, $price, $infos["periods"], $vm["subplay"], $dealNums, $infos["status"], $infos["cust_no"], $playName[$vm['subplay']], $oddStr, $winAmount, $jsonFen];
                }
            }
            $insertCount = $db->createCommand()->batchInsert("betting_detail", $keys, $vals)->execute();
            $firstId = \Yii::$app->db->getLastInsertID();
            $trans->commit();
            $lastId = $firstId + $insertCount - 1;
            for ($i = $firstId; $i <= $lastId; $i++) {
                BettingDetail::addQueSync(BettingDetail::$syncInsertType, '*', ['betting_detail_id' => $i]);
            }
            return ['code' => 0, 'msg' => "操作成功！", 'data' => $majorData];
        } catch (\yii\db\Exception $e) {
            $trans->rollBack();
            return ['code' => 2, 'data' => $e, 'msg' => '抛出错误！'];
        }
    }

}
