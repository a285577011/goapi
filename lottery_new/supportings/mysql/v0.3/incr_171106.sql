/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-11-6
 */
--合买表新增字段  （张悦玲）
ALTER TABLE programme ADD store_no INT(11) NULL  COMMENT '门店编号';

--新建实单分项表 (张悦玲)
CREATE TABLE `order_share` (
  `order_share_id` int(11) NOT NULL AUTO_INCREMENT,
  `organiz_id` int(11) NOT NULL COMMENT '发起人ID',
  `organiz_no` varchar(25) NOT NULL COMMENT '发起人cust_no',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `with_nums` int(11) NOT NULL DEFAULT '0' COMMENT '跟单数',
  `recom_remark` varchar(256) NOT NULL COMMENT '推荐理由',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_share_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4;
