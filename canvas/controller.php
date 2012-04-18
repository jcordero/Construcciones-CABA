<?php
include 'templates.php';
include 'dbaccess.php';
include '../configuracion.php';
require '../src/facebook.php';
session_start();

$facebook = new Facebook(array(
		'appId'  => '362984040415108',
		'secret' => '1b46b2b5592949f317761896e9980d4a',
));

$fn = (isset($_REQUEST["op"]) ? $_REQUEST["op"] : "");
if($fn) {
	error_log("controller::{$fn}()");
	controller::$fn();
}

class controller {
	
	static function existeUsuario() {
		$id = $_SESSION["user_profile"]["id"];
		
		$db = dbaccess::conexionDB();
		$existe = dbaccess::queryString("select count(*) as cant from usuarios where usu_id=$id",$db);
		$t = new template();
		
		if($existe=="0") {
			echo $t->render(null,"bloque_nuevo_usuario.html");
		} else {
			$usr = dbaccess::queryArray("select * from usuarios where usu_id=$id",$db);
			$usuario = json_encode($usr);
			echo $t->render(array("usuario"=>$usuario, "direccion"=>$usr['usu_direccion']),"bloque_novedades.html");
		}
	}
	
	static function buscarBarrios() {
		$db = dbaccess::conexionDB();
		$rs = $db->query("select * from aux_barrios order by 1");
		if($rs) {
			while($row = $rs->fetch_array()){
				echo '<option value="'.$row["aba_barrio"]. '">'.$row["aba_barrio"];
			}
		}
	}
	
	static function mapaInteractivoUsig() {
		$t = new template();
		echo $t->render(null,"mapaInteractivo.html");
	}

	static function crearUsuario() {
		$u = $_SESSION["user_profile"];
		$db = dbaccess::conexionDB();
		$x = $_REQUEST["x"];
		$y = $_REQUEST["y"];
		$direccion = $_REQUEST["direccion"];
		
		$db->query("INSERT INTO usuarios
						(`usu_id`,
						`usu_nombre`,
						`usu_segundo_nombre`,
						`usu_apellido`,
						`usu_sexo`,
						`usu_estado`,
						`usu_tstamp_ingreso`,
						`usu_tstamp_visita`,
						`usu_tstamp_salida`,
						`usu_lat`,
						`usu_lng`,
						`usu_cod_postal`,
						`usu_direccion`,
						`usu_notificar`)
						VALUES
						(
						'{$u["id"]}',
						'{$u["first_name"]}',
						'{$u["middle_name"]}',
						'{$u["last_name"]}',
						'{$u["gender"]}',
						'ACTIVO',
						NOW(),
						NOW(),
						null,
						{$x},
						{$y},
						'',
						'{$direccion}',
						'NO'
				)");
						
		$t = new template();
		echo $t->render(null,"post_crear_usuario.html");
	}
	
	static function cargarObrasProximas() {
		$top = ($_REQUEST["top"]  ? $_REQUEST["top"]  : 0); 
		$left = ($_REQUEST["left"] ? $_REQUEST["left"] : 0);
		$bottom = ($_REQUEST["bottom"] ? $_REQUEST["bottom"] : 0);
		$right = ($_REQUEST["right"]  ? $_REQUEST["right"]  : 0);
		
		$obras = array();
		$db = dbaccess::conexionDB();
		$sql = "select * from obras where obr_lat>$left and obr_lat<$right and obr_lng>$bottom and obr_lng<$top limit 100";
		$rs = $db->query($sql);
		if($rs) {
			while($row = $rs->fetch_array()){
				$obras[] = array(
					"expediente" 		=> $row["obr_expediente"],
					"estado_tramite"	=> $row["obr_estado_tramite"],
					"fecha"				=> $row["obr_fecha_estado"],
					"tipo"				=> $row["obr_tipo"],
					"profesional"		=> $row["obr_profesional"],
					"direccion"			=> $row["obr_direccion"],
					"lat"				=> (float) $row["obr_lat"],
					"lng" 				=> (float) $row["obr_lng"]
				);
			}
		}
		error_log("controller::cargarObrasProximas() Se envian ".count($obras)." obras");
		echo json_encode($obras);
	}	
}

?>