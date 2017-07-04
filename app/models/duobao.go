package models

import ()

type Duobao struct {
	Base
}

var DuobaoModel *Duobao

func init() {
	DuobaoModel = &Duobao{}
	DuobaoModel.Table = "duobao"
}
