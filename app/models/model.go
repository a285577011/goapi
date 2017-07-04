package models

import (
	"app/lib"
	//"fmt"
)

var ActModel map[string]*Base
var BgbModel map[string]*Base

func GetActModel(table string) *Base {
	ActModel = make(map[string]*Base)
	if ActModel[table] == nil {
		ActModel[table] = &Base{Options: map[string]string{
			"driver":   lib.GetConfig("db")["actdb.driver"].String(),
			"host":     lib.GetConfig("db")["actdb.host"].String(),
			"port":     lib.GetConfig("db")["actdb.port"].String(),
			"database": lib.GetConfig("db")["actdb.database"].String(),
			"username": lib.GetConfig("db")["actdb.username"].String(),
			"password": lib.GetConfig("db")["actdb.password"].String(),
			"charset":  lib.GetConfig("db")["actdb.charset"].String(),
		}, Table: table}
	}
	return ActModel[table]
}
func GetBgbModel(table string) *Base {
	if BgbModel[table] == nil {
		BgbModel[table] = &Base{Options: map[string]string{
			"driver":   lib.GetConfig("db")["bgdb.driver"].String(),
			"host":     lib.GetConfig("db")["bgdb.host"].String(),
			"port":     lib.GetConfig("db")["bgdb.port"].String(),
			"database": lib.GetConfig("db")["bgdb.database"].String(),
			"username": lib.GetConfig("db")["bgdb.username"].String(),
			"password": lib.GetConfig("db")["bgdb.password"].String(),
			"charset":  lib.GetConfig("db")["bgdb.charset"].String(),
		}, Table: table}
	}
	return BgbModel[table]
}

func init() {
	BgbModel = make(map[string]*Base)
	ActModel = make(map[string]*Base)
}
