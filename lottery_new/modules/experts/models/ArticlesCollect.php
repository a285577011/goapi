<?php

namespace app\modules\experts\models;

use Yii;
use app\modules\common\helpers\Constants;
use app\modules\competing\helpers\CompetConst;

/**
 * This is the model class for table "articles_collect".
 * @auther gwp
 * @property integer user_id
 * @property integer expert_articles_id
 */
class ArticlesCollect extends \yii\db\ActiveRecord {

    public static function tableName() {
        return 'articles_collect';
    }

    /**
     * 用户关注文章
     * @param $expert_articles_id  文章id
     * @param $user_id             用户id
     * @return int
     */
    public function addArticlesCollect($expert_articles_id, $user_id) {

        $collect = self::findOne(['articles_collect_id' => $expert_articles_id, 'user_id' => $user_id]);
        if ($collect)
            return ['code' => 109, 'msg' => '已经收藏'];

        $res = \Yii::$app->db->createCommand()->insert(self::tableName(), [
                    'expert_articles_id' => $expert_articles_id,
                    'user_id' => $user_id,
                    'create_time' => date('Y-m-d H:i:s'),
                ])->execute();

        if (!$res) {
            $result = ['code' => 109, 'msg' => '收藏失败'];
        } else {
            $result = ['code' => 600, 'msg' => '收藏成功'];
        }
        return $result;
    }

    /**
     * 用户删除收藏
     * @param $articles_collect_id  文章id
     * @param $user_id              用户id
     */
    public function delArticlesCollect($expert_articles_id, $user_id) {
        $collect = self::findOne(['expert_articles_id' => $expert_articles_id, 'user_id' => $user_id]);
        if (empty($collect)) {
            return ['code' => 109, 'msg' => '无法取消收藏'];
        }
        if (!$collect->delete()) {
            return ['code' => 109, 'msg' => '收藏取消失败'];
        }
        return ['code' => 600, 'msg' => '收藏已取消'];
    }

    /**
     * 获取收藏文章列
     */
    public function articlesCollectLists($userId, $page, $size, $preType) {
        $where['article_status'] = 3;
        $jwhere['expert_articles.article_status'] = 3;
        $jwhere['ac.user_id'] = $userId;
        $jwhere['expert_articles.article_type'] = $preType;
        $where['article_type'] = $preType;
        $articlesResult = Constants::ARTICLES_RESULT;
        $articlesStatus = Constants::ARTICLES_STATUS;
        $payTypeName = Constants::ARTICLES_PAY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $expertSource = Constants::EXPERT_TYPE_SOURCE;
        $total = self::find()->innerJoin('expert_articles e', ' e.expert_articles_id = articles_collect.articles_collect_id')
                ->where($where)
                ->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $field = ['expert_articles.expert_articles_id', 'e.expert_source', 'expert_articles.article_type', 'expert_articles.article_title', 'expert_articles.pay_type', 'expert_articles.pay_money',
            'expert_articles.buy_nums', 'expert_articles.read_nums', 'expert_articles.article_status', 'expert_articles.result_status', 'expert_articles.articles_code', 'e.fans_nums',
            'u.user_name', 'u.user_pic', 'e.day_nums', 'expert_articles.create_time', 'expert_articles.user_id as expert_id', 'expert_articles.buy_back', 'ac.articles_collect_id'];
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
            array_push($field2, 'sr.status', 'sr.schedule_result_3007 bf_result', 'sr.schedule_result_3010 sf_result', 'sr.schedule_result_3006 rfsf_result', 'sr.schedule_result_sbbf sbcbf_result');
        } else {
            array_push($field2, 'sr.result_status status', 'sr.result_3001 sf_result', 'sr.result_3002 rfsf_result', 'sr.result_qcbf bf_result', 'sr.result_zcbf sbcbf_result');
        }
        $artData = ExpertArticles::find()->select($field)
                ->innerJoin('expert as e', 'e.user_id = expert_articles.user_id')
                ->innerJoin('articles_collect ac', ' ac.expert_articles_id = expert_articles.expert_articles_id')
                ->leftJoin('user as u', 'u.user_id = e.user_id')
                ->where($jwhere)
                ->offset($offset)
                ->limit($size)
                ->indexBy('expert_articles_id')
                ->orderBy('expert_articles.stick desc,expert_articles.deal_status,expert_articles.create_time desc,e.month_red_nums desc')
                ->asArray()
                ->all();
        $artIdArr = array_keys($artData);
//        print_r($artIdArr);die;
        $scheData = ArticlesPeriods::find()->select($field2);
        if ($preType == 1) {
            $scheData = $scheData->leftJoin('schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->where(['in', 'articles_id', $artIdArr])
                    ->asArray()
                    ->all();
        } else {
            $scheData = $scheData->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = articles_periods.periods')
                    ->where(['in', 'articles_id', $artIdArr])
                    ->asArray()
                    ->all();
        }

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
                        $list[$artId]['articles_code'] = $artData[$artId]['articles_code'];
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
            $vl['pre_profit'] = intval($preProfit * 100);
            $artList[] = $vl;
        }
        return ['page' => $page, 'pages' => $pages, 'size' => count($artList), 'total' => $total, 'data' => $artList];
    }

}
