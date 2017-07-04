package main

import (
	"app/api/core"
	"app/conf"
	"app/lib"
)

const DEBUG string = "1"

func init() {
	lib.InitConfig(DEBUG) //初始化配置
	conf.RouteInit()

}
func main() {
	core.HttpApp.Run()
}
