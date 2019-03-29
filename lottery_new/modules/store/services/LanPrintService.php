<?php

namespace app\modules\store\services;

use yii;
use app\modules\store\helpers\TicketPrint;
use app\modules\store\helpers\StoreConstants;
use app\modules\orders\helpers\OrderDeal;
use app\modules\orders\helpers\DealPrint;
use app\modules\orders\models\MajorData;

class LanPrintService {

    /**
     * 竞彩篮球坐标填点
     * @param unknown $orderData
     * @param number $isDeal
     */
    public static function getCoord($orderData, $isDeal, $lotteryCode) {
        $orderDeal = self::getPrintData($orderData);
        if (!$isDeal) {
            return [
                'code' => 600,
                "content" => [],
                "info" => $orderDeal
            ];
        }
        $data = [];
        foreach ($orderDeal as $k => $v) {
            list ( $coord, $x ) = self::initCoord($v, $lotteryCode);
            $coord = self::setBetCoord($coord, $v, $lotteryCode, $x);
            $data[] = $coord;
        }
        return [
            'code' => 600,
            "content" => $data,
            "info" => $orderDeal
        ];
    }

    public static function getPrintData($orderData) {
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
        $major = MajorData::find()->select([
                    'major'
                ])->where([
                    'order_id' => $orderId,
                    'source' => $source
                ])->asArray()->one();
        $majorData = json_decode($major['major'], true);
        // print_r($orderData['major_type']);die;
        $mids = OrderDeal::getMids($orderData['lottery_id'], explode('|', trim($orderData['bet_val'], '^')));
        $orderDeal = DealPrint::dealPrint($orderData['lottery_id'], $orderData['bet_val'], $orderData['play_code'], $orderData['build_code'], $orderData['bet_double'], $orderData['major_type'], $majorData);
        $orderDeal = TicketPrintService::formatOrderDeal($orderDeal, $orderData['lottery_id'], $mids);
        return $orderDeal;
    }

    /**
     * 初始化坐标
     */
    public static function initCoord($v, $lotteryCode) {
        list ( $m, $n ) = explode('串', $v['play_name']);
        switch ($m) { // 几串
            case 1:
                $x = 22;
                $coord = TicketPrint::creatCoord($x, 12);
                $coord[0][0] = $coord[0][2] = $coord[0][4] = $coord[0][6] = $coord[0][8] = $coord[0][9] = $coord[0][11] = 1;
                $coord[1][2] = 1;
                $coord[2][3] = 1;
                break;
            case 2:
            case 3:
                $x = 22;
                $coord = TicketPrint::creatCoord($x, 12);
                $coord[0][0] = $coord[0][2] = $coord[0][4] = $coord[0][6] = $coord[0][8] = $coord[0][9] = $coord[0][11] = 1;
                $coord[1][2] = 1;
                if ($lotteryCode == 3005) { // 混合投注
                    $coord[2][8] = 1;
                } else {
                    $coord[2][5] = 1;
                }
                break;
            case 4:
            case 5:
            case 6:
                $x = 28;
                $coord = TicketPrint::creatCoord($x, 12);
                $coord[0][0] = $coord[0][2] = $coord[0][4] = $coord[0][6] = $coord[0][9] = $coord[0][10] = $coord[0][11] = 1;
                $coord[1][0] = 1;
                $coord[1][2] = 1;
                if ($lotteryCode == 3005) { // 混合投注
                    $coord[2][8] = 1;
                } else {
                    $coord[2][5] = 1;
                }
                break;
            case 7:
            case 8:
                $x = 38;
                $coord = TicketPrint::creatCoord($x, 12);
                $coord[0][0] = $coord[0][2] = $coord[0][4] = $coord[0][6] = $coord[0][8] = $coord[0][9] = $coord[0][12] = 1;
                $coord[1][1] = 1;
                $coord[1][2] = 1;
                if ($lotteryCode == 3005) { // 混合投注
                    $coord[2][8] = 1;
                } else {
                    $coord[2][5] = 1;
                }
                break;
        }
        return [
            $coord,
            $x
        ];
    }

    /**
     * 投注内容填点
     */
    public static function setBetCoord($coord, $data, $lotteryCode, $x) {
        $changci = 0;
        list ( $m, $n ) = explode('串', $data['play_name']);
        $chuan = $m . ',' . $n;
        foreach ($data['betVal'] as $k => $v) {
            /**
             * 周几填点
             *
             */
            $Y = $changci % 3 + 1; // 横的第几个
            $week = date('w', strtotime($v[4]));
            $flag = floor($changci / 3); // 竖的基数
            $weekStar = 4 + 10 * $flag;
            $coord = TicketPrint::setWeekCoord($coord, $weekStar, $Y, $week);
            /**
             * 场次填点
             *
             */
            $chanci = substr($v[5], - 3, 3);
            $chanci = TicketPrint::getShow($chanci);
            $chanciStar = 6 + 10 * $flag;
            foreach ($chanci as $vc) {
                $coord = TicketPrint::getChanciCoord($coord, $chanciStar, $vc, $Y);
            }
            /**
             * 内容填点
             */
            switch ($v[3]) {
                case 3001: // 胜负
                    $betStar = $m <= 3 ? 16 + 10 * $flag : 12 + 10 * $flag;
                    $coord = self::setSFCoord($coord, $betStar, $Y, $v[2]);
                    break;
                case 3002: // 让分胜负
                    $betStar = $m <= 3 ? 11 + 10 * $flag : 11 + 10 * $flag;
                    $coord = self::setRFSFCoord($coord, $betStar, $Y, $v[2]);
                    break;
                case 3003: // 胜分差
                    $betStar = $m <= 3 ? 13 + 10 * $flag : 13 + 10 * $flag;
                    $coord = self::setSFCCoord($coord, $betStar, $Y, $v[2]);
                    break;
                case 3004: // 大小分
                    $betStar = $m <= 3 ? 10 + 10 * $flag : 10 + 10 * $flag;
                    $coord = self::setDSFCoord($coord, $betStar, $Y, $v[2]);
                    break;
            }
            /**
             * 倍数串关填点
             */
            $m != 1 && $coord = TicketPrint::setMnCoord($coord, $x, $chuan); // 串关
            $bet_double = TicketPrint::getMulArr($data['bet_double']);
            foreach ($bet_double as $vbe) {
                $coord = TicketPrint::getDoubleCoord($coord, $x, $vbe);
            }
            $changci ++;
        }
        return $coord;
    }

    /**
     * 大小分填点
     * @param unknown $data 布局
     * @param unknown $flag
     * @param unknown $Y
     * @param unknown $vb
     */
    public static function setDSFCoord($data, $betStar, $Y, $betVal) {
        $betValArr = explode(',', $betVal);
        foreach ($betValArr as $vb) {
            switch ($vb) {
                case '1':
                    $data[$betStar][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '2':
                    $data[$betStar][1 + 4 * ($Y - 1)] = 1;
                    break;
            }
        }
        return $data;
    }

    /**
     * 让分胜负填点
     * @param unknown $data 布局
     * @param unknown $flag
     * @param unknown $Y
     * @param unknown $vb
     */
    public static function setRFSFCoord($data, $betStar, $Y, $betVal) {
        $betValArr = explode(',', $betVal);
        foreach ($betValArr as $vb) {
            switch ($vb) {
                case '0':
                    $data[$betStar][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '3':
                    $data[$betStar][1 + 4 * ($Y - 1)] = 1;
                    break;
            }
        }
        return $data;
    }

    /**
     * 胜分差填点
     * @param unknown $data 布局
     * @param unknown $flag
     * @param unknown $Y
     * @param unknown $vb
     */
    public static function setSFCCoord($data, $betStar, $Y, $betVal) {
        $betValArr = explode(',', $betVal);
        foreach ($betValArr as $vb) {
            switch ($vb) {
                case '11':
                    $data[$betStar][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '12':
                    $data[$betStar][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '01':
                    $data[$betStar][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '02':
                    $data[$betStar][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '13':
                    $data[1 + $betStar][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '14':
                    $data[1 + $betStar][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '03':
                    $data[1 + $betStar][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '04':
                    $data[1 + $betStar][3 + 4 * ($Y - 1)] = 1;
                    break;
                case '15':
                    $data[2 + $betStar][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '16':
                    $data[2 + $betStar][1 + 4 * ($Y - 1)] = 1;
                    break;
                case '05':
                    $data[2 + $betStar][2 + 4 * ($Y - 1)] = 1;
                    break;
                case '06':
                    $data[2 + $betStar][3 + 4 * ($Y - 1)] = 1;
                    break;
            }
        }
        return $data;
    }

    /**
     * 胜负填点
     * @param unknown $data 布局
     * @param unknown $flag
     * @param unknown $Y
     * @param unknown $vb
     */
    public static function setSFCoord($data, $betStar, $Y, $betVal) {
        $betValArr = explode(',', $betVal);
        foreach ($betValArr as $vb) {
            switch ($vb) {
                case '0':
                    $data[$betStar][0 + 4 * ($Y - 1)] = 1;
                    break;
                case '3':
                    $data[$betStar][1 + 4 * ($Y - 1)] = 1;
                    break;
            }
        }
        return $data;
    }

}
