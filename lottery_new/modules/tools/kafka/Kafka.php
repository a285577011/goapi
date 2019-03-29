<?php
namespace app\modules\tools\kafka;
/**
 * kafka消费者接口
 * @author Administrator
 *
 */
interface Kafka
{

	/**
	 * 
	 * @param unknown $params
	 */
	public function run($params);
}