<?php
namespace app\modules\tools\kafka;

use app\modules\common\helpers\Commonfun;

class SysBusinessLog implements Kafka
{

	public $args;

	public function run($params)
	{
		$logName = $params['topic'];
		$tableName = 'business_log';
		if (YII_ENV_DEV)
		{
			$tableName .= '_dev';
		}
		\yii::$app->db2->createCommand()->insert($tableName, ['type' => $logName,'data' => $params['data'],'log_ctime' => $params['c_time'],'c_time' => time()])->execute();
	}
}