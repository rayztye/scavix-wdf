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
 
class GridForm extends Template
{
	function __initialize($controller=null,$event="",$method="post")
	{
		parent::__initialize();

		if( $controller != null )
		{
			$this->id = $this->_storage_id;
			$this->set("id",$this->_storage_id);
			$this->set("action",buildQuery($controller,$event));
			$this->set("method",$method);

			$this->set("labels",array());
			$this->set("controls",array());
			$this->set("hints",array());

			//store_object($this);
		}
	}

	function addGroupLabel($label)
	{
		$this->add2var("labels",$label);
		$this->add2var("controls","");
		$this->add2var("hints","");
	}

	function Add(&$webcontrol,$label)
	{
		$this->add2var("labels",$label);
		$this->add2var("controls",$webcontrol);

		$hint = new Image(resFile('trans.gif'));
		$hint->class = "gridform_hint";
		$hint->id = $webcontrol->id."_hint";
		$this->add2var("hints",$hint);
	}
}

?>