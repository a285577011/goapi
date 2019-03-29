<?php  

namespace app\modules\tools\helpers;
class Des  
{  
    private $key = "";  
    private $iv = "";
   
    /** 
    * 构造，传递二个已经进行base64_encode的KEY与IV 
    * 
    * @param string $key 
    * @param string $iv 
    */  
    function __construct ($key, $iv = '12345678')
    {  
        if (empty($key)) {
            echo 'key and iv is not valid';  
            exit();  
        }  
        $this->key = $key;  
        $this->iv = $iv;
    }
   
    /** 
    *加密 
    * @param <type> $value 
    * @return <type> 
    */  
    public function encrypt ($value)  
    {  
        return openssl_encrypt($value,'DES-EDE3-CBC',$this->key,null, $this->iv);
    }

    /**
     *解密
     * @param <type> $value
     * @return <type>
     */
    public function decrypt ($value)
    {
        return openssl_decrypt($value, 'DES-EDE3-CBC', $this->key, 0, $this->iv);
    }

    /**
     *加密
     * @param <type> $value
     * @return <type>
     */
    public function encrypt_ecb ($value)
    {
        return openssl_encrypt($value,'des-ecb',$this->key);
    }

    /**
     *解密
     * @param <type> $value
     * @return <type>
     */
    public function decrypt_ecb ($value)
    {
        return openssl_decrypt($value, 'des-ecb', $this->key);
    }


    private function PaddingPKCS7 ($data)  
    {  
        $block_size = mcrypt_get_block_size('tripledes', 'cbc');  
        $padding_char = $block_size - (strlen($data) % $block_size);  
        $data .= str_repeat(chr($padding_char), $padding_char);  
        return $data;  
    }  
   
    private function UnPaddingPKCS7($text)  
    {  
        $pad = ord($text{strlen($text) - 1});  
        if ($pad > strlen($text)) {  
            return false;  
        }  
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {  
            return false;  
        }  
        return substr($text, 0, - 1 * $pad);  
    }  
}  
?> 