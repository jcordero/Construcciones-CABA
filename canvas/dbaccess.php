<?php
class dbaccess {
	static function conexionDB() {
		return new mysqli(config::$db_host, config::$db_user, config::$db_password,config::$db_database);
	}
	
	static function queryString( $sql, $db = null ) {
		if($db==null)
			$db = self::conexionDB();
	
		$rs = $db->query($sql);
		if($rs) {
			$row = $rs->fetch_array();
			return $row[0];
		}
	
		return "";
	}
	
	static function queryArray( $sql, $db = null ) {
		if($db==null)
			$db = self::conexionDB();
	
		$rs = $db->query($sql);
		if($rs) {
			return $rs->fetch_array();
		}
	
		return array();
	}
}