package conf

import (
	"yxapi/api/router"
	//"net/rpc"
	"yxapi/api/controller/order"
)

//路由集合

func RouteInit() {
	//Router := &router.Router{}
	router.AddRouter("/order/order/", order.Order{})
	//rpc.Register(new(act.Index))
	//rpc.Register(act.Duobao{})
	//rpc.Register(arith)
}
