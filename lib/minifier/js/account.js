var oFavouriteShops; /* pour debug */

function chainHandler(obj, handlerName, handler){
	obj[handlerName] = (function(existingFunction){
		return function(){
			handler.apply(this, arguments);
			if (existingFunction)
				existingFunction.apply(this, arguments);
		};
	})(handlerName in obj ? obj[handlerName] : null);
};

function confirmClose(evt){
	var message 			= $("#exit-info").text();
	var is_saved			= $("#edit-account").find("a.button:visible").size();
	if(is_saved > 0){
		if(typeof evt == "undefined" && window.event){ evt = window.event; }
		// For IE and Firefox
		if(evt){ evt.returnValue = message; }
		// For Safari
		return message;
	}
}

$(function(){
	// Confirm Close
	var f_edit_account		= $("#edit-account");
	if(f_edit_account.size() > 0){
		chainHandler(window, "onbeforeunload", function(e){ confirmClose(e); });
	}

	$("#password").hide();
	$("#fakePassword").show().click(function() {
		$(this).hide();
		$("#password").show();
		$("#password").focus();
	});

	/**************************************************/
	/* MODIFIER / VALIDATION
	/**************************************************/
	//EditProfile
	$(".textview a.edit").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var currentValue = $this.siblings("em").text();
		$this.parent().parent().removeClass("active").parent().find(".line-edit").addClass("active").find("input.text").attr("value",currentValue);
	});
	$(".pwdview a.edit").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var currentValue = $this.parent().parent().find("input[type='password']").attr("value");
		$this.parent().parent().removeClass("active").parent().find(".line-edit").addClass("active").find("input.text").attr("value",currentValue);
	});
	$(".selectview a.edit").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var currentValue = $this.siblings("em").text();
		$this.parent().parent().removeClass("active").parent().find(".line-edit").addClass("active").find("input.text").attr("value",currentValue);
	});
	$(".radioview a.edit").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var currentValue = $this.siblings("em").text();
		if(currentValue == "Une femme"){
			$this.parent().parent().removeClass("active").parent().find(".line-edit").addClass("active").find("input#gender-female").click();
		} else {
			$this.parent().parent().removeClass("active").parent().find(".line-edit").addClass("active").find("input#gender-male").click();
		}
	});
	$(".radioview2 a.edit").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var currentValue = $this.siblings("em").text();
		if(currentValue == "Oui"){
			$this.parent().parent().removeClass("active").parent().find(".line-edit").addClass("active").find("input.yes").click();
		} else {
			$this.parent().parent().removeClass("active").parent().find(".line-edit").addClass("active").find("input.no").click();
		}
	});
	//ValidationEdit
	$(".textedit a.button").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var newTextValue = $this.parent().parent().find("input.text").attr("value");
		$this.parent().removeClass("active").parent().find(".line-view").addClass("active").find("em").empty().text(newTextValue);
	});
	$(".pwdedit a.button").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var newPwdValue = $this.parent().parent().find("input[type='password']").attr("value");
		var hiddenValue = "";
		for(var i=0;i < newPwdValue.length;i = i+1){
			hiddenValue = hiddenValue+"*";
		}
		$this.parent().removeClass("active").parent().find(".line-view").addClass("active").find("em").empty().text(hiddenValue);
	});
	$(".selectedit a.button").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var newSelectValue = new Array;
		newSelectValue[0] = $("form select.day").attr("value");
		newSelectValue[1] = $("form select.month").attr("value");
		newSelectValue[2] = $("form select.year").attr("value");
		$this.parent().removeClass("active").parent().find(".line-view").addClass("active").find("em").empty().text(newSelectValue[0]+"/"+newSelectValue[1]+"/"+newSelectValue[2]);
	});
	$(".radioedit1 a.button").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var newRadioValue = $("form .radioedit1 p input:checked").parent().find("label").text();
		$this.parent().removeClass("active").parent().find(".line-view").addClass("active").find("em").empty().text(newRadioValue);
	});
	$(".radioedit2 a.button").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var newRadioValue = $("form .radioedit2 p input:checked").parent().find("label").text();
		$this.parent().removeClass("active").parent().find(".line-view").addClass("active").find("em").empty().text(newRadioValue);
	});
	$(".radioedit3 a.button").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var newRadioValue = $("form .radioedit3 p input:checked").parent().find("label").text();
		$this.parent().removeClass("active").parent().find(".line-view").addClass("active").find("em").empty().text(newRadioValue);
	});
	$(".radioedit4 a.button").click(function(e){
		var $this = $(this);
		e.preventDefault();
		var newRadioValue = $("form .radioedit4 p input:checked").parent().find("label").text();
		$this.parent().removeClass("active").parent().find(".line-view").addClass("active").find("em").empty().text(newRadioValue);
	});

	
	/**************************************************/
	/* EDIT PROFILE
	/**************************************************/


	/**************************************************/
	/* PHOTO USER
	/**************************************************/
	// UPLOAD
	$("#button_photo_upload").click(function()
	{
		$(".error_upload_1").hide();
		$(".error_upload_2").hide();
		$(".error_upload_3").hide();
		$("#edit-picture").submit();
	});
	$('form#edit-picture').ajaxForm({
		beforeSubmit: function() {
			if( $("#file_photo_upload").attr("value") == "" )
			{
				$(".error_upload_3").show();
				return false;
			}
			else
			{
				$(".error_upload_3").hide();
				$("#edit-picture").hide();
				$("#load_bar_jcrop").show();
			}
		},
		success: function(e) {
			   if( e == "error_upload" )
			   {
					$("#load_bar_jcrop").hide();
					$("#edit-picture").show();
					$(".error_upload_1").show();
			   }
			   else
			   {
					$("#testWrap").html('<img src="" id="cropbox" />');

					var aUploadResult = e.split(',');

					$("#cropbox").attr("src", aUploadResult[0]).hide();
					$("#preview").attr("src", aUploadResult[0]);
					$("#file_name_id").attr("value",aUploadResult[1]);

					$("#cropbox").load(function(){
						$("#load_bar_jcrop").hide();
						//affiche l'image à charger
						$("#wrapper").show();
						$("#cropbox").show();

						$("#cropbox").Jcrop({
							onChange: showCoords,
							onSelect: showCoords,
							setSelect: [0,0,80,80],
							minSize: [80,80],
							allowSelect: false,
							aspectRatio: 1
						});
					});
			   }
		}
	});

	function showCoords(c)
	{
		$('#x').val(c.x);
		$('#y').val(c.y);
		$('#w').val(c.w);
		$('#h').val(c.h);

		if (parseInt(c.w) > 0)
		{
			var rx = 80 / c.w;
			var ry = 80 / c.h;

			$('#preview').css({
				width: Math.round(rx * $("#cropbox").width()) + 'px',
				height: Math.round(ry * $("#cropbox").height()) + 'px',
				marginLeft: '-' + Math.round(rx * c.x) + 'px',
				marginTop: '-' + Math.round(ry * c.y) + 'px'
			});
		}
	}

	// CROP
	$("#button_photo_crop").click(function(e){
		$(".error_upload_1").hide();
		$(".error_upload_2").hide();
		$(".error_upload_3").hide();
		$("#crop-picture").submit();
	});
	$('form#crop-picture').ajaxForm({
		beforeSubmit: function() {
			if(!$('input[name=disclam_crop_img]').attr('checked'))
			{
				$(".error_upload_2").show();
				return false;
			}
			else
			{
				$(".error_upload_2").hide();
				$(".jcrop-holder").remove("");
				$("#preview").html("");
				$("#wrapper").hide();
				$("#file_name_id").attr("value","");
				$("#load_bar_jcrop").show();
				$("#rm_photo").show();
			}
		},
		success: function(e) {
			$("#user_image_id").attr("src",e+"?"+Math.random());
			$("#box_user_image_id").attr("src",e+"?"+Math.random());
			$("#rm_photo").removeClass("nophoto");
			$("#user_image_id").load(function(){
				$("#load_bar_jcrop").hide();
				$("#edit-picture").show();
				$("#file_photo_upload").attr("value","");
			});
		}
	});

	// DELETE
	$("#rm_photo").click(function(e){
		e.preventDefault();

		var oForm = document.createElement("FORM");
		oForm.id= "form_delete_photo";
		oForm.action = "/account_edit.php#photo";
		oForm.method = "POST";
		oForm.style.display= "none";

		var oInput = document.createElement("INPUT");
		oInput.type = "hidden";
		oInput.name = "rm_photo";
		oInput.value = "1";

		oForm.appendChild(oInput);

		document.body.appendChild(oForm);

		setTimeout(function(){
			$('#form_delete_photo').submit();
		}, 100);
	});

	// CANCEL
	$("#cancel_photo").click(function(e){
		$("#wrapper").hide();
		$("#cropbox").hide();
		$("#edit-picture").show();
	});

	/**************************************************/
	/* Newsletter sigin
	/**************************************************/
	$("#col-right #nlf-submit").click(function(e){
		if( $("#nlf-li").attr("checked") )
		{
			var iValue = 1;
		}
		else
		{
			var iValue = 0;
		}

		$.post("/account.php",{
			"action" : "newsletter_ok",
			"iValue" : iValue
			},

			function success(data){
				var newContentConfirm = $("#content-newsletter-confirm").html();
				$(".tbox.newsletter .content").html(newContentConfirm);
			}
		);
	return false;
	});

	$("#edit-account p span.error").hide();

	$("#my-account :input").click(function(){
		$("#my-account").find("a.button").fadeIn();
	});

	$("#my-info :input").click(function(){
		$("#my-info").find("a.button").fadeIn();
	});

	$("#my-info a.button, #my-account a.button").click(function(e){
		e.preventDefault();
		$(this).fadeOut();
	});

	$("#my-info a.button").click(function(){
		if( $("#public-yes").attr("checked") ) var bPublicValue = 1;
		else var bPublicValue = 0;
		var sNickname = $("#nickname").val();
		var sFirstNameValue = $("#first-name").val();
		var sLastNameValue = $("#last-name").val();
		if( $("#gender-female").attr("checked") ) var sGenderValue = 1;
		else var sGenderValue = 0;
		var sDay    = $("#day").val();
		var sMonth  = $("#month").val();
		var sYear   = $("#year").val();
		var sDateValue = sYear+'-'+sMonth+'-'+sDay;
		var sCityValue = $("#city-name").val();
		// alert(bPublicValue+sFirstNameValue+sLastNameValue+sGenderValue+sDateValue+sCityValue);
		$.post("/account_edit.php",{
			"bPublicValue" : bPublicValue,
			"sNickname" : sNickname,
			"sFirstNameValue" : sFirstNameValue,
			"sLastNameValue" : sLastNameValue,
			"sGenderValue" : sGenderValue,
			"sDateValue" : sDateValue,
			"sCityValue" : sCityValue,
			"edit_profil" : "update_my_info"
			},
			function success(data){
				$(".e3, .e1, .e4, .e6, .e5, .e8, .e9, .e10, .e11, .e12, .e13, .e14, .e15, .e16, .e17, .e18").hide();
				if(data == "false") return;
				data = data.split('_');
				$(data).each(function(i){
					if( data[i] == "error3" ) $(".e4").show();
					else if( data[i] == "error4" ) $(".e3").show();
					else if( data[i] == "error5" ) $(".e1").show();
					else if( data[i] == "error11" ) $(".e11").show();
					else if( data[i] == "error12" ) $(".e12").show();
					else if( data[i] == "error6" ) $(".e6").show();
					else if( data[i] == "error7" ) $(".e5").show();
					else if( data[i] == "error8" ) $(".e8").show();
					else if( data[i] == "error13" ) $(".e13").show();
					else if( data[i] == "error9" ) $(".e9").show();
					else if( data[i] == "error10" ) $(".e10").show();
					else if( data[i] == "error14" ) $(".e14").show();
					else if( data[i] == "error15" ) $(".e15").show();
					else if( data[i] == "error16" ) $(".e16").show();
					else if( data[i] == "error17" ) $(".e17").show();
					else if( data[i] == "error18" ) $(".e18").show();
				});
			}
		);
	});
	$("#my-account a.button").click(function(){
		if( $("#adult-yes").attr("checked") ) var sAdultZone = 1;
		else var sAdultZone = 0;
		if( $("#newsletter-yes").attr("checked") ) var sNewsLetter = 1;
		 else var sNewsLetter = 0;
		var sPassword = $("#password").val();
		var sEmail = $("#email").val();
		$.post("/account_edit.php",{
			"sAdultZone" : sAdultZone,
			"sNewsLetter" : sNewsLetter,
			"sPassword" : sPassword,
			"sEmail" : sEmail,
			"edit_profil" : "update_my_account"
			},
			function success(data){
				$(".e3, .e1, .e4, .e6, .e5, .e8, .e9, .e10, .e11, .e12, .e13, .e14, .e15, .e16, .e17, .e18").hide();
				if(data == "false") return;
				data = data.split('_');
				$(data).each(function(i){
					if( data[i] == "error3" ) $(".e4").show();
					else if( data[i] == "error4" ) $(".e3").show();
					else if( data[i] == "error5" ) $(".e1").show();
					else if( data[i] == "error11" ) $(".e11").show();
					else if( data[i] == "error12" ) $(".e12").show();
					else if( data[i] == "error6" ) $(".e6").show();
					else if( data[i] == "error7" ) $(".e5").show();
					else if( data[i] == "error8" ) $(".e8").show();
					else if( data[i] == "error13" ) $(".e13").show();
					else if( data[i] == "error9" ) $(".e9").show();
					else if( data[i] == "error10" ) $(".e10").show();
					else if( data[i] == "error14" ) $(".e14").show();
					else if( data[i] == "error15" ) $(".e15").show();
					else if( data[i] == "error16" ) $(".e16").show();
					else if( data[i] == "error17" ) $(".e17").show();
					else if( data[i] == "error18" ) $(".e18").show();
				});
			}
		);
	});
	$("#delete-my-account").click(function(){
		var popupAlertTitle = $("#deleteMyAccountTitle").text();
		var popupAlertDesc = $("#deleteMyAccountDesc").html();
		//openSimpleTwengaBox(popupAlertTitle,popupAlertDesc);

		if ($("#popAlert").size() == 0) {
			var oPopAlert = document.createElement("div");
			var popAlertId = document.createAttribute("id");
			popAlertId.nodeValue = "popAlert";
			oPopAlert.setAttributeNode(popAlertId);
			$("body").append(oPopAlert);
			delete oPopinDiv;
		}

		var popAlert = $("#popAlert");
		popAlert.html(popupAlertDesc);
		popAlert.html(popupAlertDesc);
		popAlert.dialog({autoOpen: false, width: 635, modal: true, draggable: false, dialogClass: 'popupAlert', position:'center'});
		popAlert.dialog( "option", "title", popupAlertTitle);
		if($(".popupAlert .ui-icon span").size() == 0) { $(".popupAlert .ui-icon").append("<span>X</span>"); }
		popAlert.find(".delAccountCancel").click(function(event){
			$(popAlert).dialog("close");
		});
		popAlert.dialog("open");

		$(".delAccountSubmit").click(function(){
			$.post("/account_edit.php",{"edit_profil" : "delete_my_account"},function success(data){
				twl(0,'/njs'+'/ybtbhg.cuc');
			});
			return false;
		});
		return false;
	});
	$("#infoTooltipLk").hover(function(){$("#infoTooltip").show();},function(){$("#infoTooltip").hide();});


	$("#btUnlinkFb").click(function(){
		twGetAjax('/ajax/message_confirm_unlink.php',{},function(retour){
			if(confirm(retour))
			{
				unlinkFacebook(1);	
			}
		});
		
	});
	
	//unlink the current user from facebook
	function unlinkFacebook(warn) {
		
		//get the user ID -- How to
		FB.getLoginStatus(function(response) {
			
			//console.log(response);
			if (response.session) {
				// logged in and connected user, someone you know
				var sUid = response.session.uid;
				FB.api({ method: 'Auth.revokeAuthorization' }, function(response){
					if(response="true") {
						//send mail with logins
						twGetAjax('/ajax/send_mail_unlink.php?uid='+sUid,{},function(retour){
							//SUPPRIMER LA LIGNE FACEBOOK USER avec $sUid en params
							$.ajax({
								url: '/pp/account/unregister_facebook.php?uid='+sUid,
								success: function(data) {
									if(warn==1) {
										openVerySimpleTwengaBox('','#content_unlink','');
										setTimeout('location.reload();', 1000);
									}
								}
							});
						});
					}
				});
			}
		});
	}

	// Exclusive Favourites Shops instance
	if($("#favorite-shop").length)
	{
		oFavouriteShops = new FavouriteShops({
			'shopUrl': sExclusiveFavouriteShopUrl,
			'deleteUrl': sExclusiveFavouriteDeleteUrl,
			'nbPerPage': 5,
			'boxId': 'favorite-shop',
			'tmplId': 'favouriteShopsTmpl',
			'userEmail': sUserEmail,
			'userHashedPassword': sUserHashedPassword
		});
	}
	
});


/***** Favourite Shops *****/

function FavouriteShops(options) {
	this.options = options;
	this.shops = [];
	this.box = $('#'+this.options.boxId);
	this.tmpl = tmpl(this.options.tmplId);
	this.pageMax = 0;
	this.page = 0;

	this.data = {};
	if (this.options.userEmail && this.options.userHashedPassword) {
		this.data.EMAIL = this.options.userEmail;
		this.data.PASSWORD = this.options.userHashedPassword;
	}

	this.box.hide();
	this.requestShops();
	this.attachListeners();
}

FavouriteShops.prototype.attachListeners = function() {
	var _this = this;
	this.box.find('.pagination .ct a').live('click', function() {
		_this.showPage(($(this).text())-1);
	});
	this.box.find('.pagination a.prev').live('click', function() {
		_this.showPage(_this.page-1);
	});
	this.box.find('.pagination a.next').live('click', function() {
		_this.showPage(_this.page+1);
	});
	this.box.find('.shop-del a').live('click', function() {
		_this.deleteShop(this.id.replace("shop-",""));
	});
}

FavouriteShops.prototype.requestShops = function() {
	$.ajax({
		url: this.options.shopUrl,
		dataType: 'jsonp',
		data: this.data,
		context: this,
		success: this.loadShops
	});
};

FavouriteShops.prototype.loadShops = function(shops) {
	this.shops = shops;
	this.refreshShopCount();
	this.generatePaging();
	this.showPage(this.page);
}

FavouriteShops.prototype.generatePaging = function() {
	var pagination = this.box.find('.pagination .ct');
	pagination.html("");

	if (this.shops.length > this.options.nbPerPage) {
		this.pageMax = Math.ceil(this.shops.length / this.options.nbPerPage) - 1;
		for (var i=1 ; i<=this.pageMax+1 ; i++) {
			pagination.append('<a href="javascript:void(0)">'+i+'</a>');
		}
		this.box.show();
		this.box.find('.pagination').css('visibility', 'visible');

	} else if (this.shops.length > 0) {
		this.box.show();
		this.box.find('.pagination').css('visibility', 'hidden');

	} else {
		this.box.hide();
	}
}

FavouriteShops.prototype.refreshPaging = function() {
	this.box.find('.pagination a').removeClass('active');
	this.box.find('.pagination .ct a:nth-child('+(this.page+1)+')').addClass('active');
	this.box.find('.pagination .prev, .pagination .next').removeClass('h');
	if (this.page < 1) {
		this.box.find('.pagination .prev').addClass('h');
	}
	if (this.page >= this.pageMax) {
		this.box.find('.pagination .next').addClass('h');
	}
}

FavouriteShops.prototype.showPage = function(page) {
	while(page > 0 && page*this.options.nbPerPage >= this.shops.length)
		page--;
	this.page = page;

	var first = this.page*this.options.nbPerPage;
	var last = (this.page+1)*this.options.nbPerPage - 1;
	last = (last > this.shops.length-1) ? this.shops.length-1 : last;

	$('#favorite-shop .content').html("");
	for( var i=first ; i<=last ; i++) {
		this.shops[i].LAST = (i==last);
		$('#favorite-shop .content').append(this.tmpl(this.shops[i]));
	}

	this.refreshPaging();
}

FavouriteShops.prototype.deleteShop = function (shopId) {
	var data = this.data;
	data.SHOP_ID = shopId;
	$.ajax({
		url: this.options.deleteUrl,
		dataType: 'jsonp',
		data: data
	});

	oldShops = this.shops;
	this.shops = [];
	for( var i=0 ; i<oldShops.length ; i++) {
		if (oldShops[i].SHOP_ID != shopId) {
			this.shops.push(oldShops[i]);
		}
	}
	this.refreshShopCount();
	this.generatePaging();
	this.showPage(this.page);
}

FavouriteShops.prototype.refreshShopCount = function() {
	this.box.find('.count-shop').text(this.shops.length);
}
