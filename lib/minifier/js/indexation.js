$(function()
{
	//form validation
	$('body').delegate('#submitIndexationForm', 'click', validIndexation);

	function validIndexation ()
	{

		$(this).attr('disabled', true);

		var sNameSite = $("#YourNameSite").val();
		var sSiteUrl = $("#YourUrl").val();
		var sEmail = $("#YourEmail").val();
		var bFormOk = true;

		if(sNameSite.length == 0 || sNameSite.length > 100)
		{
			$("#YourNameSiteError").addClass('h').show();
			$("#YourNameSite").addClass('error').show();
			bFormOk = false;
		}
		else
		{
			$("#YourNameSiteError").removeClass('h').hide();
			$("#YourNameSite").removeClass('error');
		}
		var iSiteUrlLength = sSiteUrl.length;

		if(iSiteUrlLength == 0 || iSiteUrlLength > 300 || sSiteUrl == 'http://')
		{
			$("#YourUrlError").addClass('h').show();
			$("#YourUrl").addClass('error').show();
			bFormOk = false;
		}
		else
		{
			$("#YourUrlError").removeClass('h').hide();
			$("#YourUrl").removeClass('error');
		}

		var pattern = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
		if(!pattern.test(sEmail))
		{
			$("#YourEmailError").addClass('h').show();
			$("#YourEmail").addClass('error').show();
			bFormOk = false;
		}
		else
		{
			$("#YourEmailError").removeClass('h').hide();
			$("#YourEmail").removeClass('error');
		}
		$(this).attr('disabled', false);

		if(bFormOk)
		{
			this.form.submit();
		}
	}
});