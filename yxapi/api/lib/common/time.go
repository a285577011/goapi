package common

import (
	"strconv"
	"time"
)

// RangeNum 生成一个区间范围的随机数
func GetTimeInt() string {
	now := strconv.FormatInt(time.Now().Unix(), 10)
	return now
}

// RangeNum 生成一个区间范围的随机数
func GetTimeDate() string {
	now := time.Now().Format("2006-01-02 15:04:05")
	return now
}
