<?php

namespace app\modules\agents\controllers;

use app\modules\agents\models\Agents;
use app\modules\agents\models\AgentsIp;
use app\modules\common\helpers\CallBackHelper;
use app\modules\tools\helpers\Toolfun;
use Yii;
use yii\web\Controller;
use app\modules\common\models\LotteryOrder;
/**
 * 代理商控制器
 */
class OrderController extends Controller {


    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
    }


    /**
     * 说明:代理商订单查询接口
     * @author chenqiwei
     * @date 2018/3/5 上午10:27
     * @param
     * @return
     */
    public function actionGetOrder(){
        $request = \Yii::$app->request;
        $orderCode = $request->post('order_code',"");
        $result = $this->getOrderDetail($orderCode);
        if($result["code"]==109){
           return $this->jsonError(109,$result["msg"]); 
        }
        return $this->jsonResult(600,$result["msg"],$result["data"]); 

    }

    /**
     * 说明:代理商订单查询接口
     * @author chenqiwei
     * @date 2018/3/5 上午10:27
     * @param
     * @return
     */
    public function actionCallback(){
        $request = \Yii::$app->request;
        $orderCode = $request->post('order_code');
        $type = 1;
        $params = [
            'order_code'=>$orderCode
        ];

        $callBackHelper = new CallBackHelper();
        $ret = $callBackHelper->todoByType($type,$params);
        return $this->jsonResult(600,'succ',$ret);
    }
    /**
 * 获取投注订单信息
 */
    public function getOrderDetail($orderCode){
        if(empty($orderCode)){
            return ["code"=>109,"msg"=>"订单编号不得为空"];
        }
        $field = LotteryOrder::FIELD;
        $orderDetail = LotteryOrder::find()->select($field)
            ->leftJoin('user','user.cust_no = lottery_order.cust_no')
            ->where(['lottery_order_code' => $orderCode])
            ->asArray()
            ->one();
        if(empty($orderDetail)){
            return ["code" => 109, "msg" => '查询结果不存在'];
        }
        return ["code" => 600, "msg" => "查询成功","data"=>$orderDetail];

    }

}
