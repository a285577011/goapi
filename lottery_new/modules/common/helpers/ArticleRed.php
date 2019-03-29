<?php

namespace app\modules\common\helpers;

use app\modules\experts\models\ArticlesPeriods;
use app\modules\common\models\ScheduleResult;
use Yii;
use app\modules\experts\models\ExpertArticles;
use app\modules\experts\models\UserArticle;
use app\modules\common\services\FundsService;
use app\modules\common\services\PayService;
use yii\base\Exception;
use app\modules\common\models\UserFunds;
use app\modules\common\models\PayRecord;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use app\modules\common\services\KafkaService;
use app\modules\cron\models\CheckLotteryResultRecord;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class ArticleRed {

    /**
     * 文章预测结果的统计
     * @auther GL zyl
     * @param type $mid
     * @return type
     */
    public function acticlePreResult($mid) {
        $result = ScheduleResult::find()->select(['schedule_result_3006', 'schedule_result_3010'])->where(['schedule_mid' => $mid, 'status' => 2])->asArray()->one();
        if (empty($result)) {
            return ['code' => 1, 'data' => '该场次还未完赛'];
        }

        $articles = ArticlesPeriods::find()->select(['articles_periods.articles_periods_id', 'articles_periods.lottery_code', 'articles_periods.pre_result', 'articles_periods.articles_id', 'ea.user_id'])
                ->innerJoin('expert_articles as ea', "ea.expert_articles_id = articles_periods.articles_id and ea.article_status in (3,4) and ea.result_status = 1")
                ->where(['articles_periods.periods' => $mid, 'articles_periods.status' => 1])
                ->asArray()
                ->all();
        if (empty($articles)) {
            return ['code' => 1, 'data' => '该场次暂无文章需清算'];
        }
        $up = '';
        $format = date('Y-m-d H:i:s');
        $userIds = [];
        foreach ($articles as $val) {
            $perResult = explode(',', $val['pre_result']);
            $field = 'schedule_result_' . $val['lottery_code'];
            $res = $result[$field];
            if (in_array($res, $perResult)) {
                $up .= "update articles_periods set status = 2, modify_time = '" . $format . "' where  articles_periods_id = {$val['articles_periods_id']}; ";
                $preCount = ArticlesPeriods::find()->where(['articles_id' => $val['articles_id'], 'status' => 1])->count();
                if ($preCount == 1) {
                    $up .= "update expert_articles set result_status = 3, stick = 0, modify_time = '" . $format . "' where  expert_articles_id = {$val['articles_id']}; ";
                    $up .= "update expert set even_red_nums = even_red_nums + 1, modify_time = '" . $format . "' where  user_id = {$val['user_id']}; ";
                    $up .= "update expert set even_back_nums = 0, modify_time = '" . $format . "' where  user_id = {$val['user_id']}; ";
                    $userIds[] = $val['user_id'];
                }
            } else {
                $up .= "update articles_periods set status = 3, modify_time = '" . $format . "' where  articles_periods_id = {$val['articles_periods_id']}; ";
                $up .= "update expert_articles set result_status = 2, stick = 0, modify_time = '" . $format . "' where  expert_articles_id = {$val['articles_id']}; ";
                $up .= "update expert set even_red_nums = 0, modify_time = '" . $format . "' where  user_id = {$val['user_id']}; ";
                $up .= "update expert set even_back_nums = even_back_nums + 1, modify_time = '" . $format . "' where  user_id = {$val['user_id']}; ";
            }
        }
        $db = Yii::$app->db;
        $periodIds = $db->createCommand($up)->execute();
        if ($periodIds == false) {
            return ['code' => 0, 'data' => $articles[0]['articles_periods_id'] . '-' . $articles[count($articles) - 1]['articles_periods_id']];
        }
        $countUp = '';
        if (!empty($userIds)) {
            $userIdStr = '(' . implode(',', $userIds) . ')';
            $daySelect = "select user_id,count(*) as d_sum_nums from expert_articles where user_id in {$userIdStr} and result_status = 3 and  DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(create_time);";
            $dayOutSelect = "select user_id,count(*) as day_nums from expert_articles where user_id in {$userIdStr} and article_status = 3 and  DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(create_time);";
            $monthSelect = "select user_id,count(*) as m_sum_nums from expert_articles where user_id in {$userIdStr} and result_status = 3 and  DATE_FORMAT( create_time, '%Y%m' ) = DATE_FORMAT( CURDATE( ) , '%Y%m' );";
            $dayCounts = $db->createCommand($daySelect)->queryAll();
            $monthCounts = $db->createCommand($monthSelect)->queryAll();
            $dayOutCounts = $db->createCommand($dayOutSelect)->queryAll();
            $upArr = [];
            foreach ($dayCounts as $dv) {
                $upArr[$dv['user_id']]['user_id'] = $dv['user_id'];
                $upArr[$dv['user_id']]['d_sum_nums'] = $dv['d_sum_nums'];
            }
            foreach ($dayOutCounts as $ov) {
                if (array_key_exists($ov['user_id'], $upArr)) {
                    $upArr[$ov['user_id']]['day_nums'] = $ov['day_nums'];
                } else {
                    $upArr[$ov['user_id']]['user_id'] = $ov['user_id'];
                    $upArr[$ov['user_id']]['d_sum_nums'] = 0;
                    $upArr[$ov['user_id']]['day_nums'] = $ov['day_nums'];
                }
            }
            foreach ($monthCounts as $mv) {
                if (array_key_exists($mv['user_id'], $upArr)) {
                    $upArr[$mv['user_id']]['m_sum_nums'] = $mv['m_sum_nums'];
                } else {
                    $upArr[$mv['user_id']]['user_id'] = $mv['user_id'];
                    $upArr[$mv['user_id']]['d_sum_nums'] = 0;
                    $upArr[$mv['user_id']]['day_nums'] = 0;
                    $upArr[$mv['user_id']]['m_sum_nums'] = $mv['m_sum_num'];
                }
            }
            foreach ($upArr as $uv) {
                $countUp .= "update expert set day_nums = {$uv['day_nums']}, day_red_nums = {$uv['d_sum_nums']}, month_red_nums = {$uv['m_sum_nums']}, modify_time = '" . $format . "' where  user_id = {$uv['user_id']}; ";
            }
            $upExpert = $db->createCommand($countUp)->execute();
            if ($upExpert == false) {
                return ['code' => 0, 'data' => $upArr[0]['user_id'] . '-' . $upArr[count($upArr) - 1]['user_id']];
            }
        }
        return ['code' => 1, 'data' => $articles[0]['articles_periods_id'] . '-' . $articles[count($articles) - 1]['articles_periods_id']];
    }

    /**
     *  专家收款/会员购文未中退款 线程插入方法
     * @return type
     */
    public function articlePushJob() {
        $field = ['user_article.user_article_id', 'user_article.user_article_code', 'ua.user_name as ua_user_name', 'ua.cust_no as ua_cust_no', 'ua.user_id as ua_user_id', 'ea.expert_articles_id', 'ea.pay_money',
            'ea.result_status', 'e.cust_no as e_cust_no', 'e.user_id as e_user_id', 'e.user_name as e_user_name'];
        $artList = UserArticle::find()->select($field)
                ->leftJoin('expert_articles as ea', 'ea.expert_articles_id = user_article.article_id')
                ->leftJoin('user as ua', 'ua.user_id = user_article.user_id')
                ->leftJoin('user as e', 'e.user_id = ea.user_id')
                ->where(['user_article.status' => 1, 'ea.deal_status' => 1])
                ->andWhere(['in', 'ea.article_status', [3, 4]])
                ->andWhere(['in', 'ea.result_status', [2, 3]])
                ->asArray()
                ->all();
        if (empty($artList)) {
            return ['code' => 600, 'msg' => '暂无文章须清算'];
        }
        //$lotteryqueue = new \LotteryQueue();
        foreach ($artList as $val) {
            $total = $val['pay_money'];
            $userArtCode = $val['user_article_code'];
            $userArtId = $val['user_article_id'];
            if ($val['result_status'] == 2) {
                $userId = $val['e_user_id'];
                $custNo = $val['e_cust_no'];
                $userName = $val['e_user_name'];
                $type = 1; // 专家收款 
                $body = '收费方案-收款';
            } elseif ($val['result_status'] == 3) {
                $userId = $val['ua_user_id'];
                $custNo = $val['ua_cust_no'];
                $userName = $val['ua_user_name'];
                $type = 2; // 会员购文退款
                $body = '收费方案-退款' . $val['user_article_code'];
            }
            KafkaService::addQue('CashArticle', ['user_id' => $userId, 'cust_no' => $custNo, 'user_name' => $userName, 'cash_type' => $type, 'user_art_id' => $userArtId, 'user_art_code' => $userArtCode, 'total' => $total, 'body' => $body],true);
            //$lotteryqueue->pushQueue('cash_articcash_articlele_job', 'expert_article', ['user_id' => $userId, 'cust_no' => $custNo, 'user_name' => $userName, 'cash_type' => $type, 'user_art_id' => $userArtId, 'user_art_code' => $userArtCode, 'total' => $total, 'body' => $body]);
        }
        return ['code' => 600, 'msg' => '成功'];
    }

    /**
     * 专家收款/会员购文未中退款 线程调方法
     * @param type $userId
     * @param type $custNo
     * @param type $userName
     * @param type $type
     * @param type $userArtId
     * @param type $userArtCode
     * @param type $total
     * @param type $body
     * @return type
     * @throws Exception
     */
    public function cashArticle($userId, $custNo, $userName, $type, $userArtId, $userArtCode, $total, $body) {
        $userArt = UserArticle::findOne(['user_article_id' => $userArtId]);
        $expertArt = ExpertArticles::findOne(['expert_articles_id' => $userArt->article_id]);
        $funds = new FundsService();
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();

        try {
            if ($type == 1) {
                $retExpert = $funds->operateUserFunds($custNo, $total, $total, 0, false, '文章收款');
                if ($retExpert['code'] != 0) {
                    throw new Exception($retExpert["msg"]);
                }
                $expertFunds = UserFunds::find()->select("all_funds")->from("user_funds")->where(["cust_no" => $custNo])->one();
                $payRecord = new PayRecord();
                $payRecord->order_code = $userArtCode;
                $payRecord->pay_no = Commonfun::getCode("PAY", "A");
                $payRecord->outer_no = Commonfun::getCode("DT", "SK");
                $payRecord->user_id = $userId;
                $payRecord->cust_no = $custNo;
                $payRecord->cust_type = 2;
                $payRecord->user_name = $userName;
                $payRecord->pay_pre_money = $total;
                $payRecord->pay_money = $total;
                $payRecord->pay_name = '余额';
                $payRecord->way_name = '余额';
                $payRecord->way_type = 'YE';
                $payRecord->pay_way = 3;
                $payRecord->pay_type_name = '方案-收款';
                $payRecord->pay_type = 18;
                $payRecord->balance = $expertFunds["all_funds"];
                $payRecord->body = $body;
                $payRecord->status = 1;
                $payRecord->pay_time = date('Y-m-d H:i:s');
                $payRecord->modify_time = date('Y-m-d H:i:s');
                $payRecord->create_time = date('Y-m-d H:i:s');
                if (!$payRecord->validate()) {
                    throw new Exception(json_encode($payRecord->getFirstErrors(), true));
                }
                if (!$payRecord->save()) {
                    throw new Exception(json_encode($payRecord->getFirstErrors(), true));
                }
                $userArt->status = 4;
                $userArt->modify_time = date('Y-m-d H:i:s');
                if (!$userArt->save()) {
                    throw new Exception(json_encode($userArt->getFirstErrors(), true));
                }
                $dealStatus = 2;
            } elseif ($type == 2) {
                $payService = new PayService();
                $refund = $payService->refund($userArtCode, '购买文章，不中返还');
                if ($refund === true) {
                    $userArt->status = 2;
                } else {
                    $userArt->status = 3;
                }
                $userArt->modify_time = date('Y-m-d H:i:s');
                if (!$userArt->save()) {
                    throw new Exception('方案不中,退款失败');
                }
                $countUserArt = UserArticle::find()->where(['status' => 1, 'article_id' => $userArt->article_id])->count();
                if ($countUserArt == 0) {
                    $dealStatus = 2;
                } else {
                    $dealStatus = 1;
                }
            } else {
                throw new Exception('非法操作');
            }
            $expertArt->deal_status = $dealStatus;
            $expertArt->modify_time = date('Y-m-d H:i:s');
            if (!$expertArt->save()) {
                throw new Exception(json_encode($userArt->getFirstErrors(), true));
            }
            $trans->commit();
            return ['code' => 600, 'msg' => '操作成功'];
        } catch (Exception $ex) {
            $trans->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }
    
    /**
     * 文章相关场次对奖
     * @param type $mid
     * @param type $JG_3006
     * @param type $JG_3010
     * @return type
     * @throws Exception
     */
    public function acticlePreResult2($mid, $JG_3006, $JG_3010) {
        $sql = "call Check_article_ZQ2('{$mid}', $JG_3006, $JG_3010); ";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "足球文章 - 兑奖完成!成功执行:{$ret['RowUpdateCount']}条";
            $data = [
                'lottery_code' => 4001,
                'periods' => $mid,
                'open_num' => $JG_3010,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }
    
    /**
     * 文章相关赛程取消对奖
     * @param type $mid
     * @return type
     * @throws Exception
     */
    public function articleCancel($mid) {
        $sql = "call Check_article_ZQ_Cancel('{$mid}'); ";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "足球文章 - 兑奖完成!成功执行:{$ret['RowUpdateCount']}条";
            $data = [
                'lottery_code' => 4001,
                'periods' => $mid,
                'open_num' => '取消',
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }
    
    /**
     * 篮球相关文章对奖
     * @param type $mid
     * @param type $Lan_3001
     * @param type $Lan_3002
     * @return type
     * @throws Exception
     */
    public function articleLanPreResult($mid, $FKe, $FZhu) {
        $sql = "call Check_article_LQ('{$mid}', $FKe, $FZhu); ";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "篮球文章 - 兑奖完成!成功执行:{$ret['RowUpdateCount']}条";
            $data = [
                'lottery_code' => 4001,
                'periods' => $mid,
                'open_num' => $FKe . ':' . $FZhu,
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }
    
    /**
     * 篮球文章赛程取消对奖
     * @param type $mid
     * @return type
     * @throws Exception
     */
    public function articleLanCancel($mid) {
        $sql = "call Check_article_LQ_Cancel('{$mid}'); ";
        $connection = \Yii::$app->db;
        try {
            $ret = $connection->createCommand($sql)->execute(1);
            $remark = "篮球文章 - 兑奖完成!成功执行:{$ret['RowUpdateCount']}条";
            $data = [
                'lottery_code' => 4001,
                'periods' => $mid,
                'open_num' => '取消',
                'remark' => $remark,
            ];
            CheckLotteryResultRecord::tosave($data);
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

}
