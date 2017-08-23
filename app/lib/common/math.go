package common

import (
	"math/rand"
	"time"
)

// RangeNum 生成一个区间范围的随机数
func RangeNum(min, max int) int {
	rand.Seed(time.Now().Unix())
	randNum := rand.Intn(max - min)
	randNum = randNum + min
	return randNum
}
