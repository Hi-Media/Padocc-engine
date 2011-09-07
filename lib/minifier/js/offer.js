$(function(){
	$.shareSearchPlugin('init', 'product');

	$(".moreBt").click(function(){
		window.location.hash = "attrib";
	});
});