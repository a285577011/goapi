package models

import ()

type PayRouteDetail struct {
	Base
}

var PayRouteDetailModel *PayRouteDetail

func init() {
	PayRouteDetailModel = &PayRouteDetail{}
	PayRouteDetailModel.Table = "pay_route_detail"
}
