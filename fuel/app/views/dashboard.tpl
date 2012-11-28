<script src="http://code.highcharts.com/highcharts.js"></script>

<script src="/assets/js/dee/ajax_event_loader.js"></script>
<script type="text/javascript" src="/assets/js/dee/dashboard.js"></script>  
               

<div id="wrapper" class="container_12">

	<!-- Widget -->
	<div class="grid_12">
	    <div class="widget minimizable">
	        <header>
	            <div class="icon" style="background: url(/assets/img/interface/icon/icon_set.png) -35px -288px;">
	                <span class="icon"></span>
	            </div>

	            <div class="title">
	                <h2>Dashboard</h2>
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
                                	<option value="NULL">See all</option>
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
                    </fieldset>
                </form>

                <div id="chart">
                	<div id="chart1" style="width:100%; height: 300px;"></div>
                </div>
	            
                <table id="datatable" class="datatable"  style="padding:15px">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Revision</th>
                            <th>Environment</th>
                            <th>Parameter(s)</th>
                            <th>Instigator</th>
                            <th>Start date</th>
	                        <th>End date</th>
	                        <th>Elapsed time</th>
                        </tr>
                    </thead>
                    <tbody>
                    	{if $aDeployQueue|@count >0}
						{foreach from=$aDeployQueue item=row}
                        <tr>
                            <td class="bold">{$row.NAME}</td>
                            <td>Rev.1.0</td>
                            <td>{$row.ENVIRONMENT}</td>
                            <td>{$row.EXTERNAL_PROPERTY}</td>
                            <td>{$row.FIRSTNAME} {$row.LASTNAME}</td>
                            <td>{$row.DATE_START}</td>
                            <td>{$row.DATE_END}</td>
                            <td>todo</td>
                        </tr>
                        {/foreach}
						{else}
							<tr><td colspan="7"><i>No processed deployment.</i></td></tr>
						{/if}   
                     
                    </tbody>
                </table>
	        </div>
	    </div>
	</div>
	<!-- /Widget -->

</div>

{literal}
<style>
.footer-table, .table thead th, .datatable thead th, .table tbody td, .datatable tbody td {
	
}

.chart_tooltip_date{
	display:block;text-align:center;border-bottom:1px solid #000;
	font-weight:bold;
	}
	.chart_table{
	font-size:12px;
	}

	.chart_table .total th, .chart_table .total td{
		border-top:1px solid #000;
		font-weight:800;
	}
	.chart_table td{
	margin:0;
	padding:0;
	text-align:right;
	}

	.chart_table th{
	margin:0;
	padding:0;
	text-align:left;
	}

</style>
{/literal}

