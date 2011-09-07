$(function(){
	$.shareSearchPlugin('init', 'product');


	if (typeof(bPopupBL) != 'undefined' && bPopupBL)
	{
		var sContinue	= $("#continue").text();
		sContinue		= encodeURIComponent(sContinue);
		confirmAlertPrice(sContinue);
	}

	$('.jsFullSpecs').click(function(){
		if($('#jPdtDetailsFull').length == 0)
		{
			$('#jPdtDetailsShort').twMask('show');
			var product = $("#product");
			$.get(	'product.php',
				{ajax: 1,page_name: 'specs', item: product.attr("data-jsIdItem"), type: product.attr("data-jsItemType")},
				function(html){
					$.twMask("hideall");
					//$('#jPdtDetailsShort').replaceWith(html);
					$('#jDetails').html(html);
					$("html").scrollTop($('#jPdtDetailsFull').offset().top);
				});
		}
		else
		{
			$("html").scrollTop($('#jPdtDetailsFull').offset().top);
		}
	});
});



function showMoreTooltip(o)
{
	var posL = o.parents("li").position().left;
	var posT = o.parents(".likeToo").position().top;
	var ttHtml = o.children("p.h").html();
	var toolTip = $("#prdTtipMed");
	if(toolTip.size() == 0) {
		toolTip = document.createElement("div");
		var toolTipId = document.createAttribute("id");
		toolTipId.nodeValue = "prdTtipMed";
		toolTip.setAttributeNode(toolTipId);
		$("body").append(toolTip);
		toolTip = $("#prdTtipMed");
		$("<span>").addClass("sp aw").appendTo(toolTip);
		$("<p>").appendTo(toolTip);
	}
	var parentLi = o.parents("li");
	if(parentLi.hasClass("last") || parentLi.next("li.last").size()>0){
		toolTip.addClass("last");
	} else {
		toolTip.removeClass("last");
	}
	toolTip.children("p").html(ttHtml);
	toolTip.css({left: posL+"px", top: posT+"px"});
	toolTip.show();
}