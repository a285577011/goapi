/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 09 20

-- 会员合买等级表 （张悦玲）
CREATE TABLE `gl_lottery_php`.`expert_level` (
  `expert_level_id` INT NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` INT(11) NOT NULL  COMMENT '会员ID',
  `cust_no` VARCHAR(15) NOT NULL  COMMENT '会员编号',
  `level` TINYINT(4) NOT NULL DEFAULT 0 COMMENT '等级',
  `level_name` VARCHAR(100) NOT NULL DEFAULT '铁牌' COMMENT '等级名',
  `value` INT(11) NOT NULL DEFAULT 0 COMMENT 'V值（成长值）',
  `made_nums` INT(11) NULL DEFAULT 0 COMMENT '定制人数',
  `win_nums` INT(11) NULL DEFAULT 0 COMMENT '中奖次数',
  `issue_nums` INT(11) NULL DEFAULT 1 COMMENT '发单次数',
  `succ_issue_nums` INT(11) NULL DEFAULT 0 COMMENT '成功发单次数',
  `win_amount` DECIMAL(18,2) NULL DEFAULT 0 COMMENT '中奖金额',
  `create_time` DATETIME NULL COMMENT '创建时间',
  `modify_time` DATETIME NULL COMMENT '修改时间',
  `update_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`expert_level_id`));

-- 详情表字段长短修改 （张悦玲）
ALTER TABLE `gl_lottery_php`.`betting_detail` CHANGE COLUMN `bet_val` `bet_val` VARCHAR(1000) NOT NULL COMMENT '投注内容' ,CHANGE COLUMN `odds` `odds` VARCHAR(1000) NULL DEFAULT NULL COMMENT '赔率' ;

-- 合买表字段长度修改 （张悦玲）
ALTER TABLE `gl_lottery_php`.`programme` CHANGE COLUMN `bet_val` `bet_val` VARCHAR(1000) NULL COMMENT '投注内容' ,CHANGE COLUMN `play_code` `play_code` VARCHAR(700) NULL DEFAULT NULL COMMENT '玩法code',
CHANGE COLUMN `play_name` `play_name` VARCHAR(1500) NULL DEFAULT NULL COMMENT '玩法名';

-- 订单表字段长度修改 （张悦玲）
ALTER TABLE `gl_lottery_php`.`lottery_order` CHANGE COLUMN `bet_val` `bet_val` VARCHAR(1000) NULL COMMENT '投注内容' ,CHANGE COLUMN `play_code` `play_code` VARCHAR(700) NULL DEFAULT NULL COMMENT '玩法code', 
CHANGE COLUMN `odds` `odds` VARCHAR(1000) NULL DEFAULT NULL COMMENT '玩法code', CHANGE COLUMN `play_name` `play_name` VARCHAR(1500) NULL DEFAULT NULL COMMENT '玩法名';

-- 追期表字段长度修改 （张悦玲）
ALTER TABLE `gl_lottery_php`.`lottery_additional` CHANGE COLUMN `bet_val` `bet_val` VARCHAR(1000) NULL COMMENT '投注内容' ,CHANGE COLUMN `play_code` `play_code` VARCHAR(700) NULL DEFAULT NULL COMMENT '玩法code',
CHANGE COLUMN `play_name` `play_name` VARCHAR(1500) NULL DEFAULT NULL COMMENT '玩法名';

-- 合买表新增字段 （张悦玲）
ALTER TABLE programme ADD level_deal TINYINT(4) NULL DEFAULT '0' COMMENT '是否已经过等级处理 0:未处理；1:已处理'