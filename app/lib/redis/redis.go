package redis

import (
	"app/lib"
	"errors"
	"github.com/garyburd/redigo/redis"
	"strconv"
	"time"
)

var DefaultKey = "redis"

// Cache is Redis cache adapter.
type Cache struct {
	p        *redis.Pool // redis connection pool
	conninfo string
	dbNum    int
	key      string
	password string
}

// StartAndGC start redis cache adapter.
// config is like {"key":"collection key","conn":"connection info","dbNum":"0"}
// the cache item in redis are stored forever,
// so no gc operation.
func (rc *Cache) StartAndGC(cf map[string]string) error {
	if _, ok := cf["key"]; !ok {
		cf["key"] = DefaultKey
	}
	if _, ok := cf["conn"]; !ok {
		return errors.New("config has no conn key")
	}
	if _, ok := cf["dbNum"]; !ok {
		cf["dbNum"] = "0"
	}
	if _, ok := cf["password"]; !ok {
		cf["password"] = ""
	}
	rc.key = cf["key"]
	rc.conninfo = cf["conn"]
	rc.dbNum, _ = strconv.Atoi(cf["dbNum"])
	rc.password = cf["password"]

	rc.connectInit()

	c := rc.p.Get()
	defer c.Close()

	return c.Err()
}

// connect to redis.
func (rc *Cache) connectInit() {
	dialFunc := func() (c redis.Conn, err error) {
		c, err = redis.Dial("tcp", rc.conninfo)
		if err != nil {
			return nil, err
		}

		if rc.password != "" {
			if _, err := c.Do("AUTH", rc.password); err != nil {
				c.Close()
				return nil, err
			}
		}

		_, selecterr := c.Do("SELECT", rc.dbNum)
		if selecterr != nil {
			c.Close()
			return nil, selecterr
		}
		return
	}
	// initialize a new pool
	rc.p = &redis.Pool{
		MaxIdle:     3,
		IdleTimeout: 180 * time.Second,
		Dial:        dialFunc,
	}
}

// actually do the redis cmds
func (rc *Cache) Do(commandName string, args ...interface{}) (reply interface{}, err error) {
	c := rc.p.Get()
	defer c.Close()

	return c.Do(commandName, args...)
}
func (rc *Cache) Lock(key string, expire int) bool {
	n, err := rc.Do("SETNX", key, 1)
	// 若操作失败则返回
	if err != nil {
		return false
	}
	// 返回的n的类型是int64的，所以得将1或0转换成为int64类型的再比较
	if n == int64(1) {
		// 设置过期时间
		rc.Do("EXPIRE", key, expire)
		return true
	}
	return false

}

// Delete delete cache in redis.
func (rc *Cache) Delete(key string) error {
	var err error
	_, err = rc.Do("DEL", key)
	return err
}

// Get cache from redis.
func (rc *Cache) Get(key string) interface{} {
	if v, err := redis.String(rc.Do("GET", key)); err == nil {
		return v
	}
	return nil
}

func GetRedis(dbNum string) *Cache {
	redis := &Cache{key: DefaultKey}
	conifg := map[string]string{
		"conn":     lib.GetConfig("db")["redis.ip"].String() + ":" + lib.GetConfig("db")["redis.port"].String(),
		"dbNum":    dbNum,
		"password": lib.GetConfig("db")["redis.password"].String(),
	}
	err := redis.StartAndGC(conifg)
	if err != nil {
		redis = nil
	}
	return redis
}
