package models

import (
	"fmt"
	"gocron/lib/db"
	"gocron/lib"
)
type Base struct {
	Table      string
	PrimaryKey string
	Options    map[string]string
	Adapter    *db.Adapter
	Tx         *db.Transaction
}
type ActModel struct {
		Base
}
func (this *Base) GetAdapter() *db.Adapter {
	if len(this.Options) == 0 {
		this.SetOptions()
	}
	return db.NewAdapter(this.Options)
}

//设置数据库连接参数
func (this *Base) SetOptions() {
	this.Options = map[string]string{
		"driver":      lib.GetConfig("db")["actdb.driver"].String(),
		"host":        lib.GetConfig("db")["actdb.host"].String(),
		"port":        lib.GetConfig("db")["actdb.port"].String(),
		"database":    lib.GetConfig("db")["actdb.database"].String(),
		"username":    lib.GetConfig("db")["actdb.username"].String(),
		"password":    lib.GetConfig("db")["actdb.password"].String(),
		"charset":     lib.GetConfig("db")["actdb.charset"].String(),
	}
}

//查询单条记录
func (this *Base) FetchRow(slt db.Select) map[string]string {
	tableGateway := db.NewTable(this.Table, this.GetAdapter())
	slt.Count=1
	//slt.Count:=1
	result, err := tableGateway.Select(slt)
	if err != nil {
		fmt.Println("mysql find error:", err)
	}
	if len(result) == 0 {
		return make(map[string]string)
	}
	return result[0]
}

//插入数据
func (this *Base) Insert(data map[string]string) int64 {
	tableGateway := db.NewTable(this.Table, this.GetAdapter())
	tableGateway.SetTx(this.Tx)

	result, err := tableGateway.Insert(data)
	if err != nil {
		fmt.Println("mysql insert error:", err)
		panic(err)
	}
	return result
}

//查询单条记录
func (this *Base) Update(data map[string]string, where map[string]string) int64 {
	tableGateway := db.NewTable(this.Table, this.GetAdapter())
	result, err := tableGateway.Update(data, where)
	if err != nil {
		fmt.Println("mysql update error:", err)
	}
	return result
}

//查询列表数据
func (this *Base) FetchAll(slt db.Select) []map[string]string {
	tableGateway := db.NewTable(this.Table, this.GetAdapter())
	result, err := tableGateway.Select(slt)
	if err != nil {
		fmt.Println("mysql fetchAll error:", err)
	}
	return result
}
//查询单条记录
func (this *Base) FetchColumn(slt db.Select,field string) string{
	tableGateway := db.NewTable(this.Table, this.GetAdapter())
	slt.Count=1
	result, err := tableGateway.Select(slt)
	if err != nil {
		fmt.Println("mysql find error:", err)
	}
	if len(result) == 0 {
		return ""
	}
	return result[0][field]
}

