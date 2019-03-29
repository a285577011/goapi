<?php

namespace app\modules\store\services;

use yii;
use app\modules\store\helpers\TicketPrint;
use app\modules\store\helpers\StoreConstants;
use app\modules\orders\helpers\OrderDeal;
use app\modules\orders\helpers\DealPrint;
use app\modules\store\services\FootBifenService;
use app\modules\orders\models\MajorData;

class FootHunheService {

    /**
     * 混合过关布点
     */
    public static function formatMuit($orderData, $isDeal = 0) {
        $betVals = explode('|', trim($orderData['bet_val'], '^'));
        $mn = TicketPrint::getMcn($orderData['build_code'] ? $orderData['build_code'] : $orderData['play_code'], $betVals);
        list ( $m, $n ) = explode('串', $mn);
        $chuan = $m . ',' . $n;
        $totalChang = count($betVals);
        if ($m == 1) {
            return self::formatSingle($orderData, $isDeal);
        }
        //奖金优化
        if ($orderData["source"] == 4) {
            $orderId = $orderData["source_id"];
            $source = 2;
        } elseif ($orderData['source'] == 7) {
            $orderId = $orderData["source_id"];
            $source = 7;
        } else {
            $source = 1;
            $orderId = $orderData["lottery_order_id"];
        }
        $major = MajorData::find()->select(['major'])->where(['order_id' => $orderId, 'source' => $source])->asArray()->one();
        $majorData = json_decode($major['major'], true);
        $mids = OrderDeal::getMids($orderData['lottery_id'], $betVals);
        $bet = DealPrint::dealPrint($orderData['lottery_id'], $orderData['bet_val'], $orderData['play_code'], $orderData['build_code'], $orderData['bet_double'], $orderData["major_type"], $majorData);
        $orderDeal = $bet;
        $orderDeal = TicketPrintService::formatOrderDeal($orderDeal, 3011, $mids);
        $result = [];
        if ($isDeal == 1) {
            foreach ($orderDeal as $k => $v) {
                $result[] = self::getFootHhggForm($v["betVal"], $v["bet_double"], $v["play_name"]);
            }
        }
        $betInfo = ["code" => 600, "content" => $result, "info" => $orderDeal];
        return $betInfo;
    }

    /**
     * 混合投注
     */
    public static function formatSingle($orderData, $isDeal = 0) {
        if ($orderData['lottery_id'] == 3011 && $orderData['play_code'] != 1) {
            return;
        }
        $x = StoreConstants::JINGZHU_BANQUANCHANG[1]['x'];
        $head = StoreConstants::JINGZHU_BANQUANCHANG[1]['head'];
        //奖金优化
        if ($orderData["source"] == 4) {
            $orderId = $orderData["source_id"];
            $source = 2;
        } elseif ($orderData['source'] == 7) {
            $orderId = $orderData["source_id"];
            $source = 7;
        } else {
            $source = 1;
            $orderId = $orderData["lottery_order_id"];
        }
        $major = MajorData::find()->select(['major'])->where(['order_id' => $orderId, 'source' => $source])->asArray()->one();
        $majorData = json_decode($major['major'], true);
        $bet = DealPrint::dealPrint($orderData['lottery_id'], $orderData['bet_val'], $orderData['play_code'], $orderData['build_code'], $orderData['bet_double'], $orderData["major_type"], $majorData);
        $mids = OrderDeal::getMids($orderData['lottery_id'], explode('|', trim($orderData['bet_val'], '^')));
        $orderDeal = $bet;
        $orderDeal = TicketPrintService::formatOrderDeal($orderDeal, $orderData['lottery_id'], $mids);
        if (!$isDeal) {
            return [
                "content" => [],
                "info" => $orderDeal
            ];
        }
        $data = [];
        foreach ($orderDeal as $k => $v) {
            $lottery_id = $v['betVal'][0][3];
            switch ($lottery_id) {
                case 3007:
                    $data[$k] = FootBifenService::getSingleCoord($v['betVal'][0][4], $v['betVal'][0][5], $v['bet_double'], $v['betVal'][0][2]);
                    break;
                case 3009:
                    $data[$k] = BanQuancService::getSingleCoord($v['betVal'][0][4], $v['betVal'][0][5], $v['bet_double'], $v['betVal'][0][2]);
                    break;
                case 3008:
                    $t = new TicketPrintService();
                    $data[$k] = $t->getFootJqsForm($v["betVal"], $v["bet_double"], $v["play_name"]);
                    break;
                case 3006:
                case 3010:
                    $t = new TicketPrintService();
                    $data[$k] = $t->getFootSpfForm($lottery_id, $v["betVal"], $v["bet_double"], $v["play_name"]);
                    break;
            }
        }
        return [
            "content" => array_values($data),
            "info" => $orderDeal
        ];
    }

    /**
     *  竞彩足球-混合投注 落点处理
     */
    public static function getFootHhggForm($betVal, $bet_double, $playName) {

        $weeks = StoreConstants::WEEKS;
        $countTwo = [4, 5, 6];
        $countThree = [7, 8];
        if (count($betVal) >= 7) {
            $contentNum = 35;
            $formHead = StoreConstants::HHSE_HEAD;
            $y = 13;
        } else {
            $contentNum = 30;
            $formHead = StoreConstants::SPF_HEAD;
            $y = 12;
        }
        for ($i = 1; $i <= $contentNum; $i++) {
            for ($j = 0; $j < $y; $j++) {
                $formBody[$i][$j] = 0;
            }
        }
        $str = substr($playName, 0, strrpos($playName, '串'));
        //投注单头部
        if (in_array($str, $countThree)) {
            $formBody[1][1] = 1;
            $formBody[1][3] = 1;
            $formBody[2][8] = 1;
        } else {
            if ($str == 2 || $str == 3) {
                $formBody[1][3] = 1;
                $formBody[2][8] = 1;
            } else {
                $formBody[1][0] = 1;
                $formBody[1][3] = 1;
                $formBody[2][8] = 1;
            }
        }
        //周几 场次 投注内容;
        foreach ($betVal as $k => $val) {
            //商:第几行
            $m = floor($k / 3);
            //余数:第几个
            $n = $k % 3;
            //周几开始行数 $x周几开始行数 $xx场次开始行数 $betX投注内容开始行数
            if (in_array(count($betVal), $countTwo)) {
                $x = 4 + $m * 11;
                $xx = 6 + $m * 11;
                $betSpf = 10 + $m * 11;
                $betRqspf = 11 + $m * 11;
                $betJqs = 12 + $m * 11;
            } elseif (in_array(count($betVal), $countThree)) {
                $x = 4 + $m * 9;
                $xx = 6 + $m * 9;
                $betSpf = 10 + $m * 9;
                $betRqspf = 11 + $m * 9;
            } else {
                $x = 4;
                $xx = 6;
                $betSpf = 10;
                $betRqspf = 11;
                $betBf = 12;
                $betBqc = 20;
                $betJqs = 23;
            }
            $formBody = TicketPrint::setWeekCoord($formBody, $x, $n + 1, $weeks[$val[0]]);
            $chanci = TicketPrint::getShow($val[1]);
            //场次开始行数
            foreach ($chanci as $vc) {
                $formBody = TicketPrint::getChanciCoord($formBody, $xx, $vc, $n + 1);
            }
            //投注内容:根据不同投法
            switch ($val[3]) {
                case 3006:
                    $formBody = TicketPrint::setSpf($formBody, $betRqspf, $n + 1, $val[2]);
                    break;
                case 3007:
                    $formBody = FootBifenService::setBetCoord($formBody, $betBf, $n + 1, $val[2]);
                    break;
                case 3008:
                    $formBody = TicketPrint::setZjq($formBody, $betJqs, $n + 1, $val[2]);
                    break;
                case 3009:
                    $formBody = BanQuancService::setBetCoord($formBody, $betBqc, $n + 1, $val[2]);
                    break;
                case 3010:
                    $formBody = TicketPrint::setSpf($formBody, $betSpf, $n + 1, $val[2]);
                    break;
            }
        }
        //串关:单关除外
        $playStr = str_replace("串", ",", $playName);
        if ($playStr != '1,1') {
            $formBody = TicketPrint::setMnCoord($formBody, $contentNum, $playStr);
        }
        //倍数
        $doubleAry = TicketPrint::getMulArr($bet_double);
        foreach ($doubleAry as $v) {
            $formBody = TicketPrint::getDoubleCoord($formBody, $contentNum, $v);
        }
        $arr = array_merge_recursive($formHead, $formBody);
        return $arr;
    }

}
