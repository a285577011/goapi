/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  kevi
 * Created: 2017-12-7
 */

--新增对奖记录表 (陈启炜)已执行
CREATE TABLE `check_lottery_result_record` (
  `check_lottery_result_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_code` varchar(45) NOT NULL COMMENT '''彩种''',
  `periods` varchar(45) NOT NULL COMMENT '''期数''',
  `open_num` varchar(45) NOT NULL COMMENT '''开奖号码''',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`check_lottery_result_record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;

