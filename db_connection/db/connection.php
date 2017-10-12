<?php 

	require dirname(__FILE__)."/../config/params.php";
	date_default_timezone_set('America/Bogota');

	/**def 
	* Connection DB
	*/
	class DB 
	{
		private $host;
		private $db;
		private $user;
		private $password;
		private $conexion;
		public  $table;

		public function __construct()
		{

			$this->conectar();
		}

		 // Para conectarnos a traves de PDO a la base de datos
		private function conectar(){
  			
			global $config;
  			
  			try {
				$this->host = $config["db"]["host"];
				$this->db   = $config["db"]["db"];
				$this->user = $config["db"]["user"];
				$this->pass = $config["db"]["password"];
  				$this->conexion = new PDO('mysql:host='.$this->host.';dbname='.$this->db, $this->user,$this->pass); 
  			
  			} catch (Exception $e) {
  			
  				echo $e;
  			
  			}
		}	
		
		//este metodo se encarga de recibir una consulta sql y ejecutarla
		public function consultar($sql){
			  $datos = $this->conexion->query($sql);				  
			  return $datos;
		}
		//public static function 

		public function save(){

			$fiels = [];
			$values = [];

			if ($this->id == null || $this->id == "") {
				foreach ($this as $key => $value) {
					if ($value != "" && $key != "table") {
						array_push($fiels, $key);
						array_push($values, "'{$value}'");
					}
				}

				//array_push($fiels, "create");
				//array_push($values, "{$this->getTimeStamp()}");

				$fiels = implode(",", $fiels);
				$values = implode(",", $values);


				$this->conectar();
				$sql = "INSERT INTO {$this->db}.{$this->table}  ( {$fiels} ,created_at,updated_at) VALUES ( {$values},'{$this->getTimeStamp()}','{$this->getTimeStamp()}' )";

				  if($datos = $this->conexion->query($sql)){
					    return $this->conexion->lastInsertId();
				  }				
				  else{
					  var_dump($this->conexion->errorInfo(). " sql: " . $sql);
				  }  			
			}else{

				return $this->update();
			}
		}

		public function findOne($column, $value)
        {
            $this->conectar();
            $sql = "SELECT * FROM {$this->db}.{$this->table} WHERE {$column} = {$value}";

//            $resultado = mysql_query($sql);
//
//            print_r($resultado); die();

            if($result = $this->conexion->query($sql))
			{

				print_r($result); die();
//                if ($result->num_rows > 0) {
//                    // output data of each row
//                    while($row = $result->fetch_assoc()) {
//                        echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
//                    }
//                } else {
//                    echo "0 results";
//                }
//                $this->conexion->close();
            }
            else
			{
                var_dump($this->conexion->errorInfo());
            }
        }

		public function update(){

			$fiels_update = [];
			$id = $this->id;

			if (isset($id)) {
				

				foreach ($this as $key => $value) {
					if ($value != "" && $key != "table" && $key !="id") {
						array_push($fiels_update, "{$key} = '{$value}'");
					}
				}
				$fiels_update = implode(",", $fiels_update);

				$this->conectar();
				$sql = "UPDATE {$this->db}.{$this->table}  SET {$fiels_update} , updated_at = '{$this->getTimeStamp()}' WHERE id = {$id}";

				  if($datos = $this->conexion->query($sql)){
					    return $this->conexion->lastInsertId();
				  }				
				  else{
					  return false;
				  }  				  
			}
		}
		public function sql($sql){

			$this->conectar();
			$sql = "UPDATE {$this->db}.{$this->table}  SET {$fiels_update} , updated_at = '{$this->getTimeStamp()}' WHERE id = {$id}";

			  if($datos = $this->conexion->query($sql)){
				    return $this->conexion->lastInsertId();
			  }				
			  else{
				  return false;
			  }  				  
		}

		private function getTimeStamp(){
			//date_default_timezone_set('America/Bogota');
			return date('Y-m-d H:i');
		}
	}

?>
