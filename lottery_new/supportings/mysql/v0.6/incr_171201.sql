/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-12-1
 */

--订单表新增出票时间 (张悦玲)已执行
ALTER TABLE `gl_lottery_php`.`lottery_order` ADD COLUMN `out_time` datetime DEFAULT NULL, COMMENT '出票时间' AFTER `create_time`;