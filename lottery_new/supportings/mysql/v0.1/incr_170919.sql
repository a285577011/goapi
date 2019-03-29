/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 09 19

-- 任选表新增字段 （张悦玲）
ALTER TABLE football_fourteen ADD win_status TINYINT(4) NULL DEFAULT '0' COMMENT '是否兑奖 0:未兑奖；1:兑奖中；2:详情已兑奖; 3:订单已兑奖' 

-- 赛程事件表字段修改类型 （张悦玲）
ALTER TABLE `gl_lottery_php`.`schedule_event` CHANGE COLUMN `event_time` `event_time` TINYINT(4) NULL DEFAULT NULL COMMENT '事件时间' ;

