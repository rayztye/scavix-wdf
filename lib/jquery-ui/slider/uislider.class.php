<?
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2012 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

class uiSlider extends uiControl
{
	var $min = 1;
	var $max = 100;
	var $value = 10;
	var $range = false;
	var $onslide = false;
	var $values = false;

	function __initialize($id)
	{
		parent::__initialize("div");
		$this->id = $id;		
	}

	function WdfRender()
	{
		$opts = array();
		if( $this->min !== false )
			$opts['min'] = $this->min;
		if( $this->max !== false )
			$opts['max'] = $this->max;
		if( $this->value !== false )
			$opts['value'] = $this->value;
		if( $this->range !== false )
			$opts['range'] = $this->range;
		if( $this->onslide !== false )
			$opts['slide'] = $this->onslide;
		if( $this->values !== false )
		{
			if( !is_array( $this->values ) )
				$this->values = array($this->values);

			$opts['values'] = "[".implode(",",$this->values)."]";

		}

		$opts = system_to_json($opts);
		$this->script("$('#{$this->id}').slider($opts);");
		return parent::WdfRender();
	}
}

?>