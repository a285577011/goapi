<?php
namespace app\modules\tools\kafka;

use app\modules\common\models\LotteryOrder;
use app\modules\common\services\OrderService;
use app\modules\common\models\BettingDetail;
use app\modules\common\helpers\Commonfun;
use app\modules\orders\services\MajorService;
use app\modules\user\models\User;
use app\modules\common\helpers\OrderNews;
use yii\db\Query;
use app\modules\orders\helpers\OrderDeal;
use app\modules\common\services\KafkaService;
use app\modules\common\services\SyncService;
use app\modules\orders\models\AutoOutOrder;
use app\modules\tools\helpers\Nm;
use app\modules\common\helpers\Winning;
/**
 * 自动派奖
 */
class AutoAward implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args = $params;
		Commonfun::updateQueue($this->args['queueId'], 2);
		$lotteryOrderCode = $params['order_code'];
		$ret=Winning::doAwardThird($lotteryOrderCode);
		SyncService::syncFromQueue('CustomMade');
		Commonfun::updateQueue($this->args['queueId'], 3);
		if($ret['code']!=600){
				KafkaService::addLog('AutoAward-fail', $ret);
				return false;
		}
		return true;
	}
}
