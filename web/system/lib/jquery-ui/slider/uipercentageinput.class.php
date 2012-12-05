<?php

class uiPercentageInput extends Control
{
	function __initialize($id, $defvalue=0, $onchange="",$decimal_point=',')
	{
		parent::__initialize("div");

		$defvalue = floatval(str_replace(",",".",$defvalue));

		$e = floor($defvalue);
		$c = round(($defvalue-$e),2) * 100;
		log_debug("PercentageInput($id): $defvalue $e $c");

		$this->id = $id;
		$this->class = "currencyinput ui-widget-content ui-widget ui-corner-all";
		$this->css("border","1px solid transparent");
		$this->onmouseover = "$(this).css({border:''});";
		$this->onmouseout = "$(this).css({border:'1px solid transparent'});";

		$integer_place = new uiSlider("{$id}_integer_place");
		$integer_place->range = 'min';
		$integer_place->min = 0;
		$integer_place->max = 100;
		$integer_place->value = $e;
		$integer_place->css("margin-bottom","8px");
		$integer_place->onslide  = "function(event, ui){ $('#{$id}_integer_place_value').text(ui.value); ";
		$integer_place->onslide .= "$('#{$id}_hidden').val( $('#{$id}_integer_place_value').text()+'.'+$('#{$id}_decimal_place_value').text() ).change(); }";
		$integer_place->onmouseover = "$('#{$id}_integer_place_value').css({color:'red'});";
		$integer_place->onmouseout = "$('#{$id}_integer_place_value').css({color:'black'});";

		$decimal_place = new uiSlider("{$id}_decimal_place");
		$decimal_place->range = 'min';
		$decimal_place->min = 0;
		$decimal_place->max = 99;
		$decimal_place->value = $c;
		$decimal_place->onslide  = "function(event, ui){ $('#{$id}_decimal_place_value').text(ui.value<10?'0'+ui.value:ui.value); ";
		$decimal_place->onslide .= "$('#{$id}_hidden').val( $('#{$id}_integer_place_value').text()+'.'+$('#{$id}_decimal_place_value').text() ).change(); }";
		$decimal_place->onmouseover = "$('#{$id}_decimal_place_value').css({color:'red'});";
		$decimal_place->onmouseout = "$('#{$id}_decimal_place_value').css({color:'black'});";

		$container = new Control("div");
		$container->class = "container";
		$container->content($integer_place);
		$container->content($decimal_place);

		$value = new Control("div");
		$value->class = "value";

		$integer_place_value = new Control("div");
		$integer_place_value->id = "{$id}_integer_place_value";
		$integer_place_value->css("float","left");
		$integer_place_value->content($e);

		$decimal_place_value = new Control("div");
		$decimal_place_value->id = "{$id}_decimal_place_value";
		$decimal_place_value->css("float","left");
		$decimal_place_value->content($c<9?"0$c":$c);


		$value->content($integer_place_value);
		$value->content("<div style='float:left'>$decimal_point</div>");
		$value->content($decimal_place_value);
		$value->content("<div style='float:left'>%</div>");
		
		$this->content($container);
		$this->content($value);
		$this->content("<input type='hidden' id='{$id}_hidden' name='{$id}' value='$defvalue' onchange='$onchange'/>");
		$this->content("<br style='clear:both; line-height:0'/>");
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'));
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}
}

?>