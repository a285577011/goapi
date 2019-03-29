<?php

namespace app\modules\experts\services;

use app\modules\experts\models\Expert;
use app\modules\experts\models\ExpertArticles;
use Yii;
use app\modules\common\helpers\Constants;
use app\modules\common\models\Schedule;
use app\modules\tools\helpers\Uploadfile;
use app\modules\user\models\User;
use app\modules\experts\models\UserArticle;
use app\modules\experts\models\UserExpert;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\PayService;
use app\modules\experts\models\ArticlesCollect;
use yii\db\Query;
use app\modules\experts\models\ArticlesReportRecord;
use app\modules\competing\models\LanSchedule;
use app\modules\experts\models\ArticlesPeriods;
use app\modules\competing\helpers\CompetConst;
use yii\db\Expression;
use app\modules\common\services\OrderService;

class ExpertService {

    public function getInfo($expertId, $identity) {
        $expertStatus = Constants::EXPERT_STATUS;
        $pactStatus = Constants::PACT_STATUS;
        $lottery = Constants::EXPERT_LOTTERY;
        $indentity = Constants::EXPERT_IDENTITY;
        $info = User::find()->select(['user.user_name', 'user.user_pic', 'user.cust_no', 'user.user_tel', 'e.*'])
                ->leftJoin('expert as e', 'e.user_id = user.user_id')
                ->where(['user.user_id' => $expertId, 'e.identity' => $identity])
                ->asArray()
                ->one();
        $info['user_tel'] = $info['user_tel'];
        $info['pact_status_name'] = $pactStatus[$info['pact_status']];
        $info['expert_status_name'] = $expertStatus[$info['expert_status']];
        $info['lottery'] = $lottery[$info['identity']];
        $info['identity'] = $info['identity'];
//        $redis = Yii::$app->redis;
//        $key = 'expert_readnums:' . $expertId;
//        $readNums = $redis->get($key);
//        if (empty($readNums)) {
//            $readNums = 0;
//        }
//        $info['read_nums'] = $readNums;
        return $info;
    }

    /**
     * 个人资料修改
     * @param type $userId
     * @param type $nickName
     * @param type $introduction
     * @return type
     */
    public function setInfo($userId, $nickName, $introduction) {
        $nickName = str_replace(' ', '', $nickName);
        $exist = User::find()->select(['user_name'])->where(['user_name' => $nickName])->andWhere(['!=', 'user_id', $userId])->asArray()->one();
        if (!empty($exist)) {
            return ['code' => 109, 'msg' => '该昵称已被征用啦！'];
        }
        $userModel = User::findOne(['user_id' => $userId]);
        $userModel->user_name = $nickName;
        if (!$userModel->save()) {
            return ['code' => 109, 'msg' => '修改失败'];
        }
        $expertModel = Expert::findOne(['user_id' => $userId]);
        $expertModel->introduction = $introduction;
        $expertModel->modify_time = date('Y-m-d H:i:s');
        if (!$expertModel->save()) {
            return ['code' => 109, 'msg' => '修改保存失败'];
        }
        return ['code' => 600, 'msg' => '修改成功'];
    }

    /**
     * 上传头像
     * @param type $file
     * @param type $custNo
     * @return type
     */
    public function UploadUserPic($baseImg, $custNo) {
        $day = date('ymdHis', time());
        $key = 'img/user/user_pic/' . $custNo . '/' . $day . '-' . 'user_pic';
//        $pic = base64_decode($baseImg);
        $pathJson = Uploadfile::qiniu_upload_base64($baseImg, $key);
        if ($pathJson == 441) {
            $result = ['code' => 441, 'msg' => '上传失败'];
            return $result;
        }
        $userData = User::findOne(['cust_no' => $custNo]);
        $userData->user_pic = $pathJson;
        if (!$userData->save()) {
            return ['code' => 109, 'msg' => '修改失败'];
        }
        $result = ['code' => 600, 'msg' => '上传成功', 'data' => $pathJson];
        return $result;
    }

    /**
     * 获取文章列表
     * @param type $page
     * @param type $size
     * @param type $start
     * @param type $end
     * @param type $artStatus
     * @param type $payType
     * @param type $title
     * @param type $expertId
     * @return type
     */
    public function getArticlesList($page, $size, $start, $end, $artStatus, $payType, $title, $expertId = '', $preType) {
        $startWhere = [];
        $endWhere = [];
        $titleWhere = [];
        $where = [];
        $articlesResult = Constants::ARTICLES_RESULT;
        $articlesStatus = Constants::ARTICLES_STATUS;
        $payTypeName = Constants::ARTICLES_PAY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        if (!empty($start)) {
            $startWhere = ['>=', 'create_time', $start . ' 00:00:00'];
        }
        if (!empty($end)) {
            $endWhere = ['<', 'create_time', $end . ' 23:59:59'];
        }
        if (!empty($title)) {
            $titleWhere = ['like', 'article_title', '%' . $title . '%', false];
        }
        if (!empty($artStatus)) {
            $where['article_status'] = $artStatus;
        }
        if (!empty($payType)) {
            $where['pay_type'] = $payType;
        }
        if (!empty($expertId)) {
            $where['user_id'] = $expertId;
        }
        $where['article_type'] = $preType;
        $total = ExpertArticles::find()->where($where)->andWhere($startWhere)->andWhere($endWhere)->andWhere($titleWhere)->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $field = ['expert_articles_id', 'article_type', 'article_title', 'pay_type', 'pay_money', 'articles_code', 'article_status', 'result_status', 'create_time', 'buy_back', 'article_content', 'buy_nums', 'read_nums'];
        $field2 = ['articles_periods.articles_id', 'articles_periods.periods', 'articles_periods.lottery_code', 'articles_periods.pre_result', 'articles_periods.pre_odds', 'articles_periods.status as pre_status',
            'articles_periods.schedule_code', 'articles_periods.visit_short_name', 'articles_periods.home_short_name', 'articles_periods.rq_nums', 'articles_periods.start_time', 'articles_periods.league_short_name',
            'articles_periods.home_team_rank', 'articles_periods.visit_team_rank', 'articles_periods.home_team_img', 'articles_periods.visit_team_img', 'articles_periods.featured', 'articles_periods.endsale_time',
            'articles_periods.fen_cutoff'];
        if ($preType == 1) {
            array_push($field2, 'sr.status', 'sr.schedule_result_3007 bf_result', 'sr.schedule_result_3010 sf_result', 'sr.schedule_result_3006 rfsf_result', 'sr.schedule_result_sbbf sbcbf_result', 's.schedule_status');
        } else {
            array_push($field2, 'sr.result_status status', 'sr.result_3001 sf_result', 'sr.result_3002 rfsf_result', 'sr.result_qcbf bf_result', 'sr.result_zcbf sbcbf_result', 's.schedule_status');
        }
        $artData = ExpertArticles::find()->select($field)->where($where)->andWhere($startWhere)->andWhere($endWhere)->andWhere($titleWhere)->indexBy('expert_articles_id')->offset($offset)->limit($size)->orderBy('expert_articles.create_time desc')->asArray()->all();
        $artIdArr = array_keys($artData);
        $scheQuery = ArticlesPeriods::find()->select($field2);
        if ($preType == 1) {
            $scheQuery = $scheQuery->leftJoin('schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('schedule as s', 's.schedule_mid = articles_periods.periods');
        } else {
            $scheQuery = $scheQuery->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('lan_schedule as s', 's.schedule_mid = articles_periods.periods');
        }
        $scheData = $scheQuery->where(['in', 'articles_id', $artIdArr])
                ->orderBy('articles_id desc, periods')
                ->asArray()
                ->all();
        $midArr = array_unique(array_column($scheData, 'periods'));
        if ($preType == 1) {
            $oddStr = ['odds3006', 'odds3010'];
            $odds = Schedule::find()->select(['schedule_id', 'schedule_mid'])->with($oddStr)->where(['in', 'schedule_mid', $midArr])->indexBy('schedule_mid')->asArray()->all();
        } elseif ($preType == 2) {
            $oddStr = ['odds3001', 'odds3002', 'odds3004'];
            $odds = LanSchedule::find()->select('schedule_mid')->with($oddStr)->where(['in', 'schedule_mid', $midArr])->indexBy('schedule_mid')->asArray()->all();
        }
        $list = [];
        foreach ($scheData as &$val) {
            $val['dx_result'] = 0;
            if (in_array($val['lottery_code'], $football) || in_array($val['lottery_code'], $basketball)) {
                $val['pre_result'] = explode(',', $val['pre_result']);
                $val['pre_odds'] = explode(',', $val['pre_odds']);
//                $oddStr = ['odds' . $val['lottery_code']];
//                $odds = Schedule::find()->with($oddStr)->where(['schedule_mid' => $val['periods']])->asArray()->one();
                $val['odds'] = $odds[$val['periods']]['odds' . $val['lottery_code']];
                $bfArr = explode(':', $val['bf_result']);
                if (in_array($val['lottery_code'], $basketball)) {
                    if ($val['status'] == 2) {
                        $val['dx_result'] = bccomp(bcadd($bfArr[0], $bfArr[1]), $val['fen_cutoff'], 2) == 1 ? 1 : 2;
                        $val['sf_result'] = bccomp($bfArr[1], $bfArr[0]) == 1 ? 3 : 0;
                        $val['rfsf_result'] = bccomp(bcadd($bfArr[1], $val['rq_nums']), $bfArr[0], 1) == 1 ? 3 : 0;
                    }
                }
            } else {
                $val['pre_result'][] = $val['pre_result'];
                $val['pre_odds'][] = [];
                $val['odds'] = [];
            }
            $artId = $val['articles_id'];
            if (array_key_exists($artId, $list)) {
                if (array_key_exists($val['periods'], $list[$artId]['pre_concent'])) {
                    $list[$artId]['pre_concent'][$val['periods']]['pre_lottery'][] = ['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured']];
                } else {
                    $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                        'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                        'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                        'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                        'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                        'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured']]]];
                }
            } else {
                $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                    'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                    'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                    'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                    'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                    'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured']]]];
                $list[$artId]['expert_articles_id'] = $artData[$artId]['expert_articles_id'];
                $list[$artId]['article_type'] = $artData[$artId]['article_type'];
                $list[$artId]['pay_type'] = $artData[$artId]['pay_type'];
                $list[$artId]['article_status'] = $artData[$artId]['article_status'];
                $list[$artId]['result_status'] = $artData[$artId]['result_status'];
                $list[$artId]['pay_type_name'] = $payTypeName[$artData[$artId]['pay_type']];
                $list[$artId]['article_status_name'] = $articlesStatus[$artData[$artId]['article_status']];
                $list[$artId]['result_status_name'] = $articlesResult[$artData[$artId]['result_status']];
                $list[$artId]['pay_money'] = $artData[$artId]['pay_money'];
                $list[$artId]['buy_nums'] = $artData[$artId]['buy_nums'];
                $list[$artId]['article_title'] = $artData[$artId]['article_title'];
                $list[$artId]['create_time'] = $artData[$artId]['create_time'];
                $list[$artId]['buy_back'] = $artData[$artId]['buy_back'];
                $list[$artId]['read_nums'] = $artData[$artId]['read_nums'];
                $list[$artId]['articles_code'] = $artData[$artId]['articles_code'];
                $list[$artId]['article_content'] = $artData[$artId]['article_content'];
            }
        }
        $artList = [];
        foreach ($list as &$vl) {
            $vl['pre_concent'] = array_values($vl['pre_concent']);
            $artList[] = $vl;
        }
        return ['page' => $page, 'pages' => $pages, 'size' => count($artList), 'total' => $total, 'data' => $artList];
    }

    /**
     * 文章提交（草稿，提交审核）
     * @auther GL zyl
     * @param type $expertId 专家ID
     * @param type $articleType 提交类型
     * @param type $preType 预测类型
     * @param type $payType 付费类型
     * @param type $payMoney 付费金额
     * @param type $periods 期数、场次
     * @param type $lotteryCode 彩种
     * @param type $preResult 预测结果
     * @param type $title 标题
     * @param type $content 文章内容
     * @return type
     */
    public function publish($expertId, $articleType, $preType, $payType, $payMoney, $periods, $lotteryCode, $preResult, $preOdds, $title, $content, $featured, $buyBack, $startTime, $articleId) {
        if (!empty($periods)) {
            $where = [];
            if (!empty($articleId)) {
                $where = ['!=', 'a.articles_id', $articleId];
            }
            $periodsArr = explode('|', $periods);
            $exist = ExpertArticles::find()->select(['expert_articles.expert_articles_id'])
                    ->leftJoin('articles_periods as a', 'a.articles_id = expert_articles.expert_articles_id')
                    ->where(['expert_articles.user_id' => $expertId, 'expert_articles.article_status' => 3, 'expert_articles.article_type' => $preType])
                    ->andWhere(['in', 'a.periods', $periodsArr])
                    ->andWhere($where)
                    ->asArray()
                    ->one();
            if (!empty($exist)) {
                return ['code' => 109, 'msg' => '您已预测过当前预测场次/期数，请勿重复预测'];
            }
            if ($preType == 1) {
                $scheData = $this->getZuScheduleInfo($periodsArr);
                $upStr = 'article_nums = article_nums + 1';
            } else {
                $scheData = $this->getLanScheduleInfo($periodsArr);
                $upStr = 'lan_article_nums = lan_article_nums + 1';
            }
            if (count($scheData) != count($periodsArr)) {
                return ['code' => 109, 'msg' => '推荐场次不存在或已无法进行推荐'];
            }
        }
        if (!empty($articleId)) {
            $articleModel = ExpertArticles::findOne(['expert_articles_id' => $articleId]);
            $del = ArticlesPeriods::deleteAll(['articles_id' => $articleId]);
            if ($del === false) {
                return ['code' => 109, 'msg' => '方案修改失败'];
            }
            $articleModel->modify_time = date('Y-m-d H:i:s');
        } else {
            $articleModel = new ExpertArticles;
            $articleModel->create_time = date('Y-m-d H:i:s');
            $articleModel->articles_code = Commonfun::getCode("GLC", "E");
        }
        if ($articleType == 1) {
            $articleModel->article_status = 1;
        } else {
            //免费文章一天只能发表三篇，无需审核
            if ($payType == 1) {
                $date = date("Y-m-d");
                $query = (new Query())->select("*")
                        ->from("expert_articles")
                        ->where(["user_id" => $expertId, 'pay_type' => 1, 'article_type' => $preType])
                        ->andWhere(["between", "create_time", $date . " 00:00:00", $date . " 23:59:59"])
                        ->count();
                if ($query >= 3) {
                    return ['code' => 109, 'msg' => '免费文章当天最多只能发布三篇'];
                } else {
                    $articleModel->article_status = 3;
                }
            } elseif ($payType == 2) {
                $articleModel->article_status = 3;
            } else {
                return ['code' => 109, 'msg' => '该付费类型不存在'];
            }
        }
//        if ($buyBack == 1) {
//            $dealStatus = 0;
//        } else {
//            $dealStatus = 2;
//        }

        $articleModel->user_id = $expertId;
        $articleModel->pay_type = $payType;
        $articleModel->pay_money = $payMoney;
        $articleModel->article_type = $preType;
        $articleModel->article_title = $title;
        $articleModel->article_content = $content;
        $articleModel->buy_back = $buyBack;
//        $articleModel->deal_status = $dealStatus;
        $articleModel->cutoff_time = (string) $startTime;
        if (!$articleModel->validate()) {
            return ['code' => 109, 'msg' => $articleModel->getFirstErrors()];
        }
        if (!$articleModel->save()) {
            return ['code' => 109, 'msg' => '提交存储失败'];
        }
        if (!empty($periods)) {
            $periodsArr = explode('|', $periods);
            $codeArr = explode('|', $lotteryCode);
            $resultArr = explode('|', $preResult);
            $oddsArr = explode('|', $preOdds);
            $featuredArr = explode('|', $featured);
            $insertArr = [];
            foreach ($periodsArr as $key => $v) {
                $fenCutoff = isset($scheData[$v]['fen_cutoff']) ? $scheData[$v]['fen_cutoff'] : '';
                if ($preType == 1) {
                    $insertArr[] = [$articleModel->expert_articles_id, $v, $codeArr[$key], $scheData[$v]['schedule_code'], $scheData[$v]['league_id'], $scheData[$v]['league_short_name'],
                        $scheData[$v]['home_short_name'], $scheData[$v]['visit_short_name'], $scheData[$v]['home_team_rank'], $scheData[$v]['visit_team_rank'], $scheData[$v]['home_team_img'], $scheData[$v]['visit_team_img'],
                        $scheData[$v]['rq_nums'], $scheData[$v]['start_time'], $scheData[$v]['endsale_time'], $resultArr[$key], $oddsArr[$key], $featuredArr[$key], date('Y-m-d H:i:s'), $fenCutoff];
                } else {
                    $rArr = explode(',', $resultArr[$key]);
                    $oArr = explode(',', $oddsArr[$key]);
                    $cArr = explode(',', $codeArr[$key]);
                    foreach ($cArr as $k => $item) {
                        $insertArr[] = [$articleModel->expert_articles_id, $v, $item, $scheData[$v]['schedule_code'], $scheData[$v]['league_id'], $scheData[$v]['league_short_name'],
                            $scheData[$v]['home_short_name'], $scheData[$v]['visit_short_name'], $scheData[$v]['home_team_rank'], $scheData[$v]['visit_team_rank'], $scheData[$v]['home_team_img'], $scheData[$v]['visit_team_img'],
                            $scheData[$v]['rq_nums'], $scheData[$v]['start_time'], $scheData[$v]['endsale_time'], $rArr[$k], $oArr[$k], $featuredArr[$key], date('Y-m-d H:i:s'), $fenCutoff];
                    }
                }
            }
            $key = ['articles_id', 'periods', 'lottery_code', 'schedule_code', 'league_id', 'league_short_name', 'home_short_name', 'visit_short_name', 'home_team_rank', 'visit_team_rank', 'home_team_img',
                'visit_team_img', 'rq_nums', 'start_time', 'endsale_time', 'pre_result', 'pre_odds', 'featured', 'create_time', 'fen_cutoff'];
            $db = Yii::$app->db;
            $res = $db->createCommand()->batchInsert('articles_periods', $key, $insertArr)->execute();
            if ($res == false) {
                return ['code' => 109, 'msg' => '提交失败'];
            }
            if ($articleType != 1) {
                $updata = "update expert set {$upStr}, modify_time = '" . date('Y-m-d H:i:s') . "' where user_id='{$expertId}'";
                $db->createCommand($updata)->execute();
            }
        }
        return ['code' => 600, 'msg' => '提交成功'];
    }

    /**
     * 获取足球赛程列表
     * @param type $page
     * @param type $size
     * @param type $startDate
     * @param type $endDate
     * @param type $league
     * @param type $likeParam
     * @return type
     */
    public function getSchedule($page, $size, $startDate, $endDate, $league, $likeParam) {
        $startWhere = [];
        $endWhere = [];
        $likeWhere1 = [];
        $likeWhere2 = [];
        $likeWhere3 = [];
        $likeWhere4 = [];
        $likeWhere5 = [];
        $where = [];
        if (!empty($startDate)) {
            $startWhere = ['>=', 'schedule.start_time', $startDate . ' 00:00:00'];
        }
        if (!empty($endDate)) {
            $endWhere = ['<', 'schedule.start_time', $endDate . ' 23:59:59'];
        }
        if (!empty($likeParam)) {
            $likeWhere1 = ['like', 'schedule.home_short_name', '%' . $likeParam . '%', false];
            $likeWhere2 = ['like', 'schedule.visit_short_name', '%' . $likeParam . '%', false];
            $likeWhere3 = ['like', 'l.league_short_name', '%' . $likeParam . '%', false];
            $likeWhere4 = ['like', 'schedule.league_id', '%' . $likeParam . '%', false];
            $likeWhere5 = ['like', 'schedule.schedule_mid', '%' . $likeParam . '%', false];
        }
        if (!empty($league)) {
            $where['schedule.league_id'] = $league;
        }
        $where['sr.status'] = 0;
        $where['l.league_type'] = 1;
        $total = Schedule::find()
                ->leftJoin('league as l', 'l.league_id = schedule.league_id and l.league_type = 1')
                ->leftJoin('schedule_result as sr', 'sr.schedule_mid = schedule.schedule_mid')
                ->andWhere(['!=', 'schedule.schedule_spf', 3])
                ->orWhere(['!=', 'schedule.schedule_rqspf', 3])
                ->andWhere($likeWhere1)
                ->orWhere($likeWhere2)
                ->orWhere($likeWhere3)
                ->orWhere($likeWhere4)
                ->orWhere($likeWhere5)
                ->andWhere($where)->andWhere($startWhere)
                ->andWhere($endWhere)
                ->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $field = ['schedule.schedule_id', 'schedule.schedule_code', 'schedule.schedule_mid', 'schedule.league_id', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time', 'schedule.rq_nums', 'l.league_short_name'];
        $scheData = Schedule::find()->select($field)
                ->leftJoin('league as l', 'l.league_id = schedule.league_id and l.league_type = 1')
                ->leftJoin('schedule_result as sr', 'sr.schedule_mid = schedule.schedule_mid')
                ->where(['!=', 'schedule.schedule_spf', 3])
                ->orWhere(['!=', 'schedule.schedule_rqspf', 3])
                ->andWhere($likeWhere1)
                ->orWhere($likeWhere2)
                ->orWhere($likeWhere3)
                ->orWhere($likeWhere4)
                ->orWhere($likeWhere5)
                ->andWhere($startWhere)
                ->andWhere($endWhere)
                ->andWhere($where)
                ->offset($offset)
                ->limit($size)
                ->orderBy('schedule.schedule_mid')
                ->asArray()
                ->all();
        return ['page' => $page, 'pages' => $pages, 'size' => count($scheData), 'total' => $total, 'data' => $scheData];
    }

    /**
     * 获取赛程详情
     * @auther GL zyl
     * @return type
     */
    public function getScheduleDetail($mid) {
        $oddStr = ['odds3006', 'odds3010'];
        $field = ['schedule.schedule_id', 'schedule.schedule_code', 'schedule.schedule_mid', 'schedule.league_id', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
            'schedule.rq_nums', 'schedule.schedule_spf', 'schedule.schedule_rqspf', 'l.league_short_name', 'ht.team_img as home_team_img', 'vt.team_img as visit_team_img', 'h.home_team_rank', 'h.visit_team_rank'];
        $scheData = Schedule::find()->select($field)
                ->leftJoin('league as l', 'l.league_id = schedule.league_id')
                ->leftJoin('team as ht', 'ht.team_id = schedule.home_team_id')
                ->leftJoin('team as vt', 'vt.team_id = schedule.visit_team_id')
                ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
                ->with($oddStr)
                ->where(['schedule.schedule_mid' => $mid])
                ->asArray()
                ->one();
        return $scheData;
    }

    /**
     * 获取文章详情
     * @param type $articleId
     * @param type $expertId
     * @return type
     */
    public function getArticleDetail($articleId, $expertId = '', $userId = '', $preType, $flag = 1) {
        $articlesResult = Constants::ARTICLES_RESULT;
        $articlesStatus = Constants::ARTICLES_STATUS;
        $payTypeName = Constants::ARTICLES_PAY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $expertSource = Constants::EXPERT_TYPE_SOURCE;
        $where['expert_articles.expert_articles_id'] = $articleId;
        if (!empty($expertId)) {
            $where['expert_articles.user_id'] = $expertId;
        }
        if (empty($userId)) {
            $field = ['expert_articles.expert_articles_id', 'e.expert_source', 'expert_articles.article_type', 'expert_articles.article_title', 'expert_articles.pay_type', 'expert_articles.pay_money', 'expert_articles.create_time',
                'expert_articles.buy_nums', 'expert_articles.read_nums', 'expert_articles.article_status', 'expert_articles.result_status', 'expert_articles.article_content', 'e.fans_nums', 'u.user_name', 'u.user_pic',
                'expert_articles.user_id as expert_id', 'expert_articles.buy_back', 'expert_articles.articles_code'];
            if ($preType == 1) {
                array_push($field, 'e.article_nums', 'e.even_red_nums', 'e.month_red_nums', 'e.day_red_nums', 'e.day_nums', 'e.two_red_nums', 'e.three_red_nums', 'e.five_red_nums');
            } else {
                array_push($field, 'e.lan_article_nums article_nums', 'e.lan_even_red_nums even_red_nums', 'e.lan_month_red_nums month_red_nums', 'e.lan_day_red_nums day_red_nums', 'e.lan_day_nums day_nums', 'e.lan_two_red_nums two_red_nums', 'e.lan_three_red_nums three_red_nums', 'e.lan_five_red_nums five_red_nums');
            }
        } else {
            $field = ['expert_articles.expert_articles_id', 'e.expert_source', 'expert_articles.article_type', 'expert_articles.article_title', 'expert_articles.pay_type', 'expert_articles.pay_money',
                'expert_articles.buy_nums', 'expert_articles.read_nums', 'expert_articles.article_status', 'expert_articles.result_status', 'expert_articles.article_content', 'expert_articles.create_time', 'e.fans_nums',
                'u.user_name', 'expert_articles.articles_code', 'u.user_pic', 'expert_articles.user_id as expert_id', 'expert_articles.buy_back', 'ue.status as attent_status', 'ua.status as is_pay'];
            if ($preType == 1) {
                array_push($field, 'e.article_nums', 'e.even_red_nums', 'e.month_red_nums', 'e.day_red_nums', 'e.day_nums', 'e.two_red_nums', 'e.three_red_nums', 'e.five_red_nums');
            } else {
                array_push($field, 'e.lan_article_nums article_nums', 'e.lan_even_red_nums even_red_nums', 'e.lan_month_red_nums month_red_nums', 'e.lan_day_red_nums day_red_nums', 'e.lan_day_nums day_nums', 'e.lan_two_red_nums two_red_nums', 'e.lan_three_red_nums three_red_nums', 'e.lan_five_red_nums five_red_nums');
            }
        }

        $field2 = ['articles_periods.articles_id', 'articles_periods.periods', 'articles_periods.lottery_code', 'articles_periods.pre_result', 'articles_periods.pre_odds', 'articles_periods.status as pre_status', 'articles_periods.schedule_code', 'articles_periods.visit_short_name', 'articles_periods.home_short_name',
            'articles_periods.rq_nums', 'articles_periods.start_time', 'articles_periods.league_short_name', 'articles_periods.home_team_rank', 'articles_periods.visit_team_rank', 'articles_periods.home_team_img', 'articles_periods.visit_team_img', 'articles_periods.featured',
            'articles_periods.endsale_time', 'articles_periods.fen_cutoff'];
        if ($preType == 1) {
            array_push($field2, 'sr.status', 'sr.schedule_result_3007 bf_result', 'sr.schedule_result_3010 sf_result', 'sr.schedule_result_3006 rfsf_result', 'sr.schedule_result_sbbf sbcbf_result', 's.schedule_status');
        } else {
            array_push($field2, 'sr.result_status status', 'sr.result_3001 sf_result', 'sr.result_3002 rfsf_result', 'sr.result_qcbf bf_result', 'sr.result_zcbf sbcbf_result', 's.schedule_status');
        }

        $query = ExpertArticles::find()->select($field)
                ->leftJoin('expert as e', 'e.user_id = expert_articles.user_id')
                ->leftJoin('user as u', 'u.user_id = expert_articles.user_id');
        if (!empty($userId)) {
            $query->leftJoin('user_expert as ue', "ue.expert_id = e.user_id and ue.user_id = {$userId} and ue.status = 1")->leftJoin('user_article as ua', "ua.article_id = expert_articles.expert_articles_id and ua.user_id = {$userId} and ua.status in (1,4)");
        }
        $detail = $query->where($where)->indexBy('expert_articles_id')->asArray()->one();
        if (empty($detail)) {
            return ['code' => 109, 'msg' => '查询结果不存在'];
        }
        $scheQuery = ArticlesPeriods::find()->select($field2);
        if ($preType == 1) {
            $scheQuery = $scheQuery->leftJoin('schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('schedule s', 's.schedule_mid = articles_periods.periods');
        } else {
            $scheQuery = $scheQuery->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('lan_schedule s', 's.schedule_mid = articles_periods.periods');
        }

        $scheData = $scheQuery->where(['articles_id' => $detail['expert_articles_id']])
                ->orderBy('articles_id desc, periods')
                ->asArray()
                ->all();
        $midArr = array_unique(array_column($scheData, 'periods'));
        if ($preType == 1) {
            $oddStr = ['odds3006', 'odds3010'];
            $odds = Schedule::find()->select(['schedule_id', 'schedule_mid'])->with($oddStr)->where(['in', 'schedule_mid', $midArr])->indexBy('schedule_mid')->asArray()->all();
        } elseif ($preType == 2) {
            $oddStr = ['odds3001', 'odds3002', 'odds3004'];
            $odds = LanSchedule::find()->select('schedule_mid')->with($oddStr)->where(['in', 'schedule_mid', $midArr])->indexBy('schedule_mid')->asArray()->all();
        }
        $list = [];
        $dayRed = [];
        foreach ($scheData as &$val) {
            $val['dx_result'] = 0;
            if (in_array($val['lottery_code'], $football) || in_array($val['lottery_code'], $basketball)) {
                $val['pre_result'] = explode(',', $val['pre_result']);
                $val['pre_odds'] = explode(',', $val['pre_odds']);
                $val['odds'] = $odds[$val['periods']]['odds' . $val['lottery_code']];
                $bfArr = explode(':', $val['bf_result']);
                if (in_array($val['lottery_code'], $basketball)) {
                    if ($val['status'] == 2) {
                        $val['dx_result'] = bccomp(bcadd($bfArr[0], $bfArr[1]), $val['fen_cutoff'], 2) == 1 ? 1 : 2;
                        $val['sf_result'] = bccomp($bfArr[1], $bfArr[0]) == 1 ? 3 : 0;
                        $val['rfsf_result'] = bccomp(bcadd($bfArr[1], $val['rq_nums']), $bfArr[0], 1) == 1 ? 3 : 0;
                    }
                }
            } else {
                $val['pre_result'][] = $val['pre_result'];
                $val['pre_odds'][] = [];
                $val['odds'] = [];
            }
            $artId = $val['articles_id'];
            if (array_key_exists($artId, $list)) {
                if (array_key_exists($val['periods'], $list[$artId]['pre_concent'])) {
                    $list[$artId]['pre_concent'][$val['periods']]['pre_lottery'][] = ['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured']];
                } else {
                    $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                        'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                        'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                        'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                        'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                        'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured']]]];
                }
            } else {
                $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                    'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                    'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                    'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                    'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                    'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured']]]];
                $list[$artId]['expert_articles_id'] = $detail['expert_articles_id'];
                $list[$artId]['article_type'] = $detail['article_type'];
                $list[$artId]['pay_type'] = $detail['pay_type'];
                $list[$artId]['article_status'] = $detail['article_status'];
                $list[$artId]['result_status'] = $detail['result_status'];
                $list[$artId]['pay_type_name'] = $payTypeName[$detail['pay_type']];
                $list[$artId]['article_status_name'] = $articlesStatus[$detail['article_status']];
                $list[$artId]['result_status_name'] = $articlesResult[$detail['result_status']];
                $list[$artId]['pay_money'] = $detail['pay_money'];
                $list[$artId]['buy_nums'] = $detail['buy_nums'];
                $list[$artId]['read_nums'] = $detail['read_nums'];
                $list[$artId]['article_title'] = $detail['article_title'];
                $list[$artId]['create_time'] = $detail['create_time'];
                $list[$artId]['article_nums'] = $detail['article_nums'];
                $list[$artId]['fans_nums'] = $detail['fans_nums'];
                $list[$artId]['even_red_nums'] = $detail['even_red_nums'];
                $list[$artId]['month_red_nums'] = $detail['month_red_nums'];
//                $list[$artId]['day_red_nums'] = $detail['day_red_nums'];
                $list[$artId]['user_name'] = $detail['user_name'];
                $list[$artId]['user_pic'] = $detail['user_pic'];
                $list[$artId]['article_title'] = $detail['article_title'];
//                $list[$artId]['day_nums'] = $detail['day_nums'];
                $list[$artId]['expert_id'] = $detail['expert_id'];
                $list[$artId]['article_content'] = $detail['article_content'];
                $list[$artId]['buy_back'] = $detail['buy_back'];
                $list[$artId]['articles_code'] = $detail['articles_code'];
                $list[$artId]['expert_source_name'] = $expertSource[$detail['expert_source']];
                $list[$artId]['expert_source'] = $detail['expert_source'];
                $dayRed[2] = ['nums' => $detail['two_red_nums'], 'pro' => floatval($detail['two_red_nums']) / 2];
                $dayRed[3] = ['nums' => $detail['three_red_nums'], 'pro' => floatval($detail['three_red_nums']) / 3];
                $dayRed[5] = ['nums' => $detail['five_red_nums'], 'pro' => floatval($detail['five_red_nums']) / 5];
                $dayRed[7] = ['nums' => $detail['day_red_nums'], 'pro' => floatval($detail['day_red_nums']) / 7];
                $tmpe = 0;
                foreach ($dayRed as $k => $v) {
                    if (round($v['pro'], 2) >= $tmpe) {
                        $tmpe = $v['pro'];
                        $nTmpe = $v['nums'];
                        $kTmpe = $k;
                    }
                }
                if ($tmpe < 0.5) {
                    $nTmpe = 0;
                    $kTmpe = 0;
                }
                $list[$artId]['day_red_nums'] = $nTmpe;
                $list[$artId]['day_nums'] = $kTmpe;
                if (array_key_exists('attent_status', $detail)) {
                    if (empty($detail['attent_status'])) {
                        $list[$artId]['attent_status'] = 2;
                    } else {
                        $list[$artId]['attent_status'] = $detail['attent_status'];
                    }
                } else {
                    $list[$artId]['attent_status'] = 2;
                }
                if ($userId == $detail['expert_id']) {
                    $list[$artId]['is_pay'] = 1;
                } elseif (array_key_exists('is_pay', $detail)) {
                    if (empty($detail['is_pay'])) {
                        $list[$artId]['is_pay'] = 0;
                    } else {
                        $list[$artId]['is_pay'] = 1;
                    }
                } else {
                    $list[$artId]['is_pay'] = 0;
                }
            }
        }
        $list[$articleId]['pre_concent'] = array_values($list[$articleId]['pre_concent']);
        if ($flag == 1) {//星星体育获取不做判断
            if ($list[$articleId]['pay_type'] == 2 && $list[$articleId]['is_pay'] == 0 && $list[$articleId]['result_status'] == 1) {
                unset($list[$articleId]['article_content']);
                foreach ($list[$articleId]['pre_concent'] as &$i) {
                    unset($i['pre_lottery']);
                }
            }
        }
        /* 添加文章是否收藏字段 -- gwp */
        if ($userId) {
            $row = ArticlesCollect::find()->select('articles_collect_id')
                    ->where(['user_id' => $userId, 'expert_articles_id' => $articleId])
                    ->asArray()
                    ->one();
            $articlesCollectId = $row['articles_collect_id'] ? true : false;
            $list[$articleId]['collect_id'] = $articlesCollectId;
        }
        $detailData = $list[$articleId];
        return ['code' => 600, 'msg' => '获取成功', 'data' => $detailData];
    }

    /**
     * C端获取推荐总概
     * @param type $type
     * @param type $expertId
     * @return type
     */
    public function getRecommend($type, $expertId = '', $scheduleType) {
        $jwhere['expert_articles.article_status'] = 3;
        if (!empty($expertId)) {
            $jwhere['expert_articles.user_id'] = $expertId;
        }
        $jwhere['article_type'] = $scheduleType;
        $startDate = date('Y-m-d', strtotime('-7 day')) . ' 00:00:00';
        $endDate = date('Y-m-d') . ' 23:59:59';
        $recommend_all = ExpertArticles::find()
                ->select('expert_articles.result_status,expert_articles_id,a.pre_odds,a.lottery_code,expert_articles.create_time')
                ->leftJoin('articles_periods as a', 'a.articles_id = expert_articles.expert_articles_id')
                ->where($jwhere)
                ->orderBy('expert_articles.create_time')
                ->asArray()
                ->all();
        $recommend = []; //综合
        $even_red_nums = []; //近期连红
        $top_red_nums = []; //最高连红
        $red_article = 0;
        $black_article = 0;
        $total = 0; //总推荐
        $new_all = [];
        $recommend['profit'] = 0;
        $recommend['red_proportion'] = 0;
        $recommend['black_proportion'] = 0;
        $recommend['zoushi'] = [];
        $recommend['even_red_nums'] = 0;
        $recommend['zoushi_result'] = ['red' => 0, 'black' => 0];
        $recommend['total'] = 0;
        $recommend['top_red_nums'] = 0;
        if (!empty($recommend_all)) {
            if ($type == 1) {
                foreach ($recommend_all as &$v) {
                    $pre_odds = explode(',', $v['pre_odds']);
                    $v['pre_odds'] = $pre_odds[array_search(max($pre_odds), $pre_odds)];
                    if ($v['lottery_code'] == 3006 || $v['lottery_code'] == 3010 || $v['lottery_code'] == 3001 || $v['lottery_code'] == 3002 || $v['lottery_code'] == 3004) {//竞彩
                        if ($v['create_time'] >= $startDate && $v['create_time'] <= $endDate) {
                            if ($v['result_status'] == 2) {//黑单
                                $black_article++;
                            }
                            if ($v['result_status'] == 3) {//红单
                                $red_article++;
                                $recommend['profit'] += $v['pre_odds'];
                            }
                            $aa[] = $v;
                        }
                        //红单
                        $top_red_nums[] = $v['result_status'];
                        if ($v['result_status'] != 1) {
                            $new_all[] = $v;
                        }
                        $total++;
                    }
                }
            } elseif ($type == 2) {
                foreach ($recommend_all as &$v) {
                    $pre_odds = explode(',', $v['pre_odds']);
                    $v['pre_odds'] = $pre_odds[array_search(max($pre_odds), $pre_odds)];
                    if ($v['lottery_code'] == 3006 || $v['lottery_code'] == 3002) {//让球
                        if ($v['create_time'] >= $startDate && $v['create_time'] <= $endDate) {
                            if ($v['result_status'] == 2) {//黑单
                                $black_article++;
                            }
                            if ($v['result_status'] == 3) {//红单
                                $red_article++;
                                $recommend['profit'] *= $v['pre_odds'];
                            }
                        }
                        //红单
                        $top_red_nums[] = $v['result_status'];
                        if ($v['result_status'] != 1) {
                            $new_all[] = $v;
                        }
                        $total++;
                    }
                }
            } else {
                foreach ($recommend_all as &$v) {
                    $pre_odds = explode(',', $v['pre_odds']);
                    $v['pre_odds'] = $pre_odds[array_search(max($pre_odds), $pre_odds)];
                    if ($v['create_time'] >= $startDate && $v['create_time'] <= $endDate) {
                        if ($v['result_status'] == 2) {//黑单
                            $black_article++;
                        }
                        if ($v['result_status'] == 3) {//红单
                            $red_article++;
                            $recommend['profit'] += $v['pre_odds'];
                        }
                    }
                    $top_red_nums[] = $v['result_status'];
                    if ($v['result_status'] != 1) {
                        $new_all[] = $v;
                    }
                    $total++;
                }
            }

            $all = $red_article + $black_article;
            if ($recommend['profit'] == 0) {
                $recommend['profit'] = 0;
            } else {
                $recommend['profit'] = bcmul(bcdiv($recommend['profit'], $all, 2), 100, 2);
            }
            $max = $this->continuous_num($top_red_nums, 3);
            $recommend['top_red_nums'] = $max; //最高连红
            $recommend['total'] = $total;
            $recommend['red_proportion'] = $all == 0 ? 0 : bcmul(bcdiv($red_article, $all), 100); ///近7日红单命中率
            $recommend['black_proportion'] = $all == 0 ? 0 : bcmul(bcdiv($black_article, $all), 100); ///近7日黑单命中率
            $recommend['zoushi'] = array_slice($new_all, -10, 10); //选最近10场
            $count1 = 0;
            $count2 = 0;
            foreach ($recommend['zoushi'] as $v) {
                if ($v['result_status'] == 2) {
                    $count1++;
                }
                if ($v['result_status'] == 3) {
                    $count2++;
                }
                $even_red_nums[] = $v['result_status'];
            }
            $even_red = $this->continuous_num($even_red_nums, 3);
            $recommend['even_red_nums'] = $even_red; //近期连红
            $recommend['zoushi_result'] = ['red' => $count2, 'black' => $count1];
        }
        if ($recommend['profit'] == 1) {
            $recommend['profit'] = 0;
        }
        return $recommend;
    }

    //获取连续出现次数最多的数的次数
    public function continuous_num($arr, $num) {
        $j = 0;
        $max = 0;
        foreach ($arr as $v) {
            if ($v == $num) {
                $j++;
            } else {
                if ($j > $max) {
                    $max = $j;
                }
                $j = 0;
            }
        }
        if ($j > $max) {
            $max = $j;
        }
        return $max;
    }

    /**
     * C端获取文章列表
     * @param type $page
     * @param type $size
     * @param type $start
     * @param type $end
     * @param type $artStatus
     * @param type $payType
     * @param type $title
     * @param type $expertId
     * @return type
     */
    public function getAppArticlesList($page, $size, $expertId = '', $payType = '', $preType) {
        $where['article_status'] = 3;
        $jwhere['expert_articles.article_status'] = 3;
        if (!empty($expertId)) {
            $where['user_id'] = $expertId;
            $jwhere['expert_articles.user_id'] = $expertId;
            $startDate = date('Y-m-d', strtotime('-30 day')) . ' 00:00:00';
        } else {
            $startDate = date('Y-m-d', strtotime('-7 day')) . ' 00:00:00';
        }
        if (!empty($payType)) {
            $where['pay_type'] = 1;
            $jwhere['expert_articles.pay_type'] = 1;
        }
        $where['article_type'] = $preType;
        $jwhere['expert_articles.article_type'] = $preType;
        $articlesResult = Constants::ARTICLES_RESULT;
        $articlesStatus = Constants::ARTICLES_STATUS;
        $payTypeName = Constants::ARTICLES_PAY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $expertSource = Constants::EXPERT_TYPE_SOURCE;
        $endDate = date('Y-m-d') . ' 23:59:59';
        $total = ExpertArticles::find()->where($where)->andWhere(['between', 'create_time', $startDate, $endDate])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $field = ['expert_articles.expert_articles_id', 'e.expert_source', 'expert_articles.article_type', 'expert_articles.article_title', 'expert_articles.pay_type', 'expert_articles.pay_money',
            'expert_articles.buy_nums', 'expert_articles.read_nums', 'expert_articles.article_status', 'expert_articles.result_status', 'expert_articles.articles_code', 'e.fans_nums',
            'u.user_name', 'u.user_pic', 'expert_articles.create_time', 'expert_articles.user_id as expert_id', 'expert_articles.buy_back', 'expert_articles.stick', 'expert_articles.deal_status', 'expert_articles.article_content'];
        if ($preType == 1) {
            array_push($field, 'e.article_nums', 'e.even_red_nums', 'e.month_red_nums', 'e.day_red_nums', 'e.day_nums', 'e.two_red_nums', 'e.three_red_nums', 'e.five_red_nums');
        } else {
            array_push($field, 'e.lan_article_nums article_nums', 'e.lan_even_red_nums even_red_nums', 'e.lan_month_red_nums month_red_nums', 'e.lan_day_red_nums day_red_nums', 'e.lan_day_nums day_nums', 'e.lan_two_red_nums two_red_nums', 'e.lan_three_red_nums three_red_nums', 'e.lan_five_red_nums five_red_nums');
        }

        $field2 = ['articles_periods.articles_id', 'articles_periods.periods', 'articles_periods.lottery_code',
            'articles_periods.pre_result', 'articles_periods.pre_odds', 'articles_periods.schedule_code', 'articles_periods.visit_short_name', 'articles_periods.home_short_name', 'articles_periods.rq_nums',
            'articles_periods.start_time', 'articles_periods.league_short_name', 'articles_periods.home_team_rank', 'articles_periods.visit_team_rank', 'articles_periods.home_team_img', 'articles_periods.visit_team_img',
            'articles_periods.status as pre_status', 'articles_periods.featured', 'articles_periods.endsale_time', 'articles_periods.fen_cutoff'];
        if ($preType == 1) {
            array_push($field2, 'sr.status', 'sr.schedule_result_3007 bf_result', 'sr.schedule_result_3010 sf_result', 'sr.schedule_result_3006 rfsf_result', 'sr.schedule_result_sbbf sbcbf_result', 's.schedule_status');
            $orderBy = 'deal_status asc, expert_articles.stick, expert_articles.create_time desc, e.month_red_nums desc, expert_articles_id desc';
        } else {
            array_push($field2, 'sr.result_status status', 'sr.result_3001 sf_result', 'sr.result_3002 rfsf_result', 'sr.result_qcbf bf_result', 'sr.result_zcbf sbcbf_result', 's.schedule_status');
            $orderBy = 'deal_status asc, expert_articles.stick, expert_articles.create_time desc, e.lan_month_red_nums desc, expert_articles_id desc';
        }
//        print_r($field2);die;
        $artData = ExpertArticles::find()->select($field)
                ->innerJoin('expert as e', 'e.user_id = expert_articles.user_id')
                ->leftJoin('user as u', 'u.user_id = e.user_id')
                ->where($jwhere)
                ->andWhere(['between', 'expert_articles.create_time', $startDate, $endDate])
                ->offset($offset)
                ->limit($size)
                ->indexBy('expert_articles_id')
                ->orderBy($orderBy)
                ->asArray()
                ->all();
        $artIdArr = array_keys($artData);
        $scheQuery = ArticlesPeriods::find()->select($field2);
        if ($preType == 1) {
            $scheQuery = $scheQuery->leftJoin('schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('schedule s', 's.schedule_mid = articles_periods.periods');
        } else {
            $scheQuery = $scheQuery->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('lan_schedule s', 's.schedule_mid = articles_periods.periods');
        }

        $scheData = $scheQuery->where(['in', 'articles_id', $artIdArr])
                ->orderBy('articles_id desc, periods')
                ->asArray()
                ->all();
        $list = [];
        $dayRed = [];
        $redisKey = "article_read_total";
        $redis = \Yii::$app->redis;
        foreach ($artIdArr as $key) {
            foreach ($scheData as &$val) {
                $val['dx_result'] = 0;
                if ($val['articles_id'] == $key) {
                    if (in_array($val['lottery_code'], $football) || in_array($val['lottery_code'], $basketball)) {
                        $val['pre_result'] = explode(',', $val['pre_result']);
                        $val['pre_odds'] = explode(',', $val['pre_odds']);
                        if ($val['featured'] != 2) {
                            $trans = array_flip($val['pre_result']);
                            $key = $trans[$val['featured']];
                            $val['profit'] = $val['pre_odds'][$key];
                        } else {
                            $val['profit'] = $val['pre_odds'][0];
                        }
                        $bfArr = explode(':', $val['bf_result']);
                        if (in_array($val['lottery_code'], $basketball)) {
                            if ($val['status'] == 2) {
                                $val['dx_result'] = bccomp(bcadd($bfArr[0], $bfArr[1]), $val['fen_cutoff'], 2) == 1 ? 1 : 2;
                                $val['sf_result'] = bccomp($bfArr[1], $bfArr[0]) == 1 ? 3 : 0;
                                $val['rfsf_result'] = bccomp(bcadd($bfArr[1], $val['rq_nums']), $bfArr[0], 1) == 1 ? 3 : 0;
                            }
                        }
                    } else {
                        $val['pre_result'][] = $val['pre_result'];
                        $val['pre_odds'][] = [];
                        $val['profit'] = 1;
                    }
                    $artId = $val['articles_id'];
                    if (array_key_exists($artId, $list)) {
                        if (array_key_exists($val['periods'], $list[$artId]['pre_concent'])) {
                            $list[$artId]['pre_concent'][$val['periods']]['pre_lottery'][] = ['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'featured' => $val['featured'], 'profit' => $val['profit']];
                        } else {
                            $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                                'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                                'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                                'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                                'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                                'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'featured' => $val['featured'], 'profit' => $val['profit']]]];
                        }
                    } else {
                        $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                            'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                            'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                            'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                            'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                            'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'featured' => $val['featured'], 'profit' => $val['profit']]]];
                        $list[$artId]['expert_articles_id'] = $artData[$artId]['expert_articles_id'];
                        $list[$artId]['article_type'] = $artData[$artId]['article_type'];
                        $list[$artId]['pay_type'] = $artData[$artId]['pay_type'];
                        $list[$artId]['article_status'] = $artData[$artId]['article_status'];
                        $list[$artId]['result_status'] = $artData[$artId]['result_status'];
                        $list[$artId]['pay_type_name'] = $payTypeName[$artData[$artId]['pay_type']];
                        $list[$artId]['article_status_name'] = $articlesStatus[$artData[$artId]['article_status']];
                        $list[$artId]['result_status_name'] = $articlesResult[$artData[$artId]['result_status']];
                        $list[$artId]['pay_money'] = $artData[$artId]['pay_money'];
                        $list[$artId]['buy_nums'] = $artData[$artId]['buy_nums'];
                        $nowRedis = $redis->zscore($redisKey, $artId);
                        $list[$artId]['read_nums'] = $artData[$artId]['read_nums'] + $nowRedis;
                        $list[$artId]['article_nums'] = $artData[$artId]['article_nums'];
                        $list[$artId]['fans_nums'] = $artData[$artId]['fans_nums'];
                        $list[$artId]['even_red_nums'] = $artData[$artId]['even_red_nums'];
                        $list[$artId]['month_red_nums'] = $artData[$artId]['month_red_nums'];
                        $list[$artId]['day_red_nums'] = $artData[$artId]['day_red_nums'];
                        $list[$artId]['user_name'] = $artData[$artId]['user_name'];
                        $list[$artId]['user_pic'] = $artData[$artId]['user_pic'];
                        $list[$artId]['article_title'] = $artData[$artId]['article_title'];
                        $list[$artId]['day_nums'] = $artData[$artId]['day_nums'];
                        $list[$artId]['create_time'] = $artData[$artId]['create_time'];
                        $list[$artId]['expert_id'] = $artData[$artId]['expert_id'];
                        $list[$artId]['buy_back'] = $artData[$artId]['buy_back'];
                        $list[$artId]['articles_code'] = $artData[$artId]['articles_code'];
                        $list[$artId]['expert_source'] = $artData[$artId]['expert_source'];
                        $list[$artId]['expert_source_name'] = $expertSource[$artData[$artId]['expert_source']];
                        $list[$artId]['stick'] = $artData[$artId]['stick'];
                        $list[$artId]['deal_status'] = $artData[$artId]['deal_status'];
                        $dayRed[2] = ['nums' => $artData[$artId]['two_red_nums'], 'pro' => floatval($artData[$artId]['two_red_nums']) / 2];
                        $dayRed[3] = ['nums' => $artData[$artId]['three_red_nums'], 'pro' => floatval($artData[$artId]['three_red_nums']) / 3];
                        $dayRed[5] = ['nums' => $artData[$artId]['five_red_nums'], 'pro' => floatval($artData[$artId]['five_red_nums']) / 5];
                        $dayRed[7] = ['nums' => $artData[$artId]['day_red_nums'], 'pro' => floatval($artData[$artId]['day_red_nums']) / 7];
                        $tmpe = 0;
                        foreach ($dayRed as $k => $v) {
                            if (round($v['pro'], 2) >= $tmpe) {
                                $tmpe = $v['pro'];
                                $nTmpe = $v['nums'];
                                $kTmpe = $k;
                            }
                        }
                        if ($tmpe < 0.5) {
                            $nTmpe = 0;
                            $kTmpe = 0;
                        }
                        $list[$artId]['day_red_nums'] = $nTmpe;
                        $list[$artId]['day_nums'] = $kTmpe;
                        $list[$artId]['article_content'] = $artData[$artId]['article_content'];

//                        $orderByStick[] = $artData[$artId]['stick'];
//                        $orderByStatus[] = $artData[$artId]['deal_status'];
//                        $orderByTime[] = $artData[$artId]['create_time'];
//                        $orderByRed[] = $artData[$artId]['month_red_nums'];
                    }
                }
            }
        }
        $artList = [];
        foreach ($list as &$vl) {
            $vl['pre_concent'] = array_values($vl['pre_concent']);
            $preProfit = 1;
            if ($vl['article_type'] == 1 || $vl['article_type'] == 2) {
                foreach ($vl['pre_concent'] as &$it) {
                    if ($vl['pay_type'] == 2) {
                        unset($it['pre_lottery']);
                        $preProfit = 0;
                    } else {
                        foreach ($it['pre_lottery'] as $ii) {
                            $preProfit *= $ii['profit'];
                        }
                    }
                }
            } else {
                $preProfit = 1;
            }
            $vl['pre_profit'] = bcmul($preProfit, 100, 2);
            $artList[] = $vl;
        }
//        array_multisort($orderByStick, SORT_DESC, $orderByStatus, SORT_ASC, $orderByTime, SORT_DESC, $orderByRed, SORT_DESC, $artList);
        return ['page' => $page, 'pages' => $pages, 'size' => count($artList), 'total' => $total, 'data' => $artList];
    }

    /**
     * 获取擅长赛事列表
     * @param type $page
     * @param type $size
     * @return type
     */
    public function getGoodLeague($expertId = '', $scheduleType) {
        $jwhere['a.article_status'] = 3;
        if (!empty($expertId)) {
            $jwhere['a.user_id'] = $expertId;
        }
        $jwhere['a.article_type'] = $scheduleType;
        $artData = ArticlesPeriods::find()
                ->select('articles_periods.league_short_name,a.result_status,articles_periods.league_id,pre_odds')
                ->leftJoin('expert_articles as a', 'a.expert_articles_id = articles_periods.articles_id')
                ->where($jwhere)
                ->andWhere(['in', 'result_status', [2, 3]])
                ->groupBy('league_id,articles_periods_id')
                ->asArray()
                ->all();
        $res = [];
        //var_dump($artData);die;
        if (empty($artData)) {
            $newartData['changci'] = [];
            $newartData['hit_rate'] = [];
            $newartData['profit_rate'] = [];
        } else {
            foreach ($artData as $key => &$v) {
                $pre_odds = explode(',', $v['pre_odds']);
                $v['pre_odds'] = $pre_odds[array_search(max($pre_odds), $pre_odds)];
                $league = $v['league_id'];
                $res[$league]['changci'] = !isset($res[$league]['changci']) ? 1 : $res[$league]['changci'] + 1;
                $res[$league]['black'] = !isset($res[$league]['black']) ? 0 : $res[$league]['black'];
                $res[$league]['red'] = !isset($res[$league]['red']) ? 0 : $res[$league]['red'];
                $res[$league]["league_short_name"] = $v['league_short_name'];
                if (!isset($res[$league]['profit'])) {
                    $res[$league]['profit'] = 0;
                }
                //$res[$league]["arr"][] = $v;
                if ($v['result_status'] == 2) {
                    $res[$league]['black'] ++;
                } elseif ($v['result_status'] == 3) {
                    $res[$league]['red'] ++;
                    $res[$league]['profit'] += $v['pre_odds'];
                }
                $res[$league]["hit_rate"] = bcmul(bcdiv($res[$league]['red'], $res[$league]['changci'], 2), 100);
            }
//            print_r($res);die;
            $newartData['changci'] = [];
            $newartData['hit_rate'] = [];
            $newartData['profit_rate'] = [];
            foreach ($res as $key => &$value) {
                if ($value['red'] >= 1) {
                    $value['profit'] = bcmul(bcdiv(round($value['profit'], 2), ($value['red'] + $value['black']), 2), 100);
                } else {
                    $value['profit'] = 0;
                }
                if ($value['changci'] >= 1) {
                    $newartData['changci'][] = $value;
                }

                $newartData['hit_rate'][] = $value;
                $newartData['profit_rate'][] = $value;
            }
            //    print_r($res);die;
            $newartData['changci'] = array_slice($this->multisort($newartData['changci'], 'changci'), 0, 3);
            $newartData['hit_rate'] = array_slice($this->multisort($newartData['hit_rate'], 'hit_rate'), 0, 3);
            $newartData['profit_rate'] = array_slice($this->multisort($newartData['profit_rate'], 'profit'), 0, 3);
        }
        return $newartData;
    }

    //多位数组按某个字段排序
    public function multisort($arr, $ziduan) {
        foreach ($arr as $key => $val) {
            $dos[$key] = $val[$ziduan];
        }
        array_multisort($dos, SORT_DESC, $arr);
        return $arr;
    }

    /**
     * 获取专家列表
     * @param type $page
     * @param type $size
     * @return type
     */
    public function getExpertList($page, $size, $expertType, $listType, $userId = '') {
        $expertSource = Constants::EXPERT_TYPE_SOURCE;
        $where = [];
        if ($listType == 1) {
            if ($expertType == 1) {
                $orderBy = 'expert.month_red_nums desc,expert.expert_id asc';
                $where = ['!=', 'identity', 2];
            } else {
                $orderBy = 'expert.lan_month_red_nums desc,expert.expert_id asc';
                $where = ['!=', 'identity', 1];
            }
        } else {
            $orderBy = 'expert.fans_nums desc,expert.expert_id asc';
            if ($expertType == 1) {
                $where = ['!=', 'identity', 2];
            } else {
                $where = ['!=', 'identity', 1];
            }
        }
        $total = Expert::find()->leftJoin('user u', 'u.user_id = expert.user_id')->where(['expert.expert_status' => 2, 'u.status' => 1])->andWhere($where)->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;

        $field = ['expert.user_id as expert_id', 'expert.expert_source', 'expert.introduction', 'expert.fans_nums', 'expert.identity', 'expert.expert_status', 'u.user_name', 'u.user_pic'];
        if ($expertType == 1) {
            array_push($field, 'expert.article_nums', 'expert.read_nums', 'expert.even_red_nums', 'expert.month_red_nums', 'expert.day_red_nums', 'expert.day_nums', 'expert.five_red_nums', 'expert.two_red_nums', 'expert.three_red_nums');
        } else {
            array_push($field, 'expert.lan_article_nums article_nums', 'expert.lan_read_nums read_nums', 'expert.lan_even_red_nums even_red_nums', 'expert.lan_month_red_nums month_red_nums', 'expert.lan_day_red_nums day_red_nums', 'expert.lan_day_nums day_nums', 'expert.lan_five_red_nums five_red_nums', 'expert.lan_two_red_nums two_red_nums', 'expert.lan_three_red_nums three_red_nums');
        }
        if (!empty($userId)) {
            $field[] = 'ue.status as attent_status';
        }
        $query = Expert::find()->select($field)->leftJoin('user as u', 'u.user_id = expert.user_id');
        if (!empty($userId)) {
            $query->leftJoin('user_expert as ue', 'ue.expert_id = expert.user_id and ue.user_id = ' . $userId . ' and ue.status = 1');
        }
        $expertData = $query->where(['expert.expert_status' => 2, 'u.status' => 1])->andWhere($where)
                ->groupBy('expert.user_id')
                ->offset($offset)
                ->limit($size)
                ->orderBy($orderBy)
                ->asArray()
                ->all();
//        $orderBy = [];
        $dayRed = [];
        foreach ($expertData as &$val) {
            $dayRed[2] = ['nums' => $val['two_red_nums'], 'pro' => floatval($val['two_red_nums']) / 2];
            $dayRed[3] = ['nums' => $val['three_red_nums'], 'pro' => floatval($val['three_red_nums']) / 3];
            $dayRed[5] = ['nums' => $val['five_red_nums'], 'pro' => floatval($val['five_red_nums']) / 5];
            $dayRed[7] = ['nums' => $val['day_red_nums'], 'pro' => floatval($val['day_red_nums']) / 7];
            $tmpe = 0;
            foreach ($dayRed as $k => $v) {
                if (round($v['pro'], 2) >= $tmpe) {
                    $tmpe = $v['pro'];
                    $nTmpe = $v['nums'];
                    $kTmpe = $k;
                }
            }
            if ($tmpe < 0.5) {
                $nTmpe = 0;
                $kTmpe = 0;
            }
            $val['day_red_nums'] = $nTmpe;
            $val['day_nums'] = $kTmpe;
            if (array_key_exists('attent_status', $val)) {
                if (empty($val['attent_status'])) {
                    $val['attent_status'] = 2;
                } else {
                    $val['attent_status'] = $val['attent_status'];
                }
            } else {
                $val['attent_status'] = 2;
            }
            $val['expert_source_name'] = $expertSource[$val['expert_source']];
//            if ($listType == 1) {
//                $orderBy[] = $val['even_red_nums'];
//            } else {
//                $orderBy[] = $val['fans_nums'];
//            }
        }
//        array_multisort($orderBy, SORT_DESC, $expertData);
        return ['page' => $page, 'size' => count($expertData), 'pages' => $pages, 'total' => $total, 'data' => $expertData];
    }

    /**
     * 获取专家详情
     * @param type $expertId
     * @return type
     */
    public function getExpertDetail($expertId, $userId = '', $expertType) {
        $expertStatus = Constants::EXPERT_STATUS;
        $lottery = Constants::EXPERT_LOTTERY;
        $indentity = Constants::EXPERT_IDENTITY;
        $expertSource = Constants::EXPERT_TYPE_SOURCE;
        if (empty($userId)) {
            $field = ['expert.user_id as expert_id', 'expert.expert_source', 'expert.introduction', 'expert.fans_nums', 'expert.identity', 'expert.lottery', 'expert.expert_status', 'u.user_name', 'u.user_pic',];
        } else {
            $field = ['expert.user_id as expert_id', 'expert.expert_source', 'expert.introduction', 'expert.fans_nums', 'expert.identity', 'expert.lottery', 'expert.expert_status', 'u.user_name', 'u.user_pic', 'ue.status as attent_status'];
        }
        if ($expertType == 1) {
            array_push($field, 'expert.article_nums', 'expert.read_nums', 'expert.even_red_nums', 'expert.month_red_nums', 'expert.day_red_nums', 'expert.day_nums', 'expert.five_red_nums', 'expert.two_red_nums', 'expert.three_red_nums');
        } else {
            array_push($field, 'expert.lan_article_nums article_nums', 'expert.lan_read_nums read_nums', 'expert.lan_even_red_nums even_red_nums', 'expert.lan_month_red_nums month_red_nums', 'expert.lan_day_red_nums day_red_nums', 'expert.lan_day_nums day_nums', 'expert.lan_five_red_nums five_red_nums', 'expert.lan_two_red_nums two_red_nums', 'expert.lan_three_red_nums three_red_nums');
        }
        $query = Expert::find()->select($field)->leftJoin('user as u', 'u.user_id = expert.user_id');
        if (!empty($userId)) {
            $query->leftJoin('user_expert as ue', "ue.expert_id = expert.user_id and ue.user_id = {$userId} and ue.status = 1");
        }
        $expertData = $query->where(['expert.user_id' => $expertId])->asArray()->one();
        if (empty($expertData)) {
            return ['code' => 109, 'msg' => '此专家不存在'];
        }
        $expertData['expert_source_name'] = $expertSource[$expertData['expert_source']];
        if (array_key_exists('attent_status', $expertData)) {
            if (empty($expertData['attent_status'])) {
                $expertData['attent_status'] = 2;
            } else {
                $expertData['attent_status'] = $expertData['attent_status'];
            }
        } else {
            $expertData['attent_status'] = 2;
        }
        $dayRed = [];
        $dayRed[2] = ['nums' => $expertData['two_red_nums'], 'pro' => (floatval($expertData['two_red_nums']) / 2)];
        $dayRed[3] = ['nums' => $expertData['three_red_nums'], 'pro' => (floatval($expertData['three_red_nums']) / 3)];
        $dayRed[5] = ['nums' => $expertData['five_red_nums'], 'pro' => (floatval($expertData['five_red_nums']) / 5)];
        $dayRed[7] = ['nums' => $expertData['day_red_nums'], 'pro' => (floatval($expertData['day_red_nums']) / 7)];
        $tmpe = 0;
        foreach ($dayRed as $k => $v) {
            if (round($v['pro'], 2) >= $tmpe) {
                $tmpe = $v['pro'];
                $nTmpe = $v['nums'];
                $kTmpe = $k;
            }
        }
        if ($tmpe < 0.5) {
            $nTmpe = 0;
            $kTmpe = 0;
        }
        $expertData['day_red_nums'] = $nTmpe;
        $expertData['day_nums'] = $kTmpe;
        $expertData['expert_status_name'] = $expertStatus[$expertData['expert_status']];
        $expertData['lottery'] = $lottery[$expertData['lottery']];
        $expertData['identity_type'] = $expertData['identity'];
        $expertData['identity'] = $indentity[$expertData['identity']];
        return $expertData;
    }

    /**
     * 获取赛程方案
     * @param type $mid
     * @param type $page
     * @param type $size
     * @return type
     */
    public function getScheduleArticles($mid, $page, $size, $payType = '', $scheduleType) {
        $payTypeName = Constants::ARTICLES_PAY;
        $expertSource = Constants::EXPERT_TYPE_SOURCE;
        $articlesResult = Constants::ARTICLES_RESULT;
        if (!empty($payType)) {
            $where['ea.pay_type'] = 1;
        }
        $where['articles_periods.periods'] = $mid;
        if (!empty($payType)) {
            $where['ea.pay_type'] = 1;
        }
        $where['ea.article_type'] = $scheduleType;
        $total = ArticlesPeriods::find()->innerJoin('expert_articles as ea', 'ea.expert_articles_id = articles_periods.articles_id and ea.article_status = 3')->where($where)->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $field = ['articles_periods.lottery_code', 'e.expert_source', 'articles_periods.pre_result', 'articles_periods.pre_odds', 'ea.expert_articles_id', 'ea.article_title', 'ea.pay_type', 'ea.pay_money', 'articles_periods.status as pre_status',
            'e.expert_status', 'u.user_name', 'u.user_pic', 'articles_periods.schedule_code', 'articles_periods.visit_short_name', 'articles_periods.home_short_name', 'articles_periods.rq_nums', 'ea.create_time',
            'articles_periods.start_time', 'articles_periods.league_short_name', 'articles_periods.home_team_rank', 'articles_periods.visit_team_rank', 'articles_periods.home_team_img', 'articles_periods.visit_team_img', 'ea.articles_code',
            'articles_periods.featured', 'e.user_id as expert_id', 'ea.buy_back', 'ea.buy_nums', 'ea.read_nums', 'ea.result_status', 'articles_periods.endsale_time', 's.schedule_status', 'articles_periods.fen_cutoff'];
        if ($scheduleType == 1) {
            array_push($field, 'e.even_red_nums', 'e.month_red_nums', 'e.day_red_nums', 'e.five_red_nums', 'e.two_red_nums', 'e.three_red_nums', 'sr.status', 'sr.schedule_result_3007 bf_result', 'e.day_nums', 'sr.schedule_result_sbbf sbcbf_result', 'sr.schedule_result_3010 sf_result', 'sr.schedule_result_3006 rqsf_result');
        } else {
            array_push($field, 'e.lan_even_red_nums even_red_nums', 'e.lan_month_red_nums month_red_nums', 'e.lan_day_red_nums day_red_nums', 'e.lan_five_red_nums five_red_nums', 'e.lan_two_red_nums two_red_nums', 'e.lan_three_red_nums three_red_nums', 'sr.result_status status', 'sr.result_qcbf bf_result', 'e.lan_day_nums day_nums', 'sr.result_zcbf sbcbf_result', 'sr.result_3001 sf_result', 'sr.result_3002 rqsf_result');
        }
        $articleQuery = ArticlesPeriods::find()->select($field)
                ->innerJoin('expert_articles as ea', 'ea.expert_articles_id = articles_periods.articles_id and ea.article_status = 3')
                ->leftJoin('expert as e', 'e.user_id = ea.user_id')
                ->leftJoin('user as u', 'u.user_id = e.user_id');
        if ($scheduleType == 1) {
            $articleQuery = $articleQuery->leftJoin('schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('schedule as s', 's.schedule_mid = articles_periods.periods');
        } else {
            $articleQuery = $articleQuery->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('lan_schedule as s', 's.schedule_mid = articles_periods.periods');
        }
        $articleData = $articleQuery->where($where)
                ->offset($offset)
                ->limit($size)
                ->asArray()
                ->all();
        $list = [];
        $dayRed = [];
        $redisKey = "article_read_total";
        $redis = \Yii::$app->redis;
        foreach ($articleData as &$val) {
            $val['pre_result'] = explode(',', $val['pre_result']);
            $val['pre_odds'] = explode(',', $val['pre_odds']);
            if ($val['featured'] != 2) {
                $trans = array_flip($val['pre_result']);
                $key = $trans[$val['featured']];
                $val['profit'] = $val['pre_odds'][$key];
            } else {
                $val['profit'] = $val['pre_odds'][0];
            }

            $artId = $val['expert_articles_id'];
            if (array_key_exists($artId, $list)) {
                if (array_key_exists($val['periods'], $list[$artId]['pre_concent'])) {
                    $list[$artId]['pre_concent'][$val['periods']]['pre_lottery'][] = ['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured'], 'profit' => $val['profit']];
                } else {
                    $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                        'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                        'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                        'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                        'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                        'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured'], 'profit' => $val['profit']]]];
                }
            } else {
                $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                    'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                    'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                    'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                    'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                    'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured'], 'profit' => $val['profit']]]];
                $list[$artId]['expert_articles_id'] = $val['expert_articles_id'];
                $list[$artId]['expert_source_name'] = $expertSource[$val['expert_source']];
                $list[$artId]['pay_type'] = $val['pay_type'];
                $list[$artId]['pay_type_name'] = $payTypeName[$val['pay_type']];
                $list[$artId]['pay_money'] = $val['pay_money'];
                $list[$artId]['buy_nums'] = $val['buy_nums'];
                $list[$artId]['even_red_nums'] = $val['even_red_nums'];
                $list[$artId]['month_red_nums'] = $val['month_red_nums'];
                $list[$artId]['day_red_nums'] = $val['day_red_nums'];
                $list[$artId]['user_name'] = $val['user_name'];
                $list[$artId]['user_pic'] = $val['user_pic'];
                $list[$artId]['article_title'] = $val['article_title'];
                $list[$artId]['day_nums'] = $val['day_nums'];
                $list[$artId]['create_time'] = $val['create_time'];
                $list[$artId]['expert_id'] = $val['expert_id'];
                $list[$artId]['buy_back'] = $val['buy_back'];
                $list[$artId]['articles_code'] = $val['articles_code'];
                $list[$artId]['result_status'] = $val['result_status'];
                $list[$artId]['result_status_name'] = $articlesResult[$val['result_status']];
                $nowRedis = $redis->zscore($redisKey, $artId);
                $list[$artId]['read_nums'] = $val['read_nums'] + $nowRedis;
                $dayRed[2] = ['nums' => $val['two_red_nums'], 'pro' => floatval($val['two_red_nums']) / 2];
                $dayRed[3] = ['nums' => $val['three_red_nums'], 'pro' => floatval($val['three_red_nums']) / 3];
                $dayRed[5] = ['nums' => $val['five_red_nums'], 'pro' => floatval($val['five_red_nums']) / 5];
                $dayRed[7] = ['nums' => $val['day_red_nums'], 'pro' => floatval($val['day_red_nums']) / 7];
                $tmpe = 0;
                foreach ($dayRed as $k => $v) {
                    if (round($v['pro'], 2) >= $tmpe) {
                        $tmpe = $v['pro'];
                        $nTmpe = $v['nums'];
                        $kTmpe = $k;
                    }
                }
                if ($tmpe < 0.5) {
                    $nTmpe = 0;
                    $kTmpe = 0;
                }
                $list[$artId]['day_red_nums'] = $nTmpe;
                $list[$artId]['day_nums'] = $kTmpe;
            }
        }
        $artList = [];
        foreach ($list as &$vl) {
            $vl['pre_concent'] = array_values($vl['pre_concent']);
            $preProfit = 1;
            foreach ($vl['pre_concent'] as $it) {
                if ($vl['pay_type'] == 2) {
                    unset($it['pre_lottery']);
                    $preProfit = 0;
                } else {
                    foreach ($it['pre_lottery'] as $ii) {
                        $preProfit *= $ii['profit'];
                    }
                }
            }
            $vl['pre_profit'] = bcmul($preProfit, 100, 2);
            $artList[] = $vl;
        }
        return ['page' => $page, 'size' => count($articleData), 'pages' => $pages, 'total' => $total, 'data' => $artList];
    }

    /**
     * 会员关注专家
     * @auther GL zyl
     * @param type $userId
     * @param type $expertId
     * @return type
     */
    public function attentExpert($userId, $expertId) {
        $expert = Expert::findOne(['user_id' => $expertId]);
        if (empty($expert)) {
            return ['code' => 109, 'msg' => '此专家不存在'];
        }
        $userExpert = UserExpert::findOne(['user_id' => $userId, 'expert_id' => $expertId]);
        if (empty($userExpert)) {
            $userExpert = new UserExpert;
        }
        $format = date('Y-m-d H:i:s');
        $userExpert->user_id = $userId;
        $userExpert->expert_id = $expertId;
        $userExpert->status = 1;
        $userExpert->create_time = $format;
        if (!$userExpert->validate()) {
            return ['code' => 109, 'msg' => '关注失败,数据验证失败'];
        }
        if (!$userExpert->save()) {
            return ['code' => 109, 'msg' => '关注失败,数据写入失败'];
        }
        $db = Yii::$app->db;
        $up = "update expert set fans_nums = fans_nums + 1 , modify_time = '" . $format . "' where user_id = {$expertId};";
        $upData = $db->createCommand($up)->execute();
        if ($upData == false) {
            return ['code' => 109, 'msg' => '关注失败,专家更新失败'];
        }
        return ['code' => 600, 'msg' => '关注成功'];
    }

    /**
     * 会员取消关注专家
     * @auther GL zyl
     * @param type $userId
     * @param type $expertId
     * @return type
     */
    public function cancelAttentExpert($userId, $expertId) {
        $userExpert = UserExpert::findOne(['user_id' => $userId, 'expert_id' => $expertId]);
        if (empty($userExpert)) {
            return ['code' => 109, 'msg' => '该会员并未关注此专家'];
        }
        $format = date('Y-m-d H:i:s');
        $userExpert->status = 2;
        $userExpert->modify_time = $format;
        if (!$userExpert->validate()) {
            return ['code' => 109, 'msg' => '取消关注失败,数据验证失败'];
        }
        if (!$userExpert->save()) {
            return ['code' => 109, 'msg' => '取消关注失败,数据写入失败'];
        }
        $db = Yii::$app->db;
        $up = "update expert set fans_nums = fans_nums - 1 , modify_time = '" . $format . "' where user_id = {$expertId};";
        $upData = $db->createCommand($up)->execute();
        if ($upData == false) {
            return ['code' => 109, 'msg' => '取消关注失败,专家更新失败'];
        }
        return ['code' => 600, 'msg' => '取消关注成功'];
    }

    /**
     * 获取关注列表
     * @param type $userId
     * @param type $page
     * @param type $size
     * @return type
     */
    public function getAttentList($userId, $page, $size) {
        $total = UserExpert::find()->where(['user_id' => $userId, 'status' => 1])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $field = ['e.user_id as expert_id', 'e.introduction', 'e.article_nums', 'e.fans_nums', 'e.read_nums', 'e.even_red_nums', 'e.identity',
            'e.month_red_nums', 'e.day_red_nums', 'e.expert_status', 'u.user_name', 'u.user_pic', 'e.day_nums', 'user_expert.status', 'e.lan_article_nums', 'e.lan_read_nums', 'e.lan_even_red_nums',
            'e.lan_month_red_nums', 'e.lan_day_red_nums', 'e.lan_day_nums'];
        $list = UserExpert::find()->select($field)
                ->leftJoin('expert as e', 'e.user_id = user_expert.expert_id')
                ->leftJoin('user as u', 'u.user_id = user_expert.expert_id')
                ->where(['user_expert.user_id' => $userId, 'user_expert.status' => 1])
                ->offset($offset)
                ->limit($size)
                ->orderBy('user_expert.create_time desc')
                ->asArray()
                ->all();
        return ['page' => $page, 'size' => count($list), 'pages' => $pages, 'total' => $total, 'data' => $list];
    }

    /**
     * 文章下单购买
     * @param type $orderData
     * @param type $userId
     * @param type $custNo
     * @return type
     */
    public function BuyArticle($orderData, $userId, $custNo) {
        //计算是否有折扣
        $discountData = [];
        $post = Yii::$app->request->post();
        $payWay = isset($post['pay_way']) ? $post['pay_way'] : 0;
        $postDiscountData = isset($post['discount_data']) ? json_decode($post['discount_data'], true) : '';
        if ($postDiscountData) {
            $coin = isset($postDiscountData['coin']) ? $postDiscountData['coin'] : '';
            $coupons = isset($postDiscountData['coupons']) ? $postDiscountData['coupons'] : [];
            $discount = OrderService::getDiscount($custNo, 100, $orderData['total'], $coin, $coupons, 1, $payWay);
            if ($discount['code'] != 600) {
                return ["code" => $discount['code'], "msg" => $discount['msg']];
            }
            $discountData = array_filter($discount['data']);
        }
        $articleId = $orderData['expert_articles_id'];
        $article = ExpertArticles::find()->select(['expert_articles_id', 'pay_money'])->where(['expert_articles_id' => $articleId, 'article_status' => 3])->asArray()->one();
        if (empty($article)) {
            return ['code' => 2, 'msg' => '此篇文章无法购买'];
        }
        if ($orderData['total'] != $article['pay_money']) {
            return ['code' => 2, 'msg' => '购买金额不对'];
        }
        $realPayMoney = $orderData['total'];
        if ($discountData) { // 有折扣
            $realPayMoney = $orderData['total'] - $discountData['discount'];
            if ($realPayMoney < 0) {
                return ["code" => 120, "msg" => '折扣金额超过订单金额'];
            }
        }
        $article_code = Commonfun::getCode('ART', 'P');
        // 使用掉优惠券
        if ($discountData) {
            $res = OrderService::useDiscount($userId, $custNo, $article_code, $discountData['parms'], 1);
            if ($res['code'] != 600) {
                return ['code' => 2, 'msg' => $res['msg']];
            }
        }
        $userArticle = new UserArticle;
        $userArticle->user_article_code = $article_code;
        $userArticle->user_id = $userId;
        $userArticle->article_id = $articleId;
        $userArticle->status = 0;
        $userArticle->create_time = date('Y-m-d H:i:s');
        if (!$userArticle->validate()) {
            return ['code' => 2, 'msg' => '数据验证失败'];
        }
        if (!$userArticle->save()) {
            return ['code' => 2, 'msg' => '数据保存失败'];
        }
        $payService = new PayService();
        $payService->productPayRecord($custNo, $userArticle->user_article_code, 17, 1, $realPayMoney, 10, $userId, $discountData);
        $data['lottery_order_code'] = $userArticle->user_article_code;
        return ['code' => 600, 'msg' => '下单成功', "result" => $data];
    }

    /**
     * 购买回调更新
     * @param type $orderCode
     * @param type $outerNo
     * @param type $totalAmount
     * @param type $payTime
     * @return boolean
     */
    public function articleNotify($orderCode, $outerNo, $totalAmount, $payTime) {
//        $tran = Yii::$app->db->beginTransaction();
        $db = Yii::$app->db;
        $format = date('Y-m-d H:i:s');
//        try {
        $userArticle = UserArticle::findOne(['user_article_code' => $orderCode, 'status' => 0]);
        if (!empty($userArticle)) {
            $userData = User::find()->select(['user.cust_no', 'uf.all_funds', 'user.user_name', 'user.user_tel'])
                    ->leftJoin('user_funds as uf', 'uf.cust_no = user.cust_no')
                    ->where(['user.user_id' => $userArticle->user_id])
                    ->asArray()
                    ->one();
            $fiele = ['status' => 1, 'outer_no' => $outerNo, 'modify_time' => $format, 'pay_time' => $payTime, 'pay_money' => $totalAmount, 'balance' => $userData['all_funds']];
            $ret = $db->createCommand()->update('pay_record', $fiele, ['order_code' => $orderCode, 'pay_type' => 17])->execute();
            if ($ret === false) {
                return ['code' => 109, 'msg' => '数据更新失败'];
            }

//                $expertData = ExpertArticles::find()->select(['e.user_id', 'e.cust_no', 'u.user_name', 'expert_articles.buy_back', 'expert_articles.pay_money'])
//                        ->innerJoin('expert as e', 'e.user_id = expert_articles.user_id')
//                        ->innerJoin('user as u', 'u.user_id = e.user_id')
//                        ->where(['expert_articles.expert_articles_id' => $userArticle->article_id])
//                        ->asArray()
//                        ->one();
//                $total = $expertData['pay_money'];
//                if ($expertData['buy_back'] == 0) {
//                    $funds = new FundsService();
//                    $retExpert = $funds->operateUserFunds($expertData['cust_no'], $total, $total, 0, false, '文章收款');
//                    if ($retExpert['code'] != 0) {
//                        return ["code" => 109, "msg" => $retExpert["msg"]];
//                    }
//                    $expertFunds = UserFunds::find()->select("all_funds")->from("user_funds")->where(["cust_no" => $expertData['cust_no']])->one();
//                    $payRecord = new PayRecord();
//                    $payRecord->order_code = $orderCode;
//                    $payRecord->pay_no = Commonfun::getCode("PAY", "A");
//                    $payRecord->outer_no = Commonfun::getCode("DT", "SK");
//                    $payRecord->user_id = $expertData['user_id'];
//                    $payRecord->cust_no = $expertData['cust_no'];
//                    $payRecord->cust_type = 2;
//                    $payRecord->user_name = $expertData['user_name'];
//                    $payRecord->pay_pre_money = $total;
//                    $payRecord->pay_money = $total;
//                    $payRecord->pay_name = '余额';
//                    $payRecord->way_name = '余额';
//                    $payRecord->way_type = 'YE';
//                    $payRecord->pay_way = 3;
//                    $payRecord->pay_type_name = '方案-收款';
//                    $payRecord->pay_type = 18;
//                    $payRecord->balance = $expertFunds["all_funds"];
//                    $payRecord->body = '收费方案-收款';
//                    $payRecord->status = 1;
//                    $payRecord->pay_time = date('Y-m-d H:i:s');
//                    $payRecord->modify_time = date('Y-m-d H:i:s');
//                    $payRecord->create_time = date('Y-m-d H:i:s');
//                    if (!$payRecord->validate()) {
//                        return ["code" => 109, "msg" => json_encode($payRecord->getFirstErrors(), true)];
//                    }
//                    if (!$payRecord->save()) {
//                        return ["code" => 109, "msg" => json_encode($payRecord->getFirstErrors(), true)];
//                    }
//                    $userArticle->status = 4;
//                } else {
//                    $userArticle->status = 1;
//                }
            $userArticle->status = 1;
            $userArticle->modify_time = $format;
            if (!$userArticle->save()) {
                return ['code' => 109, 'msg' => json_encode($userArticle->getFirstErrors(), true)];
            }
            $updata = "update expert_articles set buy_nums = buy_nums + 1, modify_time = '" . $format . "' where  expert_articles_id = {$userArticle->article_id}; ";
            $upArt = $db->createCommand($updata)->execute();
            if ($upArt == false) {
                return ['code' => 109, 'msg' => '文章购买量基数出错'];
            }
            return true;
        }
//            $tran->commit();
//        } catch (\yii\db\Exception $e) {
//            return ['code' => 109, 'msg' => json_encode($e, true)];
//        }
    }

    /**
     * 计算阅读量
     * @auther GL zyl
     * @param type $articleId
     */
//    public function cashReadNums($articleId) {
//        $format = date('Y-m-d H:i:s');
//        $updata = '';
//        $updata .= "update expert_articles set read_nums = read_nums + 1, modify_time = '" . $format . "' where  expert_articles_id = {$articleId};";
//        $expertReadNums = ExpertArticles::find()->select('user_id')->where(['expert_articles_id' => $articleId])->asArray()->one();
//        $updata .= "update expert set read_nums = read_nums + 1, modify_time = '" . $format . "' where  user_id = {$expertReadNums['user_id']};";
//        $upArt = Yii::$app->db->createCommand($updata)->execute();
//        if ($upArt === false) {
//            return false;
//        }
//        
//        $key = 'expert_readnums:' . $expertReadNums['user_id'];
//        $redis = \Yii::$app->redis;
//        $redis->incr($key);
//        $redis->executeCommand('expire', [$key, 25200]);
//        return true;
//    }

    /**
     * 说明:文章阅读量统计（每天）
     * @author chenqiwei
     * @date 2018/2/24 下午5:29
     * @param  int $articleId  文章id
     * @return
     */
    public function addReadNums($articleId) {
        $key = "article_read_total";
        $redis = \Yii::$app->redis;
        $ret = $redis->zincrby($key, 1, $articleId);
        return $ret;
    }

    /**
     * C端获取文章列表
     * @param type $page
     * @param type $size
     * @param type $start
     * @param type $end
     * @param type $artStatus
     * @param type $payType
     * @param type $title
     * @param type $expertId
     * @return type
     */
    public function getBuyArticlesList($page, $size, $userId, $preType) {
        $articlesResult = Constants::ARTICLES_RESULT;
        $articlesStatus = Constants::ARTICLES_STATUS;
        $payTypeName = Constants::ARTICLES_PAY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $buyStatus = Constants::ARTICLE_BUY_STATUS;
        $expertSource = Constants::EXPERT_TYPE_SOURCE;
        $total = UserArticle::find()->innerJoin('expert_articles e')->where(['user_article.user_id' => $userId, 'e.article_type' => $preType])->andWhere(['!=', 'status', 0])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $field = ['user_article.user_article_code', 'user_article.status as buy_status', 'user_article.create_time as buy_time', 'ea.expert_articles_id', 'ea.article_type', 'ea.article_title', 'ea.pay_type',
            'ea.pay_money', 'ea.article_status', 'ea.result_status', 'e.fans_nums', 'u.user_name', 'u.user_pic', 'ea.create_time', 'e.user_id as expert_id', 'ea.buy_back', 'e.expert_source', 'ea.buy_nums', 'ea.read_nums'];
        if ($preType == 1) {
            array_push($field, 'e.article_nums', 'e.even_red_nums', 'e.month_red_nums', 'e.day_red_nums', 'e.day_nums', 'e.two_red_nums', 'e.three_red_nums', 'e.five_red_nums');
        } else {
            array_push($field, 'e.lan_article_nums article_nums', 'e.lan_even_red_nums even_red_nums', 'e.lan_month_red_nums month_red_nums', 'e.lan_day_red_nums day_red_nums', 'e.lan_day_nums day_nums', 'e.lan_two_red_nums two_red_nums', 'e.lan_three_red_nums three_red_nums', 'e.lan_five_red_nums five_red_nums');
        }

        $field2 = ['articles_periods.articles_id', 'articles_periods.periods', 'articles_periods.lottery_code', 'articles_periods.pre_result', 'articles_periods.pre_odds', 'articles_periods.schedule_code', 'articles_periods.visit_short_name',
            'articles_periods.home_short_name', 'articles_periods.rq_nums', 'articles_periods.start_time', 'articles_periods.league_short_name', 'articles_periods.home_team_rank', 'articles_periods.visit_team_rank',
            'articles_periods.home_team_img', 'articles_periods.visit_team_img', 'articles_periods.status as pre_status', 'articles_periods.featured', 'articles_periods.endsale_time', 'articles_periods.fen_cutoff'];
        if ($preType == 1) {
            array_push($field2, 'sr.status', 'sr.schedule_result_3007 bf_result', 'sr.schedule_result_3010 sf_result', 'sr.schedule_result_3006 rfsf_result', 'sr.schedule_result_sbbf sbcbf_result', 's.schedule_status');
        } else {
            array_push($field2, 'sr.result_status status', 'sr.result_3001 sf_result', 'sr.result_3002 rfsf_result', 'sr.result_qcbf bf_result', 'sr.result_zcbf sbcbf_result', 's.schedule_status');
        }
        $artData = UserArticle::find()->select($field)
                ->leftJoin('expert_articles as ea', 'ea.expert_articles_id = user_article.article_id')
                ->leftJoin('expert as e', 'e.user_id = ea.user_id')
                ->leftJoin('user as u', 'u.user_id = e.user_id')
                ->where(['user_article.user_id' => $userId, 'ea.article_type' => $preType])
                ->andWhere(['!=', 'user_article.status', 0])
                ->indexBy('expert_articles_id')
                ->offset($offset)
                ->limit($size)
                ->orderBy('user_article.user_article_id desc,ea.result_status')
                ->asArray()
                ->all();
        $artIdArr = array_keys($artData);
        $scheQuery = ArticlesPeriods::find()->select($field2);
        if ($preType == 1) {
            $scheQuery = $scheQuery->leftJoin('schedule_result sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('schedule s', 's.schedule_mid = articles_periods.periods');
        } else {
            $scheQuery = $scheQuery->leftJoin('lan_schedule_result sr', 'sr.schedule_mid = articles_periods.periods')
                    ->leftJoin('lan_schedule s', 's.schedule_mid = articles_periods.periods');
        }
        $scheData = $scheQuery->where(['in', 'articles_id', $artIdArr])
                ->orderBy('articles_id desc, periods')
                ->asArray()
                ->all();
        $list = [];
        $dayRed = [];
        $redisKey = "article_read_total";
        $redis = \Yii::$app->redis;
        foreach ($scheData as &$val) {
            $val['dx_result'] = 0;
            if (in_array($val['lottery_code'], $football) || in_array($val['lottery_code'], $basketball)) {
                $val['pre_result'] = explode(',', $val['pre_result']);
                $val['pre_odds'] = explode(',', $val['pre_odds']);
                if ($val['featured'] != 2) {
                    $trans = array_flip($val['pre_result']);
                    $key = $trans[$val['featured']];
                    $val['profit'] = $val['pre_odds'][$key];
                } else {
                    $val['profit'] = $val['pre_odds'][0];
                }
                $bfArr = explode(':', $val['bf_result']);
                if (in_array($val['lottery_code'], $basketball)) {
                    if ($val['status'] == 2) {
                        $val['dx_result'] = bccomp(bcadd($bfArr[0], $bfArr[1]), $val['fen_cutoff'], 2) == 1 ? 1 : 2;
                        $val['sf_result'] = bccomp($bfArr[1], $bfArr[0]) == 1 ? 3 : 0;
                        $val['rfsf_result'] = bccomp(bcadd($bfArr[1], $val['rq_nums']), $bfArr[0], 1) == 1 ? 3 : 0;
                    }
                }
            } else {
                $val['pre_result'][] = $val['pre_result'];
                $val['pre_odds'][] = [];
                $val['profit'] = 1;
            }

            $artId = $val['articles_id'];
            if (array_key_exists($artId, $list)) {
                if (array_key_exists($val['periods'], $list[$artId]['pre_concent'])) {
                    $list[$artId]['pre_concent'][$val['periods']]['pre_lottery'][] = ['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured'], 'profit' => $val['profit']];
                } else {
                    $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                        'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                        'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                        'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                        'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                        'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured'], 'profit' => $val['profit']]]];
                }
            } else {
                $list[$artId]['pre_concent'][$val['periods']] = ['periods' => $val['periods'], 'visit_short_name' => $val['visit_short_name'], 'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'],
                    'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['bf_result'],
                    'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'], 'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'],
                    'visit_team_img' => $val['visit_team_img'], 'schedule_result_sbbf' => $val['sbcbf_result'], 'schedule_result' => $val['sf_result'], 'schedule_result_rqbf' => $val['rfsf_result'],
                    'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status'], 'fen_cutoff' => $val['fen_cutoff'], 'schedule_result_dxf' => $val['dx_result'],
                    'pre_lottery' => [['lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'], 'odds' => $val['odds'], 'featured' => $val['featured'], 'profit' => $val['profit']]]];
                $list[$artId]['expert_articles_id'] = $artData[$artId]['expert_articles_id'];
                $list[$artId]['article_type'] = $artData[$artId]['article_type'];
                $list[$artId]['pay_type'] = $artData[$artId]['pay_type'];
                $list[$artId]['article_status'] = $artData[$artId]['article_status'];
                $list[$artId]['result_status'] = $artData[$artId]['result_status'];
                $list[$artId]['pay_type_name'] = $payTypeName[$artData[$artId]['pay_type']];
                $list[$artId]['article_status_name'] = $articlesStatus[$artData[$artId]['article_status']];
                $list[$artId]['result_status_name'] = $articlesResult[$artData[$artId]['result_status']];
                $list[$artId]['pay_money'] = $artData[$artId]['pay_money'];
                $list[$artId]['buy_nums'] = $artData[$artId]['buy_nums'];
                $nowRedis = $redis->zscore($redisKey, $artId);
                $list[$artId]['read_nums'] = $artData[$artId]['read_nums'] + $nowRedis;
                $list[$artId]['article_nums'] = $artData[$artId]['article_nums'];
                $list[$artId]['fans_nums'] = $artData[$artId]['fans_nums'];
                $list[$artId]['even_red_nums'] = $artData[$artId]['even_red_nums'];
                $list[$artId]['month_red_nums'] = $artData[$artId]['month_red_nums'];
                $list[$artId]['day_red_nums'] = $artData[$artId]['day_red_nums'];
                $list[$artId]['user_name'] = $artData[$artId]['user_name'];
                $list[$artId]['user_pic'] = $artData[$artId]['user_pic'];
                $list[$artId]['article_title'] = $artData[$artId]['article_title'];
                $list[$artId]['day_nums'] = $artData[$artId]['day_nums'];
                $list[$artId]['create_time'] = $artData[$artId]['create_time'];
                $list[$artId]['expert_id'] = $artData[$artId]['expert_id'];
                $list[$artId]['buy_back'] = $artData[$artId]['buy_back'];
                $list[$artId]['user_article_code'] = $artData[$artId]['user_article_code'];
                $list[$artId]['buy_status'] = $artData[$artId]['buy_status'];
                $list[$artId]['buy_time'] = $artData[$artId]['buy_time'];
                $list[$artId]['buy_status_name'] = $buyStatus[$artData[$artId]['buy_status']];
                $list[$artId]['expert_source'] = $artData[$artId]['expert_source'];
                $list[$artId]['expert_source_name'] = $expertSource[$artData[$artId]['expert_source']];
                $dayRed[2] = ['nums' => $artData[$artId]['two_red_nums'], 'pro' => floatval($artData[$artId]['two_red_nums']) / 2];
                $dayRed[3] = ['nums' => $artData[$artId]['three_red_nums'], 'pro' => floatval($artData[$artId]['three_red_nums']) / 3];
                $dayRed[5] = ['nums' => $artData[$artId]['five_red_nums'], 'pro' => floatval($artData[$artId]['five_red_nums']) / 5];
                $dayRed[7] = ['nums' => $artData[$artId]['day_red_nums'], 'pro' => floatval($artData[$artId]['day_red_nums']) / 7];
                $tmpe = 0;
                foreach ($dayRed as $k => $v) {
                    if (round($v['pro'], 2) >= $tmpe) {
                        $tmpe = $v['pro'];
                        $nTmpe = $v['nums'];
                        $kTmpe = $k;
                    }
                }
                if ($tmpe < 0.5) {
                    $nTmpe = 0;
                    $kTmpe = 0;
                }
                $list[$artId]['day_red_nums'] = $nTmpe;
                $list[$artId]['day_nums'] = $kTmpe;
            }
        }
        $artList = [];
        foreach ($list as &$vl) {
            $vl['pre_concent'] = array_values($vl['pre_concent']);
            $preProfit = 1;
            if ($vl['article_type'] == 1 || $vl['article_type'] == 2) {
                foreach ($vl['pre_concent'] as $it) {
                    foreach ($it['pre_lottery'] as $ii) {
                        $preProfit *= $ii['profit'];
                    }
                }
            } else {
                $preProfit = 1;
            }
            $vl['pre_profit'] = bcmul($preProfit, 100, 2);
            $artList[] = $vl;
        }
        return ['page' => $page, 'pages' => $pages, 'size' => count($artList), 'total' => $total, 'data' => $artList];
    }

    /**
     * 删除文章
     * @param type $expertId
     * @param type $articleId
     * @return type
     */
    public function deleteArticle($articleId, $expertId) {
        $inData = ['in', 'article_status', [1, 5]];
        $expertArt = ExpertArticles::findOne(['expert_articles_id' => $articleId, 'user_id' => $expertId, $inData]);
        if (empty($expertArt)) {
            return ['code' => 109, 'msg' => '此文章无法删除'];
        }
        if (!$expertArt->delete()) {
            return ['code' => 109, 'msg' => '删除失败'];
        }
        ArticlesPeriods::deleteAll(['articles_id' => $articleId]);
        return ['code' => 600, 'msg' => '删除成功'];
    }

    //获取专家战绩
    public function get_expert_res($userId, $type) {
        $query = new Query();
        $fields = 'ap.articles_periods_id, ap.articles_id, ap.lottery_code, ap.pre_result, ap.pre_odds, ap.featured, a.user_id, a.expert_articles_id, ap.status, a.create_time, a.result_status';
        if ($type == 1) {
            $field2 = 'article_nums, read_nums, even_red_nums, month_red_nums, day_red_nums, day_nums, five_red_nums, two_red_nums, three_red_nums';
        } else {
            $field2 = 'lan_article_nums article_nums, lan_read_nums read_nums, lan_even_red_nums even_red_nums, lan_month_red_nums month_red_nums, lan_day_red_nums day_red_nums, lan_day_nums day_nums, lan_five_red_nums five_red_nums, lan_two_red_nums two_red_nums, lan_three_red_nums three_red_nums';
        }
        $data = $query->from('expert_articles a')->select($fields)
                ->innerJoin('articles_periods ap', ' a.expert_articles_id = ap.articles_id')
                ->where(['a.user_id' => $userId, 'a.article_status' => 3])
                ->andWhere(['in', 'ap.status', [2, 3]])
                ->andWhere(['=', 'a.article_type', $type])
//            ->andWhere(['>', 'a.create_time', $date])
                ->orderBy('a.expert_articles_id desc')
                ->all();
        $res = [];
        if ($type == 1) {//足球
            $res['hit'] = $this->get_expert_hit($data);       //命中数据
            $res['rate'] = $this->get_expert_rate($data);      //盈利数据
        } elseif ($type == 2) {//篮球
            $res['hit'] = $this->get_lanexpert_hit($data);       //命中数据
            $res['rate'] = $this->get_lanexpert_rate($data);      //盈利数据
        }
        $res['expert_info'] = Expert::find()->select($field2)->where(['user_id' => $userId])->asArray()->one();
//        $res['profit'] = $this -> get_expert_profit($data); //盈利数据
        return $res;
    }

    //获取专家 命中率
    public function get_expert_hit($data) {
        if (!$data) {
            return [
                'total' => [
                    "total_3_total" => 0,
                    "total_3_hit" => 0,
                    "total_3_rate" => 0,
                    "total_7_total" => 0,
                    "total_7_hit" => 0,
                    "total_7_rate" => 0,
                    "total_30_total" => 0,
                    "total_30_hit" => 0,
                    "total_30_rate" => 0,
                    "rate_total" => 0
                ],
                '3006' => [
                    "3006_3_total" => 0,
                    "3006_3_hit" => 0,
                    "3006_3_rate" => 0,
                    "3006_7_total" => 0,
                    "3006_7_hit" => 0,
                    "3006_7_rate" => 0,
                    "3006_30_total" => 0,
                    "3006_30_hit" => 0,
                    "3006_30_rate" => 0,
                    "rate_3006" => 0
                ],
                '3010' => [
                    "3010_3_total" => 0,
                    "3010_3_hit" => 0,
                    "3010_3_rate" => 0,
                    "3010_7_total" => 0,
                    "3010_7_hit" => 0,
                    "3010_7_rate" => 0,
                    "3010_30_total" => 0,
                    "3010_30_hit" => 0,
                    "3010_30_rate" => 0,
                    "rate_3010" => 0
                ]
            ];
        }
        $_3day = date('Y-m-d H:i:s', strtotime('-3 day'));
        $_7day = date('Y-m-d H:i:s', strtotime('-7 day'));
        $_30day = date('Y-m-d H:i:s', strtotime('-30 day'));
        $data_3010_3 = 0;
        $data_3010_3_ = 0;
        $data_3010_7 = 0;
        $data_3010_7_ = 0;
        $data_3010_30 = 0;
        $data_3010_30_ = 0;
        $totle_3010 = 0;
        $data_3010_hit = 0;
        $data_3010_total = 0;

        $data_3006_3 = 0;
        $data_3006_3_ = 0;
        $data_3006_7 = 0;
        $data_3006_7_ = 0;
        $data_3006_30 = 0;
        $data_3006_30_ = 0;
        $totle_3006 = 0;
        $data_3006_hit = 0;
        $data_3006_total = 0;

        $data_hit = 0; //全部命中数
        $data_total = 0; //全部场次

        foreach ($data as $v) {
            if ($v['lottery_code'] == '3010') {
                $odd_3010[] = $v;
                if ($v['create_time'] > $_3day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3010_3 += 1;
                    }
                    $data_3010_3_ += 1;
                }
                if ($v['create_time'] > $_7day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3010_7 += 1;
                    }
                    $data_3010_7_ += 1;
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3010_30 += 1;
                    }
                    $data_3010_30_ += 1;
                }
                if ($v['status'] == 2 && $v['result_status'] == 3) {
                    $data_3010_hit += 1;
                }
                $data_3010_total += 1;
            }
            if ($v['lottery_code'] == '3006') {
                if ($v['create_time'] > $_3day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3006_3 += 1;
                    }
                    $data_3006_3_ += 1;
                }
                if ($v['create_time'] > $_7day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3006_7 += 1;
                    }
                    $data_3006_7_ += 1;
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) { // 3006 30天
                        $data_3006_30 += 1;
                    }
                    $data_3006_30_ += 1;
                }
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //3006 全部
                    $data_3006_hit += 1;
                }
                $data_3006_total += 1;
            }

            if ($v['status'] == 2 && $v['result_status'] == 3) {
                $data_hit += 1;
            }
            $data_total += 1;
        }
        //3010 命中率计算规则： 命中数（status=2） + 场次数
        $data_3010['3010_3_total'] = $data_3010_3_;                                       //3日场次数 场次数 中 命中数
        $data_3010['3010_3_hit'] = $data_3010_3;                                          //3日命中数
        $data_3010['3010_3_rate'] = $this->compute_hit($data_3010_3, $data_3010_3_);    //3日命中率

        $data_3010['3010_7_total'] = $data_3010_7_;                                       //7日场次数
        $data_3010['3010_7_hit'] = $data_3010_7;                                          //7日命中数
        $data_3010['3010_7_rate'] = $this->compute_hit($data_3010_7, $data_3010_7_);    //7日命中率

        $data_3010['3010_30_total'] = $data_3010_30_;                                     //30日场次数
        $data_3010['3010_30_hit'] = $data_3010_30;                                        //30日命中数
        $data_3010['3010_30_rate'] = $this->compute_hit($data_3010_30, $data_3010_30_); //30日命中率
        $data_3010['rate_3010'] = $this->compute_hit($data_3010_hit, $data_3010_total); //3010命中率
        //3006
        $data_3006['3006_3_total'] = $data_3006_3_;                                       //3日场次数
        $data_3006['3006_3_hit'] = $data_3006_3;                                        //3日命中数
        $data_3006['3006_3_rate'] = $this->compute_hit($data_3006_3, $data_3006_3_);    //3日命中率

        $data_3006['3006_7_total'] = $data_3006_7_;                                       //7日场次数
        $data_3006['3006_7_hit'] = $data_3006_7;                                          //7日命中数
        $data_3006['3006_7_rate'] = $this->compute_hit($data_3006_7, $data_3006_7_);    //7日命中率

        $data_3006['3006_30_total'] = $data_3006_30_;                                        //30日场次数
        $data_3006['3006_30_hit'] = $data_3006_30;                                        //30日命中数
        $data_3006['3006_30_rate'] = $this->compute_hit($data_3006_30, $data_3006_30_);   //30日命中率
        $data_3006['rate_3006'] = $this->compute_hit($data_3006_hit, $data_3006_total); //3006命中率
        //总的数据
        $hit = ($data_3006_3 + $data_3010_3);
        $unhit = ($data_3006_3_ + $data_3010_3_);
        $date_total['total_3_total'] = $unhit;                                            //总的3日场次数
        $date_total['total_3_hit'] = $hit;                                                //总的3日命中数
        $date_total['total_3_rate'] = $this->compute_hit($hit, $unhit);                 //总的3日命中率

        $hit = ($data_3006_7 + $data_3010_7);
        $unhit = ($data_3006_7_ + $data_3010_7_);
        $date_total['total_7_total'] = $unhit;                                            //总的7日场次数
        $date_total['total_7_hit'] = $hit;                                                //总的7日命中数
        $date_total['total_7_rate'] = $this->compute_hit($hit, $unhit);                 //7日命中率

        $hit = ($data_3006_30 + $data_3010_30);
        $unhit = ($data_3006_30_ + $data_3010_30_);
        $date_total['total_30_total'] = $unhit;                                           //总的30日场次数
        $date_total['total_30_hit'] = $hit;                                               //总的30日命中数
        $date_total['total_30_rate'] = $this->compute_hit($hit, $unhit);               //30日命中率

        $date_total['rate_total'] = $this->compute_hit($data_hit, $data_total);        //总的比例
        return ['total' => $date_total, '3006' => $data_3006, '3010' => $data_3010];
    }

    //获取专家 命中率
    public function get_lanexpert_hit($data) {
        if (!$data) {
            return [
                'total' => [
                    "total_3_total" => 0,
                    "total_3_hit" => 0,
                    "total_3_rate" => 0,
                    "total_7_total" => 0,
                    "total_7_hit" => 0,
                    "total_7_rate" => 0,
                    "total_30_total" => 0,
                    "total_30_hit" => 0,
                    "total_30_rate" => 0,
                    "rate_total" => 0
                ],
                '3002' => [
                    "3002_3_total" => 0,
                    "3002_3_hit" => 0,
                    "3002_3_rate" => 0,
                    "3002_7_total" => 0,
                    "3002_7_hit" => 0,
                    "3002_7_rate" => 0,
                    "3002_30_total" => 0,
                    "3002_30_hit" => 0,
                    "3002_30_rate" => 0,
                    "rate_3002" => 0
                ],
                '3001' => [
                    "3001_3_total" => 0,
                    "3001_3_hit" => 0,
                    "3001_3_rate" => 0,
                    "3001_7_total" => 0,
                    "3001_7_hit" => 0,
                    "3001_7_rate" => 0,
                    "3001_30_total" => 0,
                    "3001_30_hit" => 0,
                    "3001_30_rate" => 0,
                    "rate_3001" => 0
                ],
                '3004' => [
                    "3004_3_total" => 0,
                    "3004_3_hit" => 0,
                    "3004_3_rate" => 0,
                    "3004_7_total" => 0,
                    "3004_7_hit" => 0,
                    "3004_7_rate" => 0,
                    "3004_30_total" => 0,
                    "3004_30_hit" => 0,
                    "3004_30_rate" => 0,
                    "rate_3004" => 0
                ]
            ];
        }
        $_3day = date('Y-m-d H:i:s', strtotime('-3 day'));
        $_7day = date('Y-m-d H:i:s', strtotime('-7 day'));
        $_30day = date('Y-m-d H:i:s', strtotime('-30 day'));
        $data_3001_3 = 0;
        $data_3001_3_ = 0;
        $data_3001_7 = 0;
        $data_3001_7_ = 0;
        $data_3001_30 = 0;
        $data_3001_30_ = 0;
        $totle_3001 = 0;
        $data_3001_hit = 0;
        $data_3001_total = 0;

        $data_3002_3 = 0;
        $data_3002_3_ = 0;
        $data_3002_7 = 0;
        $data_3002_7_ = 0;
        $data_3002_30 = 0;
        $data_3002_30_ = 0;
        $totle_3002 = 0;
        $data_3002_hit = 0;
        $data_3002_total = 0;

        $data_3004_3 = 0;
        $data_3004_3_ = 0;
        $data_3004_7 = 0;
        $data_3004_7_ = 0;
        $data_3004_30 = 0;
        $data_3004_30_ = 0;
        $totle_3004 = 0;
        $data_3004_hit = 0;
        $data_3004_total = 0;

        $data_hit = 0; //全部命中数
        $data_total = 0; //全部场次

        foreach ($data as $v) {
            if ($v['lottery_code'] == '3001') {
                $odd_3001[] = $v;
                if ($v['create_time'] > $_3day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3001_3 += 1;
                    }
                    $data_3001_3_ += 1;
                }
                if ($v['create_time'] > $_7day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3001_7 += 1;
                    }
                    $data_3001_7_ += 1;
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3001_30 += 1;
                    }
                    $data_3001_30_ += 1;
                }
                if ($v['status'] == 2 && $v['result_status'] == 3) {
                    $data_3001_hit += 1;
                }
                $data_3001_total += 1;
            }
            if ($v['lottery_code'] == '3002') {
                if ($v['create_time'] > $_3day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3002_3 += 1;
                    }
                    $data_3002_3_ += 1;
                }
                if ($v['create_time'] > $_7day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3002_7 += 1;
                    }
                    $data_3002_7_ += 1;
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) { // 3002 30天
                        $data_3002_30 += 1;
                    }
                    $data_3002_30_ += 1;
                }
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //3002 全部
                    $data_3002_hit += 1;
                }
                $data_3002_total += 1;
            }
            if ($v['lottery_code'] == '3004') {
                if ($v['create_time'] > $_3day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3004_3 += 1;
                    }
                    $data_3004_3_ += 1;
                }
                if ($v['create_time'] > $_7day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3004_7 += 1;
                    }
                    $data_3004_7_ += 1;
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) { // 3002 30天
                        $data_3004_30 += 1;
                    }
                    $data_3004_30_ += 1;
                }
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //3002 全部
                    $data_3004_hit += 1;
                }
                $data_3004_total += 1;
            }
            if ($v['status'] == 2 && $v['result_status'] == 3) {
                $data_hit += 1;
            }
            $data_total += 1;
        }
        //3001 命中率计算规则： 命中数（status=2） + 场次数
        $data_3001['3001_3_total'] = $data_3001_3_;                                       //3日场次数 场次数 中 命中数
        $data_3001['3001_3_hit'] = $data_3001_3;                                          //3日命中数
        $data_3001['3001_3_rate'] = $this->compute_hit($data_3001_3, $data_3001_3_);    //3日命中率

        $data_3001['3001_7_total'] = $data_3001_7_;                                       //7日场次数
        $data_3001['3001_7_hit'] = $data_3001_7;                                          //7日命中数
        $data_3001['3001_7_rate'] = $this->compute_hit($data_3001_7, $data_3001_7_);    //7日命中率

        $data_3001['3001_30_total'] = $data_3001_30_;                                     //30日场次数
        $data_3001['3001_30_hit'] = $data_3001_30;                                        //30日命中数
        $data_3001['3001_30_rate'] = $this->compute_hit($data_3001_30, $data_3001_30_); //30日命中率
        $data_3001['rate_3001'] = $this->compute_hit($data_3001_hit, $data_3001_total); //3001命中率
        //3002
        $data_3002['3002_3_total'] = $data_3002_3_;                                       //3日场次数
        $data_3002['3002_3_hit'] = $data_3002_3;                                        //3日命中数
        $data_3002['3002_3_rate'] = $this->compute_hit($data_3002_3, $data_3002_3_);    //3日命中率

        $data_3002['3002_7_total'] = $data_3002_7_;                                       //7日场次数
        $data_3002['3002_7_hit'] = $data_3002_7;                                          //7日命中数
        $data_3002['3002_7_rate'] = $this->compute_hit($data_3002_7, $data_3002_7_);    //7日命中率

        $data_3002['3002_30_total'] = $data_3002_30_;                                        //30日场次数
        $data_3002['3002_30_hit'] = $data_3002_30;                                        //30日命中数
        $data_3002['3002_30_rate'] = $this->compute_hit($data_3002_30, $data_3002_30_);   //30日命中率
        $data_3002['rate_3002'] = $this->compute_hit($data_3002_hit, $data_3002_total); //3002命中率
        //3004
        $data_3004['3004_3_total'] = $data_3004_3_;                                       //3日场次数
        $data_3004['3004_3_hit'] = $data_3004_3;                                        //3日命中数
        $data_3004['3004_3_rate'] = $this->compute_hit($data_3004_3, $data_3004_3_);    //3日命中率

        $data_3004['3004_7_total'] = $data_3004_7_;                                       //7日场次数
        $data_3004['3004_7_hit'] = $data_3004_7;                                          //7日命中数
        $data_3004['3004_7_rate'] = $this->compute_hit($data_3004_7, $data_3004_7_);    //7日命中率

        $data_3004['3004_30_total'] = $data_3004_30_;                                        //30日场次数
        $data_3004['3004_30_hit'] = $data_3004_30;                                        //30日命中数
        $data_3004['3004_30_rate'] = $this->compute_hit($data_3004_30, $data_3004_30_);   //30日命中率
        $data_3004['rate_3004'] = $this->compute_hit($data_3004_hit, $data_3004_total); //3004命中率
        //总的数据
        $hit = ($data_3002_3 + $data_3001_3 + $data_3004_3);
        $unhit = ($data_3002_3_ + $data_3001_3_ + $data_3004_3_);
        $date_total['total_3_total'] = $unhit;                                            //总的3日场次数
        $date_total['total_3_hit'] = $hit;                                                //总的3日命中数
        $date_total['total_3_rate'] = $this->compute_hit($hit, $unhit);                 //总的3日命中率

        $hit = ($data_3002_7 + $data_3001_7 + $data_3004_7);
        $unhit = ($data_3002_7_ + $data_3001_7_ + $data_3004_7_);
        $date_total['total_7_total'] = $unhit;                                            //总的7日场次数
        $date_total['total_7_hit'] = $hit;                                                //总的7日命中数
        $date_total['total_7_rate'] = $this->compute_hit($hit, $unhit);                 //7日命中率

        $hit = ($data_3002_30 + $data_3001_30 + $data_3004_30);
        $unhit = ($data_3002_30_ + $data_3001_30_ + $data_3004_30_);
        $date_total['total_30_total'] = $unhit;                                           //总的30日场次数
        $date_total['total_30_hit'] = $hit;                                               //总的30日命中数
        $date_total['total_30_rate'] = $this->compute_hit($hit, $unhit);               //30日命中率

        $date_total['rate_total'] = $this->compute_hit($data_hit, $data_total);        //总的比例
        return ['total' => $date_total, '3002' => $data_3002, '3001' => $data_3001, '3004' => $data_3004];
    }

    /**
     * 计算命中率
     * @param int $hit_num   命中数
     * @param int $unhit_num 未命中数
     */
    private function compute_hit($hit_num, $unhit_num) {
        if (empty($hit_num) || empty($unhit_num)) {
            return 0;
        }
        return bcmul(bcdiv($hit_num, $unhit_num), 100);
    }

    // 获取盈利率
    public function get_expert_rate($data) {
        if (!$data) {
            return [
                'total' => [
                    "total_3" => 0,
                    "total_7" => 0,
                    "total_30" => 0,
                    "total_rate" => 0
                ],
                '3006' => [
                    "3006_3" => 0,
                    "3006_7" => 0,
                    "3006_30" => 0,
                    "3006_rate" => 0
                ],
                '3010' => [
                    "3010_3" => 0,
                    "3010_7" => 0,
                    "3010_30" => 0,
                    "3010_rate" => 0
                ]
            ];
        }
        $_3day = date('Y-m-d H:i:s', strtotime('-3 day'));
        $_7day = date('Y-m-d H:i:s', strtotime('-7 day'));
        $_30day = date('Y-m-d H:i:s', strtotime('-30 day'));

        $data_3010_3 = [];       //3天命中数据       胜负
        $data_3010_3_total = []; //3天的场次
        $data_3010_7 = [];
        $data_3010_7_total = [];
        $data_3010_30 = [];
        $data_3010_30_total = [];
        $data_3010_hit = [];
        $data_3006_hit = [];

        $data_3006_3 = [];       //3天命中数据       让球
        $data_3006_3_total = []; //3天的场次
        $data_3006_7 = [];
        $data_3006_7_total = [];
        $data_3006_30 = [];
        $data_3006_30_total = [];
        $data_3006_total = [];
        $data_3010_total = [];

        foreach ($data as $v) {
            //处理赔率
            if (strlen($v['pre_result']) > 1) {
                $pre_odds = explode(',', $v['pre_odds']);
                $pre_result = explode(',', $v['pre_result']);
                $key = array_search($v['featured'], $pre_result);
                $v['pre_odds'] = $pre_odds[$key];
                $v['pre_result'] = $pre_result[$key];
            }

            //命中：同一文章id赔率相乘，不同赔率文章相加
            //全部场次：同上
            if ($v['lottery_code'] == 3010) {
                if ($v['create_time'] > $_3day) {     //3天3010
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3010_3[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3010_3_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_7day) {     //7天3010
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3010_7[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3010_7_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3010
                        $data_3010_30[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3010_30_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                //全部
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3010
                    $data_3010_hit[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];    //该专家全部命中
                }
                $data_3010_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];      //该专家全部场次
            }

            if ($v['lottery_code'] == '3006') {
                if ($v['create_time'] > $_3day) {     //3天3006
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3006_3[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3006_3_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_7day) {     //7天3006
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3006_7[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3006_7_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3006
                        $data_3006_30[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3006_30_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                //全部
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3006
                    $data_3006_hit[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];    //该专家全部命中
                }
                $data_3006_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];      //该专家全部场次
            }
            if ($v['lottery_code'] == '3001') {
                if ($v['create_time'] > $_3day) {     //3天3010
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3001_3[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3001_3_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_7day) {     //7天3010
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3001_7[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3001_7_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3010
                        $data_3001_30[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3001_30_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                //全部
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3010
                    $data_3001_hit[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];    //该专家全部命中
                }
                $data_3001_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];      //该专家全部场次
            }
        }

        //3010
        $data_3010_3_hit = $this->compute_odds_($data_3010_3);         //3天命中的赔率
        $data_3010_3_all = $this->compute_odds_($data_3010_3_total);   //3天全部的赔率
        $data_3010['3010_3'] = $this->compute_tatol_profit_rate_($data_3010_3_hit, $data_3010_3_all); //3天盈利率

        $data_3010_7_hit = $this->compute_odds_($data_3010_7);         //7天命中的赔率
        $data_3010_7_all = $this->compute_odds_($data_3010_7_total);   //7天全部的赔率
        $data_3010['3010_7'] = $this->compute_tatol_profit_rate_($data_3010_7_hit, $data_3010_7_all); //7天盈利率

        $data_3010_30_hit = $this->compute_odds_($data_3010_30);         //30天命中的赔率
        $data_3010_30_all = $this->compute_odds_($data_3010_30_total);   //30天全部的赔率
        $data_3010['3010_30'] = $this->compute_tatol_profit_rate_($data_3010_30_hit, $data_3010_30_all); //30天盈利率

        $data_3010_all_hit = $this->compute_odds_($data_3010_hit);                //全部命中赔率
        $data_3010_all = $this->compute_odds_($data_3010_total);                  //全部赔率
        $data_3010['3010_rate'] = $this->compute_tatol_profit_rate_($data_3010_all_hit, $data_3010_all);   //全部盈利率
        //3006
        $data_3006_3_hit = $this->compute_odds_($data_3006_3);         //3天命中的赔率
        $data_3006_3_all = $this->compute_odds_($data_3006_3_total);   //3天全部的赔率
        $data_3006['3006_3'] = $this->compute_tatol_profit_rate_($data_3006_3_hit, $data_3006_3_all); //7天盈利率

        $data_3006_7_hit = $this->compute_odds_($data_3006_7);         //7天命中的赔率
        $data_3006_7_all = $this->compute_odds_($data_3006_7_total);   //7天全部的赔率
        $data_3006['3006_7'] = $this->compute_tatol_profit_rate_($data_3006_7_hit, $data_3006_7_all); //7天盈利率

        $data_3006_30_hit = $this->compute_odds_($data_3006_30);         //30天命中的赔率
        $data_3006_30_all = $this->compute_odds_($data_3006_30_total);   //30天全部的赔率
        $data_3006['3006_30'] = $this->compute_tatol_profit_rate_($data_3006_30_hit, $data_3006_30_all); //30天盈利率

        $data_3006_all_hit = $this->compute_odds_($data_3006_hit);                //全部命中赔率
        $data_3006_all = $this->compute_odds_($data_3006_total);                  //全部赔率
        $data_3006['3006_rate'] = $this->compute_tatol_profit_rate_($data_3006_all_hit, $data_3006_all);   //全部盈利率

        $data_3_hit = $data_3010_3_hit + $data_3006_3_hit; //3天所有命中赔率
        $data_3_all = $data_3010_3_all + $data_3006_3_all; //3天所有赔率
        $data_total['total_3'] = $this->compute_tatol_profit_rate_($data_3_hit, $data_3_all);   //3天盈利率 ;

        $data_7_hit = $data_3010_7_hit + $data_3006_7_hit; //7天所有命中赔率
        $data_7_all = $data_3010_7_all + $data_3006_7_all; //7天所有赔率
        $data_total['total_7'] = $this->compute_tatol_profit_rate_($data_7_hit, $data_7_all);   //7天盈利率

        $data_30_hit = $data_3010_30_hit + $data_3006_30_hit; //30天所有命中赔率
        $data_30_all = $data_3010_30_all + $data_3006_30_all; //30天所有赔率
        $data_total['total_30'] = $this->compute_tatol_profit_rate_($data_30_hit, $data_30_all);   //30天盈利率

        $data_all_hit = $data_3006_all_hit + $data_3010_all_hit;                                         //全部盈利率
        $data_all = $data_3006_all + $data_3010_all;
        $data_total['total_rate'] = $this->compute_tatol_profit_rate_($data_all_hit, $data_all);
        ;
        return ['total' => $data_total, '3006' => $data_3006, '3010' => $data_3010];
    }

    // 获取盈利率
    public function get_lanexpert_rate($data) {
        if (!$data) {
            return [
                'total' => [
                    "total_3" => 0,
                    "total_7" => 0,
                    "total_30" => 0,
                    "total_rate" => 0
                ],
                '3001' => [
                    "3001_3" => 0,
                    "3001_7" => 0,
                    "3001_30" => 0,
                    "3001_rate" => 0
                ],
                '3002' => [
                    "3002_3" => 0,
                    "3002_7" => 0,
                    "3002_30" => 0,
                    "3002_rate" => 0
                ],
                '3004' => [
                    "3004_3" => 0,
                    "3004_7" => 0,
                    "3004_30" => 0,
                    "3004_rate" => 0
                ],
            ];
        }
        $_3day = date('Y-m-d H:i:s', strtotime('-3 day'));
        $_7day = date('Y-m-d H:i:s', strtotime('-7 day'));
        $_30day = date('Y-m-d H:i:s', strtotime('-30 day'));

        $data_3001_3 = [];       //3天命中数据       胜负
        $data_3001_3_total = []; //3天的场次
        $data_3001_7 = [];
        $data_3001_7_total = [];
        $data_3001_30 = [];
        $data_3001_30_total = [];
        $data_3001_hit = [];
        $data_3002_hit = [];
        $data_3004_hit = [];
        $data_3002_3 = [];       //3天命中数据       让球
        $data_3002_3_total = []; //3天的场次
        $data_3002_7 = [];
        $data_3002_7_total = [];
        $data_3002_30 = [];
        $data_3002_30_total = [];
        $data_3002_total = [];
        $data_3001_total = [];
        $data_3004_total = [];
        $data_3004_3 = [];       //3天命中数据       胜负
        $data_3004_3_total = []; //3天的场次
        $data_3004_7 = [];
        $data_3004_7_total = [];
        $data_3004_30 = [];
        $data_3004_30_total = [];
        foreach ($data as $v) {
            //处理赔率
            if (strlen($v['pre_result']) > 1) {
                $pre_odds = explode(',', $v['pre_odds']);
                $pre_result = explode(',', $v['pre_result']);
                $key = array_search($v['featured'], $pre_result);
                $v['pre_odds'] = $pre_odds[$key];
                $v['pre_result'] = $pre_result[$key];
            }

            //命中：同一文章id赔率相乘，不同赔率文章相加
            //全部场次：同上
            if ($v['lottery_code'] == 3001) {
                if ($v['create_time'] > $_3day) {     //3天3010
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3001_3[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3001_3_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_7day) {     //7天3010
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3001_7[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3001_7_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3010
                        $data_3001_30[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3001_30_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                //全部
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3010
                    $data_3001_hit[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];    //该专家全部命中
                }
                $data_3001_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];      //该专家全部场次
            }

            if ($v['lottery_code'] == '3002') {
                if ($v['create_time'] > $_3day) {     //3天3006
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3002_3[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3002_3_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_7day) {     //7天3006
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3002_7[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3002_7_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3006
                        $data_3002_30[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3002_30_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                //全部
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3006
                    $data_3002_hit[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];    //该专家全部命中
                }
                $data_3002_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];      //该专家全部场次
            }
            if ($v['lottery_code'] == '3004') {
                if ($v['create_time'] > $_3day) {     //3天3010
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3004_3[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3004_3_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_7day) {     //7天3010
                    if ($v['status'] == 2 && $v['result_status'] == 3) {
                        $data_3004_7[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3004_7_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                if ($v['create_time'] > $_30day) {
                    if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3010
                        $data_3004_30[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                    }
                    $data_3004_30_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];
                }
                //全部
                if ($v['status'] == 2 && $v['result_status'] == 3) {      //30天3010
                    $data_3004_hit[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];    //该专家全部命中
                }
                $data_3004_total[] = ['pre_odds' => $v['pre_odds'], 'articles_id' => $v['articles_id'],];      //该专家全部场次
            }
        }

        //3010
        $data_3001_3_hit = $this->compute_odds_($data_3001_3);         //3天命中的赔率
        $data_3001_3_all = $this->compute_odds_($data_3001_3_total);   //3天全部的赔率
        $data_3001['3001_3'] = $this->compute_tatol_profit_rate_($data_3001_3_hit, $data_3001_3_all); //3天盈利率

        $data_3001_7_hit = $this->compute_odds_($data_3001_7);         //7天命中的赔率
        $data_3001_7_all = $this->compute_odds_($data_3001_7_total);   //7天全部的赔率
        $data_3001['3001_7'] = $this->compute_tatol_profit_rate_($data_3001_7_hit, $data_3001_7_all); //7天盈利率

        $data_3001_30_hit = $this->compute_odds_($data_3001_30);         //30天命中的赔率
        $data_3001_30_all = $this->compute_odds_($data_3001_30_total);   //30天全部的赔率
        $data_3001['3001_30'] = $this->compute_tatol_profit_rate_($data_3001_30_hit, $data_3001_30_all); //30天盈利率

        $data_3001_all_hit = $this->compute_odds_($data_3001_hit);                //全部命中赔率
        $data_3001_all = $this->compute_odds_($data_3001_total);                  //全部赔率
        $data_3001['3001_rate'] = $this->compute_tatol_profit_rate_($data_3001_all_hit, $data_3001_all);   //全部盈利率
        //3006
        $data_3002_3_hit = $this->compute_odds_($data_3002_3);         //3天命中的赔率
        $data_3002_3_all = $this->compute_odds_($data_3002_3_total);   //3天全部的赔率
        $data_3002['3002_3'] = $this->compute_tatol_profit_rate_($data_3002_3_hit, $data_3002_3_all); //7天盈利率

        $data_3002_7_hit = $this->compute_odds_($data_3002_7);         //7天命中的赔率
        $data_3002_7_all = $this->compute_odds_($data_3002_7_total);   //7天全部的赔率
        $data_3002['3002_7'] = $this->compute_tatol_profit_rate_($data_3002_7_hit, $data_3002_7_all); //7天盈利率

        $data_3002_30_hit = $this->compute_odds_($data_3002_30);         //30天命中的赔率
        $data_3002_30_all = $this->compute_odds_($data_3002_30_total);   //30天全部的赔率
        $data_3002['3002_30'] = $this->compute_tatol_profit_rate_($data_3002_30_hit, $data_3002_30_all); //30天盈利率

        $data_3002_all_hit = $this->compute_odds_($data_3002_hit);                //全部命中赔率
        $data_3002_all = $this->compute_odds_($data_3002_total);                  //全部赔率
        $data_3002['3002_rate'] = $this->compute_tatol_profit_rate_($data_3002_all_hit, $data_3002_all);   //全部盈利率
        //3004
        $data_3004_3_hit = $this->compute_odds_($data_3004_3);         //3天命中的赔率
        $data_3004_3_all = $this->compute_odds_($data_3004_3_total);   //3天全部的赔率
        $data_3004['3004_3'] = $this->compute_tatol_profit_rate_($data_3004_3_hit, $data_3004_3_all); //7天盈利率

        $data_3004_7_hit = $this->compute_odds_($data_3004_7);         //7天命中的赔率
        $data_3004_7_all = $this->compute_odds_($data_3004_7_total);   //7天全部的赔率
        $data_3004['3004_7'] = $this->compute_tatol_profit_rate_($data_3004_7_hit, $data_3004_7_all); //7天盈利率

        $data_3004_30_hit = $this->compute_odds_($data_3004_30);         //30天命中的赔率
        $data_3004_30_all = $this->compute_odds_($data_3004_30_total);   //30天全部的赔率
        $data_3004['3004_30'] = $this->compute_tatol_profit_rate_($data_3004_30_hit, $data_3004_30_all); //30天盈利率

        $data_3004_all_hit = $this->compute_odds_($data_3004_hit);                //全部命中赔率
        $data_3004_all = $this->compute_odds_($data_3004_total);                  //全部赔率
        $data_3004['3004_rate'] = $this->compute_tatol_profit_rate_($data_3004_all_hit, $data_3004_all);   //全部盈利率

        $data_3_hit = $data_3001_3_hit + $data_3002_3_hit + $data_3004_3_hit; //3天所有命中赔率
        $data_3_all = $data_3001_3_all + $data_3002_3_all + $data_3004_3_all; //3天所有赔率
        $data_total['total_3'] = $this->compute_tatol_profit_rate_($data_3_hit, $data_3_all);   //3天盈利率 ;

        $data_7_hit = $data_3001_7_hit + $data_3002_7_hit + $data_3004_7_hit; //7天所有命中赔率
        $data_7_all = $data_3001_7_all + $data_3002_7_all + $data_3004_7_all; //7天所有赔率
        $data_total['total_7'] = $this->compute_tatol_profit_rate_($data_7_hit, $data_7_all);   //7天盈利率

        $data_30_hit = $data_3001_30_hit + $data_3002_30_hit + $data_3004_30_hit; //30天所有命中赔率
        $data_30_all = $data_3001_30_all + $data_3002_30_all + $data_3004_30_all; //30天所有赔率
        $data_total['total_30'] = $this->compute_tatol_profit_rate_($data_30_hit, $data_30_all);   //30天盈利率

        $data_all_hit = $data_3002_all_hit + $data_3001_all_hit + $data_3004_all_hit;                                         //全部盈利率
        $data_all = $data_3002_all + $data_3001_all + $data_3004_all;
        $data_total['total_rate'] = $this->compute_tatol_profit_rate_($data_all_hit, $data_all);
        ;
        return ['total' => $data_total, '3002' => $data_3002, '3001' => $data_3001, '3004' => $data_3004];
    }

    /**
     * @param array $data 计算数据 : 同一文章id的相乘，并且不同文章的相加
     */
    public function compute_odds_($data) {
        if (empty($data)) {
            return 0;
        }
        $tmp = [];
        foreach ($data as $k => $v) {
            if (array_key_exists($v['articles_id'], $tmp)) {
                $tmp[$v['articles_id']] *= $v['pre_odds'];
            } else {
                $tmp[$v['articles_id']] = $v['pre_odds'];
            }
        }
        return array_sum($tmp);
    }

    /**
     * 计算总的盈利率
     * @param int $param1  命中赔率
     * @param int $param2  当前时间段赔率
     */
    private function compute_tatol_profit_rate_($param1, $param2) {
        if ($param1 && $param2) {
            return bcmul(bcdiv($param1, $param2), 100);
        } else {
            return 0;
        }
    }

    public function getFansList($expertId, $page, $size) {
        $total = UserExpert::find()->where(['expert_id' => $expertId, 'status' => 1])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $userExpert = UserExpert::find()->select(['u.user_name', 'user_expert.create_time', 'u.user_pic'])
                ->leftJoin('user u', 'u.user_id = user_expert.user_id')
                ->where(['expert_id' => $expertId, 'user_expert.status' => 1])
                ->limit($size)
                ->offset($offset)
                ->asArray()
                ->all();
        return ['page' => $page, 'size' => count($userExpert), 'pages' => $pages, 'total' => $total, 'data' => $userExpert];
    }

    /**
     * 举报文章
     */
    public function reportArticle($custNo, $expertId, $articleId, $reportReasons) {

        $record = ArticlesReportRecord::find()->where(["cust_no" => $custNo, "article_id" => $articleId])->one();
        if (!empty($record)) {
            return ['code' => 109, 'msg' => '您已经举报过该文章，无需重复举报'];
        }
        $articlesReportRecord = new ArticlesReportRecord;
        $articlesReportRecord->cust_no = $custNo;
        $articlesReportRecord->expert_id = $expertId;
        $articlesReportRecord->article_id = $articleId;
        $articlesReportRecord->report_reasons = $reportReasons;
        $articlesReportRecord->create_time = date("Y-m-d H:i:s");
        if (!$articlesReportRecord->validate()) {
            return ['code' => 109, 'msg' => $articlesReportRecord->getFirstErrors()];
        }
        if (!$articlesReportRecord->save()) {
            return ['code' => 109, 'msg' => '举报失败，数据存储失败'];
        }
        $db = Yii::$app->db;
        $updata = "update expert_articles set report_num = report_num + 1, modify_time = '" . date('Y-m-d H:i:s') . "' where expert_articles_id='{$articleId}'";
        $db->createCommand($updata)->execute();
        return ['code' => 600, 'msg' => '举报成功'];
    }

    /**
     * 查看被举报详情
     */
    public function readReportRecord($article_id, $page, $size) {
        $total = ArticlesReportRecord::find()->where(['article_id' => $article_id])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $query = (new Query())->select("a.*,u.user_name,u.user_tel")
                ->from("articles_report_record as a")
                ->leftJoin("user as u", "u.cust_no = a.cust_no")
                ->where(["a.article_id" => $article_id])
                ->limit($size)
                ->offset($offset)
                ->all();
        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'data' => $query];
    }

    /**
     * 获取篮球赛程列表
     * @param type $page
     * @param type $size
     * @param type $startDate
     * @param type $endDate
     * @param type $league
     * @param type $likeParam
     * @return type
     */
    public function getLanSchedule($page, $size, $startDate, $endDate, $league, $likeParam) {
        $startWhere = [];
        $endWhere = [];
        $likeWhere1 = [];
        $likeWhere2 = [];
        $likeWhere3 = [];
        $likeWhere4 = [];
        $likeWhere5 = [];
        $where = [];
        if (!empty($startDate)) {
            $startWhere = ['>=', 'lan_schedule.start_time', $startDate . ' 00:00:00'];
        }
        if (!empty($endDate)) {
            $endWhere = ['<', 'lan_schedule.start_time', $endDate . ' 23:59:59'];
        }
        if (!empty($likeParam)) {
            $likeWhere1 = ['like', 'lan_schedule.home_short_name', '%' . $likeParam . '%', false];
            $likeWhere2 = ['like', 'lan_schedule.visit_short_name', '%' . $likeParam . '%', false];
            $likeWhere3 = ['like', 'l.league_short_name', '%' . $likeParam . '%', false];
            $likeWhere4 = ['like', 'lan_schedule.league_id', '%' . $likeParam . '%', false];
            $likeWhere5 = ['like', 'lan_schedule.schedule_mid', '%' . $likeParam . '%', false];
        }
        if (!empty($league)) {
            $where['lan_schedule.league_id'] = $league;
        }
        $where['sr.result_status'] = 0;
        $where['l.league_type'] = 2;
        $total = LanSchedule::find()
                ->leftJoin('league as l', 'l.league_code = lan_schedule.league_id and l.league_type = 2')
                ->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                ->andWhere(['!=', 'lan_schedule.schedule_sf', 3])
                ->orWhere(['!=', 'lan_schedule.schedule_rfsf', 3])
                ->andWhere($likeWhere1)
                ->orWhere($likeWhere2)
                ->orWhere($likeWhere3)
                ->orWhere($likeWhere4)
                ->orWhere($likeWhere5)
                ->andWhere($where)
                ->andWhere($startWhere)
                ->andWhere($endWhere)
                ->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $field = ['lan_schedule.schedule_code', 'lan_schedule.schedule_mid', 'lan_schedule.league_id', 'lan_schedule.visit_short_name', 'lan_schedule.home_short_name',
            'lan_schedule.start_time', 'l.league_short_name'];
        $scheData = LanSchedule::find()->select($field)
                ->leftJoin('league as l', 'l.league_code = lan_schedule.league_id and l.league_type = 2')
                ->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                ->where(['!=', 'lan_schedule.schedule_sf', 3])
                ->orWhere(['!=', 'lan_schedule.schedule_rfsf', 3])
                ->andWhere($likeWhere1)
                ->orWhere($likeWhere2)
                ->orWhere($likeWhere3)
                ->orWhere($likeWhere4)
                ->orWhere($likeWhere5)
                ->andWhere($startWhere)
                ->andWhere($endWhere)
                ->andWhere($where)
                ->offset($offset)
                ->limit($size)
                ->orderBy('lan_schedule.schedule_mid')
                ->asArray()
                ->all();
        return ['page' => $page, 'pages' => $pages, 'size' => count($scheData), 'total' => $total, 'data' => $scheData];
    }

    /**
     * 获取篮球赛程详情
     * @auther GL zyl
     * @return type
     */
    public function getLanScheduleDetail($mid) {
        $oddStr = ['odds3001', 'odds3002', 'odds3004'];
        $field = ['lan_schedule.schedule_mid', 'lan_schedule.schedule_code', 'lan_schedule.home_short_name', 'hr.team_position as home_position', 'hr.team_rank as home_rank', 'vr.team_position as visit_position',
            'vr.team_rank as visit_rank', 'lan_schedule.league_id', 'lan_schedule.visit_short_name', 'lan_schedule.home_short_name', 'lan_schedule.start_time', 'lan_schedule.schedule_sf',
            'lan_schedule.schedule_rfsf', 'lan_schedule.league_name league_short_name', 'ht.team_img as home_team_img', 'vt.team_img as visit_team_img'];
        $scheData = LanSchedule::find()->select($field)
                ->leftJoin('lan_team_rank  hr', 'hr.team_code = lan_schedule.home_team_id')
                ->leftJoin('lan_team_rank  vr', 'vr.team_code = lan_schedule.visit_team_id')
                ->leftJoin('team ht', 'ht.team_code = lan_schedule.home_team_id and ht.team_type = 2')
                ->leftJoin('team vt', 'vt.team_code = lan_schedule.visit_team_id and vt.team_type = 2')
                ->with($oddStr)
                ->where(['lan_schedule.schedule_mid' => $mid])
                ->asArray()
                ->one();
        if ($scheData['home_position'] == 1) {
            $scheData['home_position'] = '东';
        } elseif ($scheData['home_position'] == 2) {
            $scheData['home_position'] = '西';
        } else {
            $scheData['home_position'] = '未知';
        }
        if ($scheData['visit_position'] == 1) {
            $scheData['visit_position'] = '东';
        } elseif ($scheData['visit_position'] == 2) {
            $scheData['visit_position'] = '西';
        } else {
            $scheData['visit_position'] = '未知';
        }
        $scheData['rq_nums'] = $scheData['odds3002']['rf_nums'];
        $scheData['fen_cutoff'] = $scheData['odds3004']['fen_cutoff'];
        return $scheData;
    }

    public function getZuScheduleInfo($periodsArr) {
        $field = ['schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time', 'schedule.rq_nums', 'l.league_short_name',
            'h.home_team_rank', 'h.visit_team_rank', 'ht.team_img as home_team_img', 'vt.team_img as visit_team_img', 'schedule.endsale_time'];
        $scheduleData = Schedule::find()->select($field)
                ->leftJoin('league as l', 'l.league_id = schedule.league_id')
                ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
                ->leftJoin('team as ht', 'ht.team_id = schedule.home_team_id')
                ->leftJoin('team as vt', 'vt.team_id = schedule.visit_team_id')
                ->where(['in', 'schedule.schedule_mid', $periodsArr])
                ->indexBy('schedule_mid')
                ->asArray()
                ->all();
        return $scheduleData;
    }

    public function getLanScheduleInfo($periodsArr) {
        $oddStr = ['odds3002', 'odds3004'];
        $field = ['lan_schedule.schedule_mid', 'lan_schedule.schedule_code', 'lan_schedule.home_short_name', 'hr.team_position as home_position', 'hr.team_rank as home_rank', 'vr.team_position as visit_position',
            'vr.team_rank as visit_rank', 'lan_schedule.league_id', 'lan_schedule.visit_short_name', 'lan_schedule.home_short_name', 'lan_schedule.start_time', 'lan_schedule.endsale_time',
            'lan_schedule.league_name league_short_name', 'ht.team_img as home_team_img', 'vt.team_img as visit_team_img'];
        $scheData = LanSchedule::find()->select($field)
                ->leftJoin('lan_team_rank  hr', 'hr.team_code = lan_schedule.home_team_id')
                ->leftJoin('lan_team_rank  vr', 'vr.team_code = lan_schedule.visit_team_id')
                ->leftJoin('team ht', 'ht.team_code = lan_schedule.home_team_id and ht.team_type = 2')
                ->leftJoin('team vt', 'vt.team_code = lan_schedule.visit_team_id and vt.team_type = 2')
                ->with($oddStr)
                ->where(['in', 'lan_schedule.schedule_mid', $periodsArr])
                ->indexBy('schedule_mid')
                ->asArray()
                ->all();
        foreach ($scheData as &$val) {
            if ($val['home_position'] == 1) {
                $val['home_team_rank'] = '东' . $val['home_rank'];
            } elseif ($val['home_position'] == 2) {
                $val['home_team_rank'] = '西' . $val['home_rank'];
            } else {
                $val['home_team_rank'] = '未知' . $val['home_rank'];
            }
            if ($val['visit_position'] == 1) {
                $val['visit_team_rank'] = '东' . $val['visit_rank'];
            } elseif ($val['visit_position'] == 2) {
                $val['visit_team_rank'] = '西' . $val['visit_rank'];
            } else {
                $val['visit_team_rank'] = '未知' . $val['visit_rank'];
            }
            $val['rq_nums'] = $val['odds3002']['rf_nums'];
            $val['fen_cutoff'] = $val['odds3004']['fen_cutoff'];
        }
        return $scheData;
    }

    /**
     * 获取赛程方案数
     * @param type $data
     * @param type $preType
     * @param type $payType
     * @return type
     */
    public function getScheduleArtNums($data, $preType, $payType) {
        $midArr = [];
        foreach ($data as &$v) {
            $midArr[] = $v['schedule_mid'];
        }
        $ea['e.article_type'] = $preType;
        if (!empty($payType)) {
            $ea['e.pay_type'] = 1;
        }
        $countArr = ArticlesPeriods::find()->select(['periods', 'count(articles_periods_id) total'])
                ->innerJoin('expert_articles as e', 'e.expert_articles_id = articles_periods.articles_id and e.article_status = 3')
                ->where(['in', 'articles_periods.periods', $midArr])
                ->andWhere($ea)
                ->groupBy('articles_periods.periods')
                ->indexBy('periods')
                ->asArray()
                ->all();
        if (!empty($countArr)) {
            foreach ($data as &$vi) {
                if (empty($countArr[$vi['schedule_mid']]['total'])) {
                    $vi['article_total'] = 0;
                } else {
                    $vi['article_total'] = $countArr[$vi['schedule_mid']]['total'];
                }
            }
        }
        return $data;
    }

    /**
     * 获取文章详情
     * @param type $articleId
     * @param type $expertId
     * @return type
     */
    public function getArticleDetailSim($articleId) {
        $rs = ExpertArticles::find()->where(['expert_articles_id' => $articleId])->asArray()->one();
        return $rs;
    }

    /**
     * 星星赛程方案数
     * @param type $midArr
     * @param type $preType
     * @param type $payType
     * @return type
     */
    public function getXxArtNums($midArr, $preType, $payType) {
        $ea['e.article_type'] = $preType;
        if (!empty($payType)) {
            $ea['e.pay_type'] = 1;
        }
        $countArr = ArticlesPeriods::find()->select(['periods', 'count(articles_periods_id) total'])
                ->innerJoin('expert_articles as e', 'e.expert_articles_id = articles_periods.articles_id and e.article_status = 3')
                ->where(['in', 'articles_periods.periods', $midArr])
                ->andWhere($ea)
                ->groupBy('articles_periods.periods')
                ->indexBy('periods')
                ->asArray()
                ->all();
        $total = [];
        foreach ($midArr as $vi) {
            if (isset($countArr[$vi])) {
                $total[$vi] = $countArr[$vi]['total'];
            } else {
                $total[$vi] = 0;
            }
        }
        return $total;
    }

}
