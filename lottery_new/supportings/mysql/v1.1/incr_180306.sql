/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  HYY
 * Created: 2018-3-6
 */
-- 导出  表 gl_lottery_php.callback_base 结构
CREATE TABLE IF NOT EXISTS `callback_base` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL COMMENT '回调地址',
  `code` varchar(255) NOT NULL COMMENT '回调唯一标识编码(业务回调参数)',
  `times` tinyint(4) NOT NULL DEFAULT '3' COMMENT '最大回调次数',
  `name` varchar(50) NOT NULL COMMENT '回调名称',
  `agent_id` int(11) NOT NULL COMMENT '回调第三方分销商ID',
  `remark` varchar(255) NOT NULL COMMENT '回调备注',
  `c_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回调基础表';

-- 数据导出被取消选择。


-- 导出  表 gl_lottery_php.callback_detail 结构
CREATE TABLE IF NOT EXISTS `callback_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callback_base_id` int(11) NOT NULL COMMENT '回调基础表ID',
  `url` varchar(255) NOT NULL COMMENT '回调地址',
  `exec_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '执行状态0未执行,1加入队列，2执行完成',
  `callback_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '回调状态0未回调1成功,2失败',
  `exec_times` tinyint(4) NOT NULL DEFAULT '0' COMMENT '执行次数',
  `params` varchar(1000) NOT NULL COMMENT '执行回调参数',
  `c_time` int(11) NOT NULL DEFAULT '0',
  `u_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `callback_base_id` (`callback_base_id`),
  KEY `callback_statuc` (`callback_status`),
  KEY `exec_status` (`exec_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回调详情';

-- 数据导出被取消选择。


-- 导出  表 gl_lottery_php.callback_log 结构
CREATE TABLE IF NOT EXISTS `callback_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callback_base_id` int(11) NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL,
  `params` varchar(1000) NOT NULL COMMENT '回调参数',
  `return` varchar(1000) NOT NULL COMMENT '回调返回结果',
  `c_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `callback_base_id` (`callback_base_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回调日志表';

-- 数据导出被取消选择。
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;


