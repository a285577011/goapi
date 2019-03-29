<?php

namespace app\modules\common\services;

use Yii;
use yii\db\Query;
use app\modules\common\models\IceRecord;
use yii\db\Expression;
use app\modules\common\models\UserFunds;
use yii\base\Exception;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class FundsService {

    /**
     * 余额操作
     * @param type $cust_no
     * @param type $totalMoney   总金额
     * @param type $ableMoney    可用金额
     * @param type $iceMoney     冻结金额
     * @param type $optWithdraw  是否涉及不可用金额
     * @param type $body        描述
     * @return array
     */
    public function operateUserFunds($cust_no, $totalMoney, $ableMoney = 0, $iceMoney = 0, $optWithdraw = true, $body = "") {
    	if($totalMoney==0&&$ableMoney==0&&$iceMoney==0){
    		return true;
    	}
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            $userFunds = (new Query())->select("*")->from("user_funds")->where(["cust_no" => $cust_no])->one();
            if ($userFunds == null) {
                throw new Exception('用户错误', 2);
            }
            $where=['and', ['=', 'cust_no', $cust_no]];
            if($totalMoney < 0) {
                $where[] = ['>=', 'all_funds', abs($totalMoney)];
                
            }
            if($ableMoney < 0) {
                $where[] = ['>=', 'able_funds', abs($ableMoney)];
            }
            if($iceMoney < 0) {
                $where[] = ['>=', 'ice_funds', abs($iceMoney)];
            }
            if (bcadd($userFunds["all_funds"] , $totalMoney, 2) < 0) {
                throw new Exception('总余额不足', 407);
            }
            if (bcadd($userFunds["able_funds"] , $ableMoney, 2) < 0) {
                throw new Exception('可用余额不足', 407);
            }
            if (bcadd($userFunds["ice_funds"] , $iceMoney, 2) < 0) {
                throw new Exception('冻结余额不足', 407);
            }
            if ($optWithdraw) {
                $noWithdraw = bcadd($userFunds["no_withdraw"] , $ableMoney, 2);
                if ($noWithdraw < 0) {
                    $noWithdraw = 0;
                }
                $update=['all_funds'=>new Expression('all_funds+'.$totalMoney),'able_funds'=>new Expression('able_funds+'.$ableMoney),'ice_funds'=>new Expression('ice_funds+'.$iceMoney),'no_withdraw'=>$noWithdraw,'modify_time'=>date('Y-m-d H:i:s')];
                //Yii::$app->db->createCommand("update user_funds set all_funds=all_funds+{$totalMoney},able_funds=able_funds+{$ableMoney},ice_funds=ice_funds+{$iceMoney},no_withdraw={$noWithdraw} where cust_no='{$cust_no}'")->execute();
            } else {
            	$update=['all_funds'=>new Expression('all_funds+'.$totalMoney),'able_funds'=>new Expression('able_funds+'.$ableMoney),'ice_funds'=>new Expression('ice_funds+'.$iceMoney),'modify_time'=>date('Y-m-d H:i:s')];
                //Yii::$app->db->createCommand("update user_funds set all_funds=all_funds+{$totalMoney},able_funds=able_funds+{$ableMoney},ice_funds=ice_funds+{$iceMoney} where cust_no='{$cust_no}'")->execute();
            }
            
            if(!UserFunds::upData($update, $where)){
            	throw new Exception('金额不足', 110);
            }
            $tran->commit();
            //用户账户表变动
//             $lotteryqueue = new \LotteryQueue();
            // $lotteryqueue->pushQueue("backupOrder_job', 'backup_userfunds', ['tablename' => 'user_funds', "keyname" => 'cust_no', 'keyval' => $cust_no]);
            return ["code" => 0, "msg" => "支付成功"];
        } catch (Exception $e) {
            $tran->rollBack();
            return ["code" => 2, "msg" => $e->getMessage()];
        }
    }

    /**
     * 冻结金额记录
     * @param type $custNo
     * @param type $custType    用户类型
     * @param type $orderCode   订单编号
     * @param type $money       涉及金额
     * @param type $type        1、收入 2、支出
     * @param type $body        描述
     */
    public function iceRecord($custNo, $custType, $orderCode, $money, $type, $body = "") {
    	$user = (new Query())->select("ice_funds")->from("user_funds")->where(["cust_no" => $custNo])->one();
        $iceRecord = new IceRecord();
        $iceRecord->cust_no = $custNo;
        $iceRecord->order_code = $orderCode;
        $iceRecord->cust_type = $custType;
        $iceRecord->money = $money;
        $iceRecord->body = $body;
        $iceRecord->type = $type;
        $iceRecord->ice_balance = $user["ice_funds"];
        $iceRecord->create_time = date("Y-m-d H:i:s");
        if(!$iceRecord->saveData()){
        	KafkaService::addLog('iceRecord', '记录表操作失败'.var_export($iceRecord->attributes,true));
        	return false;
        }
        return \Yii::$app->db->getLastInsertID();
        //$lotteryqueue = new \LotteryQueue();
        //用户账户表变动
        // $lotteryqueue->pushQueue('backupOrder_job', 'backup_userfunds', ['tablename' => 'user_funds', "keyname" => 'cust_no', 'keyval' => $custNo]);
    }

    /**
     * 获取冻结金额记录
     * @param array $params 过滤条件
     * @return type
     */
    public function getIceRecord($params) {
        if (!isset($params["cust_no"])) {
            return [];
        }
        $query = IceRecord::find()->select(["cust_no", "order_code", "money", "ice_balance", "body", "type", "create_time"])->where(["cust_no" => $params["cust_no"]]);
        if (isset($params["size"])) {
            $size = $params["size"];
        } else {
            $size = 10;
        }
        if (isset($params["page_num"])) {
            $page = $params["page_num"];
        } else {
            $page = 1;
        }
        if (isset($params["pType"]) && ($params["pType"] == 1 || $params["pType"] == 2)) {
            $query = $query->andWhere(["type" => $params["pType"]]);
        }
        if (isset($params["order_code"]) && !empty($params["order_code"])) {
            $query = $query->andWhere(["order_code" => $params["order_code"]]);
        }
        if (isset($params["order_code"]) && !empty($params["order_code"])) {
            $query = $query->andWhere(["order_code" => $params["order_code"]]);
        }
        $total = $query->count();
        $data = $query->orderBy("ice_record_id desc")->offset($size * ($page - 1))->limit($size)->asArray()->all();
        foreach ($data as &$val) {
            if ($val["type"] == 1) {
                $val["money"] = "+" . $val["money"];
            } else {
                $val["money"] = "-" . $val["money"];
            }
        }
        $result["count"] = count($data);
        $result["size"] = $size;
        $result["current_page"] = $page;
        $result["total"] = $total;
        $result["total_pages"] = ceil($total / $size);
        $result["records"] = $data;
        return $result;
    }

}
