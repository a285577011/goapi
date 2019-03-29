<?php

use app\modules\common\helpers\ArticleRed;
use app\modules\common\helpers\Commonfun;

/**
 * @auther GL zyl
 * @date 2017年7月25日 
 * @param
 * @return 
 */
class cash_article_job {

    public function perform() {
        Commonfun::updateQueue($this->args['queueId'], 2);
        try {
            $articleCash = new ArticleRed();
            $result = $articleCash->cashArticle($this->args['user_id'], $this->args['cust_no'], $this->args['user_name'], $this->args['cash_type'], $this->args['user_art_id'], $this->args['user_art_code'], $this->args['total'], $this->args['body']);
            Commonfun::updateQueue($this->args['queueId'], 3);
            return $result;
        } catch (\yii\db\Exception $ex) {
            Commonfun::updateQueue($this->args['queueId'], 4);
            return json_encode($ex);
        }
    }

}
