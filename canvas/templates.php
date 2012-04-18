<?php
class template {
	private $content = array();
	private $template_file = "";
	
	function setTemplate($template) {
		if($template!="") {
			$path = dirname(dirname(__FILE__))."/templates/";
			$this->template_file = $path.$template;
		}
	}
	
	function setContent($content) {
		$this->content = $content;
	}
	
	function addContent($content,$value="") {
		if(is_array($content))
			$this->content = array_merge($this->content, $content);
		
		if(is_string($content))
			$this->content[$content]=$value;
	}
	
	function render($content=array(),$template="") {
		$html = "";
		$this->addContent($content);
		$this->setTemplate($template);
		
		if( is_readable($this->template_file) ) {
			$src = file_get_contents($this->template_file);
			if( count($content)>0 ) {
				$buscar = array_keys($content);
				array_walk($buscar, function(&$v) { $v="#$v#"; } );
				$reemplazar = array_values($content);
				$html = str_replace($buscar, $reemplazar, $src);
			} else 
				$html = $src;
		} else 
			error_log("template::render() No se encuentra el template '{$this->template_file}'");
		return $html;	
	}
}