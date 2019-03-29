/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-4-16
 */
--会员资金表新增字段 （张悦玲）
ALTER TABLE `user_funds` ADD COLUMN `withdraw_status` TINYINT(4) NOT NULL DEFAULT '1' COMMENT '是否允许提现  1：允许 2：禁止' AFTER `opt_id`;


