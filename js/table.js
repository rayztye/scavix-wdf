/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
(function($) {

$.fn.table = function(opts)
{
    this.opts = $.extend({bottom_pager:false,top_pager:false},opts||{});
            
	return this.each( function()
	{
		var self = $(this), current_row;

		var actions = $('.ui-table-actions .ui-icon',self);
		if( actions.length > 0 )
		{
			var w = 0;
			$('.ui-table-actions > div',self)
				.hover( function(){ $(this).toggleClass('ui-state-hover'); } )
				.each(function(){ w+=$(this).width(); });

			$('.ui-table-actions .ui-icon',self)
				.click(function()
				{
                    self.showLoadingOverlay();
					wdf.post(self.attr('id')+'/onactionclicked',{action:$(this).data('action'),row:current_row.attr('id')},function(d)
                    {
                        $('body').append(d);
                        self.hideLoadingOverlay();
                    });
				});

			$('.ui-table-actions',self).width(w);

			var on = function()
			{
				if( $('.ui-table-actions.sorting',self).length>0 )
					return;
				current_row = $(this); 
				$('.ui-table-actions',self).show()
					.position({my:'right center',at:'right-1 center',of:current_row});
			};
			var off = function(){ $('.ui-table-actions',self).hide(); };

			$('.tbody .tr',self).bind('mouseenter click',on);
			$('.caption, .thead, .tfoot',self).bind('mouseenter',off);
			self.bind('mouseleave',off);

			$('.tbody .tr .td:last-child, .thead .tr .td:last-child, .tfoot .tr .td:last-child',self).css('padding-right',w+10);
		}
		
		$('.pager',self).each( function(){ $(this).width(self.width());});
        $('.thead a',self).click(self.showLoadingOverlay);
        $(this).placePager(opts);
	});
};

$.fn.updateTable = function(html)
{
    var self = this;
    self.hideLoadingOverlay( function() {
        self.prev('.pager').remove(); 
        self.next('.pager').remove();
        self.replaceWith(html); 
        $('.thead a',self).click(self.showLoadingOverlay);
        self.placePager(self.opts);
    });
};

$.fn.gotoPage = function(n)
{
	var self = this;
    self.showLoadingOverlay();
	wdf.post(self.attr('id')+'/gotopage',{number:n},function(d){ self.updateTable(d); });
};

$.fn.placePager = function(opts)
{
    var $p = $('.pager',this).remove();
    
    if( opts && opts.top_pager )
    {
        $(this).addClass('pager_top');
        $p = $p.insertBefore(this).css('display','inline').clone(true);
    }
    if( opts && opts.bottom_pager )
    {
        $(this).addClass('pager_bottom');
        $p.insertAfter(this).css('display','inline');
    }
};

var table_loading_counter = 0;
$.fn.showLoadingOverlay = function()
{
    var self = $(this);
    if( !self.is('.table') )
        self = self.closest('.table');
    
    var loadingClass = 'loading_'+(table_loading_counter++),
        $tab = self.addClass(loadingClass),
        $pt = $tab.prev('.pager'), $pb = $tab.next('.pager')
        $offsetParent = $tab;
   
    var $ol = $("<div data-lc='"+loadingClass+"' data-for='"+self.attr('id')+"' />")
            .appendTo('body')
            .width($tab.width())
            .css('display','none')
            .css('cursor','wait')
            .css('background-color','black')
            .css('opacity','0.2')
            .css('position','absolute'),
        wait = function(ol,par)
        {
            if(!jQuery.contains(document, ol[0]))
                return;
            ol.position({my:'left top',at:'left top',of:par});
            setTimeout(function(){ wait(ol,par); },10);
        };
    $tab.data('overlay',$ol);
    $tab.data('overlay_id',loadingClass);
    
    if( $pt.length && $pb.length )
    {
        $ol.height( $pb.position().top + $pb.height() - $pt.position().top );
        $offsetParent = $pt;
    }
    else if( $pt.length )
    {
        $ol.height( $tab.position().top + $tab.height() - $pt.position().top );
        $offsetParent = $pt;
    }
    else if( $pb.length )
        $ol.height( $pb.position().top + $pb.height() - $tab.position().top );
    else
        $ol.height( $tab.height() );
    
    $ol.fadeIn('fast');
    wait($ol,$offsetParent);
};

$.fn.hideLoadingOverlay = function(callback)
{
    var self = $(this);
    if( !self.is('.table') )
        self = self.closest('.table');
    $ol = $('div[data-lc][data-for="' + self.attr('id') + '"]');
    if($ol.length > 0)
        $ol.fadeOut('fast', function() { $ol.remove(); if(callback) { callback(); wdf.ajaxReady.fire(); } });
};

})(jQuery);
