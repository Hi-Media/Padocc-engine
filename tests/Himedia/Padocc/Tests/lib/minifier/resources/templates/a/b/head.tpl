{****** @desc V6 - Head all site pages. ******}

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
	<meta charset="UTF-8" />
	<title>{$title|default:"Twenga"}</title>
	<meta name="author" content="Twenga SA" />
	<meta name="Owner" content="Twenga SA" />
	<meta name="Distribution" content="Global" />
	<meta name="Copyright" content="Copyright @ 2011 Twenga SA" />
	<meta name="description" content="{$meta_description|escape:'html'}" />
	<meta http-equiv="content-language" content="{$aMetaLang.Content_language}" />
	<meta http-equiv="country" content="{$aMetaLang.Country}" />

	
	{****** FACEBOOK ******}
	{if true == isset($aFbMeta)}
	<meta property="og:site_name" content="{$aFbMeta.sSiteName}" />
	<meta property="fb:app_id" content="{$sFbApplicationId}" />
	<meta property="og:title" content="{$aFbMeta.sTitle|escape:'html'}" />
	<meta property="og:type" content="{$aFbMeta.sType}" />
	<meta property="og:url" content="{$aFbMeta.sUrl}" />
		{if false == empty($aFbMeta.sDescription)}
	<meta property="og:description" content="{$aFbMeta.sDescription|escape:'html'}" />
		{/if}
		{if false == empty($aFbMeta.sImageUrl)}
			<meta property="og:image" content="{$aFbMeta.sImageUrl}" />
		{elseif true == is_array($aFbMeta.aImageUrls) && count($aFbMeta.aImageUrls) > 0}
			{foreach from=$aFbMeta.aImageUrls item=sFacebookMetaImageUrl}
				<meta property="og:image" content="{$sFacebookMetaImageUrl}" />
			{/foreach}
		{else}
			<meta property="og:image" content="http://s0{$smarty.const.STATIC_DOMAIN}/{$smarty.const.TWENGABUILD}/web/css/images/common/nophoto/nophoto-100-{$language_id}.png" />
		{/if}
	{elseif true == isset($sFbMeta)}
		{$sFbMeta}
	{/if}
	{if $bMetaNoIndex}
	<meta name="robots" content="noindex,follow"/>
	{/if}

	{****** MORE META ******}
	{if $sPageName == 'home'}
		{include file='common/statics_meta.tpl'}
	{/if}

	{* CONSTANT *}
	{if true == isset($sUrl) || true == isset($iNbPage)}
	<script type="text/javascript">
		var sCurrentUrl = "{$sUrl}";
		var iNbPage = {$iNbPage|intval};
	</script>
	{/if}

	{****** JQUERY *****}
	{include file='common/init_js.tpl'}

	{****** ROBOT ******}
	{if (isset($sPageName) && $sPageName == "search_listing" && $iNbResult < 4) || (isset($no_index) && $no_index == true) || isset($bCrossBrand)}
	<meta name="robots" content="noindex,follow" />
	{/if}

	{if false == empty($bGoogleBotNoIndex)}
	<meta name="googlebot" content="noindex,follow">
	{/if}

	{****** BASE ******}
	<base href="http://{$smarty.const.FULL_SITE_NAME}/"></base>

	{****** CANONICAL ******}
	{if isset($canonical_link)}
	<link rel="canonical" href="{$canonical_link}" />
	{/if}

	{****** IMAGE / FAVICON ******}
	{if isset($img_share)}
	<link rel="image_src" href="{$img_share}" />
	{/if}
	<link rel="shortcut icon" type="image/png" href="http://s0{$smarty.const.STATIC_DOMAIN}/{$smarty.const.TWENGABUILD}/web/css/images/common/favicon.png" />
	<link rel="apple-touch-icon" href="http://s0{$smarty.const.STATIC_DOMAIN}/{$smarty.const.TWENGABUILD}/web/css/images/common/apple-touch-icon.png">

	{****** XML / RPC ******}
	{if isset($pingback)}
	<link rel="pingback" href="http://{$site}/xmlrpc.php" />
	{/if}

	<!--[if lte IE 8]>{combine compress=true}<script src="/js/lib/html5.js" type="text/javascript"></script>{/combine}<![endif]-->

	{****** CSS ******}
	{include file='common/statics_css.tpl'}

	{****** MOBILE VIEWPORT OPTIMIZED ******}
	{*<meta name="viewport" content="width=device-width, initial-scale=1.0">*}
	<meta name="viewport" content="width=device-width" />
	

	{if $aGoogleAnalyticsConfiguration}
		<script type="text/javascript">
		 function loadAnalytics()
		 {ldelim}
			Analytics_Controller.getInstance({$aGoogleAnalyticsConfiguration|@json_encode});
		 {rdelim}
		</script>
		{combine compress=true onload="loadAnalytics"}
		<script type="text/javascript" src="/js/google/analytics_controllerv4.js"></script>
		{/combine}
	{/if}

	{* Begin comScore Tag *}
	{if $smarty.const.COMSCORE_TAG eq "enable"}
		{literal}
		<script>
			var _comscore = _comscore || [];
			_comscore.push({ c1: "2", c2: "6036184" });
			(function() {
				var s = document.createElement("script"), el = document.getElementsByTagName("script")[0]; s.async = true;
				s.src = (document.location.protocol == "https:" ? "https://sb" : "http://b") + ".scorecardresearch.com/beacon.js";
				el.parentNode.insertBefore(s, el);
			})();
		</script>
		<noscript><img src="http://b.scorecardresearch.com/p?c1=2&c2=6036184&cv=2.0&cj=1" /></noscript>
		{/literal}
	{/if}

	{****** Google Ads ******}
	{include file="google/adsense.tpl"}
</head>
<!--[if lt IE 7 ]>
<body id="ie6" class="IE lang-{$lang_code}">
<![endif]-->
<!--[if IE 7 ]>
<body id="ie7" class="IE lang-{$lang_code}">
<![endif]-->
<!--[if IE 8 ]>
<body id="ie8" class="IE lang-{$lang_code}">
<![endif]-->
<!--[if IE 9 ]>
<body id="ie9" class="IE lang-{$lang_code}">
<![endif]-->
<!--[if (gt IE 9)|!(IE)]><!-->
<body class="lang-{$lang_code}">
<!--<![endif]-->