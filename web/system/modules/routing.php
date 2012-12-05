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
 
global $ROUTES;

/**
 * Initializes the routing module.
 * Registers a HOOK_PARSE_URI to 'function routing_parse_uri()'
 * CONFIG['routing']:
 * - ['datasource']				DataSource for routing table, defaults to 'internal'
 * - ['allow_unrouted_calls']	true allows calls to Controller/Method. Route auto updating will only work if this is true.
 * - ['auto_update_routes']		true will automatically update the routing table if the code of the controller changed (Route attributes).
 * - ['redirect_to_route']		true will redirect calls like ?page=x&event=y to x/y. Note: ALL POST ARGUMENTS WILL BE LOST!
 */
function routing_init()
{
	global $CONFIG, $ROUTES;

	if( !isset($CONFIG['routing']['datasource']) )
		$CONFIG['routing']['datasource'] = 'internal';
	if( !isset($CONFIG['routing']['allow_unrouted_calls']) )
		$CONFIG['routing']['allow_unrouted_calls'] = true;
	if( !isset($CONFIG['routing']['auto_update_routes']) )
		$CONFIG['routing']['auto_update_routes'] = true;
	if( !isset($CONFIG['routing']['redirect_to_route']) )
		$CONFIG['routing']['redirect_to_route'] = true;

	$CONFIG['class_path']['model'][] = dirname(__FILE__).'/routing/';
	$ROUTES = false;
	register_hook_function(HOOK_PARSE_URI,'routing_parse_uri');
}

/**
 * This is a HOOK_PARSE_URI handler funktion.
 * Will prepare globals $PAGE and $event and replace the
 * default behaviour (?page=x&event=y).
 * htaccess needs to include the following lines:
 * RewriteEngine On
 * # redirect inexistant requests to index.php
 * RewriteCond %{REQUEST_FILENAME} !-f     # inexistant file
 * RewriteCond %{REQUEST_FILENAME} !-d     # inexistant dir
 * RewriteCond %{REQUEST_URI} !index.php   # not index.php
 * RewriteRule (.*) /index.php/$1 [L]      # redirect to index.php and append route
 */
function routing_parse_uri()
{
	global $CONFIG, $ROUTES, $PAGE, $event;

	if(( $ROUTES === false ) && (system_is_module_loaded('globalcache')))
		$ROUTES = globalcache_get('routing_url_routes');
	if( $ROUTES === false )
	{
		$ds = model_datasource($CONFIG['routing']['datasource']);
		$ROUTES = $ds->Select("UrlRoute");
		if(system_is_module_loaded('globalcache'))
			globalcache_set('routing_url_routes', $ROUTES, $CONFIG['system']['cache_ttl']);
	}

	$virtual = explode("index.php",$_SERVER['PHP_SELF']);
	$current_cs = trim($virtual[1],"/");
	$current = strtolower($current_cs);
	
	if( $CONFIG['routing']['redirect_to_route'] )
	{
		$rp = isset($_REQUEST['page'])?strtolower(filter_var($_REQUEST['page'], FILTER_SANITIZE_STRING)):false;
		$re = isset($_REQUEST['event'])?strtolower(filter_var($_REQUEST['event'], FILTER_SANITIZE_STRING)):false;
		if( $rp && $re )
		{
			// search the routes because REQUEST args are unknown case
			foreach( $ROUTES as $k=>$route )
				if( strtolower($route->controller) == $rp && strtolower($route->method) == $re )
					redirect($route->controller,$route->method,$_GET);
		}
	}

	foreach( $ROUTES as $k=>$route )
	{
//		if( !starts_with($current, strtolower($route->route)) )
//		{
//			log_debug("check $current agains ".$route->route);
//			continue;
//		}
		if( strtolower($route->route) != $current )
			continue;

		if( class_exists($route->controller) )
		{
			$ref = System_Reflector::GetInstance($route->controller);
			if( !$route->method || $ref->hasMethod($route->method) )
			{
				$PAGE = $route->controller;
				$event = $route->method?$route->method:false;
				break;
			}
		}
		log_debug("deleting inexistent route ".$route->route);
		$route->Delete();
		unset($ROUTES[$k]);
	}

	if( !$PAGE && $CONFIG['routing']['allow_unrouted_calls'] )
	{
		$current = explode("/",$current);

		if(count($current) > 2)
		{
			$GLOBALS['routing_args'] = array_slice(explode("/",$current_cs),2);
//			header("HTTP/1.1 404 Not Found");
//			die("Not Found");
		}
		if( count($current) > 0 && (class_exists($current[0]) || in_object_storage($current[0])) )
		{
			$PAGE = $current[0];
			$event = count($current)>1?$current[1]:false;
		}
	}

	$PAGE  = (isset($PAGE)  && $PAGE!="") ?$PAGE :$CONFIG['system']['default_page'];
	$event = (isset($event) && $event!="")?$event:$CONFIG['system']['default_event'];

	// If default values are used check if the controller
	// has the event mathod, else check all routes for
	// the controller
	if( !class_exists($PAGE) )
		return;
	
	$ref = System_Reflector::GetInstance($PAGE);
	if( !$ref->hasMethod($event) )
	{
		$current = strtolower("$PAGE/$event");
		foreach( $ROUTES as $k=>$route )
		{
			if( strtolower($route->route) != $current )
				continue;

			$PAGE = $route->controller;
			$event = $route->method?$route->method:false;
			break;
		}
	}

//	log_debug("routing_parse_uri $PAGE/$event");

	if( $PAGE && $CONFIG['routing']['auto_update_routes'] && class_exists($PAGE) )
	{
		$ma = $ref->GetMethodAttributes($event,'Route');
		foreach( $ma as $m )
			$m->Save();
	}
}

/**
 * Saves a route into the internal routing table.
 * @param string $route The route to be saved (ex: Error/500)
 * @param string $controller The controllers classname (case-sensitive!)
 * @param string $method The name of the method to be called (optional, but case-sensitive!)
 * @param bool $autogen Internal use only. Indicates that the route has been auto-generated by Reflector.
 * @return UrlRoute The UrlRoute object that was stored.
 */
function routing_save_route($route,$controller,$method=false,$autogen=false)
{
	global $CONFIG, $ROUTES;
	$ds = model_datasource($CONFIG['routing']['datasource']);
	$r = $ds->CreateInstance("UrlRoute");
	if( $r->Load("route=?0 AND controller=?1 AND (method=?2 OR method=null)",
		array($route,$controller,$method?$method:'')) )
		return $r;

	$r->route = $route;
	$r->controller = $controller;
	if( $method ) $r->method = $method;
	$r->autogenerated = $autogen?1:0;
	$r->Save();
	$ROUTES[] = $r;
	return $r;
}

?>