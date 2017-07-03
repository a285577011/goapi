package db

import ()

var adapters map[string]*Adapter

func init() {
	adapters = make(map[string]*Adapter)
}
