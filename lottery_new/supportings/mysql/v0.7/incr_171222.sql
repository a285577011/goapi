/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-12-22
 */
--新增字段赛程表 （张悦玲） （已执行）
ALTER TABLE `gl_lottery_php`.`schedule` ADD COLUMN `open_mid` VARCHAR(50) DEFAULT 0 COMMENT '赛程对外编号' AFTER `schedule_mid`;
ALTER TABLE `gl_lottery_php`.`lan_schedule` ADD COLUMN `open_mid` VARCHAR(50) DEFAULT 0 COMMENT '赛程对外编号' AFTER `schedule_mid`;


--修改代理商ip配置表新增类别  kevi
ALTER TABLE `gl_lottery_php`.`agents_ip` ADD COLUMN `type` TINYINT NOT NULL DEFAULT 1 COMMENT '类型：1代理商 2:接口合作商' AFTER `status`;

--创建合作商表 kevi
CREATE TABLE `gl_lottery_php`.`bussiness` (
  `bussiness_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL DEFAULT '名称' COMMENT '合作商名称',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 1启用 2 禁用',
  `create_time` DATETIME NULL,
  PRIMARY KEY (`bussiness_id`));


--创建ip白名单 kevi
CREATE TABLE `bussiness_ip` (
  `bussiness_ip_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `bussiness_id` int(10) NOT NULL COMMENT '合作商ID',
  `ip` varchar(15) NOT NULL COMMENT 'IP地址',
  `status` tinyint(1) DEFAULT '1' COMMENT '是用状态：1使用 2禁用',
  PRIMARY KEY (`bussiness_ip_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
SELECT * FROM gl_lottery_php.bussiness_ip;


--创建api列表 kevi

CREATE TABLE `gl_lottery_php`.`api_list` (
  `api_list_id` INT NOT NULL AUTO_INCREMENT,
  `api_name` VARCHAR(45) NULL COMMENT 'api名称',
  `api_url` VARCHAR(45) NULL COMMENT 'api地址',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：1可用 2禁用',
  PRIMARY KEY (`api_list_id`));
