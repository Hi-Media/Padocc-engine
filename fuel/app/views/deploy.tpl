<script src="/assets/js/dee/ajax_event_loader.js"></script>
<script src="/assets/js/dee/deploy.js"></script>
<div id="wrapper" class="container_12">

    <!-- Widget -->
    <div class="prefix_1 grid_1">
        <div style="border:1px solid #ccc; width:100px; height:100px">Rollback</div>
        <div style="border:1px solid #ccc; width:100px; height:100px">Retry last deploiement</div>
    </div>
	<!-- Widget -->
	<div class="grid_8  suffix_2" id="tou">
		<div class="widget minimizable">
			<header>
                <div class="icon" style="background: url(/assets/img/interface/icon/icon_set.png) -5px -288px;">
                    <span class="icon"></span>
                </div>

                <div class="title">
                    <h2>What do you want to deploy today ?</h2>
                </div>
            </header>
			<div class="content">
				<form id="deploy_form"  method="post" class="validate">
                    
                    <fieldset class="set">
						
                        <div class="field" id="PROJECT_NAME">
                            <label>Your Project </label>
                            <div class="entry">
                                <select class="required chosen" name="PROJECT_ID" data-placeholder="Select an option">
                                	<option value=""></option>
                                    {foreach from=$aProjectGroupList key=k item=project}
                                    <optgroup label="{$k}">
                                    	{foreach from=$project key=k item=row}
	                                    <option value="{$row.PROJECT_ID}">{$row.NAME}</option>
	                                    {/foreach}
                                    </optgroup>
                                    {/foreach}
                                </select>
                            </div>
                        </div>


                         <div class="field even" style="xdisplay:none" id="PROJECT_CONFIGURATION">
                            <p>You haven't rights to deploy this project, ask them :</p>
                            <label>Your configuration </label>
                            <div class="entry">
                                <select class="required chosen" name="PROJECT_CONFIGURATION_ID" data-placeholder="Select an option">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>

                        <div class="field even" style="display:none" id="PROJECT_CONFIGURATION">
                            <label>Your configuration </label>
                            <div class="entry">
                                <select class="required chosen" name="PROJECT_CONFIGURATION_ID" data-placeholder="Select an option">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>

                        <div class="field" style="display:none" id="PROJECT_ENVIRONMENT">
                            <label>Your Environment </label>
                            <div class="entry">
                                <select class="required chosen" name="PROJECT_ENVIRONMENT">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>

                        <div id="EXTERNAL_PROPERTY" style="display:none">
                        	<div class="heading">
                                <h3>Property</h3>
                            </div>
						</div>

                        <div class="field even"  id="PROJECT_ENVIRONMENT">
                            <label>Release Note </label>
                             <div class="content">
                                    <textarea class="editor"></textarea>
                                </div>
                        </div>

                        

					</fieldset>
					<footer id="DeploySection" class="pane">
									<input style="display:none" type="submit" class="bt large black right" value="I want to Deploy">
					</footer>
				</form>
			</div>
		</div>
	</div>
	<!-- /Widget -->
</div>



   

<div id="ede_panel_sync" style=" z-index: 100; position:absolute; left:0; top:0; width:100%; height:100%; overflow:auto; opacity:0;display:none ">
    <!--<input id="log_debug_switch" type="checkbox" style="display:none"/>-->
    <div id="ede_message" style="color:green; padding:5px"></div>
    <div id="DeployLogs">

        <div class="log_content"/>
    </div> 
</div>

<style>
{literal}
#ede_panel_sync{

background: -moz-linear-gradient(top,  rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.9) 54%, rgba(0,0,0,1) 99%, rgba(0,0,0,1) 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(0,0,0,0.9)), color-stop(54%,rgba(0,0,0,0.9)), color-stop(99%,rgba(0,0,0,1)), color-stop(100%,rgba(0,0,0,1))); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(top,  rgba(0,0,0,0.9) 0%,rgba(0,0,0,0.9) 54%,rgba(0,0,0,1) 99%,rgba(0,0,0,1) 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(top,  rgba(0,0,0,0.9) 0%,rgba(0,0,0,0.9) 54%,rgba(0,0,0,1) 99%,rgba(0,0,0,1) 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(top,  rgba(0,0,0,0.9) 0%,rgba(0,0,0,0.9) 54%,rgba(0,0,0,1) 99%,rgba(0,0,0,1) 100%); /* IE10+ */
background: linear-gradient(to bottom,  rgba(0,0,0,0.9) 0%,rgba(0,0,0,0.9) 54%,rgba(0,0,0,1) 99%,rgba(0,0,0,1) 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#e6000000', endColorstr='#000000',GradientType=0 ); /* IE6-9 */

{/literal}
</style>


