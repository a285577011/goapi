<?php

namespace app\modules\common\services;

use Yii;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\FundsService;
use yii\db\Query;
use app\modules\common\models\PayRecord;
use app\modules\common\helpers\Constants;
use app\modules\pay\helpers\PayTool;
use app\modules\user\helpers\WechatTool;
use app\modules\user\models\User;
use app\modules\experts\services\ExpertService;
use app\modules\common\models\Programme;
use app\modules\user\models\CouponsDetail;
use app\modules\user\helpers\UserCoinHelper;
use yii\base\Exception;

class PayService {

    public $order_code;
    public $pay_way;
    public $way_type;
    public $cust_no = "";
    public $body = "咕啦体育-彩票购买";
    public $attach = "咕啦中国";
    public $tag = "厦门分公司";
    public $payPassword = "";
    public $payPreMoney = 0;
    public $betMoney;
    public $custType = 1;
    public $user_id;
    public $activeType = 0;
    public $qbH5PayType;

    const ORDER_EXPIRE_TIME = 300; //订单未支付过期时间10分钟

    /**
     * 支付
     * @return boolean
     */

    public function Pay() {
        $payRecord = PayRecord::findOne(["order_code" => $this->order_code]);
        $ProgrammeOrder = [];
        if ($payRecord == null) {
            $ProgrammeOrder = Programme::findOne(['programme_code' => $this->order_code]);
            if (!$ProgrammeOrder || $this->pay_way != 3) { // 检查是不是合买订单或者不是余额支付
                return ["code" => 2, "msg" => "未找到订单", "result" => ""];
            }
        }
        if ($payRecord) {
            if ($payRecord->status != 0) {
                return ["code" => 2, "msg" => "订单已支付", "result" => ""];
            }
            if (date('Y--m-d H:i:s', time() - self::ORDER_EXPIRE_TIME) > $payRecord->create_time) {
                return ["code" => 2, "msg" => "订单已超时", "result" => ""];
            }
            $payRecord->modify_time = date("Y-m-d H:i:s");
            $this->betMoney = 0.01; //$payRecord->pay_pre_money;
        }
        switch ($this->pay_way) {
            case "1":
                if (!in_array($this->way_type, ["PAGE", "WAP", "APP"])) {
                    return ["code" => 2, "msg" => "订单支付类型出错", "result" => ""];
                }
                $way_name = [
                    "PAGE" => "支付宝扫码",
                    "WAP" => "支付宝H5",
                    "APP" => "支付宝APP"
                ];

                $payRecord->pay_way = 1;
                $payRecord->pay_name = "支付宝";
                $payRecord->way_type = $this->way_type;
                $payRecord->way_name = $way_name[$this->way_type];
                $payRecord->save();

                $alipay = new \app\modules\components\alipay\alipay();
                $alipay->body = $this->attach;
                $alipay->out_trade_no = $payRecord->order_code;
                $alipay->subject = $payRecord->body;
                $alipay->total_amount = $this->betMoney;
                $alipay->type = $payRecord->way_type;
                $alipay->pay();
                break;
            case "2":
                if (!in_array($this->way_type, ["NATIVE", "JSAPI", "APP"])) {
                    return ["code" => 2, "msg" => "订单支付类型出错", "result" => ""];
                }
                $way_name = [
                    "NATIVE" => "微信扫码",
                    "JSAPI" => "微信公众号",
                    "APP" => "微信APP"
                ];
                $payRecord->pay_way = 2;
                $payRecord->pay_name = "微信";
                $payRecord->way_type = $this->way_type;
                $payRecord->way_name = $way_name[$this->way_type];
                $payRecord->save();

                if ($this->way_type == "APP") {
                    $wxpay = new \app\modules\components\wxpay\wxpayapp();
                } else {
                    $wxpay = new \app\modules\components\wxpay\wxpay();
                }
                if ($this->way_type == "JSAPI") {
                    if ($payRecord->cust_type == 1) {
                        $user = (new Query())->select("*")->from("user")->where(["cust_no" => $this->cust_no])->one();
                        $uid = $user["user_id"];
                    } else {
                        $store = (new Query())->select("*")->from("store")->where(["cust_no" => $this->cust_no])->one();
                        $uid = $store["user_id"];
                    }
                    $tUser = (new Query())->select("third_uid")->from("third_user")->where(["uid" => $user["user_id"], "type" => $payRecord->cust_type])->one();
                    $wxpay->openid = $tUser["third_uid"];
                }
                $wxpay->body = $payRecord->body;
                $wxpay->attach = $this->attach;
                $wxpay->orderCode = $payRecord->order_code;
                $wxpay->productId = "GLC";
                $wxpay->tag = $this->tag;
                $wxpay->totalFee = $this->betMoney * 100;
                $wxpay->type = $payRecord->way_type;
                $order = $wxpay->productPrePay();
                if ($this->way_type == "JSAPI") {
                    return $order;
                }
                return ["code" => 600, "msg" => "生成微信订单成功", "result" => $order];
            case "3":
                try {
                    $way_name = [
                        "YE" => "余额",
                    ];
                    if ($this->activeType == 0) {
                        $this->validatePayPassword($this->cust_no, $this->payPassword);
                    }
                    if ($ProgrammeOrder) {//是发起合买订单
                        $ProgrammeService = new ProgrammeService();
                        return $ProgrammeService->creatProgrammeNotify($ProgrammeOrder);
                    }
                    $trans = \Yii::$app->db->beginTransaction();
                    $this->betMoney = $payRecord->pay_pre_money;
                    if ($payRecord->cust_no != $this->cust_no) {
                        throw new Exception('用户订单不符');
//                        return ["code" => 2, "msg" => "用户订单不符", "result" => ""];
                    }
                    if (!in_array($this->way_type, ["YE"])) {
                        throw new Exception('订单支付类型出错');
//                        return ["code" => 2, "msg" => "", "result" => ""];
                    }
                    $payRecord->pay_way = 3;
                    $payRecord->pay_name = "余额";
                    $payRecord->way_type = $this->way_type;
                    $payRecord->way_name = $way_name[$this->way_type];
                    $payRecord->save();
                    $fundsSer = new FundsService();
                    $ret = $fundsSer->operateUserFunds($this->cust_no, (0 - $this->betMoney), (0 - $this->betMoney), 0, true);
                    if ($ret["code"] != 0) {
                        throw new Exception($ret['msg']);
//                        return ;
                    }

                    $outer_no = Commonfun::getCode("YEP", "Z");
                    if ($payRecord->pay_type == 1 && $payRecord->cust_type == 1) {
                        $ret = \app\modules\common\services\OrderService::orderNotify($payRecord->order_code, $outer_no, $this->betMoney, date("Y-m-d H:i:s"), $payRecord->attributes);
                    } else if ($payRecord->pay_type == 5 && $payRecord->cust_type == 1) {
                        $programmeSer = new \app\modules\common\services\ProgrammeService();
                        $ret = $programmeSer->programmeNotify($payRecord->order_code, $outer_no, $this->betMoney, date("Y-m-d H:i:s"));
                    } else if ($payRecord->pay_type == 7 && $payRecord->cust_type == 1) {
                        $ret = \app\modules\common\services\PlanService::planNotify($payRecord->order_code, $outer_no, $this->betMoney, date("Y-m-d H:i:s"));
                    } elseif ($payRecord->pay_type == 17 && $payRecord->cust_type == 1) {
                        $expertService = new ExpertService();
                        $ret = $expertService->articleNotify($payRecord->order_code, $outer_no, $this->betMoney, date('Y-m-d H:i:s'));
                    } elseif ($payRecord->pay_type == 100) {//众筹单
                        $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $payRecord->cust_no])->one();
                        PayRecord::upData([
                            "status" => 1,
                            "outer_no" => $outer_no,
                            "modify_time" => date("Y-m-d H:i:s"),
                            "pay_time" => date("Y-m-d H:i:s"),
                            "pay_money" => $this->betMoney,
                            "balance" => $funds["all_funds"]
                                ], [
                            "order_code" => $payRecord->order_code,
                        ]);
                        \yii::sendCurlPost(\Yii::$app->params['zhonchou_callback'], ['order_code' => $payRecord->order_code, 'pay_code' => 'YE']);
                    } elseif ($payRecord->pay_type == 23) {
                        $ret = UserCoinHelper::coinRechargeNotify($payRecord->order_code, $outer_no);
                    } else {
                        throw new Exception('购买失败');
//                        return ["code" => 2, "msg" => "购买失败", "result" => ""];
                    }

                    if ($ret !== true) {
                        if (is_array($ret) && isset($ret['msg'])) {
                            $msg = $ret['msg'];
                        } else {
                            $msg = "购买失败";
                        }
                        $ret = false;
                    }

                    if ($ret == false) {
                        throw new Exception($msg);
//                        return ["code" => 2, "msg" => , "result" => ""];
                    }
                    $trans->commit(); 
                    return ["code" => 600, "msg" => "购买成功", "result" => ""];
                } catch (Exception $ex) {
                    $trans->rollBack();
                    return ['code' => 2, 'msg' => $ex->getMessage(), 'result' => ""];
                }
            default :
                return ["code" => 2, "msg" => "没有该支付", "result" => ""];
        }
        return true;
    }

    public function recharge() {
        $orderCode = Commonfun::getCode("RC", "C");
        if (!empty($this->order_code)) {
            $key = 'waitting_recharge:' . $orderCode;
            \Yii::redisSet($key, $this->order_code, 360);
        }
        switch ($this->pay_way) {
            case "2":
                if (!in_array($this->way_type, ["NATIVE", "JSAPI", "APP"])) {
                    return ["code" => 2, "msg" => "订单支付类型出错", "result" => ""];
                }
                $way_name = [
                    "NATIVE" => "微信扫码",
                    "JSAPI" => "微信公众号",
                    "APP" => "微信APP"
                ];
                PayRecord::addData([
                    "body" => $this->body,
                    "order_code" => $orderCode,
                    "pay_no" => Commonfun::getCode("PAY", "L"),
                    "pay_way" => 2,
                    "pay_name" => "微信",
                    "way_type" => $this->way_type,
                    "way_name" => $way_name[$this->way_type],
                    "pay_type" => 3,
                    "pay_type_name" => "充值",
                    "pay_pre_money" => $this->payPreMoney,
                    "cust_no" => $this->cust_no,
                    "cust_type" => $this->custType,
                    "modify_time" => date("Y-m-d H:i:s"),
                    "create_time" => date("Y-m-d H:i:s"),
                    "status" => 0,
                ]);
                /* \Yii::$app->db->createCommand()->insert("pay_record", [
                  "body" => $this->body,
                  "order_code" => $orderCode,
                  "pay_no" => Commonfun::getCode("PAY", "L"),
                  "pay_way" => 2,
                  "pay_name" => "微信",
                  "way_type" => $this->way_type,
                  "way_name" => $way_name[$this->way_type],
                  "pay_type" => 3,
                  "pay_type_name" => "充值",
                  "pay_pre_money" => $this->payPreMoney,
                  "cust_no" => $this->cust_no,
                  "cust_type" => $this->custType,
                  "modify_time" => date("Y-m-d H:i:s"),
                  "create_time" => date("Y-m-d H:i:s"),
                  "status" => 0,
                  ])->execute(); */
                if ($this->way_type == "APP") {
                    $wxpay = new \app\modules\components\wxpay\wxpayapp();
                } else {
                    $wxpay = new \app\modules\components\wxpay\wxpay();
                }
                if ($this->way_type == "JSAPI") {
                    if ($this->custType == 1) {
                        $user = (new Query())->select("*")->from("user")->where(["cust_no" => $this->cust_no])->one();
                        $uid = $user["user_id"];
                    } else {
                        $store = (new Query())->select("*")->from("store")->where(["cust_no" => $this->cust_no])->one();
                        $uid = $store["user_id"];
                    }
                    $tUser = (new Query())->select("third_uid")->from("third_user")->where(["uid" => $uid, "type" => $this->custType])->one();
                    $wxpay->openid = $tUser["third_uid"];
                }
                $wxpay->body = $this->body;
                $wxpay->attach = $this->attach;
                $wxpay->orderCode = $orderCode;
                $wxpay->productId = "GLC";
                $wxpay->tag = $this->tag;
                $wxpay->totalFee = $this->betMoney * 100;
                $wxpay->type = $this->way_type;
                $order = $wxpay->productPrePay();
                $order["order_code"] = $orderCode;
                if ($this->way_type == "JSAPI") {
                    return $order;
                }
                return ["code" => 600, "msg" => "生成微信订单成功", "result" => $order];
                break;
            case "1":
                if (!in_array($this->way_type, ["PAGE", "WAP", "APP"])) {
                    return ["code" => 2, "msg" => "订单支付类型出错", "result" => ""];
                }
                $way_name = [
                    "PAGE" => "支付宝扫码",
                    "WAP" => "支付宝H5",
                    "APP" => "支付宝APP"
                ];
                PayRecord::addData([
                    "body" => $this->body,
                    "order_code" => $orderCode,
                    "pay_no" => Commonfun::getCode("PAY", "L"),
                    "pay_way" => 1,
                    "pay_name" => "支付宝",
                    "way_type" => $this->way_type,
                    "way_name" => $way_name[$this->way_type],
                    "pay_type" => 3,
                    "pay_type_name" => "充值",
                    "pay_pre_money" => $this->payPreMoney,
                    "cust_no" => $this->cust_no,
                    "cust_type" => $this->custType,
                    "modify_time" => date("Y-m-d H:i:s"),
                    "create_time" => date("Y-m-d H:i:s"),
                    "status" => 0,
                ]);
                /*
                  \Yii::$app->db->createCommand()->insert("pay_record", [
                  "body" => $this->body,
                  "order_code" => $orderCode,
                  "pay_no" => Commonfun::getCode("PAY", "L"),
                  "pay_way" => 1,
                  "pay_name" => "支付宝",
                  "way_type" => $this->way_type,
                  "way_name" => $way_name[$this->way_type],
                  "pay_type" => 3,
                  "pay_type_name" => "充值",
                  "pay_pre_money" => $this->payPreMoney,
                  "cust_no" => $this->cust_no,
                  "cust_type" => $this->custType,
                  "modify_time" => date("Y-m-d H:i:s"),
                  "create_time" => date("Y-m-d H:i:s"),
                  "status" => 0,
                  ])->execute();
                 */
                $alipay = new \app\modules\components\alipay\alipay();
                $alipay->body = $this->attach;
                $alipay->out_trade_no = $orderCode;
                $alipay->subject = $this->body;
                $alipay->total_amount = $this->betMoney;
                $alipay->type = $this->way_type;
                $alipay->pay();
                break;
            case "4":
                //$post = Yii::$app->request->post();
                //$orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
                //计算折扣
                PayRecord::addData([
                    "body" => $this->body,
                    "order_code" => $orderCode,
                    "pay_no" => Commonfun::getCode("PAY", "L"),
                    "pay_way" => 4,
                    "pay_name" => "咕啦钱包",
                    "way_type" => "GLC",
                    "way_name" => "钱包二维码",
                    "pay_type" => 3,
                    "pay_type_name" => "充值",
                    "pay_pre_money" => $this->payPreMoney,
                    "cust_no" => $this->cust_no,
                    "cust_type" => $this->custType,
                    "modify_time" => date("Y-m-d H:i:s"),
                    "create_time" => date("Y-m-d H:i:s"),
                    "status" => 0,
                ]);
                /* \Yii::$app->db->createCommand()->insert("pay_record", [
                  "body" => $this->body,
                  "order_code" => $orderCode,
                  "pay_no" => Commonfun::getCode("PAY", "L"),
                  "pay_way" => 4,
                  "pay_name" => "咕啦钱包",
                  "way_type" => "GLC",
                  "way_name" => "钱包二维码",
                  "pay_type" => 3,
                  "pay_type_name" => "充值",
                  "pay_pre_money" => $this->payPreMoney,
                  "cust_no" => $this->cust_no,
                  "cust_type" => $this->custType,
                  "modify_time" => date("Y-m-d H:i:s"),
                  "create_time" => date("Y-m-d H:i:s"),
                  "status" => 0,
                  ])->execute(); */
                $money = $this->payPreMoney;
                $paytool = new PayTool();
                $ret = $paytool->createQbQrcode($money, $orderCode);
                if ($ret["code"] == 1) {
                    return Yii::jsonResult(600, '下单成功', ["create_time" => date("Y-m-d H:i:s"), "order_code" => $orderCode, "bet_money" => $money, "pay_url" => $ret["pay_url"]]);
                } else {
                    return Yii::jsonResult(109, '下单失败', $ret);
                }
                break;
            case "5":
                PayRecord::addData([
                    "body" => $this->body,
                    "order_code" => $orderCode,
                    "pay_no" => Commonfun::getCode("PAY", "L"),
                    "pay_way" => 5,
                    "pay_name" => "咕啦钱包H5",
                    "way_type" => "GLCH5",
                    "way_name" => "钱包H5",
                    "pay_type" => 3,
                    "pay_type_name" => "充值",
                    "pay_pre_money" => $this->payPreMoney,
                    "cust_no" => $this->cust_no,
                    "cust_type" => $this->custType,
                    "modify_time" => date("Y-m-d H:i:s"),
                    "create_time" => date("Y-m-d H:i:s"),
                    "status" => 0,
                ]);
                $money = $this->payPreMoney;
                $paytool = new PayTool();
                $ret = $paytool->createH5Pay($this->qbH5PayType, $money, $orderCode);
                if ($ret["code"] == 1) {
                    return Yii::jsonResult(600, '下单成功', ["create_time" => date("Y-m-d H:i:s"), "order_code" => $orderCode, "bet_money" => $money, "pay_url" => $ret["pay_url"], "qr_code" => $ret['qr_code']]);
                } else {
                    return Yii::jsonResult(109, '下单失败', $ret);
                }
                break;
            default :
                return ["code" => 2, "msg" => "没有该支付", "result" => ""];
        }
        return true;
    }

    public function getPayRecordType() {
        $data = [
            ["key" => "1|5", "val" => "购彩"],
            ["key" => "4", "val" => "提现"],
            ["key" => "6", "val" => "退款"],
            ["key" => "2", "val" => "转账"],
            ["key" => "11", "val" => "奖金发放"],
            ["key" => "3", "val" => "充值"],
            ["key" => "7|8|12|13", "val" => "定投计划"],
            ["key" => "16", "val" => "服务费"],
            ["key" => "9", "val" => "门店出票"],
            ["key" => "14", "val" => "提成"],
            ["key" => "15", "val" => "奖金"],
            ['key' => '17', 'val' => '方案购买'],
            ['key' => '18', 'val' => '方案收款']
        ];
        return $data;
    }

    public function getPayRecordList() {
        $post = \Yii::$app->request->post();
        $expenditureTypeArr = Constants::EXPENDITURE_TYPE;
        $size = isset($post["page_size"]) ? $post["page_size"] : 10;
        $page = isset($post["page_num"]) ? $post["page_num"] : 0;
        $data = (new Query())->select("all_funds,able_funds,ice_funds,user_integral,user_glcoin")->from("user_funds")
                ->where(["cust_no" => $this->cust_no])
                ->one();
        if ($data == null) {
            return ["code" => 2, "msg" => "用户错误", "result" => ''];
        }
        $query = (new Query())->select("cust_no,order_code,pay_name,way_name,pay_money,pay_pre_money,pay_type,pay_type_name,body,status,create_time")->from("pay_record")->where([
                    "cust_no" => $this->cust_no
                ])->andWhere(["in", "status", [1, 3]])->andWhere(['!=', 'pay_type', 10]);

        if (isset($post["pay_type"]) && !empty($post["pay_type"])) {
            if ($post["pay_type"] == "6") {
                $query = $query->andWhere(["status" => 3]);
            } else {
                $payTypes = explode("|", $post["pay_type"]);
                $query = $query->andWhere(["in", "pay_type", $payTypes]);
            }
        }
        if (isset($post["body"]) && !empty($post["body"])) {
            $query = $query->andWhere(["like", "body", "%" . $post["body"] . "%", false]);
        }
        if (isset($post["month"]) && !empty($post["month"])) {
            $query = $query->andWhere(["like", "create_time", $post["month"] . "%", false]);
        }
        if (isset($post["start_date"]) && !empty($post["start_date"])) {
            $query = $query->andWhere([">=", "create_time", $post["start_date"] . " 00:00:00"]);
        }
        if (isset($post["end_date"]) && !empty($post["end_date"])) {
            $query = $query->andWhere(["<", "create_time", $post["end_date"] . " 23:59:59"]);
        }
        $total = $query->count();
        $infos = $query->orderBy("create_time desc")->offset(($page - 1) * $size)->limit($size)->all();
        foreach ($infos as &$val) {
            if ($val["status"] == 3) {
                $val["pay_type"] = "6";
            }
            if (in_array($val["pay_type"], $expenditureTypeArr) && $val["status"] == 1) {
                $val["pay_money"] = "-" . $val["pay_pre_money"];
            } else {
                $val["pay_money"] = "+" . $val["pay_pre_money"];
            }
        }
        $month = Constants::MONTH;
        $url = Constants::PAY_TYPE_PIC_URL;
        $count = count($infos);
        $data["pagesize"] = $size;
        $data["total"] = (int) $total;
        $data["count"] = $count;
        $data["records"] = [];
        $old_time = "";
        $num = -1;
        if ($count > 0) {
            foreach ($infos as &$info) {
                $time = substr($info["create_time"], 5, 2);
                if ($old_time != $time) {
                    $old_time = $time;
                    $num++;
                    $data["records"][$num] = [];
                    $data["records"][$num]["month"] = $month[$time];
                    $data["records"][$num]["list"] = [];
                }
                $info['pay_type_pic'] = $url . $info['pay_type'];
                $data["records"][$num]["list"][] = $info;
            }
        }
        return ["code" => 600, "msg" => "获取成功", "result" => $data];
    }

    public function validatePayPassword() {
        if ($this->payPassword == "") {
            echo json_encode(["code" => 405, "msg" => "请输入密码", "result" => ""]);
            exit();
        }
        $data = (new Query())->select("user_id,pay_password")->from("user_funds")->where([
                    "cust_no" => $this->cust_no
                ])->one();
        if ($data == null) {
            echo json_encode(["code" => 2, "msg" => "用户错误", "result" => ""]);
            exit();
        } else {
            if ($data["pay_password"] == "") {
                echo json_encode(["code" => 403, "msg" => "未设置支付密码", "result" => ""]);
                exit();
            }
            if ($data["pay_password"] == md5($this->payPassword)) {
                return true;
            } else {
                echo json_encode(["code" => 406, "msg" => "密码错误", "result" => ""]);
                exit();
            }
        }
    }

    /**
     * 退款
     * @param string $orderCode
     * @param string $refund_reason
     * @param decimal $refundMoney
     * @return boolean
     */
    public function refund($orderCode, $refund_reason = "退款理由", $refundMoney = null) {
        $payRecord = (new \yii\db\Query())->select("*")->from("pay_record")->where(["order_code" => $orderCode, "status" => 1])->one();
        if ($payRecord != null) {
            if ($refundMoney == null) {
                $refundMoney = $payRecord["pay_money"];
            }
            $refundToYue = false;
            switch ($payRecord["pay_way"]) {
                case "1"://支付宝退款
                    $out_request_no = $payRecord["order_code"] . date("YmdHis");
                    $pay = new \app\modules\components\alipay\alipay();
                    $ret = $pay->refund($payRecord["order_code"], $payRecord["outer_no"], $refund_reason, $out_request_no, /* $refundMoney */ 0.01);
                    if ($ret != true) {
                        Commonfun::stationLetter(); //站内信通知-----!!未完成
                        return false;
                    }
                    break;
                case "2"://微信退款
                    if ($payRecord['way_type'] == 'APP') {
                        $pay = new \app\modules\components\wxpay\wxpayapp();
                        $out_request_no = "";
                        $ret = $pay->refund($payRecord["order_code"], /* $refundMoney */ 0.01 * 100, $payRecord["pay_money"] * 100, $out_request_no);
                    } else {
                        $pay = new \app\modules\components\wxpay\wxpay();
                        $out_request_no = "";
                        $ret = $pay->refund($payRecord["order_code"], /* $refundMoney */ 0.01 * 100, $payRecord["pay_money"] * 100, $out_request_no);
                    }
                    if ($ret != true) {
                        Commonfun::stationLetter(); //站内信通知
                        return false;
                    }
                    break;
                case "3"://余额退款
                    $pay = new \app\modules\components\wxpay\wxpay();
                    $out_request_no = Commonfun::getCode("RF", "T");
                    $fundsSer = new FundsService();
                    $ret = $fundsSer->operateUserFunds($payRecord["cust_no"], $refundMoney, $refundMoney, 0, true, "余额退款");
                    if ($ret["code"] != "0") {
                        Commonfun::stationLetter(); //站内信通知-----!!未完成
                        return false;
                    }
                    break;
                case "4"://钱包退款
                    $refundToYue = true;
                    $pay = new \app\modules\components\wxpay\wxpay();
                    $out_request_no = Commonfun::getCode("RF", "T");
                    $fundsSer = new FundsService();
                    $ret = $fundsSer->operateUserFunds($payRecord["cust_no"], $refundMoney, $refundMoney, 0, true, "余额退款");
                    if ($ret["code"] != "0") {
                        Commonfun::stationLetter(); //站内信通知-----!!未完成
                        return false;
                    }
                    break;
                case "5"://钱包退款
                    $refundToYue = true;
                    $pay = new \app\modules\components\wxpay\wxpay();
                    $out_request_no = Commonfun::getCode("RF", "T");
                    $fundsSer = new FundsService();
                    $ret = $fundsSer->operateUserFunds($payRecord["cust_no"], $refundMoney, $refundMoney, 0, true, "余额退款");
                    if ($ret["code"] != "0") {
                        Commonfun::stationLetter(); //站内信通知-----!!未完成
                        return false;
                    }
                    break;
                default:
                    return false;
            }
            if ($payRecord['pay_type'] == 1 || $payRecord['pay_type'] == 5) {
                $this->refundReturn($orderCode, $out_request_no, $refundMoney, $refundToYue, 'L', '退款-购彩-');
            } elseif ($payRecord['pay_type'] == 17) {
                $this->refundReturn($orderCode, $out_request_no, $refundMoney, $refundToYue, 'A', '退款-方案-');
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 退款交易回调
     */
    public function refundReturn($orderCode, $out_request_no, $refundMoney, $refundToYue, $letter, $body) {
        $info = PayRecord::find()->where(["order_code" => $orderCode, "status" => 1])->asArray()->one();
        if ($info == null) {
            return ["code" => 501, "msg" => "未找到交易记录"];
        }
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $info['cust_no']])->one();
            $payRecord = new PayRecord();
            $payRecord->order_code = $info['order_code'];
            $payRecord->pay_no = Commonfun::getCode("PAY", $letter);
            $payRecord->cust_no = $info['cust_no'];
            $payRecord->cust_type = $info['cust_type'];
            $payRecord->user_id = $info['user_id'];
            $payRecord->user_name = $info['user_name'];
            $payRecord->store_id = $info['store_id'];
            $payRecord->agent_code = $info['agent_code'];
            $payRecord->agent_id = $info['agent_id'];
            $payRecord->agent_name = $info['agent_name'];
            $payRecord->pay_no = $info['pay_no'];
            $payRecord->outer_no = $info['outer_no'];
            $payRecord->refund_no = $out_request_no;
            if ($refundToYue == true) {
                $payRecord->pay_name = "余额";
                $payRecord->way_name = "余额";
                $payRecord->way_type = "YE";
                $payRecord->pay_way = 3;
            } else {
                $payRecord->pay_name = $info['pay_name'];
                $payRecord->way_name = $info['way_name'];
                $payRecord->way_type = $info['way_type'];
                $payRecord->pay_way = $info['pay_way'];
            }
            $payRecord->pay_money = $refundMoney;
            $payRecord->pay_pre_money = $refundMoney;
            $payRecord->pay_type_name = '退款';
            $payRecord->pay_type = 6; //$info["pay_type"];
            $payRecord->body = $body . $info['order_code']; //"退款-购彩-" . $info['order_code'];
            $payRecord->balance = $funds["all_funds"];
            $payRecord->pay_time = date('Y-m-d H:i:s');
            $payRecord->modify_time = date("Y-m-d H:i:s");
            $payRecord->create_time = date('Y-m-d H:i:s');
            $payRecord->status = 3;
            if ($payRecord->validate()) {
                $ret2 = $payRecord->saveData();
                if ($ret2 === false) {
                    $tran->rollBack();
                    return ["code" => 502, "msg" => "数据出错"];
                }
            } else {
                $tran->rollBack();
                return ["code" => 502, "msg" => "数据出错", "result" => $payRecord->getFirstErrors()];
            }
            //退优惠
            if ($info['discount_detail']) {
                $discount_detail = json_decode($info['discount_detail'], true);
                PayService::returnDiscount($info['cust_no'], $info['order_code'], $discount_detail);
            }
            $tran->commit();
            return ["code" => 0, "msg" => "退款成功"];
        } catch (yii\base\Exception $e) {
            $tran->rollBack();
            return ["code" => 502, "msg" => "数据出错", "result" => $e];
        }
    }

    /**
     * 生成交易记录
     * @param type $custNo
     * @param type $orderCode
     * @param type $payType
     * @param type $custType
     * @param type $payPreMoney
     * @param type $bodyType
     * @param array $discountData 折扣优惠信息
     */
    public function productPayRecord($custNo, $orderCode, $payType, $custType, $payPreMoney, $bodyType, $userId = '', $discountData = []) {
//        $appName = "咕啦体育";
        //$post = Yii::$app->request->post();
        //$orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        //计算折扣
        $payTypeAll = [
            "1" => "购彩",
            "2" => "转账",
            "3" => "充值",
            "4" => "提现",
            "5" => "合买",
            "6" => "退款",
            "7" => "定投计划-认购",
            "8" => "定投计划-收款",
            "9" => "门店出票",
            "10" => "服务费",
            "11" => "奖金发放",
            "12" => "定投计划-结算收款",
            "13" => "定投计划-结算付款",
            "14" => "提成-合买",
            "15" => "奖金",
            "16" => "出票服务费",
            "17" => "方案-购买",
            "18" => "方案-收款",
            '23' => '咕币充值'
        ];
        $bodyArr = [
            "1" => "购彩消费-自购",
            "2" => "购彩消费-合买",
            "3" => "购彩消费-定制合买",
            "4" => "购彩消费-定投计划",
            "5" => "购彩消费-追号",
            "6" => "退款-购彩-" . $orderCode,
            "7" => "奖金发放-" . $orderCode,
            "8" => "定投计划-结算",
            "9" => "服务费",
            "10" => "收费方案-购买",
            "11" => "退款-方案-" . $orderCode,
            "12" => "收费方案-收款",
            '13' => '咕币充值'
        ];
        $body = $bodyArr[$bodyType];
        $insert = [
            "body" => $body,
            "order_code" => $orderCode,
            "pay_no" => Commonfun::getCode("PAY", "L"),
            "pay_type" => $payType,
            "pay_type_name" => $payTypeAll[$payType],
            "pay_pre_money" => $payPreMoney,
            "cust_no" => $custNo,
            "user_id" => $userId,
            "cust_type" => $custType,
            "modify_time" => date("Y-m-d H:i:s"),
            "create_time" => date("Y-m-d H:i:s"),
            "status" => 0,
        ];
        if ($discountData) {
            $insert['discount_money'] = $discountData['discount'];
            $insert['discount_detail'] = json_encode($discountData['parms']); //[['type'=>1,'money'=>1,'coin'=>100],['type'=>2,'money'=>2,'coupons_id'=>1]]
            $insert['total_money'] = $discountData['discount'] + $payPreMoney;
        }
        return PayRecord::addData($insert);
        /* \Yii::$app->db->createCommand()->insert("pay_record", [
          "body" => $body,
          "order_code" => $orderCode,
          "pay_no" => Commonfun::getCode("PAY", "L"),
          "pay_type" => $payType,
          "pay_type_name" => $payTypeAll[$payType],
          "pay_pre_money" => $payPreMoney,
          "cust_no" => $custNo,
          "user_id" => $userId,
          "cust_type" => $custType,
          "modify_time" => date("Y-m-d H:i:s"),
          "create_time" => date("Y-m-d H:i:s"),
          "status" => 0,
          ])->execute(); */
    }

    /**
     * 支付回调处理
     * @param type $orderCode 己方订单号
     * @param type $outer_no  第三方交易订单号
     * @param type $total_amount 交易金额
     * @param type $payTime 交易时间
     * @return type
     */
    public function notify($orderCode, $outer_no, $total_amount = 0, $payTime = null) {
        if ($payTime == null) {
            $payTime = date("Y-m-d H:i:s");
        }
        $info = (new \yii\db\Query())->select("*")->from("pay_record")->where(["order_code" => $orderCode])->one();
        if ($info == null) {
            return [
                "code" => 2,
                "msg" => "没有该交易记录"
            ];
        }
        $url = "";
        if ($info["way_type"] == "PAGE") {
            $url = "location:/paysuccess?orderCode={$orderCode}";
        }
        if ($info["way_type"] == "WAP") {
            $url = "location:/pay/success/{$orderCode}?total_amount=" . $total_amount;
        }
        if ($info["way_type"] == "APP") {
            $url = "location:/pay/success/{$orderCode}?total_amount=" . $total_amount;
        }
        if ($info["pay_type"] == "1") {
            $ret = \app\modules\common\services\OrderService::orderNotify($orderCode, $outer_no, $total_amount, $payTime, $info);
        }
        if ($info["pay_type"] == "3") {
            $ret = \app\modules\common\services\OrderService::rechargeNotify($orderCode, $outer_no, $total_amount, $payTime, $info);
            $url .= "&recharge=1";
        }
        if ($info["pay_type"] == "5") {
            $proSer = new \app\modules\common\services\ProgrammeService();
            $ret = $proSer->programmeNotify($orderCode, $outer_no, $total_amount, $payTime);
        }
        if ($info["pay_type"] == "7") {
            $ret = \app\modules\common\services\PlanService::planNotify($orderCode, $outer_no, $total_amount, $payTime);
        }
        if ($info['pay_type'] == "17") {
            $expertService = new ExpertService();
            $ret = $expertService->articleNotify($orderCode, $outer_no, $total_amount, $payTime);
        }
        if ($info['pay_type'] == "100") {
            $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $info['cust_no']])->one();
            PayRecord::upData([
                "status" => 1,
                "outer_no" => $outer_no,
                "modify_time" => date("Y-m-d H:i:s"),
                "pay_time" => date("Y-m-d H:i:s"),
                "pay_money" => $total_amount,
                "balance" => $funds["all_funds"]
                    ], [
                "order_code" => $orderCode,
            ]);
            \yii::sendCurlPost(\Yii::$app->params['zhonchou_callback'], ['order_code' => $info['order_code'], 'pay_code' => 'QB']);
        }
        if ($info['pay_type'] == '23') {
            $ret = UserCoinHelper::coinRechargeNotify($orderCode, $outer_no);
        }
        if ($ret !== true && is_array($ret) && isset($ret['msg'])) {
            $url .= "&code=error&msg={$ret['msg']}";
        }
        return [
            "code" => 0,
            "msg" => "回调操作成功",
            "url" => $url
        ];
//        if ($ret === true) {
//            return [
//                "code" => 0,
//                "msg" => "回调操作成功",
//                "url" => $url
//            ];
//        } else {
//            return [
//                "code" => 2,
//                "msg" => "回调操作失败"
//            ];
//        }
    }

    /**
     * 支付宝同步回调处理
     * @param type $orderCode
     * @param type $total_amount
     * @return string
     */
    public function aliReturnUrl($orderCode, $total_amount) {
        $url = "location:/pay/fail/{$orderCode}?total_amount=" . $total_amount;
        $info = (new \yii\db\Query())->select("*")->from("pay_record")->where(["order_code" => $orderCode])->one();
        if ($info == null) {
            return [
                "code" => 2,
                "msg" => "没有该交易记录"];
        }
        if ($info["way_type"] == "PAGE") {
            $url = "location:/payFail?orderCode={$orderCode}";
        }
        if ($info["way_type"] == "WAP") {
            $url = "location:/pay/fail/{$orderCode}?total_amount=" . $total_amount;
        }

        if ($info["pay_type"] == "3") {
            $url .= "&recharge=1";
        }
        return $url;
    }

    /**
     * 记录用户选择支付方式
     * 
     */
    public static function savePayWay($orderCoder, $payWay) {
        switch ($payWay) { // 更新支付方式
            case 3: // 余额
                $update = ['modify_time' => date("Y-m-d H:i:s"), 'pay_way' => 3, 'pay_name' => "余额", 'way_type' => 'YE', 'way_name' => "余额"];
                break;
            case 4://钱包
                $update = ['modify_time' => date("Y-m-d H:i:s"), 'pay_way' => 4, 'pay_name' => "咕啦钱包", 'way_type' => 'GLC', 'way_name' => "钱包二维码"];
                break;
            default:
                $update = ['modify_time' => date("Y-m-d H:i:s"), 'pay_way' => 0, 'pay_name' => "", 'way_type' => '', 'way_name' => ""];
        }
        $res = PayRecord::upData($update, ["order_code" => $orderCoder]);
        if ($res) {
            return ['code' => 600, 'msg' => '更新状态成功'];
        }
        return ['code' => 109, 'msg' => '支付异常111'];
    }

    /**
     * 退优惠
     * @param array $parms [['type'=>1,'coin'=>100,'money'=>10],['type'=>2,'coupons'=>['asdas','asdas'],'money'=>1]]
     */
    public static function returnDiscount($userNo, $orderCode, $parms) {
        if ($parms) {
            $m = new CouponsDetail();
            foreach ($parms as $v) {
                switch ($v['type']) {
                    case 1://古币
                        $res = $m->refundPrep($orderCode, $userNo, $v['coin'], '', '取消订单');
                        if ($res['code'] != 600) {
                            return $res;
                        }
                        break;
                    case 2://优惠券
                        $res = $m->refundPrep($orderCode, $userNo, '', $v['coupons'], '取消订单');
                        if ($res['code'] != 600) {
                            return $res;
                        }
                        break;
                }
            }
        }
        return ['code' => 600, '操作成功'];
    }

    /**
     * 根据订单编号获取优惠详情
     * $where 条件
     */
    public static function getDiscount(array $where) {
        $data = PayRecord::findOne($where);
        $discount = ['discount' => 0, 'data' => ['coin' => ['type' => 1, 'money' => 0, 'coin' => 0], 'coupons' => ['type' => 2, 'money' => 0, 'coupons' => []]]];
        if ($data->discount_detail) {
            $detail = json_decode($data->discount_detail, true);
            foreach ($detail as $v) {
                $discount['discount'] += $v['money'];
            }
            $discount['data'] = $detail;
        }
        foreach ($discount['data'] as $k => $v) {
            switch ($v['type']) {
                case 1:
                    $discount['data']['coin']['name'] = '咕币';
                    break;
                case 2:
                    $discount['data']['coupons']['name'] = '优惠券';
                    break;
            }
        }
        return $discount;
    }

}
