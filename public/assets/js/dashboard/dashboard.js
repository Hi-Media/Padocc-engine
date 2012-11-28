

function getLogs (sProject, sEnv, sStartDate, sId) {

	$.ajax({
		
		url: '/deployment/getlogs?p=' + sProject + '&e=' + sEnv + '&sd=' + sStartDate + '&id=' + sId,
		success: function(data) {
			$('#DeployLogs h2').html(
					'Execution result of "<b>' + sProject + '</b>" project '
					+ 'on "<b>' + sEnv + '</b>" environment at <b>' + sStartDate + '</b>:');
			$('#DeployLogs .log_content').html(data);
			$('#log_debug_switch').attr('checked', false);
			$('#DeployLogs').show();

			var destination = $("#DeployLogs").offset().top;
			$('html, body').animate({scrollTop: destination}, 500);
		}
	});
}

function dashboard_switch_debug (oInput) {
	var aRows = $("#DeployLogs tr.debug, #DeployLogs tr.mail");
	if ($(oInput).is(':checked')) {
		aRows.show();
	} else {
		aRows.hide();
	}
}

$(document).ready(function () {
	$('#projectChoice').change(function() {
		var sProject = $("#projectChoice").val();
		document.location.href = '/dashboard?project=' + sProject;
	});
});
