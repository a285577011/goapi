<?php

namespace app\modules\common\helpers;

use Yii;
use app\modules\common\helpers\Constants;
use app\modules\common\models\LotteryRecord;
use app\modules\common\models\DirectTrendChart;
use app\modules\common\models\GroupTrendChart;
use app\modules\common\models\MultidigitTrendChart;
use app\modules\common\models\ElevenTrendChart;

class Trend {

    /**
     * 每个彩种开奖后，走势表的写入
     * auther GL ZYL
     * 
     */
    public function actionTrend() {
        $code = Constants::LOTTERY;
        $directArr = Constants::DIRECT_TREND;
        $groupArr = Constants::GROUP_TREND;
        $multiArr = Constants::MULTIDIGIT_TREND;
        $elevenArr = Constants::ELEVEN_TREND;
        foreach ($code as $key => $val) {
            if (in_array($key, $directArr)) {
                $lastPeriods = DirectTrendChart::find()->select(['periods'])->where(['lottery_code' => $key])->orderBy('periods desc')->asArray()->one();
                $nowPeriods = intval($lastPeriods['periods']) + 1;
            }
            if (in_array($key, $groupArr)) {
                $lastPeriods = GroupTrendChart::find()->select(['periods'])->where(['lottery_code' => $key])->orderBy('periods desc')->asArray()->one();
                $nowPeriods = intval($lastPeriods['periods']) + 1;
            }
            if (in_array($key, $multiArr)) {
                $lastPeriods = MultidigitTrendChart::find()->select(['periods'])->where(['lottery_code' => $key])->orderBy('periods desc')->asArray()->one();
                $nowPeriods = intval($lastPeriods['periods']) + 1;
            }
            if (in_array($key, $elevenArr)) {
                $lastPeriods = ElevenTrendChart::find()->select(['periods'])->where(['lottery_code' => $key])->orderBy('periods desc')->asArray()->one();
                $nowPeriods = intval($lastPeriods['periods']) + 1;
            }
            $newResult = LotteryRecord::find()->select(['lottery_code', 'periods', 'lottery_numbers'])->where(['lottery_code' => $key, 'periods' => $nowPeriods, 'status' => 2])->orderBy('periods desc')->asArray()->one();
            if (!empty($newResult)) {
                $this->getCreateTrend($newResult['lottery_code'], $newResult['periods'], $newResult['lottery_numbers']);
            }
        }
    }

    /**
     * 每个彩种开奖后，走势表的写入
     * auther GL ZYL
     * @param string $code
     * @param string $periods
     * @param string $open_nums
     * @return json
     */
    public function getCreateTrend($code, $periods, $open_nums, $type = 0, $test_nums = '') {
        $directArr = Constants::DIRECT_TREND;
        $groupArr = Constants::GROUP_TREND;
        $multiArr = Constants::MULTIDIGIT_TREND;
        $elevenArr = Constants::ELEVEN_TREND;
        $error = '';
        if (in_array($code, $directArr)) {
            $error = $this->getCreateDirect($code, $periods, $open_nums, $type);
        } elseif (in_array($code, $groupArr)) {
            $error = $this->getCreateGroup($code, $periods, $open_nums, $type, $test_nums);
        } elseif (in_array($code, $multiArr)) {
            $error = $this->getCreateMultidigit($code, $periods, $open_nums, $type);
        } elseif (in_array($code, $elevenArr)) {
            $error = $this->getCreateEleven($code, $periods, $open_nums, $type);
        } else {
            return ['code' => 109, 'msg' => '参数错误'];
        }
        return $error;
    }

    /**
     * 分红蓝区种类的走势表写入
     * auther GL ZYL
     * @param string $code
     * @param string $periods
     * @param string $open_nums
     * @return json
     */
    public function getCreateDirect($code, $periods, $open_nums, $type) {
        $lastTrend = DirectTrendChart::find()->where(['lottery_code' => $code])->andWhere(['<', 'periods', $periods])->orderBy('periods desc')->limit(1)->asArray()->one();
        $direct = DirectTrendChart::find()->where(['lottery_code' => $code, 'periods' => $periods])->one();
        if (!empty($direct)) {
            if ($type == 0) {
                return false;
            }
        }
        if (empty($lastTrend)) {
            return false;
        }
        $openArr = explode('|', $open_nums);
        $redOmission = '';
        $blueOmission = '';
        $redAnalyze = '';
        $blueAnalyze = [];
        $redTailOmiss = '';
        if (!empty($lastTrend)) {
            $lastOpen = explode('|', $lastTrend['open_code']);
            $redLastAnalyze = json_decode($lastTrend['red_analysis'], true);
            $redOmission = $this->getBallTrend($lastTrend['red_omission'], $openArr[0]);
            if (strlen($lastTrend['blue_omission']) > 2) {
                $blueOmission = $this->getBallTrend($lastTrend['blue_omission'], $openArr[1]);
            } else {
                $blueOmission = isset($openArr[1]) ? $openArr[1] : '';
            }
            if ($code == 1001 || $code == 2001) {
                $redData = $this->getRedAnaylze($lastOpen[0], $openArr[0], $redLastAnalyze, $lastTrend['redtail_omission']);
                $blueAnalyze = $this->getBlueAnalyze($openArr[1]);
                $redAnalyze = json_encode($redData['analyze']);
                $redTailOmiss = $redData['tail_omiss'];
            }
        }
        if (empty($direct)) {
            $direct = new DirectTrendChart();
            $direct->create_time = date('Y-m-d H:i:s');
        } else {
            $direct->modify_time = date('Y-m-d H:i:s');
        }
        $direct->lottery_name = $lastTrend['lottery_name'];
        $direct->lottery_code = $lastTrend['lottery_code'];
        $direct->periods = $periods;
        $direct->open_code = $open_nums;
        $direct->red_omission = $redOmission;
        $direct->blue_omission = $blueOmission;
        $direct->red_analysis = $redAnalyze;
        $direct->blue_analysis = json_encode($blueAnalyze);
        $direct->redtail_omission = $redTailOmiss;
        if (!$direct->validate()) {
            return false;
        } else {
            $trendId = $direct->save();
            if (!$trendId) {
                return false;
            }
            return true;
        }
    }

    /**
     * 红蓝区遗漏数的判断
     * auther GL ZYL
     * @param string $lastTrend
     * @param string $open
     * @return string
     */
    public function getBallTrend($lastTrend, $open) {
        $last = explode(',', $lastTrend);
        $openArr = explode(',', $open);
        foreach ($last as $key => $val) {
            if ($key < 9) {
                $k = '0' . $key + 1;
            } else {
                $k = $key + 1;
            }
            if (in_array($k, $openArr)) {
                $newsOmission[$key] = 0;
            } else {
                $newsOmission[$key] = $val + 1;
            }
        }
        $omission = implode(',', $newsOmission);
        return $omission;
    }

    /**
     * 组选式彩种的走势表写入
     * auther GL ZYL
     * @param string $code
     * @param string $periods
     * @param string $open_nums
     * @return json
     */
    public function getCreateGroup($code, $periods, $open_nums, $type, $test_nums = '') {
        $lastTrend = GroupTrendChart::find()->where(['lottery_code' => $code])->andWhere(['<', 'periods', $periods])->orderBy('periods desc')->limit(1)->asArray()->one();
        $group = GroupTrendChart::find()->where(['lottery_code' => $code, 'periods' => $periods])->one();
        if (!empty($group)) {
            if ($type == 0) {
                return false;
            }
        }
        $openArr = explode(',', $open_nums);
        if ($code == '2009') {
            $puke = Constants::PUKE_NUMS;
            $nums = array_flip($puke);
            foreach ($openArr as &$i) {
                $iv = explode('_', $i);
                $newNums = $nums[$iv[1]] - 1;
                $i = "{$newNums}";
            }
        }
        $hundredOmiss = '';
        $tenOmiss = '';
        $digitOmiss = '';
        $groupOmiss = '';
        $sumOmiss = '';
        $spanOmiss = '';
        $sumtailOmiss = '';
        $analyze = '';
        $data = [];
        if (!empty($lastTrend)) {
            $hundredOmiss = $this->getGroupTrend($lastTrend['hundred_omission'], $openArr[0]);
            $tenOmiss = $this->getGroupTrend($lastTrend['ten_omission'], $openArr[1]);
            $digitOmiss = $this->getGroupTrend($lastTrend['digits_omission'], $openArr[2]);
            $groupOmiss = $this->getGroupTrend($lastTrend['group_omission'], $openArr);
            if ($code != '2009') {
                $data = $this->getGroupAnalyze($openArr, $lastTrend['sum_omission'], $lastTrend['span_omission'], $lastTrend['sumtail_omission']);
                $sumOmiss = $data['other']['sumOmission'];
                $spanOmiss = $data['other']['spanOmission'];
                $sumtailOmiss = $data['other']['sumTailOmission'];
                $analyze = json_encode($data['analyze']);
            }  else {
                $sumOmiss = '';
                $spanOmiss = '';
                $sumtailOmiss = '';
                $analyze = '';
            }
        }
        if (empty($group)) {
            $group = new GroupTrendChart();
            $group->create_time = date('Y-m-d H:i:s');
        } else {
            $group->modify_time = date('Y-m-d H:i:s');
        }
        $group->lottery_name = $lastTrend['lottery_name'];
        $group->lottery_code = $code;
        $group->periods = $periods;
        $group->open_code = $open_nums;
        $group->hundred_omission = $hundredOmiss;
        $group->ten_omission = $tenOmiss;
        $group->digits_omission = $digitOmiss;
        $group->group_omission = $groupOmiss;
        $group->analysis = $analyze;
        $group->sum_omission = $sumOmiss;
        $group->span_omission = $spanOmiss;
        $group->sumtail_omission = $sumtailOmiss;
        $group->test_nums = $test_nums;
        if (!$group->validate()) {
            return false;
        } else {
            $trendId = $group->save();
            if (!$trendId) {
                return false;
            }
            return true;
        }
    }

    /**
     * 组选式遗漏数的判断
     * auther GL ZYL
     * @param string $lastTrend
     * @param string/array $open
     * @return string
     */
    public function getGroupTrend($lastTrend, $open) {
        $last = explode(',', $lastTrend);
        if (is_string($open)) {
            $openArr = explode(',', $open);
        } else {
            $openArr = $open;
        }
        foreach ($last as $key => $val) {
            if (in_array($key, $openArr)) {
                $newsOmission[$key] = 1 - array_count_values($openArr)[$key];
            } else {
                if ($val == 0) {
                    $newsOmission[$key] = 1;
                } elseif ($val == -1) {
                    $newsOmission[$key] = $val + 2;
                } elseif ($val == -2) {
                    $newsOmission[$key] = $val + 3;
                } else {
                    $newsOmission[$key] = $val + 1;
                }
            }
        }
        $omission = implode(',', $newsOmission);
        return $omission;
    }

    /**
     * 多位数投注式彩种的走势表写入
     * auther GL ZYL
     * @param string $code
     * @param string $periods
     * @param string $open_nums
     * @return json
     */
    public function getCreateMultidigit($code, $periods, $open_nums, $type) {
        $lastTrend = MultidigitTrendChart::find()->where(['lottery_code' => $code])->andWhere(['<', 'periods', $periods])->orderBy('periods desc')->limit(1)->asArray()->one();
        $multidigit = MultidigitTrendChart::find()->where(['lottery_code' => $code, 'periods' => $periods])->one();
        if (!empty($multidigit)) {
            if ($type == 0) {
                return false;
            }
        }
        $openArr = explode(',', $open_nums);
        $millionOmiss = '';
        $hundThousOmiss = '';
        $tenThousOmiss = '';
        $thousandOmiss = '';
        $hundredOmiss = '';
        $tenOmiss = '';
        $digitOmiss = '';
        if (!empty($lastTrend)) {
            if (count($openArr) == 5) {
                $tenThousOmiss = $this->getMultiTrend($lastTrend['ten_thousand_omission'], $openArr[0]);
                $thousandOmiss = $this->getMultiTrend($lastTrend['thousand_omission'], $openArr[1]);
                $hundredOmiss = $this->getMultiTrend($lastTrend['hundred_omission'], $openArr[2]);
                $tenOmiss = $this->getMultiTrend($lastTrend['ten_omission'], $openArr[3]);
                $digitOmiss = $this->getMultiTrend($lastTrend['digits_omission'], $openArr[4]);
                $data = $this->getMultiAnalyze($code, $openArr, $lastTrend['sum_omission'], $lastTrend['span_omission'], $lastTrend['sumtail_omission']);
                $sumOmiss = $data['other']['sumOmission'];
                $spanOmiss = $data['other']['spanOmission'];
                $sumtailOmiss = $data['other']['sumTailOmission'];
                $analyze = json_encode($data['analyze']);
            } elseif (count($openArr) == 7) {
                $millionOmiss = $this->getMultiTrend($lastTrend['million_omission'], $openArr[0]);
                $hundThousOmiss = $this->getMultiTrend($lastTrend['hundred_thousand_omission'], $openArr[1]);
                $tenThousOmiss = $this->getMultiTrend($lastTrend['ten_thousand_omission'], $openArr[2]);
                $thousandOmiss = $this->getMultiTrend($lastTrend['thousand_omission'], $openArr[3]);
                $hundredOmiss = $this->getMultiTrend($lastTrend['hundred_omission'], $openArr[4]);
                $tenOmiss = $this->getMultiTrend($lastTrend['ten_omission'], $openArr[5]);
                $digitOmiss = $this->getMultiTrend($lastTrend['digits_omission'], $openArr[6]);
                $data = $this->getMultiAnalyze($code, $openArr, $lastTrend['sum_omission'], $lastTrend['span_omission'], $lastTrend['sumtail_omission']);
                $sumOmiss = $data['other']['sumOmission'];
                $spanOmiss = $data['other']['spanOmission'];
                $sumtailOmiss = $data['other']['sumTailOmission'];
                $analyze = json_encode($data['analyze']);
            }
        }
        if (empty($multidigit)) {
            $multidigit = new MultidigitTrendChart();
            $multidigit->create_time = date('Y-m-d H:i:s');
        } else {
            $multidigit->modify_time = date('Y-m-d H:i:s');
        }

        $multidigit->lottery_name = $lastTrend['lottery_name'];
        $multidigit->lottery_code = $code;
        $multidigit->periods = $periods;
        $multidigit->open_code = $open_nums;
        $multidigit->digits_omission = $digitOmiss;
        $multidigit->ten_omission = $tenOmiss;
        $multidigit->hundred_omission = $hundredOmiss;
        $multidigit->thousand_omission = $thousandOmiss;
        $multidigit->ten_thousand_omission = $tenThousOmiss;
        $multidigit->hundred_thousand_omission = $hundThousOmiss;
        $multidigit->million_omission = $millionOmiss;
        $multidigit->analysis = $analyze;
        $multidigit->span_omission = $spanOmiss;
        $multidigit->sum_omission = $sumOmiss;
        $multidigit->sumtail_omission = $sumtailOmiss;

        if (!$multidigit->validate('lottery_name', 'lottery_code', 'periods', 'open_code', 'open_code', 'digits_omission', 'ten_omission', 'hundred_omission', 'thousand_omission', 'thousand_omission', 'hundred_thousand_omission', 'million_omission')) {
            return false;
        } else {
            $trendId = $multidigit->save();
            if (!$trendId) {
                return false;
            }
            return true;
        }
    }

    /**
     * 多位数遗漏数的判断
     * auther GL ZYL
     * @param string $lastTrend
     * @param string $num
     * @return string
     */
    public function getMultiTrend($lastTrend, $num) {
        $last = explode(',', $lastTrend);
        foreach ($last as $key => $val) {
            if ($num == $key) {
                $newsOmission[$key] = 0;
            } else {
                $newsOmission[$key] = $val + 1;
            }
        }
        $omission = implode(',', $newsOmission);
        return $omission;
    }

    /**
     *  11选5投注式彩种的走势表写入
     * @param type $code
     * @param type $periods
     * @param type $open_nums
     * @param type $type
     * @return boolean
     */
    public function getCreateEleven($code, $periods, $open_nums, $type) {
        $lastTrend = ElevenTrendChart::find()->where(['lottery_code' => $code])->andWhere(['<', 'periods', $periods])->orderBy('periods desc')->limit(1)->asArray()->one();
        $eleven = ElevenTrendChart::find()->where(['lottery_code' => $code, 'periods' => $periods])->one();
        if (!empty($eleven)) {
            if ($type == 0) {
                return false;
            }
        }
        $openArr = explode(',', $open_nums);
        $optionalOmiss = '';
        $qoneOmiss = '';
        $qtwoOmiss = '';
        $qthreeOmiss = '';
        $qtwoGroupOmiss = '';
        $qthreeGroupOmiss = '';
        $analyze = '';
        $otherOmiss = '';
        if (!empty($lastTrend)) {
            $optionalOmiss = $this->getElevenTrend($lastTrend['optional_omission'], $open_nums);
            $qoneOmiss = $this->getElevenTrend($lastTrend['qone_omission'], $openArr[0]);
            $qtwoOmiss = $this->getElevenTrend($lastTrend['qtwo_omission'], $openArr[1]);
            $qthreeOmiss = $this->getElevenTrend($lastTrend['qthree_omission'], $openArr[2]);
            $qtwoGroupOmiss = $this->getElevenTrend($lastTrend['qtwo_group_omission'], "{$openArr[0]},$openArr[1]");
            $qthreeGroupOmiss = $this->getElevenTrend($lastTrend['qthree_group_omission'], "$openArr[0],$openArr[1],$openArr[2]");
            $data = $this->getElevenAnalyze($openArr, $lastTrend['open_code'], $lastTrend['span_omission']);
            $analyze = json_encode($data['analyze']);
            $otherOmiss = $data['other_omiss'];
        }
        if (empty($eleven)) {
            $eleven = new ElevenTrendChart;
            $eleven->create_time = date('Y-m-d H:i:s');
        } else {
            $eleven->modify_time = date('Y-m-d H:i:s');
        }

        $eleven->lottery_name = $lastTrend['lottery_name'];
        $eleven->lottery_code = $code;
        $eleven->periods = $periods;
        $eleven->open_code = $open_nums;
        $eleven->optional_omission = $optionalOmiss;
        $eleven->qone_omission = $qoneOmiss;
        $eleven->qtwo_omission = $qtwoOmiss;
        $eleven->qthree_omission = $qthreeOmiss;
        $eleven->qtwo_group_omission = $qtwoGroupOmiss;
        $eleven->qthree_group_omission = $qthreeGroupOmiss;
        $eleven->analysis = $analyze;
        $eleven->span_omission = $otherOmiss;
        if (!$eleven->validate()) {
            return false;
        } else {
            $trendId = $eleven->save();
            if (!$trendId) {
                return false;
            }
            return true;
        }
    }

    /**
     * 11选五遗漏数的判断
     * @param type $lastTrend
     * @param type $open
     * @return type
     */
    public function getElevenTrend($lastTrend, $open) {
        $last = explode(',', $lastTrend);
        $openArr = explode(',', $open);
        foreach ($last as $key => $val) {
            if ($key < 9) {
                $k = '0' . $key + 1;
            } else {
                $k = $key + 1;
            }
            if (in_array($k, $openArr)) {
                $newsOmission[$key] = 0;
            } else {
                $newsOmission[$key] = $val + 1;
            }
        }
        $omission = implode(',', $newsOmission);
        return $omission;
    }

    /**
     * 红球分析
     * @param type $lastOpen 上一期红球
     * @param type $nowOpen 当前期红球
     * @return type
     */
    public function getRedAnaylze($lastOpen, $nowOpen, $lastAnalyze, $lastOtherMiss) {
        $areaArr1 = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11']; // 一区的值
        $areaArr2 = ['12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22']; // 二区的值
        $areaArr3 = ['23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36']; // 三区的值
        $areaNum1 = 0; // 一区的个数
        $areaNum2 = 0; // 二区的个数
        $areaNum3 = 0; // 三区的个数
        $lastArr = explode(',', $lastOpen);
        $nowArr = explode(',', $nowOpen);
        $lastTailArr = explode(',', $lastAnalyze['tailStr']);
        $repeat = count(array_intersect($lastArr, $nowArr)); // 与上期对比，重复出现个数
        sort($nowArr);
        $rowNum = 0; //连续数的个数
        $sum = 0; //和值
        $subArr = $nowArr;
        $errArr = []; // 两两之间的差集
        $oddNum = 0; // 奇数的个数
        $evenNum = 0; //偶数的个数
        $tailArr = [];
        $tailSame = 1; // 尾数重复的个数
        $lastOtherMissArr = explode(',', $lastOtherMiss);
        $omissTail = [];

        foreach ($nowArr as $key => $now) {
            unset($subArr[$key]);
            $intNow = (int) $now;
            $sub = $intNow + 1;
            if (!empty($subArr)) {
                if ($sub == $subArr[$key + 1]) {
                    $rowNum +=1;
                }
            }
            foreach ($subArr as $sa) {
                $intSa = (int) $sa;
                $errArr[] = abs($intNow - $intSa);
            }
            if (in_array($now, $areaArr1)) {
                $areaNum1 += 1;
            } elseif (in_array($now, $areaArr2)) {
                $areaNum2 += 1;
            } elseif (in_array($now, $areaArr3)) {
                $areaNum3 += 1;
            }
            $residue = $intNow % 2;
            if ($residue == 0) {
                $evenNum += 1;
            } else {
                $oddNum += 1;
            }
            $sum += $intNow;
            $tail = substr($now, 1, 1);
            if (!in_array($tail, $tailArr)) {
                $tailArr[] = $tail;
                $omissTail[] = $tail;
            } else {
                $tailSame += 1;
                $omissTail[] = $tail;
            }
        }
        $tailOmiss = [];
        for ($i = 0; $i < 10; $i++) {
            if (in_array($i, $tailArr)) {
                $tailOmiss[$i] = 1 - array_count_values($omissTail)[$i];
            } else {
                if ($lastOtherMissArr[$i] == 0) {
                    $tailOmiss[$i] = $lastOtherMissArr[$i] + 1;
                } elseif ($lastOtherMissArr[$i] == -1) {
                    $tailOmiss[$i] = $lastOtherMissArr[$i] + 2;
                } elseif ($lastOtherMissArr[$i] == -2) {
                    $tailOmiss[$i] = $lastOtherMissArr[$i] + 3;
                } elseif ($lastOtherMissArr[$i] == -3) {
                    $tailOmiss[$i] = $lastOtherMissArr[$i] + 4;
                } else {
                    $tailOmiss[$i] = $lastOtherMissArr[$i] + 1;
                }
            }
        }
        $ACNums = count(array_unique($errArr)) - (count($nowArr) - 1);
        $areaRatio = $areaNum1 . ':' . $areaNum2 . ':' . $areaNum3;
        $parityRatio = $oddNum . ':' . $evenNum;
        $setOff = count(array_intersect($tailArr, $lastTailArr));
        $tailRepeat = $tailSame == 1 ? 0 : $tailSame;
        $data['analyze'] = ['repeatNum' => $repeat, 'rowNum' => $rowNum, 'red_sum' => $sum, 'ACNums' => $ACNums, 'areaRatio' => $areaRatio, 'parityRatio' => $parityRatio, 'tailStr' => implode(',', $tailArr), 'setOff' => $setOff, 'tailRepeat' => $tailRepeat];
        $data['tail_omiss'] = implode(',', $tailOmiss);
        return $data;
    }

    /**
     * 篮球数据分析
     * @param type $nowOpen 当前期蓝球
     * @return string
     */
    public function getBlueAnalyze($nowOpen) {
        $nowArr = explode(',', $nowOpen);
        $span = (int) max($nowArr) - (int) min($nowArr); // 跨度
        $sum = 0; // 和值
        $parityStr = ''; // 奇偶
        $sizeStr = ''; //大小
        foreach ($nowArr as $now) {
            $intNow = (int) $now;
            $sum += $intNow;
            $residue = $intNow % 2;
            if ($residue == 0) {
                $parityStr .= '偶';
            } else {
                $parityStr .= '奇';
            }
            if ($intNow < 8) {
                $sizeStr .= '小';
            } else {
                $sizeStr .= '大';
            }
        }
        $data = ['blueSpan' => $span, 'blueSum' => $sum, 'parityStr' => $parityStr, 'sizeStr' => $sizeStr];
        return $data;
    }

    /**
     * 福彩3D 开奖结果分析
     * @param type $nowOpen 当前期开奖号码
     * @param type $lastOtherOmiss 上一期其他遗漏
     * @return type
     * 
     */
    public function getGroupAnalyze($openArr, $lastSumOmissStr, $lastSpanOmissStr, $lastTailOmissStr) {
        $lastSumOmiss = explode(',', $lastSumOmissStr);
        $lastTailOmiss = explode(',', $lastTailOmissStr);
        $lastSpanOmiss = explode(',', $lastSpanOmissStr);
        $span = (int) max($openArr) - (int) min($openArr);
        $sum = 0;
        foreach ($openArr as $now) {
            $intNow = (int) $now;
            $sum += $intNow;
        }
        $sumStr = (string) $sum;
        $sumTail = substr($sumStr, 1, 1);
        $sumTailOmission = [];
        $spanOmission = [];
        $sumOmission = [];
        for ($i = 0; $i < 10; $i++) {
            if ($i == $sumTail) {
                $sumTailOmission[$i] = 0;
            } else {
                $sumTailOmission[$i] = $lastTailOmiss[$i] + 1;
            }
            if ($i == $span) {
                $spanOmission[$i] = 0;
            } else {
                $spanOmission[$i] = $lastSpanOmiss[$i] + 1;
            }
        }
        for ($j = 0; $j < 28; $j++) {
            if ($j == $sum) {
                $sumOmission[$j] = 0;
            } else {
                $sumOmission[$j] = $lastSumOmiss[$j] + 1;
            }
        }
        $data['analyze'] = ['span' => $span, 'sum' => $sum, 'sumTail' => $sumTail];
        $data['other'] = ['sumOmission' => implode(',', $sumOmission), 'spanOmission' => implode(',', $spanOmission), 'sumTailOmission' => implode(',', $sumTailOmission)];
        return $data;
    }

    /**
     * 11选五开奖结果分析
     * @param type $openArr
     * @param type $lastOpen
     * @param type $lastSpanOmiss
     * @return type
     */
    public function getElevenAnalyze($openArr, $lastOpen, $lastSpanOmiss) {
        $lastArr = explode(',', $lastOpen);
        $span = (int) max($openArr) - (int) min($openArr); // 任选跨度
        $repeatNum = count(array_intersect($lastArr, $openArr)); // 任选落号
        $threeArr = array_slice($openArr, 0, 3);
        $lastThree = array_slice($lastArr, 0, 3);
        $threeRepeat = count(array_intersect($threeArr, $lastThree)); // 前三落号
        $threeSpan = (int) max($threeArr) - (int) min($threeArr); // 前三跨度
        $sum = 0;
        foreach ($openArr as $now) {
            $intNow = (int) $now;
            $sum += $intNow;
        }
        $spanOmiss = [];
        $lastSpanOmissArr = explode(',', $lastSpanOmiss);
        for ($i = 0; $i < 7; $i++) {
            $j = $i + 4;
            if ($j == $span) {
                $spanOmiss[$i] = 0;
            } else {
                $spanOmiss[$i] = $lastSpanOmissArr[$i] + 1;
            }
        }
        $data['analyze'] = ['allSpan' => $span, 'allRepeatNum' => $repeatNum, 'threeRepeatNum' => $threeRepeat, 'threeSpan' => $threeSpan, 'sum' => $sum];
        $data['other_omiss'] = implode(',', $spanOmiss);
        return $data;
    }
    
    /**
     * 七星彩 开奖结果分析
     * @param type $nowOpen 当前期开奖号码
     * @param type $lastOtherOmiss 上一期其他遗漏
     * @return type
     * 
     */
    public function getMultiAnalyze($code, $openArr, $lastSumOmissStr, $lastSpanOmissStr, $lastTailOmissStr) {
        $lastSumOmiss = explode(',', $lastSumOmissStr);
        $lastTailOmiss = explode(',', $lastTailOmissStr);
        $lastSpanOmiss = explode(',', $lastSpanOmissStr);
        $span = (int) max($openArr) - (int) min($openArr);
        $sum = 0;
        foreach ($openArr as $now) {
            $intNow = (int) $now;
            $sum += $intNow;
        }
        $jNums = count($openArr) * 9 + 1;
        $j = 0;
        $sumN = $sum;
        $sumStr = $sum > 9 ? (string) $sum : '0' . $sum;
        $sumTail = substr($sumStr, 1, 1);
        $sumTailOmission = [];
        $spanOmission = [];
        $sumOmission = [];
        for ($i = 0; $i < 10; $i++) {
            if ($i == $sumTail) {
                $sumTailOmission[$i] = 0;
            } else {
                $sumTailOmission[$i] = $lastTailOmiss[$i] + 1;
            }
            if ($i == $span) {
                $spanOmission[$i] = 0;
            } else {
                $spanOmission[$i] = $lastSpanOmiss[$i] + 1;
            }
        }
        $n = 0;
        if ($code == '2003') {
            $jNums = 24;
            if ($sumN < 11) {
                $sumN = 11;
            } elseif ($sumN > 34) {
                $sumN = 34;
            }
            $n = 11;
        } elseif ($code == '2004') {
            $jNums = 19;
            if ($sumN < 21) {
                $sumN = 21;
            } elseif ($sumN > 39) {
                $sumN = 39;
            }
            $n = 21;
        }
        for ($j; $j < $jNums; $j++) {
            if ($j + $n == $sumN) {
                $sumOmission[$j] = 0;
            } else {
                $sumOmission[$j] = $lastSumOmiss[$j] + 1;
            }
        }
        $data['analyze'] = ['span' => $span, 'sum' => $sum, 'sumTail' => $sumTail];
        $data['other'] = ['sumOmission' => implode(',', $sumOmission), 'spanOmission' => implode(',', $spanOmission), 'sumTailOmission' => implode(',', $sumTailOmission)];
        return $data;
    }

}
