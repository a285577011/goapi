/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 10 09

-- 合买表新增字段 （张悦玲）
ALTER TABLE programme ADD programme_univalent DECIMAL(18,2) NULL  COMMENT '每份金额' ;
ALTER TABLE programme ADD programme_last_number INT(11) NULL  COMMENT '剩余份额' ;
ALTER TABLE programme ADD made_nums INT(11) NULL  COMMENT '预购总份额' ;

-- 订单表新增字段 （张悦玲）
ALTER TABLE lottery_order ADD send_status TINYINT(4) NOT NULL DEFAULT '0' COMMENT '微信推送状态' ;

-- 定制跟单删除字段（张悦玲）
ALTER TABLE diy_follow DROP COLUMN bet_money;

-- 定制跟单新增字段 （张悦玲）
ALTER TABLE diy_follow ADD bet_nums INT(11) NULL DEFAULT '0' COMMENT '每个方案认购份额' ;

-- 修改队列表queue,args字段长度 （陈启炜）
ALTER TABLE `gl_lottery_php`.`queue`  CHANGE COLUMN `args` `args` VARCHAR(500) NULL DEFAULT NULL COMMENT '任务参数' ;
