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
 
class RegionSelect extends Select
{
	function __initialize($current_language_code=false, $current_region_code=false)
	{
		parent::__initialize();
		$this->script("Locale_Settings_Init();");
		$this->setData('role', 'region');
		$this->setData('controller', buildQuery($this->id));
		
		if( $current_language_code )
		{
			if( $current_language_code instanceof CultureInfo )
				$lang = $current_language_code->ResolveToLanguage();
			else
				$lang = Localization::getLanguageCulture($current_language_code);
			$regions = $lang->GetRegions(true);
			
			if( !$current_region_code )
				$current_region_code = $lang->DefaultRegion()->Code;
		}
		else
			$regions = Localization::get_all_regions(true);
		
		if( $current_region_code )
		{
			if( $current_region_code instanceof CultureInfo )
				$this->SetCurrentValue($current_region_code->DefaultRegion()->Code);
			else
				$this->SetCurrentValue($current_region_code);
		}
		
		if( count($regions)>0 )
		{
			$sorted = array();
			foreach($regions as $code)
				$sorted[$code] = array("name"=>getString("TXT_COUNTRY_".strtoupper($code)),"code",$code);
			uasort($sorted, "RegionSelect::compareCountryNames");

			foreach($sorted as $code=>$item)
				$this->AddOption($code, $item['name']);
		}
	}
	
	public static function compareCountryNames($a, $b)
    {
		$chars = array('Ä'=>'A', 'Ö'=>'O', 'Ü'=>'U', 'ä'=>'a', 'ö'=>'o', 'ü'=>'u', 'ß'=>'ss');
		$a = strtr($a["name"], $chars);
		$b = strtr($b["name"], $chars);
		return strnatcasecmp($a, $b);
    }
	
	static function __js()
	{
		return array(jsFile('locale_settings.js'));
	}
	
	/**
	 * @attribute[RequestParam('language','string')]
	 */
	public function ListOptions($language)
	{
		$lang = Localization::getLanguageCulture($language);
		if(!$lang)
			$lang = Localization::getLanguageCulture('en');
		$regions = $lang->GetRegions(true);
		$sorted = array();
		foreach($regions as $code)
			$sorted[$code] = array("name"=>getString("TXT_COUNTRY_".strtoupper($code)),"code",$code);
		uasort($sorted, "RegionSelect::compareCountryNames");

		$res = array();
		foreach($sorted as $code=>$item)
			$res[] = "<option value='$code'>{$item['name']}</option>";
		return implode("\n",$res);
	}
}