$(document).ready(function() {

	var basePathIcons = '/assets/img/fugue-icons/icons/';

function createIcon(icon) {
	return '<img src="' + basePathIcons + icon + '.png" />';
}

jQuery(function(){
    /*==================
        Form
    ====================*/
	if(jQuery().uniform){
		$(':checkbox').each(function(){
			if(!$(this).closest('.check-list')[0] && !$(this).closest('.check-list').is('.grey-skin'))
				$(this).uniform();
		});
		
		$('.search select, .widget select:not(.chosen, .spinner-skin, .no-ui, .multiselect), .page-status select:not(.chosen, .spinner-skin, .no-ui, .multiselect), .check-list:not(.grey-skin, .button-skin, .no-ui) :checkbox, .check-list:not(.grey-skin, .button-skin, .no-ui) :radio').uniform();
		$('.grey-skin :radio').uniform({radioClass: 'radio grey-skin'});
		$('.grey-skin :checkbox').uniform({checkboxClass: 'checker grey-skin'});
		$('.widget select.spinner-skin').uniform({selectClass: 'selector spinner-skin'});
		
		
		$('.widget :file:not(.no-ui)').each(function(){
			var $this = $(this);
			$this.uniform();
			var $parent = $this.parent().mousemove(function(e){
				if(!$(e.target).is('input')){
					$this.css({
						left: e.pageX - $parent.offset().left - $this.width() + 15,
						top: e.pageY - $parent.offset().top - 15
					});
				}
			});
		});
	}
	
	$('label').each(function(){
		var $entry = $(this).find('.checker, .radio');
		if($entry[0]){
			$(this).mouseenter(function(){
				$entry.addClass('hover');
			}).mouseleave(function(){
				$entry.removeClass('hover');
			});
		}
	});
	
    $('input.datepicker').each(function(){
        var $this = $(this), options = {};
        if($this.is('.inline')){
            var other = $('<div />', {'class': 'datepicker'});
            $this.after(other).hide();
            options.altField = $this;
            $this = other;
        }
        $this.datepicker(options);
    });
    
    $('.button-skin label :checkbox, .button-skin label :radio').hide();
    $('.button-skin > label').each(function(){
        var $this = $(this),
            $input = $this.find(':radio, :checkbox'),
            is = $input.prop('checked'),
            disabled = $input.prop('disabled');
            
        $this.disableSelection();
        
        if(disabled){
            $this.addClass('disabled')
        }else{
            $this.removeClass('disabled');
        }
        
        if(is){
            $this.addClass('checked')
        }else{
            $this.removeClass('checked');
        }
        
        $this.click(function(e){
            if(!$this.is('.disabled') && $(e.target).is('label')){
                if($input.is(':radio') && !$this.is('.checked')){
                    $this.toggleClass('checked');
                    $(':radio[name="' + $input.attr('name') + '"]').not($input).not('[disabled]').parent().toggleClass('checked');
                }else if($input.is(':checkbox')){
                    $this.toggleClass('checked');
                }
            }
        });
    });
    if(jQuery().chosen)
    {
    	$('.widget select.chosen:not(.add_chosen)').chosen();
    	$('.widget select.add_chosen').chosen({
		    create_option: true,
		    // persistent_create_option decides if you can add any term, even if part of the term is also found, or only unique, not overlapping terms
		    persistent_create_option: true
		});
    }
		
/*

    if(jQuery().chosen)
		$('.widget select.chosen').chosen({
    create_option: true,
    // persistent_create_option decides if you can add any term, even if part of the term is also found, or only unique, not overlapping terms
    persistent_create_option: true
  });
		*/


		
	if(jQuery().spinner)
		$('.spinner').spinner();
		
	if(jQuery().multiselect)
		$('.multiselect').multiselect();
		
	if(jQuery().elastic)
		$('.elastic').elastic();
		
	if(jQuery().miniColors){
		$('.colorpicker').miniColors();
		
		$('.miniColors-trigger').each(function(){
			var $this = $(this);
			if($this.prev().is('input[type=text]')){
				$this.prev().css('float', 'left').css('margin-right', -30);
				$this.css({position: 'relative', top: 3});
			}
		});
	}

	if(jQuery().mask){
		// masks
		$.mask.definitions['~'] = '[+-]';
		$('.mask-date').mask('99/99/9999');
		$('.mask-phone').mask('(999) 999-9999');
		$('.mask-phoneext').mask("(999) 999-9999? x99999");
		$(".mask-tin").mask("99-9999999");
		$(".mask-ssn").mask("999-99-9999");
		$(".mask-product").mask("a*-999-a999",{placeholder:" "});
		$(".mask-eyescript").mask("~9.99 ~9.99 999");
	}
	
	if(jQuery().elrte){
		// wysiwyg
		if($.browser.msie && $.browser.version < 9){
			$('.editor').html('Content');
			$('html').addClass('ie');
		}
		$('.editor').elrte({
			toolbar: 'normal',
			styleWithCSS : false,
			height: 250
		});
	}
	
	if(jQuery().validate){
		 $.oValidOption ={
			onclick: false,
			onkeyup: false,
			onfocusout: false,
			success: function(label) {
				var c = label.closest('.entry');
				if(!c[0])
					c = label.closest('.check-list');
				if(!c[0])
					c = label.parent();
				c.removeClass('error-container');
			},
			errorPlacement: function(error, element) {
				var p = element.closest('.entry');
				if(!p[0])
					p = element.closest('.check-list');
				if(!p[0])
					p = element.parent();
					
				if(!p.find('.errors')[0])
					p.append('<div class="errors" />');
				p.addClass('error-container').find('.errors').append(error);
			}
		};
		
	}
	
	if(jQuery().validationEngine){
		$('.validate-engine').each(function(){
			var $form = $(this),
				options = {
					relative: true,
					promptPosition: 'centerRight',
					autoHideDelay: 30000,
					binded: false
				};
			
			if($form.closest('.single')[0]){
				options.onValidationComplete = function(form, success){
					var _this = this;
					if($(form).closest('.single')[0]){
							$('label').each(function(){
								var $label = $(this), $input = $label.find("["+_this.validateAttribute+"*=validate]");
								
								if($input[0]){
									if($.inArray($label.find('input')[0], _this.InvalidFields) === -1){
										$label.removeClass('ve-error-container').addClass('ve-success-container');
									}else{
										$label.removeClass('ve-success-container').addClass('ve-error-container');
									}
								}
							});
					
							if(success){
								$(form).validationEngine('detach').submit();
							}
						}
				}
			}
			
			$form.validationEngine(options);
		});
	}
	
    /*==================
        Tables
    ====================*/
	if(jQuery().dataTable){
		(function(){
			$.fn.wrapInnerTexts = function($with){
				if(!$with)
					$with = $('<span class="textnode" />');

				$(this).each(function(){
					var kids = this.childNodes;
							for (var i=0,len=kids.length;i<len;i++){
								if (kids[i].nodeName == '#text'){
									$(kids[i]).wrap($with.clone().addClass('i-' + i));
								}
							}
				});
				return $(this);
			};
			


			$('.datatable').dataTable({
				sPaginationType: 'full_numbers',
				sDom: '<"header-table"l>rt<"footer-table"ip>',
				iDisplayLength: 5,
				aLengthMenu: [5, 10, 25, 50, "All"],
				bLengthChange : false,
				oLanguage: { oPaginate: {
					sFirst: '<',
					sPrevious: '(',
					sNext: ')',
					sLast: '>'
				}},
				fnInitComplete: function(t){
					var $table = $(t.nTable), $head = $table.prev();
					$head.find('select').uniform();
					
					$head.find('.dataTables_length label').wrapInnerTexts();
					$head.find('.dataTables_filter label').wrapInnerTexts();
					$head.find('input[type=text]').wrap('<div class="entry"></div>').parent().prepend('<div class="helper">' + createIcon('magnifier') + '</div>');
					$table.find('.sorting, .sorting_asc, .sorting_desc').wrapInner($('<div class="parentsort" />')).find('.parentsort').append('<div class="sorticon" />');
				}
			});
		})();
	}
    
    /*==================
        Submenu
    ====================*/
    $('.menu .with-submenu').each(function(){
        var $this = $(this),
            $nav = $this.find('nav'),
            $a = $this.find('> a');
            
        if(!$this.is('.open, .active'))
            $nav.hide();
            
        $a.click(function(e){
            e.preventDefault();
            
            if($nav.is(':visible')){
                $nav.hide('slow');
                $this.removeClass('open');
            }else{
                $nav.show('slow').addClass('open');
                $this.addClass('open');
            }
        });
    });
    
	
	
    /*==================
        Widget
    ====================*/
	if(jQuery().accordion)
		$('.accordion').accordion();
    
	if(jQuery().reportprogress){
		$('.progressbar').each(function(){
			var $this = $(this), opts = $this.metadata();
			
			$this.reportprogress(opts.value ? opts.value : 0);
		});
	}
	
    if(jQuery().slider){
		$('.slider').each(function(){
			var opts = {}, $this = $(this);
			if($this.attr('id')){
				opts.slide = function( event, ui ) {
					var value, before = '', after = '', value = '';
					
					if(opts.outputBefore){
						before = opts.outputBefore;
					}
					if(opts.outputAfter){
						after = opts.outputAfter;
					}
					
					if(opts.range){
						value = before + ui.values[0] + after + ' - ' + before + ui.values[1] + after;
					}else{
						value = before + ui.value + after;
					}
					
					$('output[for=' + $this.attr('id') + ']').html( value );
				}
			}
			
			opts = $.extend({}, opts, $this.metadata());
			$this.slider(opts);
		});
	}
	
	$('article.item').each(function(){
		var $this = $(this), $content = $this.find('.content .inner');
		
		$this.find('header figure').mouseover(function(){
			$(this).stop(true, true).animate({top: '-10px'}, 'fast');
		}).mouseleave(function(){
			$(this).animate({top: 0}, 'fast');
		});
		
		if(!$this.is('.open'))
			$content.hide();
			
		if($this.is('.minimizable')){
			var $minimize = $('<a href="#" class="minimize"><span class="glyph sort"></span></a>');
			$this.append($minimize);
			$minimize.wrapAll('<div class="minimize-wrap"></div>');
			$minimize.click(function(e){
				if($this.is('.open')){
					$content.slideUp()
					$this.removeClass('open');
				}else{
					var height = $content.show().height();
					$content.hide().css({display: 'block', overflow: 'hidden', height: 0}).animate({height: height}, function(){ $content.css('height', 'auto'); });
					$this.addClass('open');
				}
				e.preventDefault();
			});
		}
		
	});
	
	$('article.item .with-submenu ul, .list.collapsable .with-submenu ul').hide().parent().mouseenter(function(){
		$(this).find('ul').stop(true, true).fadeIn('fast');
	}).mouseleave(function(){
		$(this).find('ul').hide('drop', { direction: 'up' }, 'fast');
	});;
	
	
    $('.widget').each(function(){
        var $this = $(this),
            minimize,
            $content = $this.find('.content'),
            currentActiveTab = $this.find('> header nav li.active a').attr('href');
            
        $content.find('> section:not(' + currentActiveTab + ')').hide();
            
        // open-close
        if($this.is('.minimizable')){
            minimize = $('<div class="minimize" />').html('<span class="glyph close"></span>');
            $this.find('> header').append(minimize);
            
			minimize.click(function(){
				minimize.find('.glyph').toggleClass('zoom-out').toggleClass('zoom-in');
				$content.slideToggle();
				$this.toggleClass('close');
			});
			
			if($this.is('.close')){
				minimize.click();
			}
        }
		
        
        // change tabs
        $this.find('> header nav li a').click(function(e){
            var $self = $(this), is = $self.is($('a[href=' + currentActiveTab + ']'));
            
            if($self.attr('href')[0] == '#'){
                var cur = $(currentActiveTab);
                var origHeight = cur.css('height');
                cur.hide();
                currentActiveTab = $self.attr('href');
                cur = $(currentActiveTab);
                var realHeight = cur.show().height(); cur.hide();
                $self.closest('nav').find('li').removeClass('active').filter($self.parent()).addClass('active');


                cur.show().css('opacity', 0).height(origHeight).animate({height: realHeight}, function(){
                    cur.height('auto').css('opacity', '1').hide().fadeIn();
                });
            
                e.preventDefault();
            }else if(is){
                e.preventDefault();
            }
        });
    });
	
    if(jQuery().sparkline){
		$('.sparkline').each(function(){
			$(this).sparkline($(this).metadata().values, {type: 'bar', barColor: '#729F00', negBarColor: '#B70000', height: '10px'});
		});
	}
	
    $('.icon[data-icon]').each(function(){
        var $this = $(this),
            path = basePathIcons + $this.attr('data-icon') + '.png';
        
        $this.css({backgroundImage: 'url(' + path + ')'});
    });
    
	(function(){
		function niceEffect($move){
			$move.mouseenter(function(){
				$move.animate({fontSize: '+=10px', left: '-=5px', opacity: 1}, 'fast');
			}).mouseleave(function(){
				$move.animate({fontSize: '-=10px', left: '+=5px', opacity: .5}, 'fast');
			});
			return $move;
		}
		
		function niceDismiss($el){
			$el.hide('scale', function(){
				$el.show().css('opacity', 0).hide('slow', function(){
					$el.remove();
				});
			});
		}
		
		function niceAdmission($el, fnComplete){
			$el.hide().show('scale', 'slow', fnComplete);
		}
		
		$('.gallery-list').addClass('no-overlay');
		var applyInList = function(){
			$('.gallery-list li').each(function(){
				if(!$(this).data('original')){
					var $this = $(this), $actions = $this.find('.actions').hide(), $caption = $this.find('figcaption').hide();
					var $move = $('<a />', {title: 'Drag', draggable: 'true'}).html('<span class="glyph move"></span>').hide();
					var $overlay = $('<div class="overlay" />').hide();
					
					$this.data('original', $this.clone(true));
					
					$this.find('figure').after($move).before($overlay);
					niceEffect($move.css('top', '-=30px'));
					applyTooltip();
					$this.mouseenter(function(){
						$move.stop(true, true).show().animate({top: '+=30px'}, 'fast');
						$actions.fadeIn();
						$overlay.fadeTo('normal', .7);
						$caption.fadeIn();
					}).mouseleave(function(){
						$move.stop(true, true).animate({top: '-=30px', opacity: 'hide'}, 'fast');
						$actions.fadeOut();
						$overlay.fadeOut();
						$caption.fadeOut();
					});
					
					$this.draggable({
						handle: 'a[draggable]',
						revert: 'invalid',
						helper: 'clone',
						cursor: 'move'
					});
				}
			});
		}
		applyInList();
		
		if(jQuery().disableSelection)
			$('.bin').disableSelection();
			
		$('.bin').droppable({
			activeClass: 'ui-state-hover',
			accept: '.gallery-list li',
			drop: function( event, ui ) {
				var $originalLi = $(ui.draggable), $backupLi = $originalLi.data('original'), $binLi = $originalLi.clone(), $icon = $('<span class="glyph zoom-out" />');
				$(this).find('.delete-all .text').show('slow');
				niceDismiss($originalLi);
				$('.bin ul').prepend($binLi);
				$binLi.find('figcaption, a[draggable], .overlay, .actions').remove().end().find('article').append($icon).end().data('original', $backupLi);
				niceAdmission($binLi);
				niceEffect($icon).click(function(){
					var $c = $backupLi.clone(true);
					$('.gallery-list').prepend($backupLi);
					niceAdmission($backupLi, function(){
						$backupLi.after($c).remove();
						applyInList();
					});
					niceDismiss($binLi);
				});
			}
		}).find('.delete-all .text').hide();
	})();
	
	/*============
		Tasks
	==============*/
	$('.tasks :checkbox').each(function(){
		var $this = $(this), $li = $this.closest('li');
		if($this.prop('checked'))
			$li.addClass('done');
			
		$this.click(function(){
			$li.toggleClass('done');
		});
	});
	
	/*============
		Users
	==============*/
	$('.users [data-icon="light-bulb"], .users [data-icon="light-bulb-off"], .posts [data-icon="light-bulb"], .posts [data-icon="light-bulb-off"]').each(function(){
		var $this = $(this), $li = $this.closest('li'), isOn = $this.is('[data-icon="light-bulb-off"]');
		
		if(isOn)
			$li.addClass('inactive');
			
		leave = function(){
			$(this).prev().show().next().remove();
		}
		$this.mouseover(function(){
			var $icon;
			if(!isOn)
				$icon = $(createIcon('light-bulb-off'));
			else
				$icon = $(createIcon('light-bulb'));
			
			
			$icon.mouseleave(leave);
			$this.hide().after($icon);
		});
	});
	
	/*============
		Notifications
	==============*/
	// air
	$('.air').mouseenter(function(){
		$(this).stop(true, true).animate({opacity: 1});
	}).mouseleave(function(){
		$(this).animate({opacity: .85});
	});

	// close notification
	$('.notification .close').click(function(e){
		$(this).closest('.notification').slideUp(function(){
			$(this).remove();
		});
		e.preventDefault();
	});

    /*==================
        Miscellaneous
    ====================*/
	if (typeof Shadowbox != 'undefined') {
		Shadowbox.init();
	}
	
	if(jQuery().fullCalendar){
		// full calendar
		$('.fullcalendar').fullCalendar({
			editable: true,
			header: {
				left: 'prev,next',
				center: 'title',
				right: 'month,basicWeek,basicDay'
			}
		});
	}
	if(jQuery().elfinder){
		// filemanger
		$('.filemanager').elfinder({
			url : 'connectors/php/connector.php',
			toolbar : [
				['back', 'reload'],
				['select', 'open'],
				['quicklook', 'rename'],
				['resize', 'icons', 'list']
			],
			contextmenu : {
				cwd : ['reload', 'delim', 'info'], 
				file : ['select', 'open', 'rename'], 
			}
		});
	}
	
	if(jQuery().modal){
		// open link as modal
		$('.modal').click(function(e){
			var modal = $($(this).attr('href')).modal({closeButton: '.widget .close'});
			e.preventDefault();
		});
	}
	
	function applyTooltip(){
		if(jQuery().tipsy){
			$('.tooltip').each(function(){
				if(!$(this).attr('original-title')){
					var gravity = $(this).attr('data-position');

					if(!gravity)
						gravity = $.fn.tipsy.autoNS;
						
					$(this).tipsy({gravity: gravity});
				}
			});
		}
	}
	applyTooltip();
	
    $('.set .field:nth-child(even), article.item .inner li:nth-child(even)').addClass('even');
	$('article.item .inner .two li:nth-child(odd)').addClass('odd');
    $('.set .field:last-child, .check-list.button-skin label:last-child, .statistics li:last-child, .pagination li:last-child').addClass('last')
	$('.single nav a').click(function(e){
		if($(this).attr('href') == '#')
			e.preventDefault();
	});
	
	$('.collapsable.list > li').each(function(){
		var $this = $(this), $section = $this.find('section');
		if(!$this.is('.open'))
			$section.hide();
			
		$this.find('h3 a').click(function(e){ e.preventDefault(); });
		$this.find('header').click(function(){
			if($this.is('.open')){
				$this.removeClass('open').find('section').slideUp();
			}else{
				$this.siblings('.open').removeClass('open').find('section').slideUp();
				$section.slideDown();
				$this.addClass('open');
			}
		}).css('cursor', 'pointer');
	});
	
	$('.statistics li').each(function(){
		var $this = $(this), $all = $this.find('> *');
		$this.mouseenter(function(){
			if(!$this.is('.active'))
				$all.stop(true, true).css('position', 'relative').animate({top: '-5px'});
		}).mouseleave(function(){
			$all.animate({top: '0'}, function(){ $(this).css('position', 'static'); });
		}).click(function(){
			$('.statistics .charts > :eq(' + $('.statistics li.active').index() + ')').hide();
			var c = $('.statistics .charts > :eq(' + $this.index() + ')').show();
			$this.addClass('active').siblings().removeClass('active');
			
			if(c.data('plot')){
				c.data('plot').replot( { resetAxes: true } );
				}
		});
	});
	setTimeout(function(){ $('.statistics .charts > :eq(' + $('.statistics li.active').index() + ')').siblings().hide(); }, 500);
	
});

});

