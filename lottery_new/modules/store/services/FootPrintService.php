<?php
namespace app\modules\store\services;

use yii;
use app\modules\store\helpers\TicketPrint;
use app\modules\store\helpers\StoreConstants;
use app\modules\orders\helpers\OrderDeal;
use app\modules\orders\helpers\DealPrint;
use app\modules\orders\models\MajorData;

class FootPrintService
{

	/**
	 * 足球任选坐标填点
	 * @param unknown $orderData
	 * @param number $isDeal
	 */
	public static function getRXCoord($orderData, $isDeal)
	{
		$orderDeal= self::getPrintData($orderData);
		foreach ($orderDeal as $k=>$v){
			$orderDeal[$k]['bet']=$orderDeal[$k]['bet_val'];
			$orderDeal[$k]['play_name']=$orderData['play_name'];
		}
		if (! $isDeal)
		{
			return [ 
				'code' => 600 , 
				"content" => [ ] , 
				"info" => $orderDeal 
			];
		}
		$data = [ ];
		foreach ($orderDeal as $k => $v)
		{
			list ( $coord , $x ) = self::initCoord($v, $orderData['play_code']);
			$coord = self::setBetCoord($coord, $v, $orderData['play_code'], $x);
			if($v['bet_double']>1){
				switch ($v['bet_double']){
					case 2:
						$coord[$x][11]=1;
						break;
					case 3:
						$coord[$x][10]=1;
						break;
					case 4:
						$coord[$x][9]=1;
						break;
					case 5:
						$coord[$x][8]=1;
						break;
					case 6:
						$coord[$x][7]=1;
						break;
					case 7:
						$coord[$x][6]=1;
						break;
					case 8:
						$coord[$x][5]=1;
						break;
					case 9:
						$coord[$x][4]=1;
						break;
					case 10:
						$coord[$x][3]=1;
						break;
					
				}
			}
			$data[] = $coord;
		}
		return [ 
			'code' => 600 , 
			"content" => $data , 
			"info" => $orderDeal 
		];
	}

	public static function getPrintData($orderData)
	{
		$orderDeal=DealPrint::getOptionalPrint($orderData['lottery_id'], $orderData['bet_val'], $orderData['bet_double'], $orderData['count']);
		return $orderDeal;
	}

	/**
	 * 初始化坐标
	 */
	public static function initCoord($v, $playCode)
	{
		switch ($v['play_code']){
			case 400202:
			case 400201:
				$x = 18;
				$coord = TicketPrint::creatCoord($x, 12);
				$coord[0][2] = $coord[0][3] = $coord[0][4] = $coord[0][8] = $coord[0][11] = 1;
				$coord[1][11] = 1;
				break;
			case 400101:
			case 400102:
				$x = 33;
				$coord = TicketPrint::creatCoord($x, 12);
				$coord[0][2] = $coord[0][3] = $coord[0][4] = $coord[0][5] = $coord[0][7]= $coord[0][12] = 1;
				break;
		}
		return [ 
			$coord , 
			$x 
		];
	}

	/**
	 * 投注内容填点
	 */
	public static function setBetCoord($coord, $data, $playCode, $x)
	{
		switch ($data['play_code']){
			case 400201://任九单式
				$i=1;
				foreach ($data['bet_val'] as $v){
					$betArr=explode(',', $v);
					$j=1;
					foreach ($betArr as $bet){
						switch ($bet){
							case '0':
								$coord[3+$j-1][1+4*($i-1)]=1;
								break;
							case '1':
								$coord[3+$j-1][2+4*($i-1)]=1;
								break;
							case '3':
								$coord[3+$j-1][3+4*($i-1)]=1;
								break;
						}
						$j++;
					}
					$i++;
				}
				break;
			case 400202://任九复式
				$count=1;
				foreach ($data['bet_val'] as $v){
					$betArr=explode(',', $v);
					$i=1;
					foreach ($betArr as $bet){
						$betS=array_reverse(str_split($bet));
						foreach ($betS as $vc){
							switch ($vc){
								case '0':
									$coord[3+$i-1][1+4*($count-1)]=1;
									break;
								case '1':
									$coord[3+$i-1][2+4*($count-1)]=1;
									break;
								case '3':
									$coord[3+$i-1][3+4*($count-1)]=1;
									break;
									default:
										break;
							}
						}
						$i++;
					}
					$count++;
				}
				$coord[5][0]=1;//复式点
				break;
			case 400101://14场单式
				$count=1;
				foreach ($data['bet_val'] as $v){
					$betArr=explode(',', trim($v,'^'));
					$j=1;
					$pos=($count-1)%3;
					$star=4;
					if($count>3){
						$pos+=1;
						$star=19;
					}
					foreach ($betArr as $bet){
						switch ($bet){
							case '0':
								$coord[$star+$j-1][1+4*$pos]=1;
								break;
							case '1':
								$coord[$star+$j-1][2+4*$pos]=1;
								break;
							case '3':
								$coord[$star+$j-1][3+4*$pos]=1;
								break;
						}
						$j++;
					}
					$count++;
				}
				break;
			case 400102://14场复式
				$count=1;
				foreach ($data['bet_val'] as $v){
					$betArr=explode(',', $v);
					$i=1;
					foreach ($betArr as $bet){
						$betS=array_reverse(str_split($bet));
						foreach ($betS as $vc){
							switch ($vc){
								case '0':
									$coord[19+$i-1][1+4*($count-1)]=1;
									break;
								case '1':
									$coord[19+$i-1][2+4*($count-1)]=1;
									break;
								case '3':
									$coord[19+$i-1][3+4*($count-1)]=1;
									break;
							}
						}
						$i++;
					}
					$count++;
				}
				$coord[21][0]=1;//复式点
				break;
				
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
	public static function setDSFCoord($data, $betStar, $Y, $betVal)
	{
		$betValArr = explode(',', $betVal);
		foreach ($betValArr as $vb)
		{
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
	public static function setRFSFCoord($data, $betStar, $Y, $betVal)
	{
		$betValArr = explode(',', $betVal);
		foreach ($betValArr as $vb)
		{
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
	public static function setSFCCoord($data, $betStar, $Y, $betVal)
	{
		$betValArr = explode(',', $betVal);
		foreach ($betValArr as $vb)
		{
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
	public static function setSFCoord($data, $betStar, $Y, $betVal)
	{
		$betValArr = explode(',', $betVal);
		foreach ($betValArr as $vb)
		{
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
