/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Administrator
 * Created: 2018-02-08
 */
--推广门店表
CREATE TABLE `store_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '门店咕啦id',
  `user_tel` varchar(45) DEFAULT NULL COMMENT '手机号码',
  `store_name` varchar(45) DEFAULT NULL,
  `store_code` varchar(45) DEFAULT NULL COMMENT '彩店编码',
  `qr_url` varchar(100) DEFAULT NULL COMMENT '二维码地址',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态0:待审核 1：不可用 2：可用',
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

--兑换码表
CREATE TABLE `redeem_code` (
  `redeem_code_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'pk_id',
  `redeem_code` varchar(32) NOT NULL COMMENT '兑换码',
  `value_amount` decimal(18,0) NOT NULL COMMENT '价值金额',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '兑换码状态 1、未使用（默认） 2、已使用 3、已过期 4、已废除',
  `store_id` int(11) DEFAULT NULL COMMENT '兑换店铺ID',
  `settle_date` date DEFAULT NULL COMMENT '结算日期',
  `type` int(11) DEFAULT '0' COMMENT '所属类型 1、体彩推广',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`redeem_code_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;


--兑换记录表
CREATE TABLE `redeem_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_code` varchar(45) DEFAULT NULL COMMENT '门店编号',
  `open_id` varchar(100) DEFAULT NULL COMMENT '用户openId',
  `redeem_code_id` int(11) DEFAULT NULL COMMENT '兑换码id',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态 0：未领取 1：已领取',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
