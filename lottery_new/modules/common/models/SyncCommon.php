<?php
namespace app\modules\common\models;

use Yii;

trait SyncCommon {

	public static $syncData = [];

	public static $syncUpdateType = 'update';

	public static $syncInsertType = 'insert';
	// 同步数据的条件
	/**
	 * 获取主键fields
	 */
	public static function getPk()
	{
		return self::primaryKey()[0];
	}

	/**
	 * 更新数据
	 * @param unknown $update
	 * @param unknown $where
	 */
	public static function upData($update, $where)
	{
		$res = \Yii::$app->db->createCommand()->update(self::tableName(), $update, $where)->execute();
		if ($res)
		{
			self::$syncData[] = ['where' => $where,'type' => self::$syncUpdateType,'field' => implode(',', array_keys($update)),'set'=>$update];
		}
		return $res;
	}

	/**
	 * 插入数据
	 *
	 * @param unknown $update
	 * @param unknown $where
	 */
	public static function addData($insert)
	{
		if (\Yii::$app->db->createCommand()->insert(self::tableName(), $insert)->execute())
		{
			$where = [self::getPk() => \Yii::$app->db->getLastInsertID()];
			self::$syncData[] = ['where' => $where,'type' => self::$syncInsertType,'field' => '*'];
			return Yii::$app->db->getLastInsertID();
		}
		return false;
	}

	/**
	 * 保存数据
	 */
	public function saveData($runValidation = true, $attributeNames = null)
	{
		if ($this->getIsNewRecord())
		{
			$type = self::$syncInsertType;
			$r = $this->insert($runValidation, $attributeNames);
		}else
		{
			$type = self::$syncUpdateType;
			$r = $this->update($runValidation, $attributeNames) !== false;
		}
		if ($r)
		{
			$data = array_filter($this->attributes, function ($v) {
				return $v !== null;
			});
			$where = [];
			$field = $type == self::$syncUpdateType ? implode(',', array_keys($this->getDirtyAttributes(null))) : '*';
			if (isset($data[self::getPk()]))
			{ // 有主键更新
				$where[self::getPk()] = $data[self::getPk()];
			}else
			{
				$where[self::getPk()] = \Yii::$app->db->getLastInsertID();
			}
			self::$syncData[] = ['where' => $where,'type' => $type,'field' => $field];
		}
		return $r;
	}

	/**
	 * 更新数据(方法重写 同步数据时候调用)
	 */
	public static function updateAll($attributes, $condition = '', $params = [])
	{
		$command = static::getDb()->createCommand();
		$command->update(static::tableName(), $attributes, $condition, $params);
		$r = $command->execute();
		if ($r)
		{
			self::$syncData[] = ['where' => $condition,'type' => self::$syncUpdateType,'field' => implode(',', array_keys($attributes)),'set'=>$attributes];
		}
		return $r;
	}

	/**
	 * 保存数据(方法重写 同步数据时候调用)
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		if ($this->getIsNewRecord())
		{
			$type = self::$syncInsertType;
			$r = $this->insert($runValidation, $attributeNames);
		}else
		{
			$type = self::$syncUpdateType;
			$r = $this->update($runValidation, $attributeNames) !== false;
		}
		if ($r)
		{
			$data = array_filter($this->attributes, function ($v) {
				return $v !== null;
			});
			$where = [];
			$field = $type == self::$syncUpdateType ? implode(',', array_keys($this->getDirtyAttributes(null))) : '*';
			if (isset($data[self::getPk()]))
			{ // 有主键更新
				$where[self::getPk()] = $data[self::getPk()];
			}else
			{
				$where[self::getPk()] = \Yii::$app->db->getLastInsertID();
			}
			self::$syncData[] = ['where' => $where,'type' => $type,'field' => $field];
		}
		return $r;
	}

	/**
	 * 添加队列更新同步数据
	 */
	public static function addQueSync($type, $field, $where,$update=[])
	{
		self::$syncData[] = ['where' => $where,'type' => $type,'field' => $field,'set'=>$update];
	}
}