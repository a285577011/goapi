package models

import (
	"app/lib/db"
	"app/lib/redis"
	"strconv"
)

type Award struct {
	Base
}

const REDISTABLE string = "act#award"

var AwardModel *Award

func init() {
	AwardModel = &Award{}
	AwardModel.Table = "award"
}
func (this *Award) GetStockById(id string) int {
	redis := redis.GetRedis("1")
	key := REDISTABLE + ":stock-" + id
	if num := redis.Get(key); num != nil {
		numInt, _ := strconv.Atoi(num.(string))
		return numInt
	}
	where := map[string]interface{}{
		"id": id,
	}
	stock := this.FetchColumn(db.Select{Where: where, Columns: "stock"}, "stock") //->where(['is_show'=>1])->order('sort ASC')->select();
	redis.ComDo("SET", key, stock)
	stockInt, _ := strconv.Atoi(stock)
	return stockInt
}

/**
 * 减少奖品库存
 * @param unknown $id
 * @return mixed|NULL|unknown|string[]|unknown[]|object
 */
func (this *Award) DecrStockById(id string) int {
	stock := this.GetStockById(id)
	if stock < 1 {
		return -1
	}
	redis := redis.GetRedis("1")
	key := REDISTABLE + ":stock-" + id
	num, err := redis.Decr(key, 1)
	if err != nil {
		return -1
	}
	if num < 0 { //超出库存
		return -1
	}
	sql := "UPDATE " + this.Table + " SET stock=stock-1 WHERE id=" + id
	stmt, err := this.GetAdapter().Prepare(sql)
	defer stmt.Close()

	stmt.Exec()
	return num
}
