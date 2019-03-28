package models

import ()

type Temple struct {
	Base
}

var TempleModel *Temple

func init() {
	TempleModel = &Temple{}
	TempleModel.Table = "temple"
}
