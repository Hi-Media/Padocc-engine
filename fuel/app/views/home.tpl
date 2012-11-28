<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>Ede - Extensible Deployment Engine !</title>
    
        <!-- CSS -->
        <link rel="stylesheet" href="/assets/css/reset.css" />
        <link rel="stylesheet" href="/assets/css/grid-fluid.css" />
        <link rel="stylesheet" href="/assets/css/websymbols.css" />
        <link rel="stylesheet" href="/assets/css/formalize.css" />
        <link rel="stylesheet" href="/assets/css/esplendido.css" />
        <link rel="stylesheet" href="/assets/css/light.css" />        
        <link rel="stylesheet" href="/assets/plugins/chosen/chosen.css" />
        <link rel="stylesheet" href="/assets/plugins/ui/ui-custom.css" />
        <link rel="stylesheet" href="/assets/plugins/tipsy/tipsy.css" />
        <link rel="stylesheet" href="/assets/plugins/validationEngine/validationEngine.jquery.css" />
        <link rel="stylesheet" href="/assets/plugins/elrte/css/elrte.min.css" />
        <link rel="stylesheet" href="/assets/plugins/miniColors/jquery.miniColors.css" />
        <link rel="stylesheet" href="/assets/plugins/fullCalendar/fullcalendar.css" />
        <link rel="stylesheet" href="/assets/plugins/elfinder/css/elfinder.css" />
        <link rel="stylesheet" href="/assets/plugins/shadowbox/shadowbox.css" />
        <link rel="stylesheet" href="/assets/css/global.css" />

<link rel="stylesheet" href="/assets/css/home.css" />
        <!-- JAVASCRIPTs -->
        <!--[if lt IE 9]>
            <script language="javascript" type="text/javascript" src="/assets/plugins/jqPlot/excanvas.min.js"></script>
            <script language="javascript" type="text/javascript" src="/assets/js/html5shiv.js"></script>
        <![endif]-->
        <script src="/assets/js/jquery.js"></script>
        
        <script src="/assets/js/browserDetect.js"></script>
        <script src="/assets/js/jquery.formalize.min.js"></script>
        <script src="/assets/plugins/jquery.form.js"></script>
       
        <script src="/assets/plugins/prefixfree.min.js"></script>
        <script src="/assets/plugins/jquery.uniform.min.js"></script>
        <script src="/assets/plugins/chosen/chosen.jquery.js"></script>
        <script src="/assets/plugins/ui/ui-custom.js"></script>
        <script src="/assets/plugins/ui/multiselect/js/ui.multiselect.js"></script>
        <script src="/assets/plugins/ui/ui.spinner.min.js"></script>
        <script src="/assets/plugins/datables/jquery.dataTables.min.js"></script>
        <script src="/assets/plugins/jquery.metadata.js"></script>
        <script src="/assets/plugins/sparkline.js"></script>
        <script src="/assets/plugins/progressbar.js"></script>
        <script src="/assets/plugins/feedback.js"></script>
        <script src="/assets/plugins/tipsy/jquery.tipsy.js"></script>
        <script src="/assets/plugins/jquery.maskedinput-1.3.min.js"></script>
        <script src="/assets/plugins/jquery.validate.min.js"></script>
        <script src="/assets/plugins/validationEngine/languages/jquery.validationEngine-en.js"></script>
        <script src="/assets/plugins/validationEngine/jquery.validationEngine.js"></script>
        <script src="/assets/plugins/jquery.elastic.js"></script>
        <script src="/assets/plugins/elrte/elrte.min.js"></script>
        <script src="/assets/plugins/miniColors/jquery.miniColors.min.js"></script>
        <script src="/assets/plugins/fullCalendar/fullcalendar.min.js"></script>
        <script src="/assets/plugins/elfinder/elfinder.min.js"></script>
        <script src="/assets/plugins/jquery.modal.js"></script>
        <script src="/assets/plugins/shadowbox/shadowbox.js"></script>
<script src="http://code.highcharts.com/highcharts.js"></script>


        <!-- chart
        <script src="/assets/plugins/jqPlot/jquery.jqplot.min.js"></script>
        <script src="/assets/plugins/jqPlot/plugins/jqplot.cursor.min.js"></script>
        <script src="/assets/plugins/jqPlot/plugins/jqplot.highlighter.min.js"></script>
        <script src="/assets/plugins/jqPlot/plugins/jqplot.barRenderer.min.js"></script>
        <script src="/assets/plugins/jqPlot/plugins/jqplot.pointLabels.min.js"></script>
        <!-- /chart -->

<script src="/assets/plugins/approach/jquery.approach.js"></script>
<script src="/assets/plugins/jquery.easing.1.3.js"></script>


                
        <link rel="shortcut icon" href="/assets/img/favicon.png">
    </head>
    <body>



      
        <div id="before_container"></div>

        <div id="out_container">
        <div id="container">
            <div class="menu_icon first">
                <div class="menu_icon_img mia">
                    <div class="menu_icon_top">
                        <div class="menu_title"><a href="/Deploy">Deploy your project</a></div>
                    </div>
                </div>
            </div>
            <div class="menu_icon">
                <div class="menu_icon_img mib">
                    <div class="menu_icon_top">
                        <div class="menu_title"><a href="/Dashboard">View the Dashboard</a></div>
                    </div>
                </div>
            </div>
            <div class="menu_icon">
                <div class="menu_icon_img mic">
                    <div class="menu_icon_top">
                        <div class="menu_title"><a href="/Project/list">List all Projects</a></div><div class="menu_title"><a href="#">Add a new Project</a></div>
                    </div>
                </div>
            </div>
            <div class="menu_icon">
                <div class="menu_icon_img mid">
                    <div class="menu_icon_top">
                        <div class="menu_title"><a href="/Users">List all users</a></div><div class="menu_title"><a href="#">Add a new user</a></div>
                    </div>
                </div>
            </div>
            <div class="menu_icon">
                <div class="menu_icon_img mie">
                    <div class="menu_icon_top">
                        <div class="menu_title"><a href="/Users">Change your preferences</a></div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
        </div>
        </div>
        <div id="mirror_container">
            <div class="menu_icon first"><div class="menu_icon_img mia"><div class="menu_icon_top"></div></div></div>
            <div class="menu_icon"><div class="menu_icon_img mib"><div class="menu_icon_top"></div></div></div>
            <div class="menu_icon"><div class="menu_icon_img mic"><div class="menu_icon_top"></div></div></div>
            <div class="menu_icon"><div class="menu_icon_img mid"><div class="menu_icon_top"></div></div></div>
            <div class="menu_icon"><div class="menu_icon_img mie"><div class="menu_icon_top"></div></div></div>
            <div class="clear"></div>
        </div>



        <div id="logo"><img src="/assets/img/interface/logo_home.png"/></div>
            

        <div class="none" id="border_bottom"></div>
        <div class="none" id="border_top"></div>


        <div id="opening"></div>
        <div id="opening_right">
            <div class="or1"></div>
            <div class="or2">
                <div class="or3">
                    <form>
                        <input type="text" value="Username"/>
                        <input type="text" value="Password"/>
                    </form>
                </div>
            </div>
            <div class="or4"></div>
        </div>

        <div class="" id="opening_left">
            <div class="or1">
                <img src="/assets/img/interface/title.png"/>
            </div>
            <div class="or2"></div>
        </div>

        
        
        <div  id="ajax_content"></div>




{literal}
<style>


</style>

<script>

 $(".menu_icon").approach({
      "marginTop": "-10px"
    , 
    "opacity": "0.5"

  }, 200);



$.Interface = {

    oMenu : $('#container'),
    oAjaxContainer : $('#ajax_content'),

    init : function()
    {
        $('.menu_icon').hover( function() {

            $.Interface.menuShowSubCat($(this));
        }, function() {
            
            //$('#ico_user .float_menu_title').remove();
            //$('#ico_user div').stop(true, false).animate({   paddingLeft: 2000,  }).hide();
        });

        // CLICK ON ICON
        $('#container .menu_icon').click( function() {

            var sColor = $(this).find('.menu_icon_img').getHexBackgroundColor();

            var sNextHeaderBoxColor = ColorLuminance(sColor, -0.1);
           
           $('body').append('<style>.widget  header{ background-image: none;background: '+sNextHeaderBoxColor+'; }</style>');

           //document.styleSheets[0].addRule(".widget header", 'background-image: none;background: '+sNextHeaderBoxColor+'; ');

            $.Interface.load($(this).find('a:first').attr('href'));
        });
    
        $.Interface.oAjaxContainer.click(function(e) {
            if( $(e.target).attr('id') == $(this).attr('id') || $(e.target).parent().attr('id') == $(this).attr('id') || $(e.target).parent().parent().attr('id') == $(this).attr('id') )
            {

               $.Interface.goHome();
            }

        });

        $('#opening_left,#opening_right').click(function(){
            $.Interface.opening();
        })

        $('#opening').animate({opacity : "0.0"}, 1000, 'easeInExpo', function(){
            $(this).remove();
        })


    },

    opening : function()
    {
        $('#opening_left img').animate({left : '-56%'}, 300, 'easeInExpo', function(){

            $('#opening_left').animate({left : '-1%'}, 300, 'easeOutExpo')
            $('#opening_right').animate({right : '-1%'}, 300, 'easeOutExpo', function(){

                $('#opening_left').animate({left : '-160%'}, 800, "easeInExpo")
                $('#opening_right').animate({right : '-160%'}, 800, "easeInExpo")
            })
        })
       

        

       
    },

    showMessage : function(sMessage)
    {
         $('#interface_message').remove();
        var oDiv = $('#before_container').append('<div id="interface_message" style="opacity:0; top:-100px;left:50%;position: absolute;"><div style="position:relative; left:-50%;"> '+sMessage+'</div></div>');
        

        $('#interface_message').animate({top : '20px', opacity:1}, 500, 'easeOutBack').delay(1000).queue( function(){
         
            $('#interface_message').clearQueue();
            $('#interface_message').animate({ opacity:0}, 3500, function(){
                $('#interface_message').remove();
                
            });

        });
    },

    goHome : function()
    {
        $.Interface.resetMenu($.Interface.oMenu);

        $.Interface.oAjaxContainer.animate({ left:"-100%"}, 200);

        return;
        $.Interface.oAjaxContainer.animate({height: '70%'}, 500, 'easeOutElastic', function () {
            $(this).animate({height : '0%'}, 500, 'easeOutBack');
        });
    },

    load: function(sPath) 
    {
      $.Interface.oAjaxContainer.css({right:'100%', left:'auto', width:'100%'})
       $('#logo img').attr({src:"/assets/img/interface/logo_anim.gif"})

       $.Interface.oAjaxContainer.load( sPath, function(){

            $('#logo img').attr({src:"/assets/img/interface/logo_home.png"})

            
            $.Interface.menuHideSubCat();            
            $.Interface.smalyseMenu($.Interface.oMenu);

            //$.Interface.oAjaxContainer.css({right:'100%', left:'auto', width:'100%'})

            $.Interface.oAjaxContainer.animate({right: '0', }, 200);
            return;
            $.Interface.oAjaxContainer.animate({height: '90%', }, 500, 'easeOutBack', function () {
            $(this).animate({height : '90%'}, 500, 'easeOutElastic');
            });

        });

    },

    smalyseMenu: function() {
        return;
        var iLeft = $.Interface.oMenu.offset().left;
        var iTop = $.Interface.oMenu.offset().top ;
        var iPaddingTop = $.Interface.oMenu.css("paddingTop");

        $.Interface.oMenu.data('old_position', {left:iLeft, top:iTop, paddingTop:iPaddingTop});
        $.Interface.oMenu.css({left:iLeft, top:iTop, position:'absolute'})
        $.Interface.oMenu.animate( {   paddingTop:'10', left:0}, 500 );
        $.Interface.oMenu.find('.menu_icon').animate( {   width:"50px", height:"50px" }, 500 );
    },

    resetMenu : function() {
         return;
        var aData = $.Interface.oMenu.data('old_position');

        $.Interface.oMenu.find('.menu_icon').animate( {  width:"100px", height:"100px" }, 500 );
        $.Interface.oMenu.animate( {left:aData.left, top:aData.top, paddingTop:aData.paddingTop}, 500, function(){
            $.Interface.oMenu.css({position:'static'})
        });    
    },

    menuShowSubCat : function(oMenuIcon) {

        $.Interface.menuHideSubCat();

        var oDiv =  $('<div/>');
        oDiv.attr({class:"float_menu_title"});
        oDiv.append(oMenuIcon.find('.menu_title').clone());

        var sColor = oMenuIcon.find('.menu_title').parent().parent().getHexBackgroundColor();

        var iLeft = oMenuIcon.offset().left + 5;
        var iTop = oMenuIcon.offset().top - 54 ;
        oDiv.css({ top:iTop});

        oDiv.find('a').css({color:sColor})

        oDiv.find('a').click(function(){
            $.Interface.load($(this).attr('href'));
            return false;
        });

        $('body').append(oDiv);
        $('.float_menu_title div').show();

        oDiv.show().animate( {   left: iLeft }, 200 );
    },

    menuHideSubCat : function ()
    {
        $('.float_menu_title').fadeToggle(function(){$(this).remove()});
    },

    getDeploymentInProgress : function()
    {
        // Add icon to the top
    }
};

$.Interface.init();


function ColorLuminance(hex, lum) {
    // validate hex string
    hex = String(hex).replace(/[^0-9a-f]/gi, '');
    if (hex.length < 6) {
        hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    }
    lum = lum || 0;
    // convert to decimal and change luminosity
    var rgb = "#", c, i;
    for (i = 0; i < 3; i++) {
        c = parseInt(hex.substr(i*2,2), 16);
        c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
        rgb += ("00"+c).substr(c.length);
    }
    return rgb;
}

$.fn.getHexBackgroundColor = function() {
    var rgb = $(this).css('color');
    if (!rgb) {
        return '#FFFFFF'; //default color
    }
    var hex_rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/); 
    function hex(x) {return ("0" + parseInt(x).toString(16)).slice(-2);}
    if (hex_rgb) {
        return "#" + hex(hex_rgb[1]) + hex(hex_rgb[2]) + hex(hex_rgb[3]);
    } else {
        return rgb; //ie8 returns background-color in hex format then it will make                 compatible, you can improve it checking if format is in hexadecimal
    }
}



</script>
{/literal}

    </body>

</html>