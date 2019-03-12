package models

import ()

type Recharge struct {
	Base
}

var RechargeModel *Recharge

func init() {
	RechargeModel = &Recharge{}
	RechargeModel.Table = "recharge"
}
