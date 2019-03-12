package main

import (
	"yxapi/api/core"
	"yxapi/api/lib"
	"yxapi/conf"
)

const DEBUG string = "1"

func init() {
	lib.InitConfig(DEBUG) //初始化配置
	conf.RouteInit()

}
func main() {
	core.HttpApp.Run()
}
