/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-2-24
 */

-- user 表新增字段 (张悦玲) (已执行)
ALTER TABLE `user` ADD COLUMN `spread_type` TINYINT(4) NOT NULL DEFAULT 0 COMMENT '是否为推广员 1：是 0：否' AFTER `is_operator`;
ALTER TABLE `user` ADD COLUMN `rebate` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '返点' AFTER `spread_type`;