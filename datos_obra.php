<?php
include 'canvas/templates.php';
include 'canvas/dbaccess.php';
include 'configuracion.php';

$expediente = str_replace('_','/',$_REQUEST["exp"]);
$obra = dbaccess::queryArray("select * from obras where obr_expediente='{$expediente}'");

$t = new template();
$data = array(
		"url" => 'http://ccaba.commsys.com.ar/datos_obra.php?exp='.$_REQUEST["exp"],
		"titulo" => "Obra en ".$obra['obr_direccion']." (".$obra['obr_tipo'].")",
		"expediente" => $expediente,
		"direccion" => $obra['obr_direccion'],
		"tipo"=> $obra['obr_tipo'],
		"responsable"=> $obra['obr_profesional']
);
echo $t->render($data,"datos_obra.html");
?>
