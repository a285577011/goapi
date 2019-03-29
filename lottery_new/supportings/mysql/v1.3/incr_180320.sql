/**
 * Author:  HYY
 * Created: 2018-3-20
 */
ALTER TABLE `callback_base`
	ADD COLUMN `third_type` TINYINT(4) NOT NULL COMMENT '第三方类型 1分销商2流量商' AFTER `agent_id`,
	ADD COLUMN `type` TINYINT(4) NOT NULL DEFAULT '1' COMMENT '回调类型' AFTER `remark`;