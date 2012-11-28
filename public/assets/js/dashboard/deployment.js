
$(function(){
	$(document).ready(function() {
		/*$('#external_properties ul').delegate('click', 'input', function () {
			//$(this).toggleClass('highlight');
			console.log("event");
			return false;
		});*/
		$('#external_properties ul').keyup(function (e) {
			displaySubmitLink();
			e.preventDefault();
		});


		updateProjectSelect(aProjectsEnvsList);

		$('#Project').change(function() {
			updateEnvSelect(aProjectsEnvsList, this.value);
			$('#DeploySectionConfigFileLink').empty();
			displaySubmitLink();
			displayAvailableRollbacks($('#Project option:selected').val(), $('#ProjectEnv option:selected').val());
			if (this.value != '') {
				$('#DeploySectionConfigFileLink').append(
						'<a class="attach" target="_blank" href="'
						+ getProjectConfigLink($('#Project option:selected').val())
						+ '">View XML config file of project "' + this.value + '"</a>');
			}
			displayResumeDeployments(this.value);
		});

		$('#ProjectEnv').change(function() {
			updateExternalProperties(aProjectsEnvsList, $('#Project option:selected').val(), this.value);
			//$('#DeploySection').empty();
			displaySubmitLink();
			displayAvailableRollbacks($('#Project option:selected').val(), this.value);
		});
	});
});

function displaySubmitLink () {
	bIsFilled = isFormFilled();
	if (bIsFilled) {
		var form = $("#new_deployment");
		var formArr = form.serializeArray();
		$.each(formArr, function(i, field) {
			formArr[i].value = $.trim(field.value);
		});
		formArr.push({name: 'action', value: 'deployment'});
		formArr.push({name: 'm', value: 'addDemand'});

		var sProject = $('#Project option:selected').val();
		var sEnv = $('#ProjectEnv option:selected').val();
		$('#DeploySection').show();
		/*$('#DeploySection').html(
				'<p class="ok">Project "<b>' + sProject + '</b>" and environment "<b>' + sEnv + '</b>" selected.'
				+ '<input type="button" value="I want to deploy" /></p>'

				<a href="#" class="bt blue right">I want to Deploy</a>
		);*/
		$('#DeploySection input').click(function(){
			//if (confirm("Are you sure to want to deploy '" + sProject + "' on '" + sEnv + "'?")) {
			var sEnv2 = prompt("Which environment did you say?");
			if (sEnv2 == sEnv) {
				var sURL = '?' + $.param(formArr);
				location.href = sURL;
			} else if (sEnv2 != null && sEnv2.length > 0) {
				alert("Mismatch between environments...");
				return false;
			}
		});

		$('#DeploySection').show();
	} else if ($('#external_properties ul li input').length > 0) {
		//$('#DeploySection').html('<p class="warning">Additional parameters must all be filled.</p>');
		//$('#DeploySection').show();
		$('#air_message').remove();
		$('#content').append(
			
					'<div id="air_message" class="notification orange air"> '+
					'<p>Additional parameters must all be filled.</p>'+
					'<a href="#" class="close">close</a>'+
					'</div>'
			);
	} else {
		$('#DeploySection').hide();
	}
}




function displayAvailableRollbacks (sProject, sEnv) {
	if (sProject == '' || sEnv == '') {
		$('#RollbackSection').hide();
	} else {
		$('#RollbackSection ul').empty().addClass('in_progress').html('&nbsp;');
		$('#RollbackSection').show();
		$.ajax({
			url: '/dashboard/getAvailableRollbacks/?p=' + sProject + '&e=' + sEnv,
			success: function(data) {
				$('#RollbackSection ul').removeClass('in_progress').html(data);
			}
		});
	}
}

function displayResumeDeployments (sProject) {
	if (sProject == '') {
		$('#ResumeSection').hide();
	} else {
		
		$('#ResumeSection div').empty().addClass('in_progress').html('&nbsp;');
		$('#ResumeSection').show();
		$.ajax({
			url: '/dashboard/getProjectEnvsInfo?p=' + sProject,
			success: function(data) {
				$('#ResumeSection div').removeClass('in_progress').html(data);
			}
		});
	}
}

function isFormFilled () {
	if ($('#Project option:selected').val() != '' && $('#ProjectEnv option:selected').val() != '') {
		bIsFilled = true;
		$('#external_properties ul li input').each(function(i, oInput){
			if ($(oInput).val().trim() == '') {
				bIsFilled = false;
			}
		});
	} else {
		bIsFilled = false;
	}
	return bIsFilled;
}

function updateProjectSelect(aProjectsEnvsList) {
	var sDisabledPrefix = 'DISABLED_';
	var iLenDisablePrefix = sDisabledPrefix.length;
	var oOption;

	for(sProjectName in aProjectsEnvsList) {
		if (
				sProjectName.length > iLenDisablePrefix
				&& sProjectName.substr(0, iLenDisablePrefix) == sDisabledPrefix
		) {
			sProjectName = sProjectName.substr(iLenDisablePrefix);
			oOption = new Option(sProjectName, sProjectName);
			$(oOption).attr('disabled', 'disabled');
		} else {
			oOption = new Option(sProjectName, sProjectName);
		}
		$('#Project').append(oOption);
	}
}

function updateEnvSelect(aProjectsEnvsList, sProjectName) {
	var sDisabledPrefix = 'DISABLED_';
	var iLenDisablePrefix = sDisabledPrefix.length;
	var oOption;

	$('#ProjectEnv').empty();
	$('#external_properties').hide();
	$('#external_properties ul').empty();
	if(aProjectsEnvsList[sProjectName] != undefined) {
		aProjectInfo = aProjectsEnvsList[sProjectName];
		$('#ProjectEnv').append(new Option('', ''));
		for(sEnvName in aProjectInfo) {
			//$('#ProjectEnv').append(new Option(sEnvName, sEnvName));

			if (
					sEnvName.length > iLenDisablePrefix
					&& sEnvName.substr(0, iLenDisablePrefix) == sDisabledPrefix
			) {
				sEnvName = sEnvName.substr(iLenDisablePrefix);
				oOption = new Option(sEnvName, sEnvName);
				$(oOption).attr('disabled', 'disabled');
			} else {
				oOption = new Option(sEnvName, sEnvName);
			}
			$('#ProjectEnv').append(oOption);
		}
	}
}

function updateExternalProperties (aProjectsEnvsList, sProjectName, sEnvName) {
	var sPrefix = 'addparam_';
	aProperties = aProjectsEnvsList[sProjectName][sEnvName];
	if (aProperties == undefined || aProperties.length == 0) {
		$('#external_properties').hide();
		$('#external_properties ul').empty();
	} else {
		$('#external_properties').hide();
		$('#external_properties ul').empty();
		for(sProperty in aProperties) {
			sLabel = aProperties[sProperty];
			$('#external_properties ul').append(
					'<li><label for="' + sPrefix + sProperty + '">' + sLabel + ': </label>'
					+ '<input type="text" id="' + sPrefix + sProperty + '" name="' + sPrefix + sProperty + '" /></li>');
		}
		$('#external_properties').show();
	}
}

function getProjectConfigLink(sProjectname) {
	sTpl = projectConfigLinkTpl;
	sTpl = sTpl.replace("#project", sProjectname);
	return sTpl;
}

function toggleRollbackSection (oA) {
	var oUL = $('#RollbackSection ul');
	var bExpand = ! oUL.is(':visible');
	if (bExpand) {
		oA.title = 'collpase this section';
		$(oA).removeClass('expand').removeClass('collapse').addClass('collapse');
		oUL.show();
	} else {
		oA.title = 'expand this section';
		$(oA).removeClass('collapse').removeClass('expand').addClass('expand');
		oUL.hide();
	}
}
