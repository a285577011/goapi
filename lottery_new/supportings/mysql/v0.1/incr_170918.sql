/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 09 18 

-- 新增apk下载表 （陈启炜）
CREATE TABLE `gl_lottery_php`.`apk_download_url` (
  `apk_download_url` INT NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` VARCHAR(100) NULL COMMENT 'apk名称',
  `url` VARCHAR(255) NOT NULL COMMENT 'apk下载地址',
  `version` VARCHAR(45) NULL COMMENT '版本号',
  `type` VARCHAR(45) NOT NULL DEFAULT '1' COMMENT 'apk类型 1:android 2:ios',
  `status` TINYINT NOT NULL DEFAULT '1' COMMENT '状态 1：下架 2：上架',
  `remark` VARCHAR(255) NULL COMMENT '说明',
  `create_time` DATETIME NULL COMMENT '创建时间',
  PRIMARY KEY (`apk_download_url`));

-- 门店详情新增字段 （张悦玲）
ALTER TABLE store_detail ADD consignee_img2 VARCHAR (100) NULL COMMENT '代销资质图片2';

-- 任选表新增字段 （张悦玲）
ALTER TABLE football_fourteen ADD nine_prize DECIMAL (18,2) NULL COMMENT '任九单注奖金';

-- 门店出票票根表 （张悦玲）
CREATE TABLE `gl_lottery_php`.`out_order_pic` (
  `out_order_pic_id` INT NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` INT(11) NOT NULL  COMMENT '会员ID',
  `order_id` INT(11) NOT NULL  COMMENT '订单ID',
  `order_img1` VARCHAR(100) NULL COMMENT '票根1',
  `order_img2` VARCHAR(100) NULL COMMENT '票根2',
  `order_img3` VARCHAR(100) NULL COMMENT '票根3',
  `order_img4` VARCHAR(100) NULL COMMENT '票根4',
  `create_time` DATETIME NULL COMMENT '创建时间',
  `modfiy_time` DATETIME NULL COMMENT '修改时间',
  `update_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`out_order_pic_id`));