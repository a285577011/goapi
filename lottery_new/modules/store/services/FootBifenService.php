<?php

namespace app\modules\store\services;

use yii;
use app\modules\store\helpers\TicketPrint;
use app\modules\store\helpers\StoreConstants;
use app\modules\orders\helpers\OrderDeal;
use app\modules\orders\helpers\DealPrint;
use app\modules\orders\models\MajorData;

class FootBifenService {

    /**
     * 获取半全场单关点布局
     */
    public static function formatSingle($orderData, $isDeal = 0) {
        if ($orderData['lottery_id'] == 3007 && $orderData['play_code'] != 1) {
            return;
        }
        $x = StoreConstants::JINGZHU_BIFEN[1]['x'];
        $head = StoreConstants::JINGZHU_BIFEN[1]['head'];
        if ($orderData["source"] == 4) {
            $orderId = $orderData["source_id"];
            $source = 2;
        } elseif ($orderData['source'] == 7) {
            $orderId = $orderData['source_id'];
            $source = 7;
        } else {
            $source = 1;
            $orderId = $orderData["lottery_order_id"];
        }
        $major = MajorData::find()->select(['major'])->where(['order_id' => $orderId, 'source' => $source])->asArray()->one();
        $majorData = json_decode($major['major'], true);
        $mids = OrderDeal::getMids($orderData['lottery_id'], explode('|', trim($orderData['bet_val'], '^')));
        $bet = DealPrint::dealPrint($orderData['lottery_id'], $orderData['bet_val'], $orderData['play_code'], $orderData['build_code'], $orderData['bet_double'], $orderData['major_type'], $majorData);
        $orderDeal = $bet;
        $orderDeal = TicketPrintService::formatOrderDeal($orderDeal, $orderData['lottery_id'], $mids);
        if (!$isDeal) {
            return [
                'code' => 600,
                "content" => [],
                "info" => $orderDeal
            ];
        }
        $data = [];
        foreach ($orderDeal as $k => $v) {
            $data[$k] = self::getSingleCoord($v['betVal'][0][4], $v['betVal'][0][5], $v['bet_double'], $v['betVal'][0][2]);
        }
        return [
            'code' => 600,
            "content" => array_values($data),
            "info" => $orderDeal
        ];
    }

    /**
     * 半全场串布点
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
        if ($orderData["source"] == 4) {
            $orderId = $orderData["source_id"];
            $source = 2;
        }elseif ($orderData["source"] == 7) {
            $orderId = $orderData["source_id"];
            $source = 7;
        } else {
            $source = 1;
            $orderId = $orderData["lottery_order_id"];
        }
        $major = MajorData::find()->select(['major'])->where(['order_id' => $orderId, 'source' => $source])->asArray()->one();
        $majorData = json_decode($major['major'], true);
        $mids = OrderDeal::getMids($orderData['lottery_id'], $betVals);
        $bet = DealPrint::dealPrint($orderData['lottery_id'], $orderData['bet_val'], $orderData['play_code'], $orderData['build_code'], $orderData['bet_double'], $orderData['major_type'], $majorData);
        $orderDeal = $bet;
        $orderDeal = TicketPrintService::formatOrderDeal($orderDeal, $orderData['lottery_id'], $mids);
        if (!$isDeal) {
            return [
                'code' => 600,
                "content" => [],
                "info" => $orderDeal
            ];
        }
        $data = [];
        foreach ($orderDeal as $k => $v) {
            list ( $m, $n ) = explode('串', $v['play_name']);
            $chuan = $m . ',' . $n;
            $totalChang = count($betVals);
            if ($m >= 4) {
                $x = StoreConstants::JINGZHU_BIFEN[2]['x'];
                $head = StoreConstants::JINGZHU_BIFEN[2]['head'];
            } elseif ($m > 1) {
                $x = StoreConstants::JINGZHU_BIFEN[1]['x'];
                $head = StoreConstants::JINGZHU_BIFEN[1]['head'];
            }
            $data[$k] = TicketPrint::creatCoord($x, 12);
            $data[$k] = TicketPrint::setCoord($head, $data[$k]);
            $changci = 0;
            foreach ($v['betVal'] as $kc => $vc) {
                $data[$k] = self::formatMuCoord($data[$k], $vc[4], $vc[5], $vc[2], $v['bet_double'], $changci, $x);
                $changci ++;
            }
            $data[$k] = TicketPrint::setMnCoord($data[$k], $x, $chuan);
        }
        return [
            'code' => 600,
            "content" => array_values($data),
            "info" => $orderDeal
        ];
    }

    public static function getSingleCoord($schedule_date, $open_mid, $bet_double, $betVal) {
        $x = StoreConstants::JINGZHU_BIFEN[1]['x'];
        $head = StoreConstants::JINGZHU_BIFEN[1]['head'];
        $data = TicketPrint::creatCoord($x, 12);
        $data = TicketPrint::setCoord($head, $data);
        $week = date('w', strtotime($schedule_date));
        $data = TicketPrint::setWeekCoord($data, 2, 1, $week);
        // $v['schedule_code']='周四010';
        $chanci = substr($open_mid, - 3, 3);
        $chanci = TicketPrint::getShow($chanci);
        foreach ($chanci as $vc) {
            $data = TicketPrint::getChanciCoord($data, 4, $vc);
        }
        $betValArr = explode(',', $betVal);
        foreach ($betValArr as $vb) {
            switch ($vb) {
                case '14':
                    $data[15][3] = 1;
                    break;
                case '22':
                    $data[15][1] = 1;
                    break;
                case '41':
                    $data[15][0] = 1;
                    break;
                case '04':
                    $data[14][3] = 1;
                    break;
                case '99':
                    $data[14][2] = 1;
                    break;
                case '11':
                    $data[14][1] = 1;
                    break;
                case '40':
                    $data[14][0] = 1;
                    break;
                case '32':
                    $data[13][3] = 1;
                    break;
                case '33':
                    $data[13][2] = 1;
                    break;
                case '00':
                    $data[13][1] = 1;
                    break;
                case '32':
                    $data[13][0] = 1;
                    break;
                case '13':
                    $data[12][3] = 1;
                    break;
                case '09':
                    $data[12][2] = 1;
                    break;
                case '90':
                    $data[12][1] = 1;
                    break;
                case '31':
                    $data[12][0] = 1;
                    break;
                case '03':
                    $data[11][3] = 1;
                    break;
                case '25':
                    $data[11][2] = 1;
                    break;
                case '52':
                    $data[11][1] = 1;
                    break;
                case '30':
                    $data[11][0] = 1;
                    break;
                case '12':
                    $data[10][3] = 1;
                    break;
                case '15':
                    $data[10][2] = 1;
                    break;
                case '51':
                    $data[10][1] = 1;
                    break;
                case '21':
                    $data[10][0] = 1;
                    break;
                case '02':
                    $data[9][3] = 1;
                    break;
                case '05':
                    $data[9][2] = 1;
                    break;
                case '50':
                    $data[9][1] = 1;
                    break;
                case '20':
                    $data[9][0] = 1;
                    break;
                case '24':
                    $data[8][2] = 1;
                    break;
                case '42':
                    $data[8][1] = 1;
                    break;
                case '10':
                    $data[8][0] = 1;
                    break;
                case '01':
                    $data[8][3] = 1;
                    break;
            }
        }
        $data[17][7] = 1;
        $bet_double = TicketPrint::getMulArr($bet_double);
        foreach ($bet_double as $vbe) {
            $data = TicketPrint::getDoubleCoord($data, $x, $vbe);
        }
        return $data;
    }

    public static function formatMuCoord($data, $schedule_date, $open_mid, $betVal, $bet_double, $changci, $x) {
        $flag = floor($changci / 3);
        $weekStar = 2 + 15 * $flag;
        $Y = $changci % 3 + 1;
        $week = date('w', strtotime($schedule_date));
        $data = TicketPrint::setWeekCoord($data, $weekStar, $Y, $week);
        // $v['schedule_code']='周四010';
        $chanci = substr($open_mid, - 3, 3);
        $chanci = TicketPrint::getShow($chanci);
        $chanciStar = 4 + 15 * $flag;
        foreach ($chanci as $vc) {
            $data = TicketPrint::getChanciCoord($data, $chanciStar, $vc, $Y);
        }
        $hanshu = 8 + 15 * $flag;
        $data = self::setBetCoord($data, $hanshu, $Y, $betVal);
        $bet_double = TicketPrint::getMulArr($bet_double);
        foreach ($bet_double as $vbe) {
            $data = TicketPrint::getDoubleCoord($data, $x, $vbe);
        }
        return $data;
    }

    /**
     * 
     * @param unknown $data
     * @param unknown $flag
     * @param unknown $Y
     * @param unknown $betVal
     */
    public static function setBetCoord($data, $flag, $Y, $betVal) {
        $betValArr = explode(',', $betVal);
        foreach ($betValArr as $vb) {
            switch ($vb) {
                case '14':
                    $data[7 + $flag][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '22':
                    $data[7 + $flag][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '41':
                    $data[7 + $flag][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '04':
                    $data[6 + $flag][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '99':
                    $data[6 + $flag][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '11':
                    $data[6 + $flag][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '40':
                    $data[6 + $flag][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '23':
                    $data[5 + $flag][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '33':
                    $data[5 + $flag][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '00':
                    $data[5 + $flag][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '32':
                    $data[5 + $flag][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '13':
                    $data[4 + $flag][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '09':
                    $data[4 + $flag][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '90':
                    $data[4 + $flag][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '31':
                    $data[4 + $flag][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '03':
                    $data[3 + $flag][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '25':
                    $data[3 + $flag][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '52':
                    $data[3 + $flag][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '30':
                    $data[3 + $flag][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '12':
                    $data[2 + $flag][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '15':
                    $data[2 + $flag][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '51':
                    $data[2 + $flag][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '21':
                    $data[2 + $flag][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '02':
                    $data[1 + $flag][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '05':
                    $data[1 + $flag][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '50':
                    $data[1 + $flag][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '20':
                    $data[1 + $flag][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '24':
                    $data[$flag][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '42':
                    $data[$flag][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '10':
                    $data[$flag][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '01':
                    $data[$flag][3 + 4 * ($Y - 1)] = 1;
                    break;
            }
        }
        return $data;
    }

}
