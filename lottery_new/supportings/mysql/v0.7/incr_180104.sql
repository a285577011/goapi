/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-1-4
 */

--新建赛事公告表 （张悦玲）已执行
CREATE TABLE `match_notice` (
  `match_notice_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '赛事公告主键',
  `match_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '赛事类型 1：足球 2：篮球 3：其他',
  `notice_title` varchar(150) NOT NULL COMMENT '赛事公告标题',
  `notice` varchar(500) NOT NULL COMMENT '赛事公告内容',
  `notice_time` datetime DEFAULT NULL COMMENT '公告发布时间',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'CURRENT_TIMESTAMP',
  PRIMARY KEY (`match_notice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4;


--新增微信模版消息存储表字段 （龚伟平） 已执行
ALTER TABLE `wx_msg_record`
ADD COLUMN `order_code`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单号' AFTER `create_time`;
