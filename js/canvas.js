$(document).ready(function(){
	
	//Primero valido si el usuario esta en el sistema, o le tengo que pedir 
	//el codigo postal y explicarle de que se trata
	cc.existeUsuario();
	
});

function ccaba() {
	this.existeUsuario = function() {
		this.controller("existeUsuario",function(data, textStatus, jqXHR) { 
			$('#usuario_nuevo').html(data); 
		});
	}	
		
	this.controller = function(operacion, callback, data, responseType) {
		if(typeof data=="undefined")
			data = {"op":operacion};
		else
			data["op"] = operacion;
		
		if(typeof responseType=="undefined")
			responseType = "html";
		
		$.ajax({
			  url: "controller.php",
			  dataType: responseType,
			  data: data,
			  success: callback,
			  error: function(jqXHR, textStatus, errorThrown) {alert(textStatus);}
			});
	}
	
	this.selectedOption = null;
	this.miMapa = null;
	
	this.esperarUsig = function() {
		if( typeof usig=="undefined" ) {
			setTimeout(cc.esperarUsig,200);
		} else {
			cc.selectedOption = null;
						
			ac = new usig.AutoCompleter('b', {
	       		skin: 'usig2',
	       		onReady: function() {
	       			$('#b').val('').removeAttr('disabled').focus();	        			
	       		},
	       		afterSelection: function(option) {
	       			if (option instanceof usig.Direccion || option instanceof usig.inventario.Objeto) {
	       				cc.selectedOption = option;
	       			}
	       		},
	       		afterGeoCoding: cc.afterGeoCoding
	       	});
									
			cc.miMapa = new usig.MapaInteractivo('mapa');
		}
	}	
	
	this.esperarMapaUsig = function() {
		if( (typeof usig=="object") && (typeof usig.MapaInteractivo=="function")) {
			cc.miMapa = new usig.MapaInteractivo('mapa',{
				onReady:function(){
					if(typeof usuario=="object") {
						try {
							var pt = new usig.Punto(parseFloat(usuario.usu_lat), parseFloat(usuario.usu_lng));
							cc.miMapa.addMarker(pt, true, "Mi casa");			
						}catch(e) {
							console.error('Agrego marker home: '+e);
						}
						cc.miMapa.api.events.on({'moveend':cc.mapMove});
						cc.cargarObrasProximas(cc.miMapa.api.getExtent());
					}
				}
			});
		}		
		else {
			setTimeout(cc.esperarMapaUsig,200);
		}
	};

	this.mapMove = function(e) {
		var ext = e.object.getExtent();
		cc.cargarObrasProximas(ext);
	};
	
	this.cargarObrasProximas = function(bounds) {
		var limites = {
				"top":bounds.top,
				"left":bounds.left,
				"bottom":bounds.bottom,
				"right":bounds.right
		};
		this.controller("cargarObrasProximas",function(data, textStatus, jqXHR) { 
			//Crear los markers en el mapa
			for(var j=0; j<data.length; j++) {
				var obra = data[j];
				try {
					var pt = new usig.Punto(obra.lat, obra.lng);
					var h = 'Expediente:<b>'+obra.expediente + '</b>' +
							'<br/> Dirección: <b>' + obra.direccion + '</b>' + 
							'<br/> Tipo de obra: <b>' + obra.tipo + '</b>' + 
							'<br/> Fecha: <b>' + obra.fecha + '</b>' + 
							'<br/><button onclick="cc.datosObra(\''+obra.expediente+'\')">Ver detalle de obra</button>';
					cc.miMapa.addMarker(pt, false, h );
				}catch(e) {
					console.error('Agrego marker obra: '+e);
				}
			} 
		}, limites, "json");
	};
	
	this.datosObra = function(expediente) {
		var h = '<iframe id="novedades" name="novedades" width="650" height="400" frameborder="0" src="http://ccaba.commsys.com.ar/datos_obra.php?exp='+encodeURIComponent(expediente)+'"></iframe>';
		$('#novedades').html(h);
	};
	
	this.afterGeoCoding = function(pt) {
		if (pt instanceof usig.Punto) {
			if (cc.selectedOption instanceof usig.Direccion) {
				cc.selectedOption.setCoordenadas(pt);
				console.log("Ubicacion del usuario: x=" + pt.x + " y=" + pt.y);
			}				
			var dirId = cc.miMapa.addMarker(cc.selectedOption, true);
		}		
	}
	
	this.crearUsuario = function() {
		if( cc.selectedOption ) {
			var calle = cc.selectedOption.getCalle();
			var altura = cc.selectedOption.getAltura();
			var cruce = cc.selectedOption.getCalleCruce();
			
			var usr = {
			 "x":cc.selectedOption.getCoordenadas().getX(),
			 "y":cc.selectedOption.getCoordenadas().getY(),
			 "direccion":(calle ? calle.toString() : "") + " " + (altura ? altura.toString() : "CRUCE ") + (cruce ? cruce.toString() : "") 
			};
			
			this.controller("crearUsuario",function(data, textStatus, jqXHR) { 
				$('#usuario_nuevo').html(data); 
			}, usr);
		}
	}
}
var cc = new ccaba();
