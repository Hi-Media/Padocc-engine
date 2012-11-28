
<!-- Widget -->
<div class="grid_12">
    <div class="widget minimizable">
        <header>
            <div class="icon">
                <span class="icon" data-icon="table"></span>
            </div>

            <div class="title">
                <h2>{$title}</h2>
            </div>
        </header>

        <div class="content">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Environment</th>
                        <th>Parameters</th>
                        <th>Instigator</th>
                        <th>Start date</th>
                        <th>End date</th>
                        <th>Elapsed time</th>
                    </tr>
                </thead>
                <tbody>
                	{if $aQueue|@count >0}
					{foreach from=$aQueue item=row}
                    <tr>
                        <td>
                        	<a style="padding-left: 20px;background:url(http://aai.prod.twenga.local/css/images/icon/attach.png) no-repeat" target="_blank" title="View current XML config file of project &quot; {$row.project} &quot;" href="?action=deployment&m=GetConfig&project= {$row.project}">{$row.project} </a>
                        </td>
						<td>{$row.env}</td>
						<td>{$row.parameters}</td>
						<td>{$row.instigator_email}<!-- <img width="53%" src='{$row.instigator_image}'/> --></td>
						<td>{$row.start_date}</td>
						<td class="_{$row.status}">{$row.end_date}</td>
						<td>{$row.elapsed_time}</td>
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