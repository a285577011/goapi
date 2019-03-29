<?php

namespace app\modules\cron\controllers;

use yii\web\Controller;
use app\modules\experts\models\ExpertArticles;
use app\modules\experts\models\Expert;

class ArticleController extends Controller {

    /**
     * 说明:每天清理文章阅读量 redis->mysql
     * @author chenqiwei
     * @date 2018/2/24 下午6:00
     * @return
     */
    public function actionArticleReadTotal(){
        $redisKey = "article_read_total";
        $redis = \Yii::$app->redis;
        $articleIds = $redis->zrangebyscore($redisKey,'-inf','+inf');
        $articleIdArray = [];
        $sql = "";
        foreach($articleIds as $articleId){
            $articleIdArray[$articleId] = $redis->zscore($redisKey,$articleId);
        }
        foreach ($articleIdArray as $k=>$v){
            $sql .= "UPDATE expert_articles SET read_nums = read_nums+{$v} WHERE expert_articles_id = {$k};";
            $articleIdArr[] = $k;
        }
        $articleTypes = ExpertArticles::find()->select(['expert_articles_id', 'article_type', 'user_id'])->where(['in', 'expert_articles_id', $articleIdArr])->indexBy('expert_articles_id')->asArray()->all();
        $read = [];
        foreach ($articleTypes as $key => $val) {
            if(array_key_exists($val['user_id'], $read)){
                if($val['article_type'] == 1) {
                    $read[$val['user_id']]['zu'] += $articleIdArray[$key];
                }  else {
                    $read[$val['user_id']]['lan'] += $articleIdArray[$key];
                }
            }  else {
                if($val['article_type'] == 1) {
                    $read[$val['user_id']]['zu'] = $articleIdArray[$key];
                    $read[$val['user_id']]['lan'] = 0;
                }  else {
                    $read[$val['user_id']]['lan'] = $articleIdArray[$key];
                    $read[$val['user_id']]['zu'] = 0;
                }
            }
        }
        foreach ($read as $rk => $item) {
            $sql .= "UPDATE expert SET read_nums = read_nums+{$item['zu']}, lan_read_nums = lan_read_nums+{$item['lan']} WHERE user_id = {$rk};";
        }
        $dbRet = \Yii::$app->db->createCommand($sql)->execute();
        $redis->del($redisKey);
        return $this->jsonResult(600,'succ',$dbRet);
    }

}
