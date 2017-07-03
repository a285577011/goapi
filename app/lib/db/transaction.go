package db

import (
	"database/sql"
)

type Transaction struct {
	Tx *sql.Tx
}

func (this *Transaction) Commit() error {
	return this.Tx.Commit()
}

func (this *Transaction) Rollback() error {
	return this.Tx.Rollback()
}

func (this *Transaction) Exec(query string, args ...interface{}) (sql.Result, error) {
	result, err := this.Tx.Exec(query, args...)
	return result, err
}

func (this *Transaction) Prepare(query string) (*sql.Stmt, error) {
	stmt, err := this.Tx.Prepare(query)
	return stmt, err
}
