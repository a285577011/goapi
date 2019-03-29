<?php
namespace app\modules\test\controllers;

use app\modules\common\models\LotteryOrder;
use app\modules\common\services\KafkaService;
use app\modules\user\models\User;
use yii\web\Controller;
use app\modules\tools\helpers\Zmf;

class KeviController extends Controller
{
    public $enableCsrfValidation = false;
   
    public function __construct($id,$module,$config=[])
    {
        parent::__construct($id,$module,$config);
    }

    public function actionDes(){
//          $zmfModel = new Zmf();
//          $data=[];
//          $ret = $zmfModel->to1000($data);
          $request = \Yii::$app->request;
        $data = $request->post();
        $zmfObj = new Zmf();
        $ret = $zmfObj->to1101($data);
        print_r($post);die;
//           $ret = $w->decrypt($xmlret['body']);
//           var_dump($ret);die;
//         echo phpinfo();die;
        
//         $cipher_list = mcrypt_list_algorithms();
//         $mode_list = mcrypt_list_modes();
        
//         echo $this->encrypt('chenqiwei', 'nihao');
    }
    
    /**
     * 说明: zmf投注接口
     * @author  kevi
     * @date 2018年1月8日 上午11:45:06
     * @param 
     * @return 
     */
    public function actionZmf1000(){
        $request = \Yii::$app->request;
        $id = $request->post('id');
        $data = [
            'lotteryId'=>"BSK003",//玩法代码
            'issue'=>'',//期号（竞彩玩法忽略此字段）
            'records'=>[
                'record'=>[
                    'id'=>$id,//投注序列号(不可重复)订单编号
                    'lotterySaleId'=>'502',//销售代码(竞彩自由过关，过关方式以^分开)
                    'freelotterySaleId'=>0,//1:自由过关 0:非自由过关
                    'phone'=>'13960774169',//手机号（可不填）
                    'idCard'=>'350681199002095254',//身份证号（可不填）
                    'code'=>"20180105|5|301|16^20180105|5|302|16^",//注码。投注内容
                    'money'=>200,//金额
                    'timesCount'=>1,
                    'issueCount'=>1,//期数
                    'investCount'=>1,//注数
                    'investType'=>0,//投注方式
                ]
            ]
        ];
        $zmfObj = new Zmf();
        $ret = $zmfObj->to1000($data);
        $this->jsonResult(600,'succ', $ret);
        
    }
    
    public function actionZmf1001(){
        $request = \Yii::$app->request;
        $lotteryId = $request->post('lotteryId');
        $issue = $request->post('issue','');
        $data = [
            'lotteryId'=>$lotteryId,//玩法代码
            'issue'=>$issue,//期号（竞彩玩法忽略此字段）
        ];
        $zmfObj = new Zmf();
        $zmfObj->to1001();
//        $zmfObj = new Zmf();
//        $ret = $zmfObj->to1001($data);
        $this->jsonResult(600,'succ', $ret);
    
    }
    
    public function actionZmf1100(){
        $request = \Yii::$app->request;
        $id = $request->post('id');
        $data = [
            'lotteryId'=>"BSK003",//玩法代码
            'issue'=>'',//期号（竞彩玩法忽略此字段）
            'records'=>[
                'record'=>[
                    'id'=>$id,//投注序列号(不可重复)
                    'lotterySaleId'=>'502',//销售代码(竞彩自由过关，过关方式以^分开)
                    'freelotterySaleId'=>0,//1:自由过关 0:非自由过关
                    'phone'=>'13960774169',//手机号（可不填）
                    'idCard'=>'350681199002095254',//身份证号（可不填）
                    'code'=>"20180105|5|301|16^20180105|5|302|16^",//注码。投注内容
                    'money'=>200,'timesCount'=>1,//金额
                    'issueCount'=>1,//期数
                    'investCount'=>1,//注数
                    'investType'=>0,//投注方式
                ]
            ]
        ];
        $zmfObj = new Zmf();
        $ret = $zmfObj->to1000($data);
        $this->jsonResult(600,'succ', $ret);
    
    }
    
    public function actionZmf1101(){
        //1接收zmf的xml参数
        
        \Yii::redisSet('zmf_1:'.time(), '10' , 60);
        
        
        $request = \Yii::$app->request;
        $data = $request->post();
        $id = 1013;
        $ret = \Yii::redisSet('zmf'.time(), $data , 7200);
//         print_r($data);die;

        $data2 = [
            'records'=>[
                'record'=>[
                    'id'=>$id,
                    'result'=>0,
                ]
            ]
        ];
        $zmfObj = new Zmf();
        $ret = $zmfObj->toxml($data2,$id);

         return $ret;
//        $this->jsonResult(600,'succ', $ret);

    }


    public function actionJson(){
        $request = \Yii::$app->request;
        $params = $request->getRawBody();
        return $params;
    }

    public function actionTime(){
        $db = \Yii::$app->db;
        $fromTable = 'betting_detail';
        $toTable = $fromTable.'_backup';
        $sql = "call time_backup('{$fromTable}','{$toTable}');";
        $ret = $db->createCommand($sql)->execute();
        return $this->jsonResult(600,'succ',$ret);

    }

    /**
     * 说明:读写分离测试
     * @author chenqiwei
     * @date 2018/2/1 下午2:53
     * @param
     * @return
     */
    public function actionSha(){
        $str = 'kevichenqiwei';
        $a = sha1($str);
        echo $a;die;

    }

    public function actionLotteryOrderDetail(){
        $orderCode = 'GLCHHGG18022413T0000003';
//        Commonfun::updateQueue($this->args['queueId'], 2);

        $selectArr = [
            'user.user_remark',//订单编号
            'lottery_order_code',//订单编号
            'lottery_name',//玩法名称
            'play_name',//投注方式
            'periods',//期数
            'lottery_order.cust_no',//用户咕啦编号
            'store_no',//接单门店编号
            'bet_val',//投注内容
            'bet_money',//投注金额
            'bet_double',//投注倍数
            'count',//总注数
            'win_amount',//中奖金额
            'lottery_order.status',//订单状态（1未支付 2处理中 3待开奖、4中奖、5未中奖、6出票失败、9过点撤销、10拒绝出票
            'deal_status',//兑奖状态（兑奖处理 0:未处理 ；1：已兑奖 ；2：派奖失败； 3：派奖成功   4:退款失败   5：退款成功
            'lottery_order.create_time',//订单创建时间
            'award_time',//派奖时间
        ];


        $orderDetial = LotteryOrder::find()->select($selectArr)
            ->leftJoin('user','user.user_id = lottery_order.user_id')
            ->where(['lottery_order_code'=>$orderCode])->asArray()->one();

//        SyncService::syncFromQueue('LotteryJob');
        return $this->jsonResult(600,'succ',$orderDetial);
    }

}