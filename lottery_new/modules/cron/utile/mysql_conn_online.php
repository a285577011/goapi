<?php
// Find and initialize Composer
// NOTE: You should NOT use this when developing against php-resque.
// The autoload code below is specifically for this demo.
class Conn extends PDO{
// 	private $dsn = "mysql:host=114.55.100.32;dbname=dianjince";	private $username = "development";	private $password = "Develop123!@#";
	
    private $dsn = "mysql:host=117.25.155.149;dbname=db_data2force";	private $username = "gelinroot";	private $password = "glt#789A";
	 
//     private $dsn = "mysql:host=114.55.100.32;dbname=dianjince";	
//     private $username = "development";	
//     private $password = "Develop123!@#";
    
	public $db =null;
	
	public function __construct(){
		parent::__construct($this->dsn,$this->username,$this->password);
	}
	public function query($sql){
		$rs = parent::query($sql);
		if(empty($rs))return null;
		$rs->setFetchMode(PDO::FETCH_ASSOC);
		$result = $rs->fetchAll();
		return $result;
	}
	
	public function findOne($sql){
		$res = $this->query($sql);
		if(empty($res))return null;
		else return $res[0];
	}
	
	public function insert($table,$params,$idname="id"){
		$files = [];
		$vs = [] ;
		$values = [];
		$i =0;
		foreach ($params as $key=>$val){
			$i++;
			$files[$i] = "`".$key."`";
			$vs[$i] = "?";
			$values[$i] = $val;
		}
		$sql = "insert into ".$table."(".implode(",", $files).") values(".implode(",", $vs).")";
		$stmt = parent::prepare($sql);
		foreach ($values as $key=>$v){
			$stmt->bindValue($key, $v);
		}
		
		if($stmt->execute()){
			$id = parent::lastInsertId();
			$result = $this->query("select * from {$table} where {$idname}={$id}");
			if(!empty($result))return $result[0];
			else return null;
		}else{
			print_r($stmt->errorInfo());
			return null;
		}
	}
}


