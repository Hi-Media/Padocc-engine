{****** @desc Twenga V6 - Index page accessing in Twenga Home ******}

{****** HEAD ******}
{include file="common/head.tpl"}

	{****** HEADER ******}
	{* add hdtop for Header Top, hdsearch for Header Search Bar *}
	{include file="common/header.tpl" hdtop=true hdsearch=true}

	<div id="home" class="gtHome maskHome">
	
		{****** SLIDESHOW ******}
		{include file="index/slideshow.tpl"}
		{include file="index/reinsurance.tpl"}

		{****** EVENT SHOPPING ******}
		{if $SHOP_IDEAS && false == empty($SHOP_IDEAS)}
			{include file="index/event_product.tpl"}
		{/if}

		{****** EVENT DAY ******}
		{include file="events/banners.tpl" page="home"}

		{****** CATEGORIES ******}
		{if false == empty($aUniverses)}
			{include file="module/category/catTopList.tpl" aCategories=$aUniverses aCategoriesPlus=$aOtherUniverses}
		{/if}

		{****** POPULAR BRANDS ******}
		{if $iPopularBrands > 0}
			{include file="search/popular_brands.tpl"}
		{/if}

		{****** SHOPS ******}
		{* include file="index/static_shops.tpl" *}

		{if false == empty($aTopMerchant)}
			{include file="index/shops.tpl"}
		{/if}
		
		{****** SOCIAL FEATURES ******}
		{if false == empty($aFacebookStream) || false == empty($aSearchPolls) || false == empty($aUserFacebook)}
			{include file="index/social.tpl"}
		{/if}

		{****** TOP CATEGORY SEARCH ******}
		{if false == empty($aOtherCategories[0])}
			{include file="module/category/catTopSearch.tpl"}
		{/if}
	</div>
	<noscript>
		{combine compress=true}
		<link media="all" href="/css/index_nojs.css" rel="stylesheet" type="text/css" />
		{/combine}
	</noscript>
	{****** FOOTER ******}
	{include file="common/footer.tpl" ftsearch=true}

{****** FOOT ******}
{include file="common/foot.tpl"}