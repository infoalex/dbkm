<?php
/**
 * Dailyscript - Web | App | Media
 *
 * Clase que se utiliza para editar los .ini utilizados como archivos de configuración
 * de la carpeta config de las app
 *
 * @category    Sistema
 * @package     Libs
 * @author      Iván D. Meléndez
 * @copyright   Copyright (c) 2013 Dailyscript Team (http://www.dailyscript.com.co) 
 */

class DwConfig {
    
    /**
     * Método que se utiliza para leer un .ini
     * @param type $file Nombre del archivo (sin el .ini);
     * @param type $source 
     */
    public static function read($file, $source='', $force=FALSE) { 
        $tmp = $file;
        $file = Config::read($file, $force);
        foreach($file as $seccion => $filas) { 
            foreach($filas as $variable => $valor) { 
                if ($valor == '1') { 
                    $file[$seccion][$variable] = 'On';
                } else if (empty($valor)) { 
                    $file[$seccion][$variable] = ($tmp=='databases') ? NULL : 'Off'; 
                } 
            }                
        }
        if($source) { 
            return (isset($file[$source])) ? $file[$source] : NULL;
        } 
        return $file;
     }
    
    /**
     * Método que se utiliza para escribir un .ini 
     * Se eliminan las variables cuyo valor sea delete-var   
     * @param type $file Nombre del archivo (sin el .ini);
     * @param type $source 
     */
    public static function write($file, $data, $source='') {                
        $vars = self::read($file, '', TRUE);        
        //Verifico si tiene copia del original
        if(!is_file(APP_PATH."config/$file.org.ini")) {               
            //@TODO Verificar que funcione en windows
            $org = APP_PATH."config/$file.ini";
            $des = APP_PATH."config/$file.org.ini";
            exec("cp $org $des");//Copio el original
            @chmod("$des", 0777);//Permisos
            exec("rm -rf $org");//Elimino el original para crear el nuevo            
            exec("touch $des");//Creo el nuevo .ini          
        }
        //Armo el archivo
        $ini = ";; Archivo de configuración".PHP_EOL;
        $ini.= PHP_EOL;
        $ini.= "; Si desea conocer más acerca de este archivo".PHP_EOL;
        $ini.= "; puede abrir el archivo $file.org.ini, el cual tendrá".PHP_EOL;
        $ini.= "; la descripción de los parámetros aplicados.".PHP_EOL;               
        $ini.= PHP_EOL;
        //Verifico si no está el source especificado para crearlo
        if(!array_key_exists($source, $vars)) {
            $vars[$source] = $data;
        } 
        //Recorro el archivo
        foreach($vars as $seccion => $filas) { 
            $ini.= "[$seccion]".PHP_EOL;
            if(is_array($filas)) {
                foreach($filas as $variable => $valor) { 
                    if($source && $seccion==$source) {                            
                        if(array_key_exists($variable, $data)) {                        
                            if($data[$variable]!='delete-var') {
                                $valor = $data[$variable]; 
                            } else {
                                continue;
                            }
                        }
                    } 
                    $variable = Filter::get($variable, 'lower');
                    if ( in_array($valor , array('On', 'Off')) || is_numeric($valor) ) {                     
                        $ini.= "$variable = $valor" . PHP_EOL;
                    } else {
                        $valor = Filter::get($valor, 'htmlspecialchars');
                        $ini.= "$variable = \"$valor\"" . PHP_EOL;
                    } 
                }
            }
            $ini.= PHP_EOL;
        }
        $ini.= PHP_EOL;
        $rs = file_put_contents(APP_PATH . "config/$file.ini", $ini);
        @chmod(APP_PATH . "config/$file.ini", 0777);
        //Actualizo las variables de configuracion
        self::read($file, '', TRUE);
        return $rs;
    }     
    
    /**
     * Método para crear variables tipo define del config.ini
     */
    public static function load() {
        $config = self::read('config');        
        // Nombre del aplicativo
        if (!defined('APP_NAME')) {
            define('APP_NAME', $config['application']['name']);
        }
        //Carga y define automáticamente las variables definidas en el config.ini
        if(isset($config['custom'])) {
            foreach($config['custom'] as $variable => $valor) {
                $variable = Filter::get($variable, 'upper');                
                if(in_array($valor, array('On','Off'))) {
                    $valor = ($valor=='On') ? TRUE : FALSE;
                }
                if($variable=='APP_AJAX') {                    
                    $valor = (Session::get('app_ajax') && ($valor)) ? TRUE : FALSE;                    
                    define($variable, $valor);
                } else {
                    define($variable, $valor);
                }
            }    
        }        
        //Se verifica que tipo de dispositivo es
        Load::lib('Mobile_Detect');
        $detect = new Mobile_Detect();                
        define('MOBILE', $detect->isMobile());
        define('TABLET', $detect->isTablet());
        define('DESKTOP', (!MOBILE && !TABLET) ? TRUE : FALSE);
                
        //Establezco el nombre de la empesa o cliente de la aplicación         
        $empresa = Load::model('config/empresa')->getInformacionEmpresa();
        if(!defined('APP_CLIENT')) {
            define('APP_CLIENT', (empty($empresa->siglas)) ? 'Empresa LTDA' : $empresa->siglas);
        }
        if(!defined('APP_CLIENT_LOGO')) {
            define('APP_CLIENT_LOGO', (empty($empresa->logo)) ? NULL : $empresa->logo);
        }        
    }
    
}

?>
