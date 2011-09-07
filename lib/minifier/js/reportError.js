/*** Script report-error.js ***/

$(function(){
	var popinDiv;
	var errorForm = $("#oErrorSend");
	var jErrorMessage;
	var curForm;
	var bNoError


	// sélection du cas pour afficher le bon formulaire
	$("#formUserAlertSelect").change(function(e){
		if( $(this).val() == "Illegal content" ) {
			if(curForm != "illegalCt"){
				var loadForm = tmpl("illegalCtChoice",{});
			} else {
				var loadForm = 0;
			}
		} else if( $(this).val() != -1 ) {
			if(curForm != "errorCt"){
				var loadForm = tmpl("errorCt",{});
			} else {
				var loadForm = 0;
			}
		}
		if( loadForm ) {
			errorForm.find("fieldset").html(loadForm);
			initErrorReportForm();
		}
	})

	function initErrorReportForm()
	{
		jErrorMessage = errorForm.find(".error-message");
		jErrorMessage.hide();

		// CANCEL BUTTON
		/*errorForm.find(".jsCancel").click(function(e){
			history.back();
		});*/

		// CHOOSE ILLEGAL FORM TYPE
		formIllegalChoice(errorForm.find("input[name=type]"));
	}

	function formIllegalChoice(inp)
	{
		inp.change(function(e){
			// Text highlight
			var oId = $(this).attr("id");
			$(this).siblings("label").removeClass("active");
			if($(this).is(":checked")) $(this).siblings("label[for="+oId+"]").addClass("active");
			// Load choosen form
			var loadForm = tmpl("illegalCt",{FORM: $(this).val()});
			$("#illegalForm").html(loadForm);
			initErrorReportForm();
		});
	}

	//NEW VERSION
	errorForm.ajaxForm({
		beforeSubmit: function() {
			return checkForm(jErrorMessage);
		},
		success: function(e) {
			if( e == "error_upload" ) {
				$("#file1").parent("p").addClass("error");
				$("#file2").parent("p").addClass("error");
				$("#file3").parent("p").addClass("error");
			} else if ( e == "SIZE" ) {
				$("#file1").parent("p").addClass("error");
				$("#file2").parent("p").addClass("error");
				$("#file3").parent("p").addClass("error");
			} else if ( e == "" ) {
				//SHOW POPIN 5 SECS THEN REDIRECT
				var oPopinDiv = document.createElement("div");
				var popinDivId = document.createAttribute("id");
				popinDivId.nodeValue = "popinDiv";
				oPopinDiv.setAttributeNode(popinDivId);

				$("body").append(oPopinDiv);
				var popinDiv = $("#popinDiv");
				popinDiv.html(tmpl("errorReportSuccess",{}));
				popinDiv.dialog({
					autoOpen: true,
					width: 500,
					modal: true,
					draggable: false,
					dialogClass: "popinOk"
				});
				popinDiv.dialog("open");

				setTimeout(function(){
					popinDiv.dialog("close");
					location.reload();
				}, 5000);
			} else {
				//unknown error
			}
		}
	});
	
});

function checkEmail(jEmail)
{
	var regEmail = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	if( jEmail.val() == "" || false == regEmail.test(jEmail.val()) )
	{
		jEmail.attr("value","");
		jEmail.parent("p").addClass("error");
		if(bNoError) bNoError = false;
	}
	else
	{
		jEmail.parent("p").removeClass("error");
		return true;
	}
}

function checkSelect(jSelect)
{
	if (jSelect.val() == "-1")
	{
		jSelect.parent("p").addClass("error");
		if(bNoError) bNoError = false;
	}
	else
	{
		jSelect.parent("p").removeClass("error");
		return true;
	}
}

function checkMessage(jTextarea)
{
	if (jTextarea.val() == "")
	{
		jTextarea.parent("p").addClass("error");
		if(bNoError) bNoError = false;
	}
	else
	{
		jTextarea.parent("p").removeClass("error");
		return true;
	}
}

function checkCGV(sDomId)
{
	if (document.getElementById(sDomId) && true==document.getElementById(sDomId).checked)
	{
		$("#"+sDomId).parent("p").removeClass("error");
		return true;
	}
	else
	{
		$("#"+sDomId).parent("p").addClass("error");
		if(bNoError) bNoError = false;
	}
}

function checkFiles(sDomId)
{
	if ($(sDomId).val().substr(-3) == "pdf" || $(sDomId).val().substr(-3) == "")
	{
		$(sDomId).parent("p").removeClass("error");
		return true;
	}
	else
	{
		$(sDomId).parent("p").addClass("error");
		if(bNoError) bNoError = false;
	}
}


function checkForm(jErrorMessage)
{
	bNoError = true;
	if($("#formId").val() == "a") {
		checkMessage($("#your-message"));
		checkEmail($("#your-email"));
		checkCGV("your-cgv");
	} else if ($("#formId").val() == "b1"){
		checkMessage($("#denomination"));
		checkMessage($("#lastName"));
		checkMessage($("#firstName"));
		checkMessage($("#work"));
		checkEmail($("#eMail"));
		checkMessage($("#yourMessage"));
		checkCGV("yourCgv");
		checkFiles("#file1");
		checkFiles("#file2");
		checkFiles("#file3");
	} else if ($("#formId").val() == "b2"){
		checkMessage($("#lastName"));
		checkMessage($("#firstName"));
		checkEmail($("#eMail"));
		checkMessage($("#yourMessage"));
		checkCGV("yourCgv");
		checkFiles("#file1");
		checkFiles("#file2");
		checkFiles("#file3");
	}

	if(bNoError)
		jErrorMessage.hide();
	else
		jErrorMessage.show();
	return bNoError;
}
