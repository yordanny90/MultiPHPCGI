<?php
/**
 * Yordanny Mejías Venegas.
 * Creado: 2017-10-03
 * Modificado: 2022-05-25
 */
mb_detect_order(array(
    'UTF-8',
    'ASCII',
    'ISO-8859-1',
    'ISO-8859-2',
    'ISO-8859-3',
    'ISO-8859-4',
    'ISO-8859-5',
    'ISO-8859-6',
    'ISO-8859-7',
    'ISO-8859-8',
    'ISO-8859-9',
    'ISO-8859-10',
    'ISO-8859-13',
    'ISO-8859-14',
    'ISO-8859-15',
    'ISO-8859-16',
    'Windows-1251',
    'Windows-1252',
    'Windows-1254',
));

if(!function_exists('detect_charset')){
    /**
     * Detecta el set de caracteres del string
     * @param $var
     * @return false|string
     * @see mb_detect_encoding()
     */
    function detect_charset($var){
        $var=strval($var);
        $cs=mb_detect_encoding($var, mb_detect_order(), true);
        if($cs===false) $cs=mb_detect_encoding($var, mb_list_encodings(), true);
        return $cs;
    }
}

if(!function_exists('detect_charset_list')){
	/**
	 * <b>IMPORTANTE: Solo usar para investigación, test o debug</b><br>
	 * Detecta todos los charset compatibles con el string<br>
	 * @param string $var
	 * @return array
	 * @deprecated No utilizar en producción
	 */
	function detect_charset_list($var){
		$enc_list=mb_list_encodings();
		$list=array();
		$charset_actual=detect_charset($var);
		foreach($enc_list AS $cs){
			if($cs==mb_detect_encoding($var, $cs, true) && $var===mb_convert_encoding($var, $cs, $charset_actual)){
				$list[]=$cs;
			}
		}
		return $list;
	}
}

if(!function_exists('to_charset')){
    /**
     * Convierte un object, array o string a un charset determinado.<br>
     * Solo los datos de tipo string se convertirán.
     * @param mixed $var Valor que se convertirá
     * @param string $charset Nombre del charset al que se convertirá
     * @param bool $clone Si es TRUE, clona los object y array para evitar la modificación de los datos originales.
     * @return mixed
     * @see mb_detect_order()
     */
    function &to_charset($var, $charset=null, $clone=true){
        if(!$charset) $charset=ini_get('default_charset');
        if(!$charset) $charset=mb_internal_encoding();
        if(is_array($var)){
            if($clone) $resp=array();
            else $resp=&$var;
            foreach($var AS $k=>&$v){
                $resp[$k]=&to_charset($v, $charset, $clone);
            }
            return $resp;
        }elseif(is_object($var)){
            if($clone) $resp=new stdClass();
            else $resp=&$var;
            $iterator=get_object_vars($var);
            foreach($iterator AS $k=>&$v){
                $resp->$k=&to_charset($v, $charset, $clone);
            }
            return $resp;
        }elseif(is_string($var)){
            $cs=detect_charset($var);
            if($cs!=$charset){
                if($cs===false){
                    $var=mb_convert_encoding($var, $charset);
                }else{
                    $var=mb_convert_encoding($var, $charset, $cs);
                }
            }
            return $var;
        }else{
            return $var;
        }
    }
}

if(!function_exists('to_utf8')){
    /**
     * @param $var
     * @param bool $clone Si es TRUE, clona los object y array para evitar la modificación de los datos originales.
     * @return array|string
     * @see to_charset()
     */
    function &to_utf8($var, $clone=true){
        return to_charset($var, 'UTF-8', $clone);
    }
}

if(!function_exists('to_iso')){
    /**
     * @param $var
     * @param bool $clone Si es TRUE, clona los object y array para evitar la modificación de los datos originales.
     * @return array|string
     * @see to_charset()
     */
    function &to_iso($var, $clone=true){
        return to_charset($var, 'ISO-8859-1', $clone);
    }
}

if(!function_exists('toHTML')){
    /**
     * Alias de la función htmlentities().<br>
     * Se detecta la codificación del string
     * @param $string
     * @param null $quote_style
     * @param string $charset Default=ini_get('default_charset');
     * @param bool $double_encode
     * @return string
     * @see detect_charset()
     * @see htmlentities()
     */
    function toHTML($string, $quote_style=ENT_COMPAT){
        return htmlentities(strval($string), $quote_style, detect_charset($string));
    }
}

if(!function_exists('fromHTML')){
    /**
     * Alias de la función html_entity_decode().<br>
     * Se detecta la codificación del string
     * @param string $string
     * @return string
     * @see detect_charset()
     * @see html_entity_decode()
     */
    function fromHTML($string, $quote_style=ENT_COMPAT){
        return html_entity_decode($string, $quote_style, detect_charset($string));
    }
}

if(!function_exists('toHTML_all')){
    /**
     * Convierte todos los string del array u objeto
     * @param array|stdClass|string $var
     * @param int $quote_style
     * @param bool $clone
     * @return array|stdClass|string
     * @see toHTML()
     */
    function &toHTML_all($var, $quote_style=ENT_COMPAT, $clone=true){
        if(is_array($var)){
            if($clone) $resp=array();
            else $resp=&$var;
            foreach($var AS $k=>&$v){
                $resp[$k]=&toHTML_all($v, $quote_style, $clone);
            }
            return $resp;
        }elseif(is_object($var)){
            if($clone) $resp=new stdClass();
            else $resp=&$var;
            $iterator=get_object_vars($var);
            foreach($iterator AS $k=>&$v){
                $resp->$k=&toHTML_all($v, $quote_style, $clone);
            }
            return $resp;
        }elseif(is_string($var)){
            $var=toHTML($var, $quote_style);
            return $var;
        }else{
            return $var;
        }
    }
}

if(!function_exists('fromHTML_all')){
    /**
     * Convierte todos los string del array u objeto
     * @param array|stdClass|string $var
     * @param int $quote_style
     * @param bool $clone
     * @return array|stdClass|string
     * @see fromHTML()
     */
    function &fromHTML_all($var, $quote_style=ENT_COMPAT, $clone=true){
        if(is_array($var)){
            if($clone) $resp=array();
            else $resp=&$var;
            foreach($var AS $k=>&$v){
                $resp[$k]=&fromHTML_all($v, $quote_style, $clone);
            }
            return $resp;
        }elseif(is_object($var)){
            if($clone) $resp=new stdClass();
            else $resp=&$var;
            $iterator=get_object_vars($var);
            foreach($iterator AS $k=>&$v){
                $resp->$k=&fromHTML_all($v, $quote_style, $clone);
            }
            return $resp;
        }elseif(is_string($var)){
            $var=fromHTML($var, $quote_style);
            return $var;
        }else{
            return $var;
        }
    }
}

if(!function_exists('fromJSON')){
    /**
     * Alias de json_decode(). Esta función genera un array asociativo por defecto<br>
     * El string se convierte a UTF-8 automáticamente
     * @param $string
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return mixed
     * @see json_decode()
     */
    function fromJSON($string, $assoc=true, $depth=512, $options=0){
        return json_decode($string, $assoc, $depth, $options);
    }
}

if(!function_exists('toJSON')){
	/**
	 * Alias de json_encode().<br>
	 * El valor se convierte a UTF-8 automáticamente
	 * @param object|array|string|bool|int|float $value
	 * @param int $options
	 * @param int $depth
	 * @return false|string
	 * @see json_encode()
	 */
	function toJSON($value, $options=0, $depth=512){
		if(func_num_args()>2){
			$json=json_encode($value, $options, $depth);
			if(!is_string($json)) $json=json_encode(to_utf8($value), $options, $depth);
		}
		elseif(func_num_args()==2){
			$json=json_encode($value, $options);
			if(!is_string($json)) $json=json_encode(to_utf8($value), $options);
		}
		else{
			$json=json_encode($value);
			if(!is_string($json)) $json=json_encode(to_utf8($value));
		}
		return $json;
	}
}

if(!function_exists('sendJSON')){
    /**
     * Realiza el envío del objeto json, finaliza el proceso actual mediante exit()
     * @param object|array $json Objeto JSON
     * @param null $content_to Default=null. Si se especifica se usará como llave para guardar lo que contiene el buffer de salida en el objeto json
     * @param bool $compress Default=true. Si es TRUE, se intenta utilizar la compresión
     * @param bool $cache Default=FALSE.
	 * @param int $options_json Default=0. Ver documentación de {@see json_encode()}
     * @see json_encode()
     * @see ob_gzhandler()
     */
    function sendJSON(&$json, $content_to=null, $compress=true, $cache=false, $options_json=0){
        if($content_to && is_string($content_to)){
            $content='';
            while(ob_get_level()){
                $content=ob_get_clean().$content;
            }
            $json[$content_to]=&$content;
        }
		while(ob_get_level()){
			ob_get_clean();
		}
        if($compress){
            if(function_exists('ob_gzhandler')){
                ob_start('ob_gzhandler');
            }
        }
        if(!$cache){
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);// HTTP 1.1
            header("Pragma: no-cache", true);// HTTP 1.0
        }
        header('Content-Type: application/json', true);
        if(!is_int($options_json)) $options_json=0;
		$json['__memory__']=[
			'usage'=>memory_get_usage(),
			'peak'=>memory_get_peak_usage(),
			'lastErr'=>error_get_last(),
			'lastErrSQL'=>function_exists('sql_error')?sql_error():null,
		];
		if(defined('SQL_LOG_DEBUG') && !empty($GLOBALS[SQL_LOG_DEBUG])){
			$json['__sql__']=array_column($GLOBALS[SQL_LOG_DEBUG], 'sql');
		}
        echo toJSON($json, $options_json);
        exit;
    }
}

if(!function_exists('sendJSON_andStay')){
    /**
     * Realiza el envío del objeto json, y continua con la ejecución de algún proceso secundario
     * @param object|array $json Objeto JSON
     * @param null $content_to Default=null. Si se especifica se usará como llave para guardar lo que contiene el buffer de salida en el objeto json
     * @param bool $cache Default=FALSE.
	 * @param int $options_json Default=0. Ver documentación de {@see json_encode()}
     * @see json_encode()
     * @see ob_gzhandler()
     */
    function sendJSON_andStay(&$json, $content_to=null, $cache=false, $options_json=0){
        if($content_to && is_string($content_to)){
            $content='';
            while(ob_get_level()){
                $content=ob_get_clean().$content;
            }
            $json[$content_to]=&$content;
        }
		while(ob_get_level()){
			ob_get_clean();
		}
        if(!ob_get_level()){
            ob_start();
        }
        if(!$cache){
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);// HTTP 1.1
            header("Pragma: no-cache", true);// HTTP 1.0
            header("Expires: 0");
        }
		if(!is_int($options_json)) $options_json=0;
		$json['__memory__']=[
			'usage'=>memory_get_usage(),
			'peak'=>memory_get_peak_usage(),
			'lastErr'=>error_get_last(),
			'lastErrSQL'=>function_exists('sql_error')?sql_error():null,
		];
		if(defined('SQL_LOG_DEBUG') && !empty($GLOBALS[SQL_LOG_DEBUG])){
			$json['__sql__']=array_column($GLOBALS[SQL_LOG_DEBUG], 'sql');
		}
        echo toJSON($json, $options_json);
        header('Content-Type: application/json', true);
        header('Connection: close',true);
        header('Content-Length: '.($len=ob_get_length()),true);
        header('Content-Encoding: none', true);
        ignore_user_abort(true);
        if(function_exists('apache_setenv')) apache_setenv('no-gzip', '1');
        if(preg_match('/nginx/i', $_SERVER['SERVER_SOFTWARE']??'')){
            header('X-Accel-Buffering: no', true);
        }
        ob_end_flush();
        flush();
        $diff=intval(ini_get('output_buffering'))-$len;
        if($diff>0){
            echo str_repeat("\0", $diff+1);
            flush();
        }
        if(function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    }
}