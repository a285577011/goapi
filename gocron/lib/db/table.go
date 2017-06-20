package db

import (
	"database/sql"
	"errors"
	"fmt"
	//"git.oschina.net/pbaapp/goyaf"
	"strings"
)

//表结构
type Table struct {
	table   string //表名
	adapter *Adapter
	tx      *Transaction
}

//实例化表对象
func NewTable(table string, adapter *Adapter) *Table {
	return &Table{table: table, adapter: adapter}
}

func (this *Table) SetTx(tx *Transaction) {
	this.tx = tx
	fmt.Println(this.tx)
}

//设置表明
func (this *Table) SetTable(table string) {
	this.table = table
}

//查询参数
type Select struct {
	Columns string
	Where   map[string]interface{}
	Order   string
	Count   int
	Offset  int
	Group   string
}

func (this *Select) columnsToString() string {
	if len(this.Columns) == 0 {
		return "*"
	}
	return this.Columns
}

func (this *Select) orderToString() string {
	if len(this.Order) == 0 {
		return ""
	}
	return "order by " + this.Order
}

func (this *Select) groupToString() string {
	if len(this.Group) == 0 {
		return ""
	}
	return "group by " + this.Group
}

func (this *Select) countToString() string {
	if this.Count == 0 {
		return ""
	}
	return fmt.Sprintf("limit %d,%d", this.Offset, this.Count)
}

func (this *Select) whereTostring() string {
	if len(this.Where) == 0 {
		return ""
	}
	whereString := " where "
	for k, v := range this.Where {
	switch v.(type) {
    case string:

    	v:= v.(string)
		//检测v中是否包含表达式
		if strings.Index(strings.TrimLeft(v, " "), "db_expression:") == 0 {
			vs := strings.Split(strings.TrimLeft(v, " "), ":")
			if vs[1] == "nil" {
				whereString += k + " and "
			} else {
				whereString += k + v + " and "
			}
			continue
		}

		if strings.IndexAny(k, "=><?") == -1 {
			whereString += k + "='" + v + "' and "
			continue
		}
		whereString += strings.Replace(k, "?", "'"+v+"'", -1) + " and "
		break;
    case []string:
    	v:= v.([]string)
    	vs:=strings.Join(v,",")
    	whereString += k + " in (" + vs + ") and "
    	break;
    default:
        break;
     }
	}
	return strings.TrimRight(whereString, " and ")
}

//查询数据
func (this *Table) Select(slt Select) ([]map[string]string, error) {
	var result []map[string]string

	sql := this.SelectToSql(slt)
	rows, err := this.adapter.Query(sql)
	if err != nil {
		return result, err
	}
	defer rows.Close()

	columns, _ := rows.Columns()
	scanArgs := make([]interface{}, len(columns))
	values := make([]interface{}, len(columns))
	for i := range values {
		scanArgs[i] = &values[i]
	}

	for rows.Next() {
		row := make(map[string]string)
		err = rows.Scan(scanArgs...)
		for i, col := range values {
			if col != nil {
				row[columns[i]] = string(col.([]byte))
			} else {
				row[columns[i]] = ""
			}
		}

		result = append(result, row)
	}

	return result, nil
}

func (this *Table) SelectToSql(slt Select) string {
	sql := fmt.Sprintf("SELECT %s FROM %s %s %s %s %s",
		slt.columnsToString(),
		this.table,
		slt.whereTostring(),
		slt.groupToString(),
		slt.orderToString(),
		slt.countToString())
	fmt.Println(sql)
	return sql
}

//将where转换为sql的where语句
func (this *Table) whereToString(where map[string]string) string {
	if len(where) == 0 {
		return ""
	}

	whereString := " where "
	for k, v := range where {
		if strings.IndexAny(k, "=><") == -1 {
			whereString += k + "=" + v + " and "
			continue
		}
		whereString += strings.Replace(k, "?", v, -1) + " and "
	}
	return strings.TrimRight(whereString, " and ")
}

//插入数据
func (this *Table) Insert(data map[string]string) (LastInsertId int64, err error) {
	if len(data) == 0 {
		return 0, errors.New("data is empty")
	}

	//todo:这里要采用结构化的方式
	sqlstring := "INSERT " + this.table + " SET "
	for k, v := range data {
		sqlstring += k + "='" + v + "',"
	}
	sqlstring = strings.TrimRight(sqlstring, ",")
	//goyaf.Debug(sqlstring)

	var res sql.Result
	if this.tx != nil {
		res, err = this.tx.Exec(sqlstring)
	} else {
		res, err = this.adapter.Exec(sqlstring)
	}
	if err != nil {
		return
	}

	LastInsertId, err = res.LastInsertId()
	if err != nil {
		return
	}

	return
}

//更新数据
func (this *Table) Update(data map[string]string, where map[string]string) (affect int64, err error) {
	if len(data) == 0 {
		return 0, errors.New("data is empty")
	}

	sql := "UPDATE " + this.table + " SET "
	for k, v := range data {
		sql += k + "='" + v + "',"
	}
	sql = strings.TrimRight(sql, ",")
	sql += this.whereToString(where)
	//goyaf.Debug(sql)

	stmt, err := this.adapter.Prepare(sql)
	if err != nil {
		return
	}
	defer stmt.Close()

	res, err := stmt.Exec()
	if err != nil {
		return
	}

	affect, err = res.RowsAffected()
	if err != nil {
		return
	}

	return
}

//删除数据
func (this *Table) Delete(where map[string]string) (affect int64, err error) {
	sql := "DELETE FROM " + this.table
	sql += this.whereToString(where)
	//goyaf.Debug(sql)

	stmt, err := this.adapter.Prepare(sql)
	if err != nil {
		return
	}
	defer stmt.Close()

	res, err := stmt.Exec()
	if err != nil {
		return
	}

	affect, err = res.RowsAffected()
	if err != nil {
		return
	}

	return
}
