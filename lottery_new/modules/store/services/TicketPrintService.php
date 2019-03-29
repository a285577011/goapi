<?php

namespace app\modules\store\services;

use app\modules\common\helpers\Constants;
use app\modules\store\helpers\StoreConstants;
use app\modules\store\helpers\TicketPrint;
use app\modules\store\helpers\Eleventh;
use app\modules\orders\helpers\SzcDeal;
use app\modules\orders\helpers\OrderDeal;
use app\modules\competing\helpers\CompetConst;
use app\modules\orders\helpers\DealPrint;
use app\modules\orders\models\MajorData;


class TicketPrintService {

    const SD11X5_DANTUO_TO_SINGLE = [
        201122 => 201102,
        201123 => 201103,
        201124 => 201104,
        201125 => 201105,
        201126 => 201106,
        201127 => 201107,
        201128 => 201108,
        201154 => 201134,
        201155 => 201135
    ]; //胆拖对应的单式投注玩法

    //打印大乐透
    public function DealDlt($betVal, $playCode, $double,$add,$isDeal) {
        //判断投注单复式
        $DltF = [];
        $DltD = [];
        $m = 0;
        $n = 1;
        $betAry = explode("^", trim($betVal));
        $playAry = explode(",", $playCode);
        foreach ($playAry as $key => $val) {
            if ($val == 200102) {
                $DltF[$key]["bet"][$n] = $betAry[$key];
            } else {
                $DltD[$key]["bet"] = $betAry[$key];
            }
    }
        $newD = [];
        //直选单式5注为一张
        foreach ($DltD as $k => $v) {
            if ($n == 5) {
                $newD[$m]["bet"][$n] = $v["bet"];
                $newD[$m]["count"][$n] = 1;
                $m++;
                $n = 1;
            } else {
                $newD[$m]["bet"][$n] = $v["bet"];
                $newD[$m]["count"][$n] = 1;
                $n++;
            }
        }
        $newD1 = [];
        //计算单式注数
        foreach ($newD as $it) {
            $count = 0;
            foreach ($it["count"] as $v) {
                $count += $v;
            }
            $newD1[] = ['bet' => $it["bet"], 'count' => $count];
        }
        //投注99倍为一张票
        //单式
        $newD2 = [];
        $count = 0;
        foreach ($newD1 as $k => $v) {
            $outNums = ceil($double / 99);
            $allBetDouble = $double;
            for ($i = 1; $i <= $outNums; $i++) {
                if ($allBetDouble >= $i * 99) {
                    $betDouble = 99;
                } else {
                    $betDouble = $allBetDouble - (99 * ($i - 1));
                }
                $newD2[] = ["bet" => $v["bet"], "bet_double" => $betDouble, "df" => 0,"add"=>$add, "play_name" => "直选单式", "bet_money" => $betDouble * $v["count"] * (2+$add)];
            }
        }
        //复式
        $newF1 = [];
        foreach ($DltF as $k => $v) {
            $noteNums = SzcDeal::noteNums(2001, $v["bet"][1], 200102);
            $newF1[] = ['bet' => $v["bet"], "count" => $noteNums['data']];
        }
        $newF2 = [];
        foreach ($newF1 as $k => $v) {
            $outNums = ceil($double / 99);
            $allBetDouble = $double;
            for ($i = 1; $i <= $outNums; $i++) {
                if ($allBetDouble >= $i * 99) {
                    $betDouble = 99;
                } else {
                    $betDouble = $allBetDouble - (99 * ($i - 1));
                }
                $newF2[] = ["bet" => $v["bet"], "bet_double" => $betDouble, "df" => 1,"add"=>$add, "play_name" => "直选复式", "bet_money" => $betDouble * $v["count"] * (2+$add)];
            }
        }
        $dataAry = array_merge_recursive($newD2, $newF2);
        //是否需要落点处理
        $result = [];
        if($isDeal==1){
            $ticket = new TicketPrint();
            foreach ($dataAry as $k => $v) {
                $result[] = $ticket->getDltForm($v["bet"], $v["bet_double"], $v["df"],$add);
            }
        }
        $betInfo = ["content" => $result, "info" => $dataAry];
        return $betInfo;
    }

	/**
	 * 获取福建11选五的打票内容
	 */
	public static function getSD11X5Content($orderData, $isDeal = 0)
	{
		try
		{
			if (! $orderData)
			{
				throw new \Exception('订单数据不存在', 110);
			}
			$playcodes = explode(',', $orderData['play_code']);
			$betData = [ ];
			$hasLe = false;
			$betVals = explode('^', trim($orderData['bet_val'], '^'));
			$lottery_code = $orderData['lottery_id'];
			$i = 0;
			foreach ($playcodes as $k => $pc)
			{
				if (Eleventh::isLeXuan($lottery_code, $pc))
				{ // 乐选玩法暂不支持
					throw new \Exception('暂不支持含乐选订单!', 110);
					$hasLe = true;
					continue;
				}
				if (Eleventh::isDanTuo($lottery_code, $pc))
				{ // 任选;直选；组选;胆拖） 拆成单注
					$ret = Eleventh::caiDanTuo($lottery_code, $pc, $betVals[$k]);
					foreach ($ret as $kc => $v)
					{
						$betData[$i]['bet_val'] = $v;
						$betData[$i]['play_code'] = Eleventh::dantuoToSingle($lottery_code)[$pc];
						$i ++;
					}
				}else
				{ // 非胆拖
					$betData[$i] = [ 
						'play_code' => $pc , 
						'bet_val' => $betVals[$k] 
					];
				}
				$i ++;
			}
			if (! $betData)
			{
				throw new \Exception('暂无数据可打印', 111);
			}
			$betData = self::mergeFj11X5($betData, $lottery_code);
			$data = [ ];
			if ($isDeal)
			{
				$outNums = ceil($orderData["bet_double"] / 99);
				for ($i = 0; $i < $outNums; $i ++)
				{
					$betDouble = 99;
					if ($i == $outNums - 1)
					{
						$betDouble = $orderData["bet_double"] % 99;
					}
					$j = 0;
					foreach ($betData as $playcode => $v)
					{
						foreach ($v as $vc)
						{
							$data[$i . '--' . $j] = self::formatSD11X5($lottery_code, $vc, $playcode, $betDouble);
							$j ++;
						}
					}
				}
				$data = array_values($data);
			}
			$content = [ ];
			foreach ($betData as $pc => $v)
			{
				foreach ($v as $vc)
				{
					$content[] = [ 
						"bet" => $vc , 
						"bet_double" => $orderData["bet_double"] , 
						"df" => 0 , 
						"play_name" => Eleventh::getPlayName($lottery_code, $pc) , 
						"bet_money" => Eleventh::getBetMoney($lottery_code, $pc, $vc, $orderData["bet_double"]) 
					];
				}
			}
		}
		catch (\Exception $e)
		{
			return [ 
				'code' => $e->getCode() , 
				'msg' => $e->getMessage() 
			];
		}
		return [ 
			'code' => 600 , 
			'data' => $data , 
			'content' => $content 
		];
	}

    /**
     * 合并打票内容
     * @param unknown $betData
     */
    public static function mergeFj11X5($betData, $lottery_code) {
        $initData = [];
        $playcodes = array_unique(array_column($betData, 'play_code'));
        foreach ($playcodes as $v) {
            $initData[$v] = [];
        }
        foreach ($betData as $k => $v) {
            array_push($initData[$v['play_code']], $v['bet_val']);
        }
        $data = [];
        $max = 5; // 每张票最大注数
        foreach ($initData as $playcode => $v) {
            if (Eleventh::checkIsMix($lottery_code, $playcode)) { // 复式都打不同一张
                $n = 1;
                foreach ($v as $vc) {
                    $data[$playcode][$n][0] = $vc;
                    $n++;
                }
            } else {
                $totalNum = count($v); // 总注数
                if ($totalNum <= $max) { // 小于等于五注
                    $data[$playcode][0] = $v;
                } else {
                    $nums = ceil($totalNum / $max); // 总票数(5注一票)
                    for ($i = 0; $i < $nums; $i ++) {
                        $data[$playcode][$i] = array_slice($v, $max * ($i), $max);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 
     * @param unknown $singBetData 格式单张票内容
     * $playcode玩法
     * $multiple 倍数
     */
    public static function formatSD11X5($lottery_code, $singBetData, $playcode, $multiple) {
        $flag = 1; // 坐标规格
        $isMax = false;
        $isZuxuanMax = false;
        $isZhixuan = false;
        switch (true) {
            case Eleventh::isRenxuanAndZhixuan1($lottery_code, $playcode): // 任选,直选1
                $x = StoreConstants::SD11X5_COORD_DATA['renxuan_x'];
                $head = StoreConstants::SD11X5_COORD_DATA['head']['renxuan'];
                $flag = 1;
                if (Eleventh::checkIsMix($lottery_code, $playcode)) {
                    $isMax = true;
                }
                if (Eleventh::isZhixuan($lottery_code, $playcode)) {
                    $isZhixuan = true;
                }
                break;
            case Eleventh::isZhuxuanAndZhixuan($lottery_code, $playcode): // 组选，直选
                $x = StoreConstants::SD11X5_COORD_DATA['no_renxuan_x'];
                $head = StoreConstants::SD11X5_COORD_DATA['head']['no_renxuan'];
                $flag = 2;
                if (Eleventh::checkIsMix($lottery_code, $playcode)) { // 复式
                    $isMax = true;
                    if (Eleventh::isZhuxuanMax($lottery_code, $playcode)) { // 组选复式
                        $isZuxuanMax = true;
                    }
                }
                if (Eleventh::isZhixuan($lottery_code, $playcode)) { // 直选
                    $isZhixuan = true;
                }
                break;
        }
        $y = StoreConstants::SD11X5_COORD_DATA['y'];
        $formBody = TicketPrint::creatCoord($x, $y);
        $formBody = self::formatSD11X5Head($lottery_code, $formBody, $head, $playcode);
        $oper = $isZhixuan ? ';' : ',';
        switch ($flag) {
            case 1:
                $i = 1;
                foreach ($singBetData as $v) {
                    $bet = explode($oper, $v);
                    foreach ($bet as $vc) {
                        $vcc = explode(',', $vc);
                        foreach ($vcc as $vccc) {//复式投注
                            $formBody[2 * $i][$vccc - 1] = 1;
                        }
                    }
                    if ($isMax||(count($singBetData)==1&&Eleventh::isQianyiDan($lottery_code,$playcode))) {
                        $formBody[2 * $i + 1][9] = 1;
                    }
                    $i ++;
                }
                if ($multiple >= 10) {
                    list ( $shi, $one ) = str_split($multiple);
                    $formBody[12][$shi + 1] = 1;
                    $formBody[13][$one + 1] = 1;
                } else {
                    $formBody[13][$multiple + 1] = 1;
                }
                break;
            case 2:
                $i = 1;
                $numCount = Eleventh::getZZNum($lottery_code, $playcode);
                switch ($numCount) {
                    case 2:
                        $discount = 3;
                        break;
                    case 3:
                        $discount = 2;
                        break;
                }
                foreach ($singBetData as $v) {
                    $bet = explode($oper, $v);
                    foreach ($bet as $k => $vc) {
                        if ($isZuxuanMax) { // 组选复式数字同行
                            $formBody[2 + $numCount * ($i - 1) + $discount * ($i - 1)][$vc + 1] = 1;
                        } else {
                            $vcc = explode(',', $vc);
                            foreach ($vcc as $vccc) {
                                $formBody[2 + $numCount * ($i - 1) + $discount * ($i - 1) + $k][$vccc + 1] = 1;
                            }
                        }
                    }
                    if ($isMax) {
                        $formBody[6][10] = 1; // 复式点
                    }
                    if ($multiple >= 10) {
                        list ( $shi, $one ) = str_split($multiple);
                        $formBody[27][$shi + 1] = 1;
                        $formBody[28][$one + 1] = 1;
                    } else {
                        $formBody[28][$multiple + 1] = 1;
                    }
                    $i ++;
                }
                break;
        }
        return $formBody;
    }

    public static function formatSD11X5Head($lottery_code, $formBody, $head, $playcode) {
        foreach ($head as $v) {
            list ( $x, $y ) = explode(',', $v);
            $formBody[$x][$y] = 1;
        }
        switch (true) {
            case Eleventh::isRenxuanAndZhixuan1($lottery_code, $playcode): // 任选直选1
                $renNum = $playcode % 10;
                $formBody[1][$renNum - 1] = 1;
                break;
            case Eleventh::isZhuxuan($lottery_code, $playcode): // 组选
            	$renNum = $playcode % 10;
                $formBody[1][$renNum-1] = 1;
                $formBody[1][7] = 1;
                break;
            case Eleventh::isZhixuan1($lottery_code, $playcode): // 直选
            	$renNum = $playcode % 10;
                $formBody[1][$renNum+1] = 1;
                $formBody[1][6] = 1;
                break;
        }
        return $formBody;
    }
    /**
     * 竞彩足球:胜平负、让球胜平负
     * $betAry:投注单信息
     */
    public function dealFootballSpf($data,$isDeal,$dealLottery){
        $mCn = CompetConst::COUNT_MCN;
        $manerCn = Constants::MANNER;
        //奖金优化
        if ($data["source"] == 4) {
            $orderId = $data["source_id"];
            $source = 2;
        }elseif ($data['source'] == 7) {
            $orderId = $orderData["source_id"];
            $source = 7;
        } else {
            $source = 1;
            $orderId = $data["lottery_order_id"];
        }
        $major = MajorData::find()->select(['major'])->where(['order_id' => $orderId, 'source' => $source])->asArray()->one();
        $majorData = json_decode($major['major'],true);
        $orderDeal = DealPrint::dealPrint($data["lottery_id"],$data["bet_val"],$data["play_code"],$data["build_code"],$data["bet_double"],$data["major_type"],$majorData);
        $scheduleDate = OrderDeal::getMids($data["lottery_id"],explode('|', trim($data['bet_val'],'^')));
        //处理串关:M串N,场次信息
        $orderDeal = self::formatOrderDeal($orderDeal,$data["lottery_id"],$scheduleDate);
        //是否需要落点处理
        $result = [];
        if($isDeal==1){
            foreach ($orderDeal as $k => $v) {
                if($dealLottery==3008){
                    $result[] = $this->getFootJqsForm($v["betVal"],$v["bet_double"],$v["play_name"]);
                }else{
                    $result[] = $this->getFootSpfForm($dealLottery,$v["betVal"],$v["bet_double"],$v["play_name"]);
                }
            }
        }
        $betInfo = ["code"=>600,"content" => $result, "info" => $orderDeal];
        return $betInfo;
            
    }
    /**
     * 竞彩足球-胜平负 落点处理
     * 竞彩足球-让球胜平负 落点处理
     */
    public function getFootSpfForm($lotteryId,$betVal,$bet_double,$playName){
        $weeks = StoreConstants::WEEKS;
        $countTwo = [4,5,6];
        $countThree = [7,8];
        if(in_array(count($betVal),$countThree)){
            $formHead = StoreConstants::HHSE_HEAD;
            $contentNum = 35;
        }else{
            $formHead = StoreConstants::SPF_HEAD;
            $contentNum = 30;
        }
        for ($i = 1; $i <= $contentNum; $i++) {
            for ($j = 0; $j < 12; $j++) {
                $formBody[$i][$j] = 0;
            }
        } 
        $str = substr($playName,0,strrpos($playName,'串'));
        //投注单头部
        if(in_array($str,$countThree)){
            $formBody[1][1]=1;
            $formBody[1][3]=1;
            $formBody[2][5]=1;
         }else{
            if($str==1){    
                $formBody[1][3]=1;
                $formBody[2][3]=1;
            }elseif($str==2 || $str==3){
                $formBody[1][3]=1;
                $formBody[2][5]=1;
            }else{
                $formBody[1][0]=1;
                $formBody[1][3]=1;
                $formBody[2][5]=1;
            }
         }
         //周几 场次 投注内容;
        foreach ($betVal as $k => $val) {
            //商:第几行
            $m = floor($k/3);
            //余数:第几个
            $n = $k%3;
            //周几开始行数 $x周几开始行数 $xx场次开始行数 $betX投注内容开始行数
            if(in_array(count($betVal),$countTwo)){
               $x = 4+$m*11;
               $xx = 6+$m*11;
               if($lotteryId==3010){
                  $betX = 10+$m*11; 
               }elseif($lotteryId==3006){
                  $betX = 11+$m*11;
               }
            }else{
               $x = 4+$m*9;
               $xx = 6+$m*9;
               if($lotteryId==3010){
                   $betX = 10+$m*9; 
               }elseif($lotteryId==3006){
                   $betX = 11+$m*9;
               }
            }
            $formBody = TicketPrint::setWeekCoord($formBody,$x,$n+1,$weeks[$val[0]]);
            $chanci=TicketPrint::getShow($val[1]);
            //场次开始行数
            foreach ($chanci as $vc){
                $formBody=TicketPrint::getChanciCoord($formBody, $xx, $vc,$n+1);
            }
            //投注内容
            $formBody = TicketPrint::setSpf($formBody,$betX,$n+1,$val[2]);
         }
        //串关:单关除外
        $playStr = str_replace("串",",",$playName);
        if($playStr!='1,1'){
           $formBody = TicketPrint::setMnCoord($formBody,$contentNum,$playStr); 
        }
        //倍数
        $doubleAry = TicketPrint::getMulArr($bet_double);
        foreach ($doubleAry as $v){
            $formBody=TicketPrint::getDoubleCoord($formBody,$contentNum, $v);
        }
        $arr = array_merge_recursive($formHead, $formBody);
	return $arr;
    }
    /**
     *  竞彩足球-进球数 落点处理
     */
    public static function getFootJqsForm($betVal,$bet_double,$playName){
    	$formHead = StoreConstants::SPF_HEAD;
        $weeks = StoreConstants::WEEKS;
        $contentNum = 30;
        for ($i = 1; $i <= $contentNum; $i++) {
            for ($j = 0; $j < 12; $j++) {
                $formBody[$i][$j] = 0;
            }
        } 
        $str = substr($playName,0,strrpos($playName,'串'));
        //投注单头部
        if($str==1){    
            $formBody[1][3]=1;
            $formBody[2][3]=1;
        }elseif($str==2 || $str==3){
            $formBody[1][3]=1;
            $formBody[2][5]=1;
        }else{
            $formBody[1][0]=1;
            $formBody[1][3]=1;
            $formBody[2][5]=1;
        }
        
         //周几 场次 投注内容;
        foreach ($betVal as $k => $val) {
            //商:第几行
            $m = floor($k/3);
            //余数:第几个
            $n = $k%3;
            //周几开始行数 $x周几开始行数 $xx场次开始行数 $betX投注内容开始行数
            if(count($betVal)==4||count($betVal)==5||count($betVal)==6){
               $x = 4+$m*11;
               $xx = 6+$m*11;
               $betX = 12+$m*11;
            }else{
               $x = 4;
               $xx = 6;
               $betX = 23;
            }
            $formBody = TicketPrint::setWeekCoord($formBody,$x,$n+1,$weeks[$val[0]]);
            $chanci=TicketPrint::getShow($val[1]);
            //场次开始行数
            foreach ($chanci as $vc){
                $formBody=TicketPrint::getChanciCoord($formBody, $xx, $vc,$n+1);
            }
            //投注内容
            $formBody = TicketPrint::setZjq($formBody,$betX,$n+1,$val[2]);
         }
        //串关:单关除外
        $playStr = str_replace("串",",",$playName);
        if($playStr!='1,1'){
           $formBody = TicketPrint::setMnCoord($formBody,$contentNum,$playStr); 
        }
        //倍数
        $doubleAry = TicketPrint::getMulArr($bet_double);
        foreach ($doubleAry as $v){
            $formBody=TicketPrint::getDoubleCoord($formBody,$contentNum, $v);
        }
        $arr = array_merge_recursive($formHead, $formBody);
	return $arr;
    }

	public static function formatOrderDeal($orderDeal, $lottery_id,$mids)
	{
		foreach ($orderDeal as $k => $v)
		{
			$orderDeal[$k]["bet"] = $v["bet_val"];
			// 串关
			// $v["play_code"] = $orderData['build_code'] ? $orderData['build_code'] : $v["play_code"];
			$orderDeal[$k]["play_name"] = TicketPrint::getMcn($v["play_code"], $v["bet_val"]);
			// 场次、投注内容
			foreach ($v["bet_val"] as $key => $val)
			{
				if ($lottery_id == 3011||$lottery_id==3005)
				{
					$pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
					preg_match($pattern, $val, $result);
					$resArr = explode('*', trim($result[2], '*'));
					$betStr = "";
					$playStr = "";
					foreach ($resArr as $str)
					{
						preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
						$playStr = $r[1];
						$betStr = $r[2];
					}
					$date = $mids[$result[1]]["schedule_code"];
					$orderDeal[$k]["betVal"][$key][] = substr($date, 0, - 3);
					$orderDeal[$k]["betVal"][$key][] = substr($date, - 3);
					$orderDeal[$k]["betVal"][$key][] = $betStr;
                    $orderDeal[$k]["betVal"][$key][] = $playStr;
                    $orderDeal[$k]["betVal"][$key][] = $mids[$result[1]]["schedule_date"];
                    $orderDeal[$k]["betVal"][$key][] = $mids[$result[1]]["open_mid"];
				}else
				{
					$pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
					preg_match($pattern, $val, $result);
					$date = $mids[$result[1]]["schedule_code"];
					$orderDeal[$k]["betVal"][$key][] = substr($date, 0, - 3);
					$orderDeal[$k]["betVal"][$key][] = substr($date, - 3);
					$orderDeal[$k]["betVal"][$key][] = $result[2];
					$orderDeal[$k]["betVal"][$key][] = $lottery_id;
					$orderDeal[$k]["betVal"][$key][] = $mids[$result[1]]["schedule_date"];
					$orderDeal[$k]["betVal"][$key][] = $mids[$result[1]]["open_mid"];
				}
			}
		}
		return $orderDeal;
	}
    	

}
