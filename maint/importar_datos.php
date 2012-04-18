<?php
include '../configuracion.php';

class importar {
	
	private function conexionDB() {
		return new mysqli(config::$db_host, config::$db_user, config::$db_password,config::$db_database);
	}
	
	private function queryString( $sql, $db = null ) {
		if($db==null)
			$db = self::conexionDB();
	
		$rs = $db->query($sql);
		if($rs) {
			$row = $rs->fetch_array();
			return $row[0];
		}
	
		return "";
	}
	
	
	private function recuperarDatasetRemoto() {
		$ch = curl_init (config::$url) ;
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
		$res = curl_exec ($ch) ;
		curl_close ($ch) ;
		return $res ;
	}
	
	private function geoRef($direccion) {
		$d = str_replace(array('?', ','),  array('Ñ',''), $direccion);
		$p = strrpos($d, " ");
		$calle = utf8_decode(trim(substr($d,0,$p)));
		
		$altura = intval(trim(substr($direccion,$p)));
		$url = "http://usig.buenosaires.gov.ar/servicios/GeoCoder?cod_calle=".rawurlencode($calle)."&altura=$altura";
		
		//Pido la georeferenciacion a la USIG
		$ch = curl_init ($url) ;
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
		$json = curl_exec ($ch) ;
		curl_close ($ch) ;
		
		//Acomodo la respuesta chota de JSON usig
		$json = str_replace("'",'"',$json);
		$json = substr($json,1,strlen($json)-2);
		
		//Creo un objeto PHP a partir del objeto JSON
		$obj = json_decode($json);
		
		//No existe la calle?
		if( $obj=="ErrorCalleInexistente" )
		{
			//Hago otro intento en caso que sea una avenida
			$url = "http://usig.buenosaires.gov.ar/servicios/GeoCoder?cod_calle=".rawurlencode($calle." AV.")."&altura=$altura";
			
			//Pido la georeferenciacion a la USIG
			$ch = curl_init ($url) ;
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
			$json = curl_exec ($ch) ;
			curl_close ($ch) ;
			
			//Acomodo la respuesta chota de JSON usig
			$json = str_replace("'",'"',$json);
			$json = substr($json,1,strlen($json)-2);
			
			//Creo un objeto PHP a partir del objeto JSON
			$obj = json_decode($json);
		}	
		
		if(is_object($obj))
			return $obj;
		else
			return $json;
	}
	
	public function traerDataset() {
		//Cual es el ultimo batch?
		$db = $this->conexionDB();
		$ultimo = intval( $this->queryString("select max(bat_id) from batch", $db) );
		$id = $ultimo + 1;
		
		//Inicio el proceso batch
		$db->query("INSERT INTO `batch`(`bat_id`,`bat_tstamp`,`bat_cantidad`,`bat_estado`) VALUES ({$id},NOW(),0,'EN PROCESO')");
		
		//Recupero el CSV (ISO-8859-1)
		$datos = $this->recuperarDatasetRemoto();
		$obras = explode("\n",$datos);

		//Resultado
		$insertados = 0;
		$actualizados = 0;
		$borrados = 0;
		
		//Importo el dataset en la base de obras. Verifico si la obra existe.
		$j = 0;
		foreach($obras as $obra) {
			//Descarto la primera linea
			if($j>0) {
				//Partes "N_EXPEDIENTE","DIRECCION","SMP","ESTADO_TRAMITE","FECHA_ESTADO","TIPO_OBRA","NOMBRE_PROFESIONAL"
				$partes_obra = str_getcsv(utf8_encode($obra));
				if( count($partes_obra)==7 ) {
					$exp = $partes_obra[0];
					
					//Existe el expediente?
					$exp = $this->queryString("select obr_expediente from obras where obr_expediente='{$exp}'", $db);
					if($exp=="") {
							
						//Abro smp
						if($partes_obra[2]!="")
							list($seccion, $manzana, $parcela) = explode("-",$partes_obra[2]);
						else {
							$seccion = $manzana = $parcela = "";
						}			
						
						//Georeferencia
						$geo = $this->geoRef($partes_obra[1]);
						if(is_object($geo)) {
							$x = $geo->x;
							$y = $geo->y;
						} else {
							error_log("importar::traerDataset() Exp: {$exp} Falla georef para '{$partes_obra[1]}' error: $geo");
							$x = 0;
							$y = 0;
						}			
							
						$db->query("INSERT INTO `obras`(`obr_expediente`,`obr_direccion`,`obr_estado_tramite`,`obr_fecha_estado`,`obr_tipo`,`obr_profesional`,`obr_seccion`,`obr_manzana`,`obr_parcela`,`obr_lat`,`obr_lng`,`obr_fecha_creacion`,`obr_estado`,`obr_batch`) VALUES
								('{$exp}','{$partes_obra[1]}','{$partes_obra[3]}','{$partes_obra[4]}','{$partes_obra[5]}','{$partes_obra[6]}','{$seccion}','{$manzana}','{$parcela}',{$x},{$y},NOW(),'ACTIVO',$id)");	
						$insertados++;
					}
					else
					{
						//La georeferencia esta sonada?
						$x = intval($this->queryString("select obr_lat from obras where obr_expediente='{$exp}'", $db));
						if($x==0) {
							$geo = $this->geoRef($partes_obra[1]);
							if(is_object($geo)) {
								$x = $geo->x;
								$y = $geo->y;
							} else {
								error_log("importar::traerDataset() Exp: {$exp} Falla georef para '{$partes_obra[1]}' error: $geo");
								$x = 0;
								$y = 0;
							}
							$db->query("UPDATE `obras` SET `obr_lat`={$x}, `obr_lng`={$y}, `obr_estado_tramite`='{$partes_obra[3]}', `obr_fecha_estado`='{$partes_obra[4]}', `obr_batch`={$id} WHERE `obr_expediente`='{$exp}'");
						}
						else
							$db->query("UPDATE `obras` SET `obr_estado_tramite`='{$partes_obra[3]}', `obr_fecha_estado`='{$partes_obra[4]}', `obr_batch`={$id} WHERE `obr_expediente`='{$exp}'");
						$actualizados++;
					}		
				}
			}
			$j++;	
		}
		
		//Cuanto quedan borrados?
		$borrados = intval( $this->queryString("select count(*) from obras where obr_batch < {$id}", $db));
		
		//Marco los borrados como tales
		$db->query("UPDATE `obras` SET `obr_estado`='INACTIVO' WHERE obr_batch < {$id}");
		
		error_log("importar::traerDataset() BATCH $id, INSERTADOS=$insertados ACTUALIZADOS=$actualizados BORRADOS=$borrados");

		//Fin del proceso batch
		$db->query("UPDATE `batch` set `bat_cantidad`={$insertados},`bat_estado`='FINALIZADO' WHERE `bat_id`={$id}");
	}
}

$i = new importar();
$i->traerDataset();

?>