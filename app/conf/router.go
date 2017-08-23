package conf

import (
	"app/api/controller/act"
	"app/api/router"
	//"net/rpc"
)

//路由集合

func RouteInit() {
	//Router := &router.Router{}
	router.AddRouter("/act/index/", act.Index{})
	router.AddRouter("/act/duobao/", act.Duobao{})
	router.AddRouter("/act/turntable/", act.Turntable{})
	//rpc.Register(new(act.Index))
	//rpc.Register(act.Duobao{})
	//rpc.Register(arith)
}
