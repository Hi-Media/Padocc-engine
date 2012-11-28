
<script src="/assets/js/dee/ajax_event_loader.js"></script>
<!--<script type="text/javascript" src="/assets/js/dee/dashboard.js"></script>  -->
               

<div id="wrapper" class="container_12 project_list">

	<!-- Widget -->
    <div class="prefix_1 grid_1">
        <div onclick=" $.Interface.load('/Project');" style="border:1px solid #ccc; width:100px; height:100px">ADD NEW</div>
    </div>

	<!-- Widget -->
	<div class="grid_8 suffix_2"> 
	    <div class="widget minimizable">
	        <header>
	            <div class="icon" style="background: url(/assets/img/interface/icon/icon_set.png) -64px -288px;">
	                <span class="icon"></span>
	            </div>

	            <div class="title">
	                <h2>Project listing</h2>
	            </div>
	        </header>

	        <div class="content">

                <table  class="datatable"  style="padding:15px">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Current Revision</th>
                            <th>Creator</th>
                            <th>Owner</th>
                            <th>Group</th>
                            <th>Creation date</th>
                        </tr>
                    </thead>
                    <tbody>
                    	{if $aProject|@count >0}
						{foreach from=$aProject item=row}
                        <tr id="{$row.PROJECT_ID}">
                            <td class="bold">{$row.NAME}</td>
                            <td>Rev.{$row.REVISION}</td>
                            <td>{$row.CREATOR_FIRSTNAME} {$row.CREATOR_LASTNAME}</td>
                            <td>{$row.OWNER_FIRSTNAME} {$row.OWNER_LASTNAME}</td>
                            <td>{$row.GROUP}</td>
                            <td>{$row.DATE_INSERT}</td>
                        </tr>
                        {/foreach}
						{else}
							<tr><td colspan="7"><i>No projects.</i></td></tr>
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
.project_list .datatable tr{
	cursor: pointer;
}
.project_list .datatable tr:hover {
	background-color: #ddd;
}
.footer-table, .table thead th, .datatable thead th {
    background: #414141;
}

</style>
<script>

$(".project_list .datatable  td").click(function(){
    //alert($(this).attr("ID"));

    $.Interface.load('/Project/edit?PROJECT_ID='+$(this).parent().attr('ID'));
})

</script>


{/literal}

