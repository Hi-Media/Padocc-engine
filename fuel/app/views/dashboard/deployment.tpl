{include file="header.tpl"}
<script type="text/javascript" src="/assets/js/dashboard/deployment.js"></script>

<!-- QuickActions section -->
<section id="quick-actions" class="quick-actions grid_12">
    
</section>

<!-- Content section -->
<section id="content">

    <header class="pagetitle grid_12">
        <h1>Deployment</h1>
        <nav class="breadcrumbs">
            <ul>
                <li><a href="#">Bread</a></li>
                <li><a href="#">Crumbs</a></li>
                <li><a href="#">Here</a></li>
            </ul>
        </nav>
    </header>

	<script>
		aProjectsEnvsList = {$aProjectsEnvsList};
	</script>

	 <!-- Widget -->
	<div class="grid_12">
		<div class="widget minimizable">
			<header>
                <div class="icon">
                    <span class="icon" data-icon="ui-text-field-medium"></span>
                </div>

                <div class="title">
                    <h2>What do you want to deploy today ?</h2>
                </div>
            </header>
			<div class="content">
				<form action="#" class="validate" id="new_deployment">
                    <fieldset class="set">
						 <div class="field">
                            <label>Select your Project: </label>
                            <div class="entry">
                            	<input type="hidden" readonly="readonly" size="30" name="instigator_email" value="{$sInstigatorEmail}" />
                                <select id="Project" class="required" name="Project">
                                    <option value="">View all</option>
                                </select>
                            </div>
                        </div>
                        <div class="field even">
                            <label>Select your Environment: </label>
                            <div class="entry">
                                <select id="ProjectEnv" class="required" name="ProjectEnv">
                                    <option value="">View all</option>
                                </select>
                            </div>
                        </div>

                       
                        <div id="external_properties" class="field" style="display:none">
							<div class="heading">
								<h3>Additional parameters:</h3>
							</div>
							<ul></ul>
						</div>

						

						
					</fieldset>
					<footer id="DeploySection" class="pane" style="height:32px; display:none">
									<input type="submit" class="bt blue right" value="I want to Deploy">
					</footer>
				</form>
			</div>
		</div>
	</div>
	<!-- /Widget -->


	<div id="ResumeSection">
					<span>&nbsp;</span>
					<div>&nbsp;</div>
	</div>


<div id="content" class="">
	<div id="resume">
		<div class="main-title">
			<h1 class="center">Summary</h1>
		</div>
	</div>

	<div class="panel" id="panel-2">
		<div class="box">
			
			<div class="content">
				
				<form id="xnew_deployment">
					<ul>
						<li>
							<label>instigator:</label>
							<input class="instigator" type="text" readonly="readonly" size="30" name="instigator_email" value="{$sInstigatorEmail}" />
						</li><li>
							<label for="Project">project name:</label>
							<select name="xproject" id="xProject">
								<option></option>
							</select>
							<span id="DeploySectionConfigFileLink"></span>
						</li><li>
							<label for="ProjectEnv">environment:</label>
							<select name="env" id="xProjectEnv">
								<option></option>
							</select>
						</li>
					</ul>
					
				</form>
				
				<div id="RollbackSection">
					<p class="available_rollbacks">
						Need a quick rollback?
						<a class="expand" href="javascript:;" onclick="toggleRollbackSection(this);" title="expand this section"></a>
					</p>
					<ul></ul>
				</div>
				
			</div>
			<div class="foot simple">
			</div>
		</div>
	</div>


</div>
<div id="tpl">
<script>
//projectConfigLinkTpl = _APP_ADDRESS+"/?action=deployment&m=GetConfig&project=#project";
projectConfigLinkTpl = "/deployment/GetConfig?&project=#project";
</script>
</div>



 </section>
                <!-- /Content section -->

            

{include file="footer.tpl"}