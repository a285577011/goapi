/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-12-14
 */

--提现withdraw表新增字段  （张悦玲）（已执行）
ALTER TABLE `gl_lottery_php`.`withdraw` ADD COLUMN `remark` VARCHAR(255) DEFAULT NULL COMMENT '备注' AFTER `status`;