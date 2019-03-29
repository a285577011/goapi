<?php

namespace app\modules\publicinterface\controllers;

use app\modules\experts\models\ExpertArticles;
use app\modules\experts\models\ArticlesPeriods;
use yii\web\Controller;
use Yii;
use app\modules\experts\services\ExpertService;
use app\modules\experts\models\ArticlesCollect;

class ExpertController extends Controller {
    
    /**
     * 获取文章列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetArticleList() {
        $request = Yii::$app->request;
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $expertId = $request->post('expert_id', '');
        $payType = $request->post('pay_type', '');
        $preType = $request->post('pre_type', 1);
        $expertService = new ExpertService();

        if($preType == 1 && $size ==5 && $page<3 && !$expertId){//足球且页数小于3页的做redis缓存
            $data = \YII::redisGet('cache:get_article_list:'.$page,2);//redis缓存，如果存在直接返回
            if(!$data){
                $data = $expertService->getAppArticlesList($page, $size, $expertId, $payType, $preType);
                \YII::redisSet('cache:get_article_list:'.$page,$data,600);//redis缓存，如果存在直接返回
            }
        }else{
            $data = $expertService->getAppArticlesList($page, $size, $expertId, $payType, $preType);
        }

        return $this->jsonResult(600, '获取成功', $data);
    }
    /**
     * 获取推荐总概
     * @auther GL xiejh
     * @return type
     */
    public function actionGetArticleRecommend() {
        $request = Yii::$app->request;
        $type= $request->post('type', 0);
        $expertId = $request->post('expert_id', '');
        $scheduleType = $request->post('schedule_type', 1);
        $expertService = new ExpertService();
        $data = $expertService->getRecommend($type,$expertId,$scheduleType);
        return $this->jsonResult(600, '获取成功', $data);
        
    }
    
    
    /**
     * 获取擅长赛事列表
     * @auther GL xiejh
     * @return type
     */
    public function actionGetGoodLeague() {
        $request = Yii::$app->request;
        $expertId = $request->post('expert_id', '');
        $scheduleType = $request->post('schedule_type', 1);
        $expertService = new ExpertService();
        $data = $expertService->getGoodLeague($expertId,$scheduleType);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取专家列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetExpertList() {
        $request = Yii::$app->request;
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
//        $size = 10000;
        $expertType = $request->post('expert_type', 1);
        $listType = $request->post('list_type', 1);
        $userId = $this->userId;
        if(empty($expertType) || empty($listType)) {
            return $this->jsonError(100, '参数缺失');
        }

        if($expertType == 1 && $size ==7 && !$userId){//足球专家且页数小于3页的做redis缓存
            $data = \YII::redisGet('cache:get_expert_list',2);//redis缓存，如果存在直接返回
            if(!$data){
                $expertService = new ExpertService();
                $data = $expertService->getExpertList($page, $size, $expertType, $listType, $userId);
                \YII::redisSet('cache:get_expert_list',$data,1800);//redis缓存，如果存在直接返回
            }
        }else{
            $expertService = new ExpertService();
            $data = $expertService->getExpertList($page, $size, $expertType, $listType, $userId);
        }
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取专家详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetExpertDetail() {
        $request = Yii::$app->request;
        $expertId = $request->post('expert_id', '');
        $expertType = $request->post('expert_type', 1);
        if(empty($expertId)) {
            return $this->jsonError(100, '参数缺失');
        }
        $userId = $this->userId;
        $expertService = new ExpertService();
        $detail = $expertService->getExpertDetail($expertId, $userId, $expertType);
        $data['data'] = $detail; 
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取文章详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetArticleDetail() {
        $request = Yii::$app->request;
        $articleId = $request->post('article_id', '');
        $sourceType = $request->post('source_type', '');
        $preType = $request->post('pre_type', 1);
        if(empty($articleId)) {
            return $this->jsonError(100, '参数缺失');
        }
        if(empty($sourceType)) {
            if(empty($this->userId)) {
                return $this->jsonError(402, '请先登录');
            }
        }
        $userId = $this->userId;
        $expertService = new ExpertService();
        $detail = $expertService->getArticleDetail($articleId, '', $userId, $preType);
//        print_r($detail['data']['create_time']);die;
        if($detail['code'] != 600) {
            return $this->jsonError(109, $detail['msg']);
        }
        //判断 如果该文章时间已过1个月 则直接返回内容，不做计数统计
        if(strtotime("-1 month")<strtotime($detail['data']['create_time'])){//已经超过一个月时间
            if(!empty($userId)) {
                $key = 'articleread:' . $articleId;
                $redis = \yii::$app->redis;
                if($redis->sadd($key, $userId)) {
                    $expertService->addReadNums($articleId);
                }
                $redis->executeCommand('expire', [$key, 604800]);
            }
        }
        $data['data'] = $detail['data'];
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取场次相关文章列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetScheduleArticle() {
        $request = Yii::$app->request;
        $mid = $request->post('mid', '');
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $payType = $request->post('pay_type', '');
        $scheduleType = $request->post('schedule_type', 1);
        if(empty($mid)) {
            return $this->jsonError(100, '参数缺失');
        }
        $expertService = new ExpertService();
        $data = $expertService->getScheduleArticles($mid, $page, $size, $payType, $scheduleType);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 会员关注/取消关注庄家
     * @auther GL zyl
     * @return type
     */
    public function actionAttentExpert() {
        $userId = $this->userId;
        $request = Yii::$app->request;
        $expertId = $request->post('expert_id', '');
        $attentType = $request->post('attent_type', '');
        if(empty($expertId) || empty($attentType)) {
            return $this->jsonError(100, '参数缺失');
        }
        $expertService = new ExpertService();
        if($attentType == 1) {
            $data = $expertService->attentExpert($userId, $expertId);
        }  else {
            $data = $expertService->cancelAttentExpert($userId, $expertId);
        }
        if($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, $data['msg'], true);
    }
    
    /**
     * 获取关注列表
     * @auther GL zyl
     * @return type
     */
    public function  actionGetAttentList() {
        $userId = $this->userId;
        $request = Yii::$app->request;
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $expertService = new ExpertService();
        $data = $expertService->getAttentList($userId, $page, $size);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取购买文章列表
     * @return type
     */
    public function actionBuyArticleList() {
        $userId = $this->userId;
        $request = Yii::$app->request;
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $preType = $request->post('pre_type', 1);
        $expertService = new ExpertService();
        $data = $expertService->getBuyArticlesList($page, $size, $userId, $preType);
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 收藏文章：增删改
     * @outher gwp
     * @param  int  $userId             收藏者id
     * @param  int  $type               1=新增收藏，2=删除收藏
     * @param  int  $expertArticlesId   文章id
     */
    public function actionArticlesCollect(){
        $userId = $this->userId;
        $request = Yii::$app->request;
        $expertArticlesId = $request->post('expertArticlesId');
        $type = $request->post('type');

        if(empty($expertArticlesId) || empty($type)) return $this->jsonError(100, '参数缺失');

        $articlesCollect = new ArticlesCollect();
        switch ($type) {
            case 1:
                //执行收藏
                $res = $articlesCollect -> addArticlesCollect($expertArticlesId, $userId);
                break;
            case 2:
                //执行删除
                $res = $articlesCollect -> delArticlesCollect($expertArticlesId, $userId);
                break;
            default:
                return $this->jsonError(109, '非法操作');exit;
        }
        if($res['code'] != 600) {
            return $this->jsonError(109, $res['msg']);
        }
        return $this->jsonResult(600, $res['msg'], true);
    }

    /**
     * 获取收藏文章列表
     * @outher gwp
     * @param  int  page_num             当前页数
     * @param  int  size                 每页总条数
     */
    public function actionArticlesCollectLists()
    {
        $userId = $this->userId;
        $request = Yii::$app->request;
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $preType = $request->post('pre_type', 1);
        $articlesCollect = new ArticlesCollect();
        $data = $articlesCollect->articlesCollectLists($userId, $page, $size, $preType);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取专家粉丝列表
     * @return type
     */
    public function actionGetFansList(){
        $userId = $this->userId;
        $request = Yii::$app->request;
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $expertService = new ExpertService();
        $data = $expertService->getFansList($userId, $page, $size);
        return $this->jsonResult(600, '获取成功', $data);
    }
    /**
     * 用户举报专家文章
     */
    public function actionReportArticle(){
       $custNo = $this->custNo;
       $request = Yii::$app->request;
       $expertId = $request->post('expert_id', '');
       $articleId = $request->post('article_id', '');
       $reportReasons = $request->post('report_reasons', '');
       if(empty($custNo)||empty($expertId)||empty($articleId)||empty($reportReasons)){
           return $this->jsonError(109, '参数缺失');
       }
        $expertService = new ExpertService();
        $res = $expertService->reportArticle($custNo,$expertId,$articleId, $reportReasons);
        if ($res['code'] != 600) {
            return $this->jsonError(109, $res['msg']);
        }
        return $this->jsonResult(600, '举报成功', true);
    }
    /**
     * 获取文章详情(星星体育)
     * @return type
     */
    public function actionGetArticleDetailxx() {
    	$request = Yii::$app->request;
    	$articleId = $request->post('article_id', '');
    	$sourceType = $request->post('source_type', '');
    	$preType = $request->post('pre_type', 1);
    	if(empty($articleId)) {
    		return $this->jsonError(100, '参数缺失');
    	}
    	$expertService = new ExpertService();
    	$detail = $expertService->getArticleDetail($articleId, '', '', $preType,2);
    	//        print_r($detail['data']['create_time']);die;
    	if($detail['code'] != 600) {
    		return $this->jsonError(109, $detail['msg']);
    	}
    	$data['data'] = $detail['data'];
    	return $this->jsonResult(600, '获取成功', $data);
    }
    /**
     * 获取文章详情简单(星星体育)
     * @return type
     */
    public function actionGetArticleDetailsimxx() {
    	$request = Yii::$app->request;
    	$articleId = $request->post('article_id', '');
    	if(empty($articleId)) {
    		return $this->jsonError(100, '参数缺失');
    	}
    	$expertService = new ExpertService();
    	$detail = $expertService->getArticleDetailSim($articleId);
    	return $this->jsonResult(600, '获取成功', $detail);
    }
    
    public function actionGetScheduleArticleTotal() {
        $request = \Yii::$app->request;
        $midArr = $request->post('schedule_info', '');
        $preType = $request->post('pre_type', 1);
        $payType = $request->post('pay_type', '');
        $expertService = new ExpertService();
        $total = $expertService->getXxArtNums($midArr, $preType, $payType);
        return $this->jsonResult(600, '获取成功', $total);
    }
}

