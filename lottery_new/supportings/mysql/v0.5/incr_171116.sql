/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-11-16
 */
--订单表删除字段 (陈启炜) 
ALTER TABLE `gl_lottery_php`.`lottery_order` 
DROP COLUMN `is_generate_child`,
DROP COLUMN `is_win`,
DROP COLUMN `pay_time`,
DROP COLUMN `out_order_code`,
DROP INDEX `out_order_code` ;

--用户表删除字段 (陈启炜) 
ALTER TABLE `gl_lottery_php`.`user` 
DROP COLUMN `account_time`,
DROP COLUMN `balance`,
DROP COLUMN `user_email`;

--agents表新增字段 (陈启炜) 
ALTER TABLE `gl_lottery_php`.`agents` 
ADD COLUMN `to_url` VARCHAR(100) NULL COMMENT '跳转url地址' AFTER `upagents_name`;
ALTER TABLE `gl_lottery_php`.`agents` 
ADD COLUMN `agents_code` VARCHAR(45) NULL DEFAULT NULL COMMENT '代理商简码pike' AFTER `review_remark`;

