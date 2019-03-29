/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-3-7
 */
--追期表  lottery_additional 表新增字段  (张悦玲)
ALTER TABLE `lottery_additional` ADD COLUMN `remark` VARCHAR(100) NULL  COMMENT '备注' AFTER `status`;
