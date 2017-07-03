package conf

import (
"app/controller/act"
)
//路由集合
var Routes map[string]interface{}
func RouteInit() map[string]interface{}{
	Routes["/act/index/"] = act.Index{}
	return Routes
}
func init() {
	Routes = make(map[string]interface{})
}
