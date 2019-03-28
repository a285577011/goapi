package main

import (
	//"fmt"
	"log"
	"net/http"
	_ "net/http/pprof"
	"yxapi/api/core"
	"yxapi/api/lib"
	"yxapi/conf"
)

const DEBUG string = "0"

func init() {
	lib.InitConfig(DEBUG) //初始化配置
	conf.RouteInit()

}
func main() {
	go func() {
		log.Println(http.ListenAndServe("localhost:10000", nil))
	}()
	core.HttpApp.Run()
}
