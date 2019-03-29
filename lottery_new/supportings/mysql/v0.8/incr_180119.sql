/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  李佳能 or 龚伟平
 * Created: 2018-1-8
 */

-- ----------------------------
-- 第三方通知表
-- ----------------------------
DROP TABLE IF EXISTS `api_notice`;
CREATE TABLE `api_notice` (
  `notice_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '通知记录ID',
  `user_id` int(11) DEFAULT NULL COMMENT '商户id',
  `name` varchar(25) DEFAULT '' COMMENT '通知名称',
  `periods` varchar(25) DEFAULT '' COMMENT '通知期数',
  `param` varchar(255) NOT NULL DEFAULT '' COMMENT '通知 内容',
  `lose_num` tinyint(4) NOT NULL DEFAULT '0' COMMENT '通知失败次数',
  `url` varchar(255) DEFAULT NULL COMMENT '推送的url',
  `third_order_code` varchar(50) DEFAULT NULL COMMENT '第三方订单编号',
  `lottery_order_code` varchar(50) DEFAULT NULL COMMENT '咕啦订单编号',
  `type` tinyint(2) DEFAULT '1' COMMENT '类型：1=新期通知数字彩【1100】,2=新期通知-足球胜负彩【1101】,3=开奖号码通知-数字彩【1102】, 4=开奖号码通知-足球胜负彩【1103】,5=下注结果通知【1104】,6=出票结果通知【1105】',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `response_msg` varchar(255) DEFAULT '' COMMENT '第三方通知响应',
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`notice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4;


-- 合作商添加des密钥 （龚伟平）已执行
ALTER TABLE `bussiness` ADD COLUMN `des_key`  varchar(50)  DEFAULT NULL COMMENT 'des加密的key' AFTER `status`;