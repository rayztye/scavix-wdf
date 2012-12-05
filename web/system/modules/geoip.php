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
 
/*
	geoip-module
    Created on : Jun 14, 2009, 1:17:55 PM
    Description:
				Modul to localize ip-adresses which uses the free version
				of GeoIP from maxmind (http://www.maxmind.com/app/geolitecity).
				In the majority of cases maxmind publishes updates for the GeoLiteCity.dat
				on the first day each month.
*/
function geoip_init()
{
	global $IS_DEVELOPSERVER;

	if( !function_exists('geoip_country_code_by_name') )
	{
		require_once(dirname(__FILE__)."/geoip/geoip.inc");
		require_once(dirname(__FILE__)."/geoip/geoipcity.inc");
	}

	if( !system_is_module_loaded('curlwrapper') )
		system_die("Missing module: curlwrapper!");
		
	if( !isset($GLOBALS['current_ip_addr']) )
		$GLOBALS['current_ip_addr'] = get_ip_address();
}

function get_geo_location_by_ip($ip_address=null)
{
	if( is_null($ip_address) ) 
		$ip_address = $GLOBALS['current_ip_addr'];

	// local ips throw an error, so ignore them:
	if(starts_with($ip_address, "192.168.1."))
		return false;
	if( function_exists('geoip_open') )
	{
		$gi = geoip_open(dirname(__FILE__)."/geoip/GeoLiteCity.dat",GEOIP_STANDARD);
		$location = geoip_record_by_addr($gi,$ip_address);
		geoip_close($gi);
		return $location;
	}
	$location = @geoip_record_by_name($ip_address);
	return (object) $location;
}

function get_geo_region()
{
	include(dirname(__FILE__)."/geoip/geoipregionvars.php");
	if( function_exists('geoip_open') )
	{
		$gi = geoip_open(dirname(__FILE__)."/geoip/GeoLiteCity.dat",GEOIP_STANDARD);
		$location = geoip_record_by_addr($gi,$GLOBALS['current_ip_addr']);
		geoip_close($gi);
		if(!isset($GEOIP_REGION_NAME[$location->country_code]))
			return "";
	}
	else
		$location = (object) geoip_record_by_name($GLOBALS['current_ip_addr']);
	return $GEOIP_REGION_NAME[$location->country_code][$location->region];
}

function get_coordinates_by_ip($ip = false)
{
	// ip could be something like "1.1 ironportweb01.gouda.lok:80 (IronPort-WSA/7.1.1-038)" from proxies
	if($ip === false)
		$ip = $GLOBALS['current_ip_addr'];
	if(starts_with($ip, "1.1 ") || starts_with($ip, "192.168.1."))
		return false;
	
	if( function_exists('geoip_open') )
	{
		$gi = geoip_open(dirname(__FILE__)."/geoip/GeoLiteCity.dat",GEOIP_STANDARD);
		$location = geoip_record_by_addr($gi,$ip);
		geoip_close($gi);
	}
	else
		$location = (object) geoip_record_by_name($ip);
	
	if(!isset($location->latitude) && !isset($location->longitude))
	{
		log_error("get_coordinates_by_ip: No coordinates found for IP ".$ip);
		return false;
	}
	
	$coordinates = array();
	$coordinates["latitude"] = $location->latitude;
	$coordinates["longitude"] = $location->longitude;

	return $coordinates;
}

function get_countrycode_by_ip($ipaddr = false)
{
	if($ipaddr === false)
		$ipaddr = $GLOBALS['current_ip_addr'];
	if( isset($_SESSION['geoip_countrycode_by_ip_'.$ipaddr]) && $_SESSION['geoip_countrycode_by_ip_'.$ipaddr] != "" )
		return $_SESSION['geoip_countrycode_by_ip_'.$ipaddr];

//	// maxmind installed as server module?
//	if(isset($_SERVER["GEOIP_COUNTRY_CODE"]))
//		return $_SERVER["GEOIP_COUNTRY_CODE"];

	if( function_exists('geoip_open') )
	{
		$gi = geoip_open(dirname(__FILE__)."/geoip/GeoLiteCity.dat",GEOIP_STANDARD);
		$country_code = geoip_country_code_by_addr($gi,$ipaddr);
//		log_debug("country: ".$country_code);
		geoip_close($gi);
	}
	else
		$country_code = geoip_country_code_by_name($ipaddr);
	
	if($country_code == "")
	{
		$location = get_geo_location_by_ip($ipaddr);
		if($location && isset($location->country_code))
			$country_code = $location->country_code;
	}
	$_SESSION['geoip_countrycode_by_ip_'.$ipaddr] = $country_code."";
	
	return $country_code;
}

function get_countryname_by_ip()
{
//	// maxmind installed as server module?
//	if(isset($_SERVER["GEOIP_COUNTRY_CODE"]))
//		return $_SERVER["GEOIP_COUNTRY_CODE"];
	if( function_exists('geoip_open') )
	{
		$gi = geoip_open(dirname(__FILE__)."/geoip/GeoLiteCity.dat",GEOIP_STANDARD);
		$country_name = geoip_country_name_by_name($gi,$GLOBALS['current_ip_addr']);
		geoip_close($gi);
	}
	else
		$country_name = geoip_country_name_by_name($GLOBALS['current_ip_addr']);

	return $country_name;
}

function get_timezone_by_ip($ip = false)
{
	$useglobalcache = system_is_module_loaded("globalcache");
	$insession = (session_id() != "");
	if($ip === false)
		$ip = $GLOBALS['current_ip_addr'];
	$key = "get_timezone_by_ip.".(defined("_nc") ? _nc."-" : "")."-".$ip;

	if(starts_with($ip, "1.1 ") || starts_with($ip, "192.168.1."))
		return false;
	
	$ret = false;
	if($useglobalcache)
		$ret = globalcache_get($key);
	elseif($insession && isset($_SESSION[$key]))
		$ret = $_SESSION[$key];
	if($ret)
		return $ret;
			
	// new url with api key:
	$url = "http://api.ipinfodb.com/v2/ip_query.php?key=6a6ef9d4d82491036a4f3dbd465d52d2e2d5253d1285a3dda02b65752b5474f8&ip=".$GLOBALS['current_ip_addr']."&timezone=true";
	$f = false;
	try
	{
		$xml = sendHTTPRequest($url, false, 60 * 60, $f, false, 2);
	}catch(Exception $ex){ log_error("Unable to get Timezone for ".$ip." ($url)"); return false; }
	if( preg_match_all('/<TimezoneName>([^<]*)<\/TimezoneName>/', $xml, $zone, PREG_SET_ORDER) )
	{
		$zone = $zone[0];
		if($zone[1] != "")
		{
			if($useglobalcache)
				globalcache_set($key, $zone[1], 24 * 60 * 60);
			elseif($insession)
				$_SESSION[$key] = $zone[1];		
			return $zone[1];
		}
	}
//	log_error("No timezone found for ".$GLOBALS['current_ip_addr']." via ipinfodb.com");

	$coords = get_coordinates_by_ip($ip);
	if($coords === false)
	{
		log_error("No timezone found for IP ".$ip." (missing coordinates)");
		// disaster-fallback: use our timezone:
		$ret = "Etc/GMT+2";
		if($useglobalcache)
			globalcache_set($key, $ret, 24 * 60 * 60);
		elseif($insession)
			$_SESSION[$key] = $ret;		
		return $ret;		
	}

	// ALTERNATIVE 1:
//	ws.geonames.org had only timeouts on 2/10/2010...
//	$url = "http://ws.geonames.org/timezone?lat=".$coords['latitude'].'&lng='.$coords['longitude'];
	$url = "http://api.geonames.org/timezone?lat=".$coords['latitude'].'&lng='.$coords['longitude']."&username=scendix";
	$f = false;
	try
	{
		$xml = sendHTTPRequest($url, false, 60 * 60, $f, false, 2);
	}catch(Exception $ex){ log_error("Unable to get Timezone for ".$ip." ($url) ".$ex->getMessage()); return false; }
	if( preg_match_all('/<timezoneId>([^<]*)<\/timezoneId>/', $xml, $zone, PREG_SET_ORDER) )
	{
		$zone = $zone[0];
		if($useglobalcache)
			globalcache_set($key, $zone[1], 24 * 60 * 60);
		elseif($insession)
			$_SESSION[$key] = $zone[1];		
		return $zone[1];
	}
	log_error("No timezone found for ".$ip." via geonames.org");

	// ALTERNATIVE 2:
	$url = "http://www.earthtools.org/timezone/".$coords['latitude'].'/'.$coords['longitude'];
	$f = false;
	try
	{
		$xml = sendHTTPRequest($url, false, 60 * 60, $f, false, 2);
	}catch(Exception $ex){ log_error("Unable to get Timezone for ".$ip." ($url)"); return false; }
	if( preg_match_all('/<offset>([^<]*)<\/offset>/', $xml, $zone, PREG_SET_ORDER) )
	{
		$zone = $zone[0];
		$zone[1] = round($zone[1], 0);
		$ret = "Etc/GMT".($zone[1] < 0 ? $zone[1] : "+".$zone[1]);
		if($useglobalcache)
			globalcache_set($key, $ret, 24 * 60 * 60);
		elseif($insession)
			$_SESSION[$key] = $ret;		
		return $ret;
	}
	log_error("No timezone found for ".$ip." via earthtools.org");

	// disaster-fallback: use our timezone:
	$ret = "Etc/GMT+2";
	if($useglobalcache)
		globalcache_set($key, $ret, 24 * 60 * 60);
	elseif($insession)
		$_SESSION[$key] = $ret;		
	return $ret;
}

