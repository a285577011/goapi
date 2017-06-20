package models
import (
	"gocron/lib"
)

func GetActModel(table string) *Base{
	ActModel := &Base{Options:map[string]string{
		"driver":      lib.GetConfig("db")["actdb.driver"].String(),
		"host":        lib.GetConfig("db")["actdb.host"].String(),
		"port":        lib.GetConfig("db")["actdb.port"].String(),
		"database":    lib.GetConfig("db")["actdb.database"].String(),
		"username":    lib.GetConfig("db")["actdb.username"].String(),
		"password":    lib.GetConfig("db")["actdb.password"].String(),
		"charset":     lib.GetConfig("db")["actdb.charset"].String(),
	},Table:table}
	return ActModel
}
func GetBgbModel(table string) *Base{
	ActModel := &Base{Options:map[string]string{
		"driver":      lib.GetConfig("db")["bgdb.driver"].String(),
		"host":        lib.GetConfig("db")["bgdb.host"].String(),
		"port":        lib.GetConfig("db")["bgdb.port"].String(),
		"database":    lib.GetConfig("db")["bgdb.database"].String(),
		"username":    lib.GetConfig("db")["bgdb.username"].String(),
		"password":    lib.GetConfig("db")["bgdb.password"].String(),
		"charset":     lib.GetConfig("db")["bgdb.charset"].String(),
	},Table:table}
	return ActModel
}
