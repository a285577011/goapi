<?php

namespace app\modules\openapi\services;

use Yii;
use yii\db\Query;
use app\modules\experts\models\Expert;
use app\modules\experts\models\ExpertArticles;
use app\modules\common\helpers\Constants;
use app\modules\common\models\Schedule;
use app\modules\experts\models\ArticlesPeriods;
use app\modules\tools\helpers\Uploadfile;
use app\modules\user\models\User;
use app\modules\experts\models\UserArticle;
use app\modules\experts\models\UserExpert;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\PayService;
use app\modules\common\services\FundsService;
use app\modules\common\models\UserFunds;
use app\modules\common\models\PayRecord;
use app\modules\experts\models\ArticlesCollect;

class ArticlesService {
     /**
     * 获取专家方案列表
     */
    public function getArticlesList($startDate, $endDate) {
        $articlesResult = Constants::ARTICLES_RESULT;
        $articlesStatus = Constants::ARTICLES_STATUS;
        $payTypeName = Constants::ARTICLES_PAY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $expertSource = Constants::EXPERT_TYPE_SOURCE;
        $field = ['expert_articles.expert_articles_id', 'e.expert_source', 'expert_articles.article_type', 'expert_articles.article_title', 'expert_articles.pay_type', 'expert_articles.pay_money',
            'expert_articles.buy_nums', 'expert_articles.read_nums', 'expert_articles.article_status', 'expert_articles.result_status', 'expert_articles.articles_code', 'e.article_nums', 'e.fans_nums',
            'e.even_red_nums', 'e.month_red_nums', 'e.day_red_nums', 'u.user_name', 'u.user_pic', 'e.day_nums', 'expert_articles.create_time', 'expert_articles.user_id as expert_id', 'expert_articles.buy_back',
            'expert_articles.stick', 'expert_articles.deal_status', 'e.two_red_nums', 'e.three_red_nums', 'e.five_red_nums'];
        $field2 = ['articles_periods.articles_id', 'articles_periods.periods', 'articles_periods.lottery_code',
            'articles_periods.pre_result', 'articles_periods.pre_odds', 'articles_periods.schedule_code', 'articles_periods.visit_short_name', 'articles_periods.home_short_name', 'articles_periods.rq_nums',
            'articles_periods.start_time', 'articles_periods.league_short_name', 'articles_periods.home_team_rank', 'articles_periods.visit_team_rank', 'articles_periods.home_team_img', 'articles_periods.visit_team_img',
            'articles_periods.status as pre_status', 'articles_periods.featured', 'sr.status', 'sr.schedule_result_3007', 'sr.schedule_result_3006', 'sr.schedule_result_3010', 'articles_periods.endsale_time',
            's.schedule_status'];
        $artData = ExpertArticles::find()->select($field)
                ->innerJoin('expert as e', 'e.user_id = expert_articles.user_id')
                ->leftJoin('user as u', 'u.user_id = e.user_id')
                ->where(["expert_articles.article_status"=>3]);
        if(!empty($startDate)){
            $artData=$artData->andWhere(['>=', 'expert_articles.create_time', $startDate . ' 00:00:00']);
        }
        if(!empty($endDate)){
            $artData=$artData->andWhere(['<=', 'expert_articles.create_time', $endDate . ' 23:59:59']);
        }
        $artData=$artData->indexBy('expert_articles_id')
                ->orderBy('expert_articles.stick desc, deal_status asc, expert_articles.create_time desc, month_red_nums desc, expert_articles_id desc')
                ->asArray()
                ->all();
        $artIdArr = array_keys($artData);
        $scheData = ArticlesPeriods::find()->select($field2)
                ->leftJoin('schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                ->leftJoin('schedule s', 's.schedule_mid = articles_periods.periods')
                ->where(['in', 'articles_id', $artIdArr])
                ->asArray()
                ->all();
        $list = [];
        $dayRed = [];
        foreach ($artIdArr as $key) {
            foreach ($scheData as &$val) {
                if ($val['articles_id'] == $key) {
                    if (in_array($val['lottery_code'], $football)) {
                        $val['pre_result'] = explode(',', $val['pre_result']);
                        $val['pre_odds'] = explode(',', $val['pre_odds']);
                        if ($val['featured'] != 2) {
                            $trans = array_flip($val['pre_result']);
                            $key = $trans[$val['featured']];
                            $val['profit'] = $val['pre_odds'][$key];
                        } else {
                            $val['profit'] = $val['pre_odds'][0];
                        }
                    } else {
                        $val['pre_result'][] = $val['pre_result'];
                        $val['pre_odds'][] = [];
                        $val['profit'] = 1;
                    }
                    $artId = $val['articles_id'];
                    if (array_key_exists($artId, $list)) {
                        $list[$artId]['pre_concent'][] = ['periods' => $val['periods'], 'lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'visit_short_name' => $val['visit_short_name'],
                            'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'], 'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'],
                            'profit' => $val['profit'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['schedule_result_3007'], 'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'],
                            'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'], 'visit_team_img' => $val['visit_team_img'], 'featured' => $val['featured'],
                            'schedule_result' => $val['schedule_result_3010'], 'schedule_result_rqbf' => $val['schedule_result_3006'], 'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status']];
                    } else {
                        $list[$artId]['pre_concent'][] = ['periods' => $val['periods'], 'lottery_code' => $val['lottery_code'], 'pre_result' => $val['pre_result'], 'visit_short_name' => $val['visit_short_name'],
                            'home_short_name' => $val['home_short_name'], 'rq_nums' => $val['rq_nums'], 'start_time' => $val['start_time'], 'league_name' => $val['league_short_name'], 'pre_odds' => $val['pre_odds'], 'pre_status' => $val['pre_status'],
                            'profit' => $val['profit'], 'schedule_status' => $val['status'], 'schedule_result_qcbf' => $val['schedule_result_3007'], 'schedule_code' => $val['schedule_code'], 'home_team_rank' => $val['home_team_rank'],
                            'visit_team_rank' => $val['visit_team_rank'], 'home_team_img' => $val['home_team_img'], 'visit_team_img' => $val['visit_team_img'], 'featured' => $val['featured'],
                            'schedule_result' => $val['schedule_result_3010'], 'schedule_result_rqbf' => $val['schedule_result_3006'], 'endsale_time' => $val['endsale_time'], 'sale_status' => $val['schedule_status']];
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
                        $list[$artId]['read_nums'] = $artData[$artId]['read_nums'];
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
                    }
                }
            }
        }
        $artList = [];
        foreach ($list as &$vl) {
            $preProfit = 1;
            if ($vl['article_type'] == 1) {
                foreach ($vl['pre_concent'] as $it) {
                    $preProfit *= $it['profit'];
                }
            } else {
                $preProfit = 1;
            }
            $vl['pre_profit'] = intval($preProfit* 100);
            $artList[] = $vl;
        }
//        array_multisort($orderByStick, SORT_DESC, $orderByStatus, SORT_ASC, $orderByTime, SORT_DESC, $orderByRed, SORT_DESC, $artList);
        return $artList;
    }
}


