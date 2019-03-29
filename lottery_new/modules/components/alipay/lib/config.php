<?php

class config {

    const Config = array(
        //应用ID,您的APPID。
        'app_id' => "2017061607505632",
        //商户私钥，您的原始格式RSA私钥
        'merchant_private_key' => "MIIEowIBAAKCAQEAusqnNJ2230U2I7dQMUAy0npfa8baLCDodlHc8q/rplWGNmDi34ipuZHPkMubSWTlm+melck/kUC2wnDp6ax5suYwgtvm9l0zqh+nvrCPVACAAVOUKBD7dNkdBu2jXSoQti7HN5N+iaF4K9+BJJr9S7arLhmwcISObl+kjCXuudFNprotR428yXe1YnadVBle6yfGVdbrKVoqznOWFw2Vporb2nsIT3cS6IdGVJLkDF5To+hs4p2AyYNPY2hAYHFXBrRAiC2cEQq6bbsPa94hcpuoTVfhHoO8ZXZnksRTZVYmdY+K16Q3we1oAV+FfkR6p2OxW2nwogAqLhV5qL7lSQIDAQABAoIBAEGMV/y407GqAgOqknOWCb+evdl+YJVXvvu2Yoivf0xRetWeTj8PIDBEoMg5CvdIduKtqr75bls0kG3PXeZoZhSHfsKNFJGjxzuN/DmIj+N0gXb1s2oT+4nXnr4NqmRJHLAx3ir8kU0O4rLSrekAkp59Lbjxvt7dRXYqDf44WXvPA6gByygguAinlMWSHzX5ZAj0C0oy8T/rXqP22Am/00voev2o5pd3eNtEEbXF/K0dkvGjntbtgEWYseZDKyZ7TDiYiWWZ8gk+VMC7TSwxbEv7atSTClqoB/iCEeEjbis1OdrU3cE/ucydgCYy3Xp7BH7dc7VWRCA/zs4aJbG7QAECgYEA3j5oaqhaiARO6Oj3r77v56aWpMwGYhY2D6lXXbg9r/dJMKoDJXQgfyiM5kRiKSHEcanBfYV+Ybw4tZunYQWZvZHJGVYciEQFZ5g8NgafrvCzzXufyyKS1mScZZqY3jq5NszqtvdZdompscJyFkpfRXYVNlGe9DowtRZclxi1bAECgYEA1ym+qCyueTaGnt4Xa2k1YFsHejVYz+183JJWWS3bktNNb4uz1kvl8i42kCFhb6fmIdXsmeQHxso5nqrd3X0ZgwRZcXDEHm3jQvLhvdytYJ1hlCg72V2rocMG/xs4FqGos4xtyZbltuiG5H0vbMiJ3/jN3KyULlAfnTBg+LF3GUkCgYAWEOY3KM4MUTkwgOkOxwt02aJ9bFB57rChb3PgN0nQreHTdh3n2xba6UDMICAK63JgwrUWbGm29IRA69p7lJ3GJ7Jq1JTypqZOudvIlXHHYdjIXyznc2BW7xhkMixZbEU1frUTQ43baiGyJ5dCRVIeSHIkuYfpwdlSMY0x518kAQKBgQCOJA7HDRWKECJpC3FPTSas7BYJfvqYCl0lXitbKLdYzOzoFtOMa4GSN0Nmfhbfa3zVt3xhwcn9YpUkI98ENmPHMPVhwsxdCd1L2iaVhhanr/DJrrazB3WeHLgfibzI/qzzSH8y4NQKvJx4j9bnt6eV5ckh0oTl/5qS/mXhhWOXuQKBgG8tRtAXF5NBk/Irmfqed1r8lgigexBVeC3xONtP39QvAPh1NIbX9fBxZWbYIKMOzenckqc1fEjUbqPYWMb/83mnhzKWzx4rtj/O+c7kuku44ynaUXFSvYHg/8fFa1SRkuFiCCqYTjMfDnfodApLZ9933C6Xv1K6/iHdeSUDvl8r",
        //异步通知地址
        'notify_url' => "",
        //同步跳转
        'return_url' => "",
        //编码格式
        'charset' => "UTF-8",
        //签名方式
        'sign_type' => "RSA2",
        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuSTznIqMrp67hxLMLsj4db9iEiZNxtMAJBJWXbPyUnA0nOYdaCSzHpJBdJiZP6sqB5lP6k2qtCpXCK/e5jI4quH7XerUdNAeAsiXqbGAlkYVUlfJas5VtHXyC0F7UNcLg4XFh4lr1WWNHtFsPK7zXp0tlE2RFnnpOA1N34tV9nje0S+09HilwQ9HYln5/AqnDes2nLdDmVO3pfdy6tvpRQpA83I1eUsxjI796KEs+IgXQTWEHsSTbAszdY3gUEn90NqW2C7I7+yOIc2jJr1+5iYeDioq7zHy5NHCmjqFmvjngMNo0i37tUs0NRu+tnVZDQVdFlGql1bGMwbmJw16OQIDAQAB",
    );

}
