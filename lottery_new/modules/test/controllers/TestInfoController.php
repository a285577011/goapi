<?php
namespace app\modules\test\controllers;

use app\modules\platform\models\Platform;
use yii\web\Controller;
use app\modules\agents\models\Agents;

/** 
* 说明：萨沙涉及到哈接收到
* @author hoy
* @author     hoy
* @date 2016年8月18日 下午4:48:15 
*/ 
class TestInfoController extends Controller
{
   
    private $testServices = null;
    private $platformServices;
    public $enableCsrfValidation = false;

    public function __construct($id,$module,$config=[])
    {
        parent::__construct($id,$module,$config);
    }
    public function actionIndex(){
    	header("Content-type: text/html; charset=utf-8");
    	$str = "{\"不显示类别名\":{\"平台背景\":\"国资背景,上市背景\",\"互金协会\":\"未加入\"},\"产品信息\":{\"ICP备案主体\":\"深圳市同心科创金融服务有限公司\",\"ICP备案号\":\"粤ICP备14095222号-1\",\"上线时间\":\"2015-4-18\",\"平台网址\":\"www.88bank.com\",\"项目类型\":\"企业贷类\",\"投资期限\":\"活期,1个月,2个月,3个月,4个月,5个月,6个月,9个月,12个月,12个月以上\",\"年化收益\":\"7-11%\",\"债权转让\":\"有\",\"服务费用\":\"无\",\"提现费用\":\"有,目前，在免费提现额度内，每天有3次免费提现机会，超过3次每笔收取2元；超出免费提现额度，提现手续费率为0.5 %，但至少收取3元；\",\"提现门槛\":\"有,100元起提现\",\"提现到账\":\"最快T+0\"},\"风控措施\":{\"第三方担保.\":\"官网披露本息担保\",\"银行托管\\/存管\":\"官网无介绍\",\"托管\\/存管银行\":\"官网未披露具体银行\",\"托管\\/存管核实情况\":\"--\"},\"工商信息\":{\"企业名称\":\"深圳市同心科创金融服务有限公司\",\"企业类型\":\"有限责任公司\",\"法定代表\":\"朱立行\",\"注册资本\":\"20000万元人民币\",\"注册号\":\"440301111121137\",\"统一社会信用代码\":\"91440300312027309X\",\"登记机关\":\"深圳市市场监督管理局前海注册工作组\",\"核准日期\":\"2016-03-11\",\"营业开始时间\":\"2014-08-20\",\"营业结束时间\":null,\"经营范围\":\"接受金融机构委托从事金融业务流程外包业务、（根据法律、行政法规、国务院规定等规定需要审批的，依法取得相关审批文件后方可经营）；依托互联网技术手段，提供金融中介服务（根据国家规定需要审批的，获得审批后方可经营）；经济信息咨询；投资兴办实业（具体项目另行申报）；投资管理、投资咨询、投资顾问（根据法律、行政法规、国务院决定等规定需要审批的，依法取得相关审批文件后方可经营）；国内贸易（不含专营、专控、专卖商品）。^\",\"注册地址\":\"深圳市前海深港合作区前湾一路1号A栋201室（入驻深圳市前海商务秘书有限公司）\",\"公司状态\":\"登记成立\",\"注册时间\":null,\"主要人员\":\"陈倩婷(监事) 朱立行(董事长) 李东明(董事) 丁秋实(董事) 朱立行(董事) 张丽君(董事) 吴菡(董事) 闫梓(总经理) \"},\"变更记录\":[[\"变更事项\",\"变更前内容\",\"变更后内容\",\"变更日期\"],[\"高管人员\",\"吴菡(董事) 陈倩婷(监事) 李东明(董事长) 丁秋实(董事) 朱立行(总经理) 朱立行(董事) 张丽君(董事)\",\"闫梓(总经理) 朱立行(董事长) 丁秋实(董事) 李东明(董事) 陈倩婷(监事) 吴菡(董事) 张丽君(董事) 朱立行(董事)\",\"2016-03-11\"],[\"董事成员\",\"吴菡(董事) 陈倩婷(监事) 李东明(董事长) 丁秋实(董事) 朱立行(总经理) 朱立行(董事) 张丽君(董事)\",\"闫梓(总经理) 朱立行(董事长) 丁秋实(董事) 李东明(董事) 陈倩婷(监事) 吴菡(董事) 张丽君(董事) 朱立行(董事)\",\"2016-03-11\"],[\"一照一码升级\",\"\",\"\",\"2016-02-05\"],[\"法定代表人（负责人）\",\"李东明\",\"朱立行\",\"2016-02-05\"],[\"监事成员\",\"李东明(执行（常务）董事) 朱立行(总经理) 丁秋实(监事)\",\"吴菡(董事) 陈倩婷(监事) 李东明(董事长) 丁秋实(董事) 朱立行(总经理) 朱立行(董事) 张丽君(董事)\",\"2015-11-11\"],[\"董事成员\",\"李东明(执行（常务）董事) 朱立行(总经理) 丁秋实(监事)\",\"吴菡(董事) 陈倩婷(监事) 李东明(董事长) 丁秋实(董事) 朱立行(总经理) 朱立行(董事) 张丽君(董事)\",\"2015-11-11\"],[\"实收资本\",\"人民币0.0000万元\",\"人民币16000.0000万元\",\"2015-02-16\"],[\"企业类型\",\"有限责任公司（法人独资）\",\"有限责任公司\",\"2015-02-06\"],[\"章程\",\"\",\"\",\"2015-02-06\"],[\"股东（投资人）\",\"深圳市同心投资基金股份公司 20000.0000(万元) 100.0000%\",\"深圳市同心投资基金股份公司 14000.0000(万元) 70.0000% \\r\\n广东省粤科投资发展有限公司 6000.0000(万元) 30.0000%\",\"2015-02-06\"]],\"股东背景\":{\"广东省粤科投资发展有限公司\":\"企业法人\",\"深圳市同心投资基金股份公司\":\"企业法人\"},\"联系方式\":{\"公司地址\":\"深圳市南山区深南大道9005号EPC艺术中心\",\"400电话\":\"400-788-4007\",\"固定电话\":\"0755-86667760\",\"客服邮箱\":\"kefu@88bank.com\"},\"不展示参数\":{\"第三方担保\":\"官网有披露担保\",\"银行存管\\/托管\":\"无\",\"融资情况\":\"无\"}}";
    	$arr = json_decode($str,true);
        echo "<pre>";
      	print_r($arr);
      	echo "</pre>";
    }
    
    public function actionInfoTest(){
    	echo 1111;
    }
    public function actionPwd(){
    	opcache_reset();
    	
    	die;
    }
    public function actionDere(){
    	\Yii::$app->aliyunSdk->sendMail("测试", "还是测试", "55319900@qq.com");
    }
    
    public function actionKevi1(){
        $redis = \yii::$app->redis;
        $phone = '13045972366';
        $ret = $redis->executeCommand('zadd',["sms_limit:{$phone}",'123456',time()]);
        $redis->executeCommand('expire',['sms_limit:'.$phone,30]);
        print_r($ret);
    }
    
    public function actionKevi2(){
        $request = \Yii::$app->request;
        $ip = $request->getUserIP();
        
        $a = strtotime(date('Y-m-d 23:59:59'));
        echo $a;
    }
    
    //年化收益 从参数表中 同步到 平台列表中
    public function actionPm_plt(){
        $pmModel = new PlatformParams();
        $pms = $pmModel->find()
                ->select(['platform.platform_id','value'])
                ->innerJoin('platform','platform.platform_id = platform_params.platform_id')
                ->where(['params_id'=>29])
                ->orderBy('platform.platform_id')
                ->asArray()
                ->all();
        $redis = \Yii::$app->redis;
        foreach($pms as $pm){
            $incomeArr = explode(',', $pm['value']);
             $platform = new Platform();
             $plt = $platform->findOne(['platform_id'=>$pm['platform_id']]);
             $plt->income_min = $incomeArr[0];
             $plt->income_max = $incomeArr[1];
             $plt->save();
             unset($incomeArr);
             unset($plt);
             $redis->executeCommand('zadd',['kevi_count',time(),$pm['platform_id']]);
        }
        echo '转移成功';
    }
    public function actionAgentsTest(){
        $request = \Yii::$app->request;
//         $appId = $request->post('appId');
//         $secretKey = $request->post('secret_key');
        $agentsId = $request->post('agents_id');
        $userTel = $request->post('user_tel');
        $custNo = $request->post('gl_cust_no');
        $agents = Agents::find()->select(['agents_appid','secret_key'])->where(['agents_id'=>$agentsId])->asArray()->one();
        if(empty($agents)){
            return $this->jsonError(100,'没有该代理商');
        }
        $post_data = [
            'appId'=>$agents['agents_appid'],
            'access_token'=>md5($agents['agents_appid'].$agents['secret_key']),
            'user_tel'=>$userTel,
            'gl_cust_no'=>$custNo,
        ];
        $ret = \Yii::sendCurlPost('http://php.javaframework.cn/api/agents/agents/platform-user-login', $post_data);
        if($ret['code']==600){
            return $this->jsonResult($ret['code'], $ret['msg'], $ret['result']);
        }
        return $this->jsonError($ret['code'], $ret['msg']);
    }
}