{literal}
<style type="text/css">
	#debug_menu_header{background:#f2f2f2;border:2px solid #fe6a00;box-shadow:5px 5px 5px #e0e0e0;font-family:verdana;font-size:13px;position:fixed;left:5px;top:5px;width:180px;z-index:1000;display:none;}
	#debug_menu_header p{background:#e0e0e0;border-top:1px solid #333;margin:0;}
	#debug_menu_header p:hover{background:#d0d0d0;}
	#IE6 #debug_menu_header{position:absolute;}
	#debug_menu_header .on{color:#00c621}
	#debug_menu_header .off{color:#c60000}
	#debug_menu_header ul{margin:0;padding:0 2px;}
	#templates {font-size:11px}
	#debug_menu_header .semi{float:left;border:1px solid #d2d2d2;width:86px}
	#debug_menu_header .ok{background:#fff;border:1px solid #00c621;}
	#debug_menu_header li{list-style:none;border:none;border-top:1px solid #d2d2d2;}
	#debug_menu_header .last{border-bottom:none;}
	#debug_menu_header a{color:#000;display:block;padding:2px 4px;text-decoration:none;}
	#debug_menu_header .title{background-color:#612e27;color:#fff;cursor:move;margin:0;padding:2px 0;text-align:center;}
	#debug_menu_header .title:hover{background-color:#333;}
	#debug_menu_header .title span{float:right;font-weight:bold;margin-right:5px;cursor:pointer;}
	#ie7 #debug_menu_header .title span, #ie6 #debug_menu_header .title span{margin-top:-17px;}
	#debug_menu_header li a:hover{background-color:#d2d2d2;}
	#debug_menu_header .bounds{list-style:none outside none;border:none;border-bottom:1px solid #d2d2d2;overflow:hidden;}
	#debug_menu_header .box{list-style:none outside none;background-color:#F2F2F2;border:none;overflow:hidden;}
	#debug_menu_header a span {float:right;text-transform:uppercase}
	#IE6 #debug_menu_header li, #IE6 #debug_menu_header a{zoom:1}
	.IE #debug_menu_header a span{margin-top:-1.2em;position:relative}
	#resource_on{color:#16afdb;display:none;float:right}
	#templates{overflow:auto;height:300px}
	#resource_off{color:#f00;display:block;float:right}
	#col-left .resource{clear:left}
	.resource{float:left;position:relative;font-size:1px;text-indent:-9999px}
	.resource div{text-indent:0;position:absolute;z-index:10000;white-space:nowrap;padding:0 5px;background:#595959;border:1px solid black;font:menu;font-size:12px;line-height:16px;color:#ffffff}
	.resource_top div{background-color:#16AFDB !important;z-index:20000!important}
	.sys{display:block!important;color:red;font:menu;}
	#consoleIE{line-height:17px;font-size:8px;border:1px solid #d2d2d2;border-top:none;text-align:left;margin:0;padding:0;width:200px;position:absolute;top:0;left:0;color:#16AFDB}
	#consoleIE li{padding:0 5px;margin:0;display:block;border-top:1px solid #d2d2d2;}
	#consoleIE .head{color:black;font-weight:bold;}
	.debug_menu_header_a{overflow:hidden;text-align:center;}
	#debug_menu_header a.debug_menu_header_a span{float:none}
</style>
<script type="text/javascript">
$(document).ready(function(){
	$(function() {
		$("#debug_menu_header").show().draggable();
	});
	$("#debug_menu_header .slide").click(function() {
		$("#debug_menu_header .toolbar").slideToggle('slow');
	});
});

	$(function() {
		if (typeof window.console == 'undefined') {
			(function() {
					var
						_oConsole;

					$(function() {
							$('body').append('<ul id="consoleIE" class="sys h"><li class="head">console twenga</li></ul>');
							_oConsole = $('#consoleIE');
						}
					);

					window.console = {
						log : function() {
							var
								_str = '';

							$.each(arguments, function(i, o) {
									_str = _str.concat(' ', o.toString());
								}
							);

							_oConsole.append('<li>'.concat(_str, '</li>'));
						}
					};
				}
			)();
		}
	});

	$(function() {
		var
			_location	= window.location.href,
			_resources	= $('.resource'),
			_mod		= 0,
			_on			= $('#resource_off'),
			_off		= $('#resource_on'),
			_inv		= [],
			_container	= $('#templates'),
			_tmp		= null,
			_top		= null,
			_spans,
			_i,
			_elm;

		$('.pre_dev').dblclick(function() {
			$(this).find('span:first').toggle();
		});

		$('.resource div').each(function() {
			_container.append('<li><a href="'.concat(_location, '#smarty_res_', this.innerHTML.substr(0, this.innerHTML.indexOf(':')), '" class="template">', this.innerHTML.substr((-1 != (ndx = this.innerHTML.lastIndexOf('/')) ? ndx + 1 : this.innerHTML.indexOf(':') + 2)),'</a></li>'));
		});

		$('.template').mouseover(function() {
			var _current = $('#'.concat(this.hash.substr(1))).parent();

			if (_current != _top) {
				if (_top != null) {
					_top.removeClass('resource_top');
				}
				_top = _current;
			}
			_current.addClass('resource_top');
		});

		$('#toggle_templates').click(function() {
			_resources.toggle();
			_container.toggle();
			_on.toggle();
			_off.toggle();
			return false;
		});
	});
</script>
{/literal}

<div id="debug_menu_header">
	<p class="title">TOOLBAR<span class="slide">*</span></p>
	<div class="toolbar" style="display:none;">
		{if isset($sDbSightTime)}
		<ul>
			<li class="box">
				<div><strong>XDATA:</strong> {$sDbSightTime}<br /><strong>VO</strong></div>
				<span class="semi{if $bUseVo} ok{/if}"><a class="debug_menu_header_a" href="/protected/toolBarModes.php?type=bDisableVo&amp;mode=off"><span class="on">ON</span></a></span>
				<span class="semi{if !$bUseVo} ok{/if}"><a class="debug_menu_header_a" href="/protected/toolBarModes.php?type=bDisableVo&amp;mode=on"><span class="off">OFF</span></a></span>
				<div><strong>Environment</strong></div>
				<span class="semi{if $bVoDev} ok{/if}"><a class="debug_menu_header_a" href="/protected/toolBarModes.php?type=bVoDev&amp;mode=on"><span class="on">DEV</span></a></span>
				<span class="semi{if !$bVoDev} ok{/if}"><a class="debug_menu_header_a" href="/protected/toolBarModes.php?type=bVoDev&amp;mode=off"><span class="off">PROD</span></a></span>
				<div><strong>TwengaCache</strong></div>
				<span class="semi{if $bUseTcDev} ok{/if}"><a class="debug_menu_header_a" href="/protected/toolBarModes.php?type=bUseTcDev&amp;mode=on"><span class="on">DEV</span></a></span>
				<span class="semi{if !$bUseTcDev} ok{/if}"><a class="debug_menu_header_a" href="/protected/toolBarModes.php?type=bUseTcDev&amp;mode=off"><span class="off">PROD</span></a></span>
			</li>
		</ul>
		{/if}
		{if isset($bBounds) && $bBounds eq "enable"}
		<ul>
			<li class="box">
				{if $STATUS_BOUNDS_CAT eq 1}
				<a href='#' onclick="javascript:return false;">BOUNDS ({$MIN_BOUND} - {$MAX_BOUND}) : <span class="on">YES</span></a>
				{elseif isset($STATUS_BOUNDS_CAT) && $STATUS_BOUNDS_CAT eq 0}
				<a href='#' onclick="javascript:return false;">BOUNDS ({$MIN_BOUND} - {$MAX_BOUND}) : <span class="off">NO</span></a>
				{else}
				<a href="#" onclick="javascript:window.open('/internal_popup.php?v=1&s={$STATUS_BOUNDS_CAT}&t={$aResult[prod].TYPE}&cat_id={$iCatId}&mode=valid_bounds','internal_popup','screenX=1,screenY=0,resizable=yes,scrollbars=yes,height=300,width=800');return false;">
					BOUNDS ({$MIN_BOUND} - {$MAX_BOUND})<span class="on">YES</span>
				</a>
				<a href="#" onclick="javascript:window.open('/internal_popup.php?v=0&s={$STATUS_BOUNDS_CAT}&t={$aResult[prod].TYPE}&cat_id={$iCatId}&mode=valid_bounds','internal_popup','screenX=1,screenY=0,resizable=yes,scrollbars=yes,height=300,width=800');return false;">
					&nbsp;<span class="off">NO</span>
				</a>
				{/if}
			</li>
		</ul>
		{/if}
		<ul>
			<li>{htl type="bHideToolbar"}HIDE TOOLBAR{/htl}</li>
			<li>{htl type="gbot"}SEO{/htl}</li>
			<li>{htl type="bIsInternal"}INTERNAL{/htl}</li>
			<li>{htl type="bIsBCT"}BCT{/htl}</li>
			<li>{htl type="allow_twenga_debug"}DEBUG BAR{/htl}</li>
			<li>{htl type="show_trad_id"}TRADS{/htl}</li>
			<li>{htl type="bShowDwLogs"}SHOW DW LOGS{/htl}</li>
			{if isset($bIsSearchScore)}
			<li>
				<form method="post" style="float:left" id="formShuffle">
				<input type="hidden" name="shuffle" id="idShuffle" />
				</form>
				<a href="javascript:void(0);" onclick="document.getElementById('idShuffle').value={if $iShuffle}'false'{else}'true'{/if};document.getElementById('formShuffle').submit();return false;">
					SHUFFLE<span style="color:{if $iShuffle}#16afdb{else}#D4315A{/if};">{if $iShuffle}ON{else}OFF{/if}</span>
				</a>
			</li>
			{/if}
			<li>{htl type="bShowBenchMark"}BENCHMARK{/htl}</li>
			<li>
				{htl type="bShowTemplatesName"}TEMPLATES{/htl}
				{htb type="bShowTemplatesName"}<ul id="templates"></ul>{/htb}
			</li>
			<li>{htl type="bForceCompile"}FORCE COMPILE{/htl}</li>
			<li>{htl type="bEnableCache"}CACHE{/htl}</li>
		</ul>
		<p>{htl type="NewSession" onValue="new" toggle=false}NEW SESSION{/htl}</p>
		<p>{htl type="clear_cache" onValue="clear" toggle=false}CLEAR CACHE{/htl}</p>
	</div>
</div>