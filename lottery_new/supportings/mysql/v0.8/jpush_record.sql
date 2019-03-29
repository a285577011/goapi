/*
Navicat MySQL Data Transfer

Source Server         : gl
Source Server Version : 50718
Source Host           : 211.149.205.201:3306
Source Database       : gl_lottery_php

Target Server Type    : MYSQL
Target Server Version : 50718
File Encoding         : 65001

Date: 2018-01-26 14:14:58
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `jpush_record`
-- ----------------------------
DROP TABLE IF EXISTS `jpush_record`;
CREATE TABLE `jpush_record` (
  `jpush_notice_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '极光推送ID',
  `titile` varchar(30) NOT NULL DEFAULT '' COMMENT '推送标题',
  `msg` varchar(255) NOT NULL DEFAULT '' COMMENT '推送内容',
  `jump_url` varchar(100) DEFAULT '' COMMENT '跳转url',
  `push_time` datetime DEFAULT NULL COMMENT '推送时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '推送状态',
  `response` varchar(500) DEFAULT '' COMMENT '推送返回响应',
  `opt_id` tinyint(4) DEFAULT NULL COMMENT '操作人ID',
  `create_time` datetime NOT NULL COMMENT '推送时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`jpush_notice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4;
