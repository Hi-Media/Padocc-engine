
$(document).ready(function() {

	$('#PROJECT_NAME select').change(function() {

		$('#PROJECT_CONFIGURATION').fadeOut();
		$('#PROJECT_ENVIRONMENT').fadeOut();
		$('#EXTERNAL_PROPERTY').fadeOut();
		$('#DeploySection input').fadeOut();
		$.post("/deploy/get_project_configurations", { PROJECT_ID: $('#PROJECT_NAME option:selected').val()}, 
			function(data){

				$('#PROJECT_CONFIGURATION select option').remove();
				$('#PROJECT_ENVIRONMENT select option').remove();
				
				$(data).each(function(i,o){
					
					var sActive = "";
					var sSelected = "";
					if(o.STATUS == "ACTIVE")
					{
						sActive = ' ( current )';
						sSelected = ' selected="selected"';

						$('#PROJECT_ENVIRONMENT select').append(
								$( "<option selected='selected' value=''></option>" )
						);

						$($.parseJSON(o.ENVIRONMENT)).each(function(i2, env){
							$('#PROJECT_ENVIRONMENT select').append(
								$( "<option value='"+ env.NAME +"'>" + env.NAME + "</option>" )
							);
						})
						
						$('#PROJECT_ENVIRONMENT select').trigger("liszt:updated");

					}

					$('#PROJECT_CONFIGURATION select').append(
						$( "<option "+sSelected+" value='"+ o.PROJECT_CONFIGURATION_ID +"'> Rev." + o.REVISION + sActive + "</option>" )
					);


					$('#PROJECT_CONFIGURATION select').trigger("liszt:updated");

					$('#PROJECT_CONFIGURATION').fadeIn();
					$('#PROJECT_ENVIRONMENT').fadeIn();

				})
				
			
		}, 'json' );
	});

	$('#PROJECT_CONFIGURATION select').change(function() {
		$('#PROJECT_ENVIRONMENT').fadeOut();
		$('#EXTERNAL_PROPERTY').fadeOut();
		$('#DeploySection input').fadeOut();
		$('#PROJECT_ENVIRONMENT select option').remove();
		$('#EXTERNAL_PROPERTY div:not(.heading)').remove();

		$.post("/deploy/get_configuration", { PROJECT_CONFIGURATION_ID: $('#PROJECT_CONFIGURATION option:selected').val()}, 
			function(data){
				data= data[0]

				$($.parseJSON(data.ENVIRONMENT)).each(function(i, env){

						$('#PROJECT_ENVIRONMENT select').append(
								$( "<option selected='selected' value=''></option>" )
						);
						
						$('#PROJECT_ENVIRONMENT select').append(

							$( "<option value='"+ env.NAME +"'>" + env.NAME + "</option>" )
						);
				})

				$('#PROJECT_ENVIRONMENT select').trigger("liszt:updated");
				$('#PROJECT_ENVIRONMENT').fadeIn();

		}, 'json' );
	});

	$('#PROJECT_ENVIRONMENT select').change(function() {

		$('#EXTERNAL_PROPERTY').fadeOut();
		$('#EXTERNAL_PROPERTY div:not(.heading)').remove();
		

		$.post("/deploy/get_configuration", { PROJECT_CONFIGURATION_ID: $('#PROJECT_CONFIGURATION option:selected').val()}, 
			function(data){
				data= data[0];

				if(data.EXTERNAL_PROPERTY.length)
				{
					$($.parseJSON(data.EXTERNAL_PROPERTY)[$('#PROJECT_ENVIRONMENT option:selected').val()]).each(function(i, externalProperty){

						$('#EXTERNAL_PROPERTY').append('<div class="field even" id=""><label>'+externalProperty.DESCRIPTION+'</label><div class="entry"><input type="text" class="required" name=EXTERNAL_PROPERTY['+externalProperty.NAME+']"  /></div></div>');
					})
				}

				$('#EXTERNAL_PROPERTY').fadeIn();
				$('#DeploySection input').fadeIn();
				


		}, 'json' );
	});


	/**/

	$('#deploy_form').on('submit', function() {
		$('.validate').validate();
		
		if($("#deploy_form").valid())
		{
			$('#ede_panel_sync').show();
				
			$.post("/deploy/add", $(this).serializeArray(), function(data){
				
				if(parseInt(data) > 0)
				{
					$('#ede_panel_sync').animate( {   opacity: "1" }, 2000,'easeInBounce', function(){

						$('#ede_message').html('Wake up neo...');


					}).delay(2000).queue(function(){
							//$('#ede_message').slideUp();
							//alert(data)
							$(this).dequeue();
							updateLogs(parseInt(data), 0);
					});
				}
				
			}, 'json' )
		}


		


		return false;
		
	});



});








function updateLogs (iDeployQueueId, iPreviousUpdateLength) {

$('#ede_message').append(".");

	$.post("/deploy/get_logs", {DEPLOY_QUEUE_ID:iDeployQueueId}, function(data){
	

			if (data != 'wait' && data !== '' && data.length !== iPreviousUpdateLength) {
				/*$('#DeployLogs h2').html(
						'Current execution result of "<b>' + sProject + '</b>" project '
						+ 'on "<b>' + sEnv + '</b>" environment:');*/
				$('#DeployLogs .log_content').html(data);
				/*$('#log_debug_switch').attr('checked', false);*/
				$('#DeployLogs').show();
				






				$("#ede_panel_sync table").attr({id:"t1"});
				a = $("#ede_panel_sync table").clone();
				a.attr({id:"t2"});

				$("#DeployLogs .log_content").prepend('<div id="uu" style="top:0; position:fixed; width:100%; height:45px; background:#000; overflow:hidden"></div>');

				$("#uu").append(a);
				$("#t1 thead").css({visibility:"hidden"});
				$('#ede_panel_sync').animate({scrollTop: $('#ede_panel_sync').prop('scrollHeight')},  2000);
//$('#ede_panel_sync').scrollTop ($('#ede_panel_sync').prop('scrollHeight'))



			}
			if (
				data == ''
				|| ($('#DeployLogs .log_content td[class~="indent0"][class~="text"]:contains("OK")').length == 0
					&& $('#DeployLogs .log_content td[class~="indent0"][class~="text"]:contains("ERROR")').length == 0)
			) 
			{



				setTimeout(function() {updateLogs( iDeployQueueId, data.length);}, 1000);

			}

	},'json' )
	

}
//












$(function(){

	return;
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
