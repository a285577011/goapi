package models

import ()

var GlModel map[string]*Base

func GetActModel(table string) *Base {
	GlModel = make(map[string]*Base)
	if GlModel[table] == nil {
		GlModel[table] = &Base{Table: table}
	}
	return GlModel[table]
}

func init() {
	GlModel = make(map[string]*Base)
}
