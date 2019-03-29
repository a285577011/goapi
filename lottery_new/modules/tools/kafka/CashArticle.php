<?php
namespace app\modules\tools\kafka;

use app\modules\common\models\ApiOrder;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\ArticleRed;
use app\modules\common\services\SyncService;

class CashArticle implements Kafka
{
	public $args;
	public function run($params)
	{
		$this->args=$params;
		Commonfun::updateQueue($this->args['queueId'], 2);
		try
		{
			$articleCash = new ArticleRed();
			$result = $articleCash->cashArticle($this->args['user_id'], $this->args['cust_no'], $this->args['user_name'], $this->args['cash_type'], $this->args['user_art_id'], $this->args['user_art_code'], $this->args['total'], $this->args['body']);
			SyncService::syncFromQueue('CashArticle');
			Commonfun::updateQueue($this->args['queueId'], 3);
			return $result;
		}
		catch (\yii\db\Exception $ex)
		{
			Commonfun::updateQueue($this->args['queueId'], 4);
			return json_encode($ex);
		}
	}
}