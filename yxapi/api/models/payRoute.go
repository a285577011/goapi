package models

import ()

type PayRoute struct {
	Base
}

var PayRouteModel *PayRoute

func init() {
	PayRouteModel = &PayRoute{}
	PayRouteModel.Table = "pay_route"
}
