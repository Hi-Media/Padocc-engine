jQuery(function($){
	var defaultOptions = {
		draggable: true,
		minimizable: true,
		closeable: true,
		minToStack: 2, // 0 for no minimum
		titleText: 'Notifications',
		moreText: 'view all :total notifications &rsaquo;',
		lessText: 'view less',
		closeText: 'Close',
		minimizeText: 'Minimize'
	}, options, self, $feedback, $title, $list, $more = null, $bar;
	
	function init(){
		self.createFramework(true);
	};
	
	function applyEffect($e){
		$e.mouseenter(function(){
			$e.animate({opacity: 1});
		}).mouseleave(function(){
			$e.animate({opacity: .85});
		});
		return $e;
	}
	
	self = {
		createFramework: function(onlyIfNotInitialize){
			if(onlyIfNotInitialize && self.isFrameworkReady()){
				return null;
			}
			
			var title = '<h1>' + options.titleText + '</h1>';
			$list = $('<ul />');
			$feedback = $('<div class="feedback" />').appendTo($('body'));
			$feedback.append($title = $(title));
			$feedback.append($list);
			
			$bar = applyEffect($('<div class="feedback-bar" />').append($title.clone().click(self.hideBar)).hide());
			
			// drag
			if(options.draggable){
				$feedback.draggable({
					handle: $title
				});
			}
			
			// close
			$feedback.prepend($('<span class="glyph close" title="' + options.closeText + '" />').click(self.close));
			
			// minimize
			if(options.minimizable){
				$feedback.prepend($('<span class="glyph minus zoom-out" title="' + options.minimizeText + '" />').click(self.minimize));
			}
			
			applyEffect($feedback);
			
			return self;
		},
		isFrameworkReady: function(){
			return $('.feedback')[0] != undefined;
		},
		addMessage: function(data){
			var defaultData = {
				title: '',
				message: '',
				color: ''
			},
			colors = {green: '#91CE43', red: '#D92316', yellow: '#CDB907'},
			$close = $('<span class="glyph close" />'),
			color = false;
			
			data = $.extend({}, defaultData, data);
			
			if(colors[data.color]){
				color = colors[data.color];
			}else{
				color = data.color;
			}
			
			var $item = $('<li />');
			if(data.title)
				$item.append('<h2' + (color ? ' style="color: ' + color + '"' : '') + '>' + data.title + '</h2>');
					
			if(data.message)
				$item.append('<p>' + data.message + '</p>');
				
			$item.append($close);
			
			$list.prepend($item);
			
			$close.click(function(){
				self.removeMessage($item.index());
			});
			
			
			self.updateMore();
			
			if(!$feedback.parent().is('.ui-effects-wrapper'))
				$feedback.effect('shake', {times: 4}, 55);
			
			if(self.isMinimized())
				self.hideBar();
				
			return self;
		},
		updateMore: function(){
			if(!self.isOpen()){
				self.hideRemaining();
			}
			
			if($more !== null && $more[0]){
				if(self.isStacked())
					$more.remove();
				else{
					$more.slideUp(function(){ $more.remove(); });
				}
			}
				
			if(self.isStacked()){ 
				if(self.isOpen()){
					$more = $('<a class="more-less less">' + options.lessText + '</a>');
				}else{
					$more = $('<a class="more-less">' + self.getMoreText() + '</a>');
				}
				$more.click(self.toggleMore);
				$feedback.append($more);
			}
		},
		isStacked: function(){
			return self.totalMessages() > options.minToStack && self.canStack();
		},
		canStack: function(){
			return options.minToStack != 0;
		},
		hideRemaining: function(){
			if(self.canStack()){
				$list.children().each(function(index, item){
					if(index+1 > options.minToStack){
						$(item).hide();
					}
				});
				
				return self;
			}else{
				return false;
			}
		},
		totalMessages: function(){
			return $list.children().length;
		},
		getMoreText: function(){
			return options.moreText.replace(':total', $list.children().length);
		},
		toggleMore: function(){
			if(self.isOpen()){
				$more.html(self.getMoreText());
				self.hideRemaining();
			}else{
				$list.find('li:not(:visible)').slideDown();
				$more.toggleClass('less').html(options.lessText);
			}
			
			$feedback.toggleClass('open');
			
			return self;
		},
		isOpen: function(){
			return $feedback.is('.open');
		},
		minimize: function(){
			self.showBar();
			
			return self;
		},
		showBar: function(){
			if(!$('.feedback-bar')[0])
				$('body').append($bar);
				
			$bar.slideDown();
			$feedback.stop(true, true).hide('drop', {direction: 'up'}, 200);
		},
		hideBar: function(){
			$bar.hide(); // dont put effect
			$feedback.show();
		},
		isMinimized: function(){
			return $bar.is(':visible');
		},
		close: function(){
			$feedback.hide('drop', {direction: 'down'}, 200, function(){
				$feedback.remove();
			});
			$bar.slideUp(function(){
				$bar.remove();
			});
		},
		removeMessage: function(index){
			var $item = $list.find('> :eq(' + index + ')');
			if($item[0]){
				if($list.children().length == 1 ){
					self.close();
				}else{
					$item.slideUp(function(){
						$item.remove();
						self.updateMore();
					});
					$($list.find('> :not(:visible)').get(0)).slideDown();
				}
			}
			
			return self;
		}
	};
		
	$.feedback = function(opts){		
		if(!opts)
			opts = {};
			
		options = $.extend({}, defaultOptions, opts);
		
		
		init();
		
		return self;
	}
});