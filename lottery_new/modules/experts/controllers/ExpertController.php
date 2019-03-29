<?php

namespace app\modules\experts\controllers;

use yii\web\Controller;
use Yii;
use app\modules\user\services\IUserService;
use app\modules\user\models\User;
use app\modules\experts\models\Expert;
use app\modules\common\helpers\Constants;
use app\modules\tools\helpers\SmsTool;
use app\modules\experts\services\ExpertService;
use app\modules\common\services\ScheduleService;
use app\modules\experts\models\ExpertArticles;
use app\modules\competing\helpers\CompetConst;

class ExpertController extends Controller {

    private $userService;

    public function __construct($id, $module, $config = [], IUserService $userService) {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    /**
     * 说明: 专家登录
     * @author  GL zyl
     * @param
     * @return 
     */
    public function actionExpertLogin() {
        $request = \Yii::$app->request;
        $expertTel = $request->post('account', '');
        $password = $request->post('password', '');
        if (empty($expertTel) || empty($password)) {
            return $this->jsonError(100, '参数缺失');
        }
        $JavaUser = $this->userService->getJavaUser($expertTel, $password);
        if (empty($JavaUser)) {
            return $this->jsonError(401, '登录失败,请稍后重试');
        }
        if ($JavaUser['httpCode'] != 1) {
            return $this->jsonError($JavaUser['httpCode'], $JavaUser['msg']);
        } else {//java接口认证成功--生成或者更新系统用户数据
            $userDetail = $this->userService->getJavaUserDetail($JavaUser['custNo']);
            if ($userDetail['httpCode'] == 200) {
                $result = $this->userService->setRegisterData($userDetail);
                $result['register_from'] = 2; //注册来源咕啦钱包
                $user = $this->userService->createOrUpdateUser($expertTel, $JavaUser['custNo'], $result);
            } else {
                return $this->jsonError(401, '登录失败,请稍后重试');
            }
        }
        $expertInfo = Expert::find()->select(['expert_status','identity'])->where(['user_id' => $user['data']['user_id'], 'expert_status' => 2])->asArray()->one();
        if (empty($expertInfo)) {
            return $this->jsonError(401, '登录失败,请先通过专家认证');
        }
        $token = $this->userService->autoLogin($user['data']['cust_no'], $user['data']['user_id'], 'expert'); //自动登录
        return $this->jsonResult(600, '登录成功', ['identity'=>$expertInfo['identity'],'token' => $token, 'user_type' => $user['data']['user_type'], 'cust_no' => $user['data']['cust_no']]);
    }

    /**
     * web端 专家首页
     * @auther GL zyl
     * @return type
     */
    public function actionGetExpertInfo() {
        $expertId = $this->userId;
        $custNo = $this->custNo;
        $check = Constants::CHECK_STATUS;
        $identity = Yii::$app->request->post('identity','1');
        $expertService = new ExpertService();
        $info = $expertService->getInfo($expertId,$identity);
        $javaAuthInfo = $this->userService->javaGetAuthInfo($custNo);
        if ($javaAuthInfo == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }

        if ($javaAuthInfo['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        $info['real_name'] = $javaAuthInfo['data']['realName'];
        $info['card_no'] = $javaAuthInfo['data']['cardNo'];
        $info['bank_info'] = $javaAuthInfo['data']['depositBank'] . $javaAuthInfo['data']['bankNo'];
        $info['check_status'] = $javaAuthInfo['data']['checkStatus'];
        $info['check_status_name'] = $check[$javaAuthInfo['data']['checkStatus']];
        $data['data'] = $info;
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 查询专家战绩
     */
    public function actionGetExpertExploits() {
        $userId = $this->userId;
        $type = Yii::$app->request->post('type','1');
        $expertService = new ExpertService();
        $exploits = $expertService->get_expert_res($userId,$type);
        if (empty($exploits)) {
            return $this->jsonError(109, '暂无战绩！');
        }
        return $this->jsonResult(600, '查询战绩成功', $exploits);
    }

    /**
     * 个人信息修改
     * @auther GL zyl
     * @return type
     */
    public function actionSetInfo() {
        $userId = $this->userId;
        $request = Yii::$app->request;
        $nickName = $request->post('nick_name', '');
        $introduction = $request->post('introduction', '');
        if (empty($nickName) || empty($introduction)) {
            return $this->jsonError(100, '参数缺失');
        }
        $expertService = new ExpertService();
        $data = $expertService->setInfo($userId, $nickName, $introduction);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '修改成功', true);
    }

    /**
     * 说明:用户头像上传至七牛
     * @author  GL zyl
     * @param
     * @return
     */
    public function actionUploadUserPic() {
        $request = Yii::$app->request;
        $pic = $request->post('user_pic', '');
        if (empty($pic)) {
            return $this->jsonError(100, '参数缺失');
        }
        $custNo = $this->custNo;
        $expertService = new ExpertService();
        $path = $expertService->UploadUserPic($pic, $custNo);
        if ($path['code'] != 600) {
            return $this->jsonError($path['code'], $path['msg']);
        }
        $data['data'] = $path['data'];
        return $this->jsonResult(600, '上传成功', $data);
    }

    /**
     * 说明: 发送短信验证码忘记密码
     * @author GL zyl
     * @param account //手机号
     * @param cType // 4:忘记密码
     * @return 
     */
    public function actionGetSmsCode() {
        $request = \Yii::$app->request;
        $userId = $this->userId;
        $userTel = $request->post('user_tel');
        $cType = 4;
        if (empty($userTel)) {
            return $this->jsonError(100, '参数缺失');
        }
        $saveKey = Constants::SMS_KEY_UPPWD;
        $smstool = new SmsTool();
        $smstool->sendSmsCode($cType, $saveKey, $userTel);
        return $this->jsonResult(600, '发送成功', true);
    }

    /**
     * 说明: 忘记密码
     * @author  GL zyl
     * @param account   手机号
     * @param smsCode   短信验证码
     * @param password  新密码
     * @return 
     */
    public function actionUpdatePwd() {
        $request = \Yii::$app->request;
        $userTel = $request->post('user_tel');
        $smsCode = $request->post('smsCode');
        $password = $request->post('password');
        if (empty($smsCode) || empty($password) || empty($userTel)) {
            return $this->jsonError(100, '参数缺失');
        }
        $saveKey = Constants::SMS_KEY_UPPWD;
        SmsTool::check_code($saveKey, $userTel, $smsCode);
        $result = $this->userService->javaUpdatePwd($userTel, $password); //java认证接口
        if ($result['httpCode'] == 200) {
            return $this->jsonResult(600, '修改成功!', true);
        } else if ($result['httpCode'] == 414) {
            return $this->jsonError(401, '修改失败，该手机号未注册!');
        } else {
            return $this->jsonError(401, '修改失败，请稍候再试!');
        }
    }

    /**
     * 发布文章
     * @auther GL zyl
     * @return type
     */
    public function actionPublish() {
        $expertId = $this->userId;
        $request = Yii::$app->request;
        $articleId = $request->post('article_id', '');
        $articleType = $request->post('article_type', '');
        $preType = $request->post('pre_type', '');
        $payType = $request->post('pay_type', '');
        $payMoney = $request->post('pay_money', 0);
        $periods = $request->post('periods', '');
        $lotteryCode = $request->post('lottery_code', '');
        $preResult = $request->post('pre_result', '');
        $preOdds = $request->post('pre_odds', '');
        $title = $request->post('title', '');
        $content = $request->post('content', '');
        $featured = $request->post('featured', '');
        $startTime = $request->post('start_time', '');
        $buyBack = $request->post('buy_back', 1);
        $actionType = $request->post('action_type', '');
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        if (empty($articleType) || empty($actionType)) {
            return $this->jsonError(100, '参数缺失');
        }
        if ($actionType == 2) {
            if (empty($articleId)) {
                return $this->jsonError(100, '参数缺失');
            }
        }
        if ($articleType == 2) {
            if (empty($preType) || empty($periods) || empty($lotteryCode) || $preResult == '' || empty($title) || empty($content) || empty($payType) || empty($startTime) || empty($preOdds)) {
                return $this->jsonError(100, '参数缺失');
            }
            $format = strtotime(date('Y-m-d H:i:s')) * 1000;
            if ($startTime < $format) {
                return $this->jsonError(109, '预测内容超时,请重新添加');
            }
        }
        if ($payType == 2) {
            if (empty($payMoney)) {
                return $this->jsonError(100, '付费金额未定义');
            }
        }
        if (!empty($periods)) {
            if (empty($lotteryCode) || $preResult == '') {
                return $this->jsonError(109, '玩法和预测结果不可为空');
            }
            if (in_array($lotteryCode, $football) || in_array($lotteryCode, $basketball)) {
                if (empty($preOdds)) {
                    return $this->jsonError(109, '赔率不可为空');
                }
            }
        }
        $expertService = new ExpertService();
        $data = $expertService->publish($expertId, $articleType, $preType, $payType, $payMoney, $periods, $lotteryCode, $preResult, $preOdds, $title, $content, $featured, $buyBack, $startTime, $articleId);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '提交成功', true);
    }

    /**
     * 获取场次列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetScheduleList() {
        $request = Yii::$app->request;
        $startDate = $request->post('start_date', '');
        $endDate = $request->post('end_date', '');
        $league = $request->post('league_id', '');
        $likeParam = $request->post('like_param', '');
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $scheduleType = $request->post('schedule_type', 1);
        $expertSercive = new ExpertService();
        if($scheduleType == 1) {
            $data = $expertSercive->getSchedule($page, $size, $startDate, $endDate, $league, $likeParam);
        }  else {
            $data = $expertSercive->getLanSchedule($page, $size, $startDate, $endDate, $league, $likeParam);
        }
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取场次详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetScheduleDetail() {
        $request = Yii::$app->request;
        $mid = $request->post('mid', '');
        $scheduleType = $request->post('schedule_type', 1);
        if (empty($mid)) {
            return $this->jsonError(100, '参数缺失');
        }
        $expertSercive = new ExpertService();
        if($scheduleType == 1) {
            $detail = $expertSercive->getScheduleDetail($mid);
        }  else {
            $detail = $expertSercive->getLanScheduleDetail($mid);
        }
        $data['data'] = $detail;
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取联赛列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetLeagueList() {
        $request = Yii::$app->request;
        $scheduleType = $request->post('schedule_type', 1);
        $scheduleService = new ScheduleService;
        if($scheduleType == 1) {
            $leagueData = $scheduleService->getLeague();
        }  else {
            $leagueData = $scheduleService->getLanLeague();
        }
        
        $data['data'] = $leagueData;
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取文章列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetArticleList() {
        $expertId = $this->userId;
        $request = Yii::$app->request;
        $start = $request->post('start_date', '');
        $end = $request->post('end_date', '');
        $artStatus = $request->post('article_status', '');
        $payType = $request->post('pay_type', '');
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $title = $request->post('title', '');
        $preType = $request->post('pre_type', 1);
        $expertService = new ExpertService();
        $data = $expertService->getArticlesList($page, $size, $start, $end, $artStatus, $payType, $title, $expertId, $preType);
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取文章详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetArticleDetail() {
        $expertId = $this->userId;
        $request = Yii::$app->request;
        $articleId = $request->post('article_id', '');
        $preType = $request->post('pre_type', 1);
        if (empty($articleId)) {
            return $this->jsonError(100, '参数缺失');
        }
        $expertService = new ExpertService();
        $detail = $expertService->getArticleDetail($articleId, $expertId, $expertId, $preType);
        if ($detail['code'] != 600) {
            return $this->jsonError(109, $detail['msg']);
        }
        $data['data'] = $detail['data'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取单价
     * @return type
     */
    public function actionGetArticlePrice() {
        $data['data'] = Constants::ARTICLES_PRICE;
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 删除文章
     * @return type
     */
    public function actionDeleteArticle() {
        $expertId = $this->userId;
        $request = Yii::$app->request;
        $articleId = $request->post('article_id', '');
        if (empty($articleId)) {
            return $this->jsonError(100, '参数缺失');
        }
        $expertService = new ExpertService();
        $del = $expertService->deleteArticle($articleId, $expertId);
        if ($del['code'] != 600) {
            return $this->jsonError(109, $del['msg']);
        }
        return $this->jsonResult(600, '删除成功', true);
    }
     /**
     * 获取文章被举报记录列表
     * @return type
     */
    public function actionGetArticleReport(){
        $request = Yii::$app->request;
        $article_id = $request->post('article_id', '');
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        if(empty($article_id)){
            return $this->jsonError(109, '参数缺失');
        }
        $expertService = new ExpertService();
        $data = $expertService->readReportRecord($article_id, $page, $size);
        return $this->jsonResult(600, '获取成功', $data);
    }

}
