{* @desc V3 - r.php - analytics tracking. *}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
	{combine compress=true}<link media="all" href="/css/r.css" rel="stylesheet" type="text/css" />{/combine}
	{if $aGoogleAnalyticsConfiguration}
		{combine compress=true}<script type="text/javascript" src="/js/google/analytics_controllerv4.js"></script>{/combine}
		<script type="text/javascript">
			Analytics_Controller.getInstance({$aGoogleAnalyticsConfiguration|@json_encode});
		</script>
	{/if}
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>

  		{if isset($tracker_url)}
   			<script type="text/javascript" src="{$tracker_url}"></script>
		{/if}

		{if $smarty.const.DEFAULT_GEOZONE == 2 || $smarty.const.DEFAULT_GEOZONE == 5 || $smarty.const.DEFAULT_GEOZONE == 7 || $smarty.const.DEFAULT_GEOZONE == 10}
			<link rel="canonical" href="http://{$smarty.const.FULL_SITE_NAME}/" />
		{/if}

		{if $isSemTesting}
			{literal}
			<script type="text/javascript">
			/* <![CDATA[ */
			var google_conversion_id = 961663978;
			var google_conversion_language = "fr";
			var google_conversion_format = "3";
			var google_conversion_color = "ffffff";
			var google_conversion_label = "GFQ3CNaq4wIQ6qfHygM";
			var google_conversion_value = 0;
			/* ]]> */
			</script>
			<script type="text/javascript" src="http://www.googleadservices.com/pagead/conversion.js"></script>
			{/literal}
			<noscript>
				<div style="display:inline;"><img height="1" width="1" style="border-style:none;" alt="" src="http://www.googleadservices.com/pagead/conversion/961663978/?label=GFQ3CNaq4wIQ6qfHygM&amp;guid=ON&amp;script=0"/></div>
			</noscript>
		{/if}

		{if !$aGoogleAnalyticsConfiguration}
		{literal}
		<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push( ['_setAccount', '{/literal}{$sGoogleAnalyticsAccount}{literal}'] );
		_gaq.push( ['_setDomainName', '.{/literal}{$smarty.const.TOPLEVEL_HOST}{literal}'] );
		_gaq.push( ['_setAllowHash', false] );
		//_gaq.push( ['_trackEvent', 'Outbound Links', '{/literal}{$sGoogleAnalyticsUniverse}{literal}', '{/literal}{$sGoogleAnalyticsAffiliateStatusStr}{literal}' ] );
		_gaq.push( ['_trackPageview']);
		_gaq.push([
			'_addTrans' ,
			'{/literal}{$sGoogleAnalyticsOrderId}-{$sGoogleAnalyticsAffiliateStatus}',
			'{$sGoogleAnalyticsRefererUrl}',
			'{$sGoogleAnalyticsAffiliateStatus}',
			'',
			'',
			'',
			'',
			''
		]);

		_gaq.push([
			'_addItem',
			'{$sGoogleAnalyticsOrderId}-{$sGoogleAnalyticsAffiliateStatus}',
			'{$sGoogleAnalyticsItemName}',
			'{$sGoogleAnalyticsTargetSiteName}-{$sGoogleAnalyticsSiteId}',
			'{$sGoogleAnalyticsUniverse}',
			'{$sGoogleAnalyticsAffiliateStatus}{literal}',
			'1'
		]);

		_gaq.push(['_trackTrans']);

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src   =  'http://s0{/literal}{$smarty.const.STATIC_DOMAIN}{literal}/{/literal}{$smarty.const.TWENGABUILD}{literal}/web/js/ga.js';
			(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
		})();
		</script>
		{/literal}
		{/if}
		<script type="text/javascript">
		{literal}
		function redirection()
		{
			if (/MSIE (\d+\.\d+);/.test(navigator.userAgent))
			{
         	   var referLink = document.createElement('a'); referLink.href = "{/literal}{$sUrl}{literal}"; document.body.appendChild(referLink); referLink.click();
    		} 
    		else
        	{
				location.href = "{/literal}{$sUrl}{literal}";
    		}
    	}
	    
		window.onload = function(){setTimeout("redirection();",{/literal}{$iWaitTime}{literal}); }
		{/literal}
		</script>
	</head>
	<body>
	{if $bEnableRedirectionTemplate}
		<div class="inset">
			<div class="wLogo">
				<div title="Twenga, {tr _id=10226}." class="Logo" href="/"></div>
				<span class="caption">{tr _id=18171}</span>
			</div>
			<p>{tr _id=2601}</p>
			<span class="nextStep">{tr _id=20481 merchant=$sSiteName}</span>
			<img src="http://s0{$smarty.const.STATIC_DOMAIN}/{$smarty.const.TWENGABUILD}/webv4/css/images/common/ajax-loader3.gif" width="61" height="58" />
		</div>
		{capture assign=link}<a class="lk" href="{$sUrl}">{$sSiteName}</a>{/capture}
		<span class="baseline">{tr _id=20501 merchant_name=$link}</span>
	{else}
		<noscript>
			{capture assign=link}<a class="lk" href="{$sUrl}">{$sSiteName}</a>{/capture}
			<span class="baseline">{tr _id=20501 merchant_name=$link}</span>
		</noscript>
	{/if}
	</body>
</html>
