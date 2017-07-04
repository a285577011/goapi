package conf

import (
	"app/api/controller/act"
	"app/api/router"
)

//路由集合

func RouteInit() {
	//Router := &router.Router{}
	router.AddRouter("/act/index/", act.Index{})
	router.AddRouter("/act/duobao/", act.Duobao{})
}
