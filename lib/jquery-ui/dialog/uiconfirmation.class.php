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
default_string("TITLE_CONFIRMATION", "Confirm");
default_string("TXT_CONFIRMATION", "Please confirm");

class uiConfirmation extends uiDialog
{
	const OK_CANCEL = 1;
	const YES_NO = 2;
	var $Mode;
	
	function __initialize($text_base='CONFIRMATION',$ok_callback=false,$button_mode=self::OK_CANCEL)
	{
		$options = array(
			'autoOpen'=>true,
			'modal'=>true,
			'width'=>450,
			'height'=>300
		);
		
		$title = "TITLE_$text_base";
		$text  = "TXT_$text_base";
		
		parent::__initialize($title,$options);
		switch( $button_mode )
		{
			case self::OK_CANCEL:
				$this->AddButton('BTN_OK',$ok_callback);
				$this->AddCloseButton('BTN_CANCEL');
				break;
			case self::YES_NO:
				$this->AddButton('BTN_YES',$ok_callback);
				$this->AddCloseButton('BTN_NO');
				break;
			default:
				throw new Exception("Wrong button_mode: $button_mode");
		}
		$this->Mode = $button_mode;
		$this->content($text);
	}
	
	function SetOkCallback($action)
	{
		switch( $this->Mode )
		{
			case self::OK_CANCEL:
				$this->SetButton('BTN_OK',$action);
				break;
			case self::YES_NO:
				$this->SetButton('BTN_YES',$action);
				break;
		}
	}
}
?>
