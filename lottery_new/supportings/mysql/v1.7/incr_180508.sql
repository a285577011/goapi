/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-5-8
 */
--流量单表新增字段（张悦玲）(已执行)
ALTER TABLE `api_order` ADD COLUMN `major_type` TINYINT(4) NULL DEFAULT 0 COMMENT '奖金优化类型 0：无奖金优化 1：平均优化 2：博热优化 3：博冷优化' AFTER `status`;

