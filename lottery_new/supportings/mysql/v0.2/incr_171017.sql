/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 10 15


-- 创建商务平台表 陈启炜
CREATE TABLE `gl_lottery_php`.`bussiness_platform` (
  `bussiness_platform_id` INT NOT NULL AUTO_INCREMENT COMMENT 'id',
  `bussiness_platform_code` VARCHAR(45) NOT NULL COMMENT '编码标识',
  `platform_name` VARCHAR(100) NOT NULL COMMENT '平台名称',
  `company_name` VARCHAR(45) NULL COMMENT '所属公司名称',
  `status` VARCHAR(45) NOT NULL DEFAULT 0 COMMENT '可用状态 1：可用 2：不可用',
  `create_time` DATETIME NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL COMMENT '系统更新时间',
  PRIMARY KEY (`bussiness_platform_id`),
  INDEX `bussiness_platform_code` (`bussiness_platform_code` ASC),
  INDEX `status` (`status` ASC));

-- 创建商务平台会员表 陈启炜
CREATE TABLE `gl_lottery_php`.`bussiness_platform_user` (
  `bussiness_platform_user_id` INT NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` INT NULL COMMENT '系统用户id',
  `bussiness_platform_id` INT NOT NULL COMMENT '商务平台id',
  `user_tel` VARCHAR(45) NOT NULL COMMENT '商务用户手机号',
  `status` TINYINT NOT NULL COMMENT '可用状态1:可用2:不可用',
  `create_time` DATETIME NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL,
  PRIMARY KEY (`bussiness_platform_user_id`),
  INDEX `plt_id` (`bussiness_platform_id` ASC),
  INDEX `user_tel` (`user_tel` ASC),
  INDEX `status` (`status` ASC),
  INDEX `uid` (`uid` ASC));

--新建11选5的走势表 （张悦玲）
CREATE TABLE `eleven_trend_chart` (
  `eleven_trend_chart_id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_name` varchar(25) NOT NULL COMMENT '彩种名',
  `lottery_code` varchar(25) NOT NULL COMMENT '彩种编号',
  `periods` varchar(50) NOT NULL COMMENT '期数',
  `open_code` varchar(50) NOT NULL COMMENT '开奖号码',
  `optional_omission` varchar(100) DEFAULT NULL COMMENT '任选走势',
  `qone_omission` varchar(100) DEFAULT NULL COMMENT '前一走势',
  `qtwo_omission` varchar(100) DEFAULT NULL COMMENT '前2走势',
  `qthree_omission` varchar(100) DEFAULT NULL COMMENT '前三走势',
  `qtwo_group_omission` varchar(100) DEFAULT NULL COMMENT '前二组选走势',
  `qthree_group_omission` varchar(100) DEFAULT NULL COMMENT '前三组选走势',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`eleven_trend_chart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4;


