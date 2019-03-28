package common

import (
	"fmt"
	"strconv"
	"time"
	"yxapi/api/lib/redis"
)

// 生成一个订单编号
func CreateOrderSn(prefix string) string {
	now := time.Now()
	year, month, day := now.Date()
	timeString := strconv.Itoa(year) + strconv.Itoa(int(month)) + strconv.Itoa(day) + strconv.Itoa(time.Now().Hour())
	//"YX:" . $orderType . ":" . $time . ":" . $orderType;
	redisKey := "YX:" + prefix + ":" + timeString + prefix
	orderSnPre := "YX" + prefix + timeString + prefix
	orderNum := getOrderNum(redisKey)
	return orderSnPre + orderNum
}

/**
获取订单自增编号
*/
func getOrderNum(redisKey string) string {
	redisM := redis.GetRedis()
	Num, err := redisM.Incr(redisKey, 1)
	if err != nil {
		panic("订单自增失败")
	}
	NumStr := strconv.Itoa(Num)
	redisM.ComDo("expire", redisKey, 7200)
	NumStr = fmt.Sprintf("%07d", Num)
	return NumStr
}
