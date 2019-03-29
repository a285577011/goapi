/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  A
 * Created: 2017-12-18
 */

--pay_record新增字段 （张悦玲）（已执行）
ALTER TABLE `pay_record` ADD COLUMN `discount_money` DECIMAL(18,2) NOT NULL DEFAULT '0' COMMENT '折扣金额' AFTER `update_time`;
ALTER TABLE `pay_record` ADD COLUMN `discount_detail` VARCHAR(1000) NOT NULL DEFAULT '' COMMENT '折扣明细' AFTER `discount_money`;
ALTER TABLE `pay_record` ADD COLUMN `total_money` DECIMAL(18,2) NOT NULL DEFAULT '0' COMMENT '订单总支付金额（未优惠折扣）' AFTER `discount_detail`;