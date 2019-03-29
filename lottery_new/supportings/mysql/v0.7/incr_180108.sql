/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-1-8
 */

--新增接口投注订单表 （张悦玲） (已执行)
CREATE TABLE `api_order` (
  `api_order_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '接口订单ID',
  `api_order_code` varchar(50) NOT NULL COMMENT '接口订单编号',
  `third_order_code` varchar(50) NOT NULL COMMENT '第三方订单编号',
  `user_id` int(11) NOT NULL COMMENT '会员ID',
  `lottery_code` varchar(25) NOT NULL COMMENT '彩种编号',
  `periods` varchar(50) DEFAULT NULL COMMENT '彩种期数',
  `play_code` varchar(50) NOT NULL COMMENT '投注玩法',
  `bet_val` varchar(500) NOT NULL COMMENT '投注内容',
  `bet_money` int(11) NOT NULL DEFAULT '0' COMMENT '投注金额',
  `multiple` int(11) NOT NULL DEFAULT '1' COMMENT '投注倍数',
  `is_add` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否追加',
  `end_time` datetime DEFAULT NULL COMMENT '截止时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:接单成功 2：下单成功 3：重复订单 4：余额不足 5：无门店接单 6：投注内容有误 7：下单失败',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`api_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;