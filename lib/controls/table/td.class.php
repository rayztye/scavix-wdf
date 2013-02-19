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
 
class Td extends Control
{
    var $options = false;
	var $CellFormat = false;
	
	var $colspan = "";

	function __initialize($options)
	{
		parent::__initialize("div");
		$this->class = "td";
        $this->options = $options;
	}

	function GetContent()
	{
		$tmp = clone $this;
		$tmp->Tag = "";
		return $tmp->WdfRender();
	}

	function SetContent($content)
	{
		$this->content($content,true);
	}
}
