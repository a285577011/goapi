package router

import (
//"fmt"
//"net/http"
)

//控制器集合
var Routers map[string]interface{}

//增加控制器
func AddRouter(path string, controller interface{}) {
	Routers[path] = controller
}

//增加控制器
func GetRouters() map[string]interface{} {
	return Routers
}
func init() {
	Routers = make(map[string]interface{})
}
