
        {include file="header.tpl"}
            
        <script type="text/javascript" src="/assets/js/dashboard/dashboard.js"></script>  
                <!-- QuickActions section -->
                <section id="quick-actions" class="quick-actions grid_12">
                    
                </section>
                
                <!-- Content section -->
                <section id="content">

                    <header class="pagetitle grid_12">
                        <h1>Dashboard</h1>
                        <nav class="breadcrumbs">
                            <ul>
                                <li><a href="#">Bread</a></li>
                                <li><a href="#">Crumbs</a></li>
                                <li><a href="#">Here</a></li>
                            </ul>
                        </nav>
                    </header>
                    
                    <!-- Widget -->
                    <div class="">
                        <div id="myModal" class="widget grid_6" hidden>
                            <header>
                                <div class="icon">
                                    <span class="icon" data-icon="applications-stack"></span>
                                </div>
                                
                                <div class="title">
                                    <h2>Modal</h2>
                                </div>
                            </header>
                            <div class="content">
                                <div class="inner">
                                    <p>Lorem ipsum tempus consectetur porttitor egestas sed eleifend eget tincidunt pharetra, varius tincidunt morbi malesuada elementum mi torquent mollis eu lobortis curae, purus amet vivamus amet nulla torquent nibh eu diam. aliquam pretium donec aliquam tempus lacus tempus feugiat lectus cras non velit mollis, sit et integer egestas habitant auctor integer sem at nam. massa himenaeos netus vel, dapibus nibh. </p>
                                </div>
                                <footer class="pane">
                                    <a href="#" class="close bt red">Close</a>
                                </footer>
                            </div>
                        </div>
                    </div>
                    <!-- /Widget -->

                   
                    <!-- Widget -->
					<div class="grid_12">
						<div class="widget minimizable">
							<header>
                                <div class="icon">
                                    <span class="icon" data-icon="ui-text-field-medium"></span>
                                </div>

                                <div class="title">
                                    <h2>Select a project</h2>
                                </div>
                            </header>
							<div class="content">
								<form action="#" class="validate">
                                    <fieldset class="set">
										 <div class="field last">
                                            <label>Project: </label>
                                            <div class="entry">
                                                <select id="projectChoice" class="required" name="select-default">
                                                    <option value="">View all</option>
                                                    {$sProjectChoiceOptions}
                                                </select>
                                            </div>
                                        </div>
									</fieldset>
								</form>
							</div>
						</div>
					</div>
					<!-- /Widget -->

                    {if $aContentSummary}
                        {include file="dashboard/summary_deployment.tpl" aQueue=$aContentSummary title="Latest deployments by environment of <b>$sSelectedProject</b> project"}
                    {/if}


                    {if $sSelectedProject}
                        {assign var="title" value="of <b>$sSelectedProject</b> project"}
                    {else}
                        {assign var="title" value=", all projects combined"}
                    {/if}

                    {include file="dashboard/summary_deployment.tpl" title="The last $nb_processed_demands_to_display deployments $title"}

                    <!-- Widget -->
                    <div class="grid_12">

                        <div class="widget minimizable js-init">
                            <header>
                                <div class="icon">
                                    <span style="background-image: url(images/fugue-icons/icons/clipboard-text.png);" class="icon" data-icon="clipboard-text"></span>
                                </div>

                                <div class="title">
                                    <h2>Live Log</h2>
                                </div>

                            <div class="minimize"><span class="glyph zoom-out"></span></div></header>

                            <div class="content inner text">
                           
                                <div class="panel" id="panel-2">
                                    <div class="box">
                                        
                                        <div class="content">
                                        
                                            
                                            <div id="DeployLogs">
                                                <h2></h2>
                                                <div class="log_buttons"><label class="debug" for="log_debug_switch">Show debug traces</label><input id="log_debug_switch" type="checkbox" onclick="dashboard_switch_debug(this);" /></div>
                                                <div class="log_content">{$realTimeLogs}</div>
                                            </div>
                                        </div>
                                        <div class="foot simple">
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                    <!-- /Widget -->
  



<div id="tpl">
<script>
projectConfigLinkTpl = _APP_ADDRESS+"/?action=deployment&m=GetConfig&project=#project";

</script>
</div>
			



                    
                  

                </section>
                <!-- /Content section -->

            

{include file="footer.tpl"}