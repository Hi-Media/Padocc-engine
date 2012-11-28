
    var chart;
    $(document).ready(function() {
        chart = new Highcharts.Chart({
    
            chart: {
                renderTo: 'chart1',
                type: 'column',

            },
    
            title: {
                text: 'Number of deployment by month'
            },
    
    
            yAxis: {
                allowDecimals: false,
                min: 0,
                title: {
                    text: 'Number of deployment'
                }
            },
    
            tooltip: {
                formatter: function() {
                    return '<b>'+ this.x +'</b><br/>'+
                        this.series.name +': '+ this.y +'<br/>'+
                        'Total: '+ this.point.stackTotal;
                }
            },

            tooltip: {
            useHTML: true,
            crosshairs: true,
            shared: true,
            formatter: function() {
                var s = '<div class="chart_tooltip_date">'+ this.x +'</div><table class="chart_table">';

                for(var i=0; i<this.points.length; i++)
                {
                    s += '<tr><th style="color:'+this.points[i].series.color+'">'+ this.points[i].series.name +': </th>' +
                         '<td>'+ new Number(this.points[i].y).toFixed(0) +'&nbsp;('+ Math.round(this.points[i].percentage) +'%)</td></tr>';
                }
                s += '<tr class="total"><th>Total: </th><td>'+new Number(this.points[0].total).toFixed(0)+'&nbsp;deployement(s)</td></tr>';

                return s+'</table>';
            },
        },
    
            plotOptions: {
                column: {
                    stacking: 'normal'
                }
            },
    
            
        });

        $('#PROJECT_NAME select').change(function() {

            chart.showLoading("loading");

            $.post("/Dashboard/get_graph", { PROJECT_ID: $('#PROJECT_NAME option:selected').val()}, 
                function(data){

                    $(chart.series).each(function(i,o){o.remove(false)})

                    chart.xAxis[0].setCategories(data.categories, false);

                    $(data.series).each(function(i,o){
                        chart.addSeries(o, false);
                    })
                    chart.redraw();

                    chart.hideLoading();
                
                }
            , 'json' );

            $.post("/Dashboard/get_queue", { PROJECT_ID: $('#PROJECT_NAME option:selected').val()}, 
                function(data){

                    table = $('#datatable').dataTable();
                    oSettings = table.fnSettings();

                    table.fnClearTable(this);

                    for (var i=0; i<data.aaData.length; i++)
                    {
                      table.oApi._fnAddData(oSettings, data.aaData[i]);
                    }

                    oSettings.aiDisplay = oSettings.aiDisplayMaster.slice();
                    table.fnDraw();
                }

            , 'json' );

        });

        $('#PROJECT_NAME select').val("NULL");
        $('#PROJECT_NAME select').trigger('change');   

      


});

