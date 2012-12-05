<?php
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
function webservice_init()
{
	global $CONFIG;

	if( !isset($CONFIG['webservice']['base_path']))
		$CONFIG['webservice']['base_path'] = dirname(__FILE__)."/";

	if( !isset($CONFIG['webservice']['call_timeout']))
		$CONFIG['webservice']['call_timeout'] = 60;

	$CONFIG['class_path']['system'][] = dirname(__FILE__)."/webservice/";
	$CONFIG['class_path']['system'][] = $CONFIG['webservice']['base_path'].'current/';
}

function webservice_generate_classes($wsdl_path,$soap_class_name,$classes_path = false,$wsdlonly = false,$fetchwsdl = true,$cert_file=false)
{
	global $CONFIG;

	try
	{
		if( !$classes_path )
			$classes_path = $CONFIG['webservice']['base_path']."/updated/";

		if( $fetchwsdl )
		{
			log_debug("Fetching new WSDL file '$wsdl_path'");
			$wsdl = file_get_contents($wsdl_path);
			$wsdl_path = $CONFIG['webservice']['base_path']."$soap_class_name.wsdl";
			if( file_exists($wsdl_path) )
				unlink($wsdl_path);
			//log_debug("Storing WSDL ($wsdl_path)");
			file_put_contents($wsdl_path,$wsdl);
		}
		
		if( $wsdlonly )
			return;

		$wsdl_path = ".".makerelative($wsdl_path);

		log_debug("Generating wrapper classes for service $soap_class_name");
		$client = new WebServiceClient($wsdl_path,false);
		$client->GenerateDataClasses("",$classes_path);

		$cp_to_restore = false;
		foreach( $CONFIG['class_path']['system'] as $cp_to_restore=>$path )
		{
			if( $path == $CONFIG['webservice']['base_path'].'current/' )
			{
				$CONFIG['class_path']['system'][$cp_to_restore] = $classes_path;
				break;
			}
		}

		foreach( glob($classes_path."*.class.php") as $class )
		{
			require_once($class);
		}

		if( $cp_to_restore )
		{
			$CONFIG['class_path']['system'][$cp_to_restore] = $CONFIG['webservice']['base_path'].'current/';
		}

		$client->GenerateServiceClass($soap_class_name,$classes_path,$cert_file);
	}
	catch(Exception $ex)
	{
		trace($ex);
	}
}

?>