<?
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
 
function zend_init()
{
	global $CONFIG;

	if( !isset($CONFIG['zend']['include_path']) )
		$CONFIG['zend']['include_path'] = dirname(__FILE__)."/zend";

	$inc_path = ini_get("include_path");
	ini_set("include_path", $CONFIG['zend']['include_path'].PATH_SEPARATOR.$inc_path);

	if( isset($CONFIG['zend']['modules']) && is_array($CONFIG['zend']['modules']) )
	{
		foreach( $CONFIG['zend']['modules'] as $zend )
			require_once($zend);
	}
}

function zend_load($module)
{
	$module = str_replace("_","/", str_replace(".php.php",".php","$module.php") );
	require_once($module);
}

function zend_font_path()
{
	return dirname(__FILE__)."/zend/fonts/";
}

?>