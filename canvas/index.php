<?php
require '../src/facebook.php';
include 'templates.php';

session_start();

$facebook = new Facebook(array(
		'appId'  => '362984040415108',
		'secret' => '1b46b2b5592949f317761896e9980d4a',
));

// Get User ID
$user = $facebook->getUser();
if ($user) {
	
	try {
		$user_profile = $facebook->api('/me');
		$_SESSION["user_profile"] = $user_profile;
	} catch (FacebookApiException $e) {
		error_log($e);
		$user = null;
	}
	
	//Datos del usuario, cargo contenido para el usuario autenticado
	if($user) {
		$t = new template();
		$user_profile["saludo"] = ($user_profile['gender']=="female" ? "Bienvenida" : "Bienvenido");
		echo $t->render($user_profile,"usuario.html");		
	}
} else {
	//User not authenticated
	$loginUrl = $facebook->getLoginUrl(array(
	    'canvas'    => 1,
	    'fbconnect' => 0,
	    'scope' => 'email,publish_stream,offline_access',
	    'redirect_uri' => 'https://apps.facebook.com/construccionescaba/'
	));
	$t = new template();
	echo $t->render(array("login"=>$loginUrl),"landing.html");
}
?>