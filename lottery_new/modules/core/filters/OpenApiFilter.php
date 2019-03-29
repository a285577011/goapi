<?php 
namespace app\modules\core\filters;

use app\modules\common\services\KafkaService;
use app\modules\tools\helpers\Toolfun;
use app\modules\openapi\models\ApiIp;
use yii\base\ActionFilter;
use app\modules\user\models\User;
use app\modules\openapi\models\Bussiness;

class OpenApiFilter extends ActionFilter
{
    private $rateLimit = 60;
    private $rateTime = 60;

    public function beforeAction($action)
    {
        $userIp = Toolfun::getUserIp();
        $api =  \Yii::$app->request->getUrl();
        $param =  \Yii::$app->request->post();

        KafkaService::addLog('openapi_log',['ip'=>$userIp,'params'=>$param]);

        $appId = $param['message']['head']['venderId'];
        $bussiness = $this->getUserInfo($appId);//获取用户id
        if(empty($bussiness)){
            $this -> responseHead(40004, 'venderId错误');
        }else if($bussiness['status'] ==2){
            $this -> responseHead(40004, 'venderId未激活,请联系管理员');
        }else if(!$bussiness['user_id'] || !$bussiness['cust_no']){
            $this -> responseHead(40004, 'venderId未绑定咕啦体育账户,请联系管理员');
        }else{
            \Yii::$userId = $bussiness['user_id'];
            \Yii::$custNo = $bussiness['cust_no'];
            define('DES_KEY', $bussiness['des_key']);
        }
        $ret = $this->checkIpApiAllowed($userIp, $api); //权限验证
        if(!$ret){
            $this -> responseHead(40006, 'venderId权限不足');
        }
        return parent::beforeAction($action);
    }

    public function responseHead($code){
        $result = [
            'message'=> [
                'head' => [
                    'status' => $code,
                    'venderId' => '',
                    'messageId' => '',
                    'md' => '',
                ],
                'body' => 'NULL '
            ],
        ];
        echo json_encode($result); exit;
    }

    /**
     * 说明: 
     * @author  kevi
     * @date 2017年12月22日 下午2:03:10
     * @param
     * @return 
     */
    public function getUserInfo($appId){
        $bussiness = Bussiness::find()->select(['user_id','cust_no', 'status', 'des_key'])
            ->where(['bussiness_appid'=>$appId])->asArray()->one();
        return $bussiness;
    }
    
    /**
     * 说明: 验证接口权限
     * @author  kevi
     * @date 2017年12月21日 上午9:05:48
     * @param   $ip     用户ip
     * @param   $api    访问接口
     * @return  boolean
     */
    public function checkIpApiAllowed($ip,$api){
        $apiIpWhite = ApiIp::find()->select(['api_ip_id','limit_time','limit_num'])
            ->leftJoin('api_list','api_list.api_list_id = api_ip.api_list_id and api_list.status=1')
            ->leftJoin('bussiness_ip_white','bussiness_ip_white.bussiness_ip_white_id = api_ip.bussiness_ip_id and bussiness_ip_white.status=1')
            ->where(['bussiness_ip_white.ip'=>$ip,'api_url'=>$api])
            ->one();
        $ret = $apiIpWhite?true:false;
        return $ret;
    }
    
    //速率限制
    public function getRateLimit($request, $action)
    {
        return [$this->rateLimit, $this->rateTime]; // 每秒请求次数
    }
    //访问次数查询
    public function loadAllowance($request, $action)
    {
        return [$this->allowance, $this->allowance_updated_at];
    }
    //访问记录保存
    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        $this->allowance = $allowance;
        $this->allowance_updated_at = $timestamp;
        $this->save();
    }

//     public function afterAction($action, $result)
//     {
//         return parent::afterAction($action, $result);
//     }
//except only
    
}
?>