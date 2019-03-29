<?php

namespace app\modules\store\controllers;

use Yii;
use yii\web\Controller;
use app\modules\common\models\Store;
use yii\db\Query;
use app\modules\store\helpers\Storefun;
use app\modules\user\services\IUserService;
use app\modules\common\services\OrderService;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\PayRecord;
use app\modules\user\models\UserFollow;
use app\modules\common\services\PayService;
use app\modules\common\models\UserFunds;
use app\modules\common\helpers\Constants;
use app\modules\store\helpers\StoreConstants;
use app\modules\common\services\TogetherService;
use app\modules\tools\helpers\SmsTool;
use app\modules\store\services\StoreService;
use app\modules\user\models\User;
use app\modules\common\models\BettingDetail;
use app\modules\common\helpers\Winning;
use app\modules\common\models\OutOrderPic;
use app\modules\common\models\StoreOperator;
use app\modules\competing\services\OptionalService;
use app\modules\competing\services\BasketService;
use app\modules\competing\helpers\CompetConst;
use app\modules\store\models\StoreOptLog;
use app\modules\orders\helpers\DetailDeal;
use app\modules\user\models\UserGrowthRecord;
use app\modules\common\models\ProgrammeUser;
use app\modules\tools\helpers\Toolfun;
use app\modules\openapi\services\ApiNoticeService;
use app\modules\common\models\ApiOrder;
use app\modules\common\services\SyncService;
use app\modules\competing\services\FootballService;
use app\modules\competing\services\BdService;
use app\modules\common\helpers\OrderNews;
use app\modules\competing\services\WorldcupService;
use app\modules\common\services\KafkaService;
use app\modules\store\services\TicketPrintService;
use app\modules\store\services\BanQuancService;
use app\modules\store\services\FootBifenService;
use app\modules\store\services\FootHunheService;
use app\modules\store\services\LanPrintService;
use app\modules\store\services\FootPrintService;

class TicketPrintController extends Controller {    
    /**
     * 投注单打印票样
     * lottery_order_code 订单Code
     * isDeal 是否需要进行落点处理
     */
    public function actionPrintContent(){
        $request = Yii::$app->request;
        $printFootball = Constants::PRINT_FOOTBALL;
       
        $lotteryOrderCode = $request->post("lottery_order_code","");
        $isDeal = $request->post("isDeal",0);
        if(empty($lotteryOrderCode)){
            return $this->jsonError(109, '参数缺失');
        }
        $field=["lottery_order.lottery_id","lottery_order.play_code","lottery_order.bet_val","lottery_order.bet_double","lottery_order.play_name","lottery_order.periods",
            "lottery_order.bet_money","lottery_order.lottery_name","lottery_order.is_bet_add","lottery_order.lottery_order_code","lottery_order.build_code","u.user_name",
            "lottery_order.major_type","lottery_order.lottery_order_id","lottery_order.source_id","lottery_order.count"];
        $data = LotteryOrder::find()->select($field)
                ->leftJoin("user as u","u.cust_no = lottery_order.cust_no")
                ->where(["lottery_order.lottery_order_code" => $lotteryOrderCode])
                ->asArray()
                ->one();
        $ticketService = new TicketPrintService();
        $result=[];
        if($data["lottery_id"]==2001){
            //大乐透
            $res = $ticketService->DealDlt($data["bet_val"],$data["play_code"],$data["bet_double"],$data["is_bet_add"],$isDeal);
            $result=["content"=>$res["content"],"bet"=>$res["info"],"info"=>$data];
        }elseif($data["lottery_id"]==2011){
            //福建11选5
            $res=$ticketService::getSD11X5Content($data,$isDeal);
            if($res['code']==600){
             $result=["content"=>$res['data'],"info"=>$data,"bet"=>$res['content']];
            }else{
             return $this->jsonError($res['code'],$res['msg']);
            }
        }elseif(in_array($data["lottery_id"], $printFootball)){
            //判断是否能生成打印票样
            $result = \app\modules\orders\helpers\DealPrint::getIsPrint($data["lottery_id"],$data["bet_val"]);
            if($result["code"]!=600){
               return $this->jsonError($result['code'],$result['msg']);
            }
            //竞彩足球--胜平负
            switch ($result["data"]){
            	case 3010:
                case 3006:
                case 3008:
            		$res =$ticketService->dealFootballSpf($data,$isDeal,$result["data"]);
                        if($res["code"]!=600){
                           return $this->jsonError($res['code'],$res['msg']); 
                        }
            		$result=["content"=>$res["content"],"bet"=>$res["info"],"info"=>$data];
            		break;
            	case 3009:
            		$res=BanQuancService::formatMuit($data,$isDeal);
            		if($res["code"]!=600){
            			return $this->jsonError($res['code'],$res['msg']);
            		}
            		$result=["content"=>$res["content"],"bet"=>$res["info"],"info"=>$data];
            		break;
                case 3007:
                        $res=FootBifenService::formatMuit($data,$isDeal);
                        if($res["code"]!=600){
                        	return $this->jsonError($res['code'],$res['msg']);
                        }
                        $result=["content"=>$res["content"],"bet"=>$res["info"],"info"=>$data];
                        break;
                case 3011:
                	$res=FootHunheService::formatMuit($data,$isDeal);
                	$result=["content"=>$res["content"],"bet"=>$res["info"],"info"=>$data];
                	break;
            }
        }
        elseif(in_array($data["lottery_id"], Constants::PRINT_BASEKBALL)){//竞彩篮球
        	//判断是否能生成打印票样
        	$result = \app\modules\orders\helpers\DealPrint::getIsPrint($data["lottery_id"],$data["bet_val"]);
        	if($result["code"]!=600){
        		return $this->jsonError($result['code'],$result['msg']);
        	}
        	$res=LanPrintService::getCoord($data,$isDeal,$result["data"]);
        	if($res["code"]!=600){
        		return $this->jsonError($res['code'],$res['msg']);
        	}
        	$result=["content"=>$res["content"],"bet"=>$res["info"],"info"=>$data];
        }
        elseif(in_array($data["lottery_id"], [4001,4002])){//足球任9，任14
        	$res=FootPrintService::getRXCoord($data,$isDeal);
        	if($res["code"]!=600){
        		return $this->jsonError($res['code'],$res['msg']);
        	}
        	$result=["content"=>$res["content"],"bet"=>$res["info"],"info"=>$data];
        }
        //是否需要返回落点处理
        if($isDeal==1){
            $ticketFormat = self::dealFormat($result);
            return $this->jsonResult(600, '获取成功',$ticketFormat);
        }else{
            return $this->jsonResult(600, '获取成功', $result);
        }
    }
    
    /**
     * 处理投注内容，返回打印模板所需的数据格式
     * @param array $data
     * @return type
     */
    public function dealFormat($data){
        $printFootball = Constants::PRINT_FOOTBALL;
        $content =$data["content"];
        $lotteryInfo = $data["info"];
        $betInfo = $data["bet"];
        $lotteryName='';
		if (in_array($lotteryInfo["lottery_id"], Constants::PRINT_BASEKBALL))
		{
			$lotteryName = '竞篮';
		}elseif (in_array($lotteryInfo["lottery_id"], Constants::PRINT_FOOTBALL))
		{
			$lotteryName = '竞足';
		}
        //票样
        $newCont = [];
        foreach ($content as $k => $v){
            $str = "";
            $playName='';
            foreach ($v as $key => $value) {
                foreach ($value as $m => $n){
                    if($m+1==count($value)){
                        $str.=$n; 
                    }else{
                        $str.=$n.","; 
                    }
                }
                if($key+1!=count($v)){
                     $str.="|";
                }
            }
           $newCont[$k]["item"]=$str;
           if(in_array($lotteryInfo["lottery_id"], Constants::PRINT_BASEKBALL)||in_array($lotteryInfo["lottery_id"], Constants::PRINT_FOOTBALL)){
           	$playName=$betInfo[$k]["play_name"].'，'; //竞彩过关方式：串关
           }
           //投注内容信息
           $str1 = "";
           $str1 .=$lotteryInfo["lottery_order_code"]."#".($k+1)."\n".$lotteryName.$lotteryInfo["lottery_name"]."，".$playName.$betInfo[$k]["bet_double"]."倍， "  .$betInfo[$k]["bet_money"]."元 ";
           //大乐透独有追加投注
           if($lotteryInfo["lottery_id"]==2001){
               if($betInfo[$k]["add"]){
                   $str1.="追加";
                }
           }
           $newCont[$k]["header"]=$str1;
        }
        $data = ["data"=>$newCont];
        $info = json_encode($data);
        return $info;
    }
}