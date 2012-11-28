
<script src="/assets/plugins/codemirror/lib/codemirror.js"></script>
<link rel="stylesheet" href="/assets/plugins/codemirror/lib/codemirror.css">
<script src="/assets/plugins/codemirror/mode/xml/xml.js"></script>
<script src="/assets/plugins/codemirror/lib/util/closetag.js"></script>    

<script src="/assets/plugins/codemirror/mode/javascript/javascript.js"></script>
<script src="/assets/plugins/codemirror/mode/css/css.js"></script>
<script src="/assets/plugins/codemirror/mode/htmlmixed/htmlmixed.js"></script>

<script src="/assets/plugins/codemirror/lib/util/searchcursor.js"></script>
<script src="/assets/plugins/codemirror/lib/util/match-highlighter.js"></script>


<script src="/assets/js/dee/ajax_event_loader.js"></script>
<script type="text/javascript" src="/assets/js/dee/project.js"></script>  
               

<div id="wrapper" class="container_12">


    <!-- Widget -->
	<div class="grid_8 prefix_2 suffix_2"> 
		<div class="widget minimizable">
			<header>
                <div class="icon">
                    <span class="icon" data-icon="ui-text-field-medium"></span>
                </div>

                <div class="title">
                    <h2>{if $EDIT}Edit this project{else}Create a project{/if}</h2>
                </div>
            </header>
			<div class="content">
				<form id="formtest" method="post" action="{if $EDIT}/Project/update{else}/Project/add{/if}" class="validate">
                    <fieldset class="set">
						 <div class="field">
                            <label>Name: </label>
                            <div class="entry">                                
                                <input id="PROJECT_NAME" name="PROJECT_NAME" type="text" class="required" name="text" minlength="3" {if $EDIT}value = "{$aProject.NAME}" disabled="disabled"{/if} />
                                {if $EDIT}
                                <input type="hidden" id="PROJECT_ID" name="PROJECT_ID" value="{$aProject.PROJECT_ID}"/>
                                {/if}
                            </div>
                        </div>
                       

                        <div class="field">
                            <label>Group: </label>
                            <div id="helper_fix" class="entry with-helper">
                                <div class="helper">
                                    <span title="For future filtering, specify a group like B2C, DISTRIB, B2B !" data-position="nw" data-icon="help" class="icon tooltip" style="background-image: url(/assets/img/fugue-icons/icons/help.png);"></span>
                                </div>
                                <select class="required chosen add_chosen"  name="PROJECT_GROUP" data-placeholder="Select an option">
                                    <option value=""></option>
                                    {foreach from=$aProjectGroup item=row}
                                    <option {if $EDIT AND $row.GROUP == $aProject.GROUP}selected="selected"{/if} value="{$row.GROUP|capitalize}">{$row.GROUP|capitalize}</option>
                                    {/foreach}
                                </select>
                               
                            </div>
                        </div>
                      
                        <div class="field">
                            <label>Owner: </label>
                            <div id="helper_fix" class="entry with-helper">
                                <div class="helper">
                                    <span title="A owner has the ability to give rights to other users. That should be you." data-position="nw" data-icon="help" class="icon tooltip" style="background-image: url(/assets/img/fugue-icons/icons/help.png);"></span>
                                </div>

                                {if $EDIT}{assign var="ID" value=$aProject.OWNER_ID}{else}{assign var="ID" value=$USER_ID}{/if}

                                <select class="required chosen" name="USER_ID">
                                    <option value="">Select an option</option>
                                    {foreach from=$aUserList item=row}
                                    <option {if $row.USER_ID == $ID}selected="selected"{/if} value="{$row.USER_ID}">

                                        {if $row.USER_ID == $USER_ID}Me{else}
                                        {$row.FIRSTNAME|capitalize} {$row.LASTNAME|capitalize}
                                        {/if}
                                    </option>
                                    {/foreach}
                                </select>
                               
                            </div>
                        </div>

                        <div class="heading">
                            <h3>Configuration <span style="font-size:10px">(F11)</span></h3>
                        </div>



                        <div class="field">
                            <div class="check-list">
                                <textarea id="PROJECT_CONFIGURATION" name="PROJECT_CONFIGURATION" style="position:absolute; top:-10000px" class="required">{if $EDIT}{$aProject.CONFIGURATION}{/if}</textarea>

                                {if $EDIT}Rev.{$aProject.REVISION}{/if}
                                <div style="display:inline" id="codemirror"></div>
                            </div>
                        </div>
                        <div class="field even">
                            
                            <div class="entry with-helper" style="width:100%">
                                <div class="helper">
                                    <span data-icon="book--arrow" class="icon" style="background-image: url(/assets/img/fugue-icons/icons/book--arrow.png);"></span>
                                </div>
                                <input id="config_help" type="text" name="text-with-icon">

                            </div>
                        </div>
					</fieldset>
                    <footer class="pane" id="DeploySection">
                        <input type="submit" value="Save" class="bt black large right">
                    </footer>

				</form>
			</div>
		</div>
	</div>
	<!-- /Widget -->
</div>







{literal}




 <link rel="stylesheet" href="/assets/plugins/codemirror/theme/ambiance.css">
<style>

      #helper_fix .chzn-single{
        padding-left:40px;
      }
      span.CodeMirror-matchhighlight { background: #e9e9e9 }
      .CodeMirror-focused span.CodeMirror-matchhighlight { background: #e7e4ff; !important }

.CodeMirror-fullscreen {
        display: block;
        position: fixed;
        top: 0; left: 0;
        width: 100%;
        z-index: 9999;
      }

.xCodeMirror-scroll {
  height: auto;
  overflow-y: hidden;
  overflow-x: auto;
  min-height: 200px;
}
/*
#wrapper .widget .field > label, .page-status .field > label {
    width:10%;
}
#wrapper .widget .field .entry, .page-status .field .entry{
    width:90%;
}*/
</style>
{/literal}