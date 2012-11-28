
$(document).ready(function() {


$('.validate').validate($.extend($.oValidOption,{

        submitHandler: function(form) {
          
           //$('#PROJECT_CONFIGURATION').val(myCodeMirror.getValue());
            $(form).find('input[type=submit]').hide();
            $(form).ajaxSubmit(function(data) { 
                //alert(data+"Thank you for your comment!"); 
                $.Interface.goHome();

                if($('#PROJECT_ID').length)
                	$.Interface.showMessage("Project "+$('#PROJECT_NAME').val()+" has been updated !");
               	else
               		$.Interface.showMessage("Project "+$('#PROJECT_NAME').val()+" has been added !");
            });
        }

  }));
	

	 $('#PROJECT_NAME').rules("add", {
        remote : {
            url : '/Projects/project_exist',
            type : 'post',
        },
        messages: {
            remote: "Project Already taken :("
        }                
    });

    $('#PROJECT_CONFIGURATION').rules("add", {
           
        remote : {
            url : '/Projects/configuration_valid',
            type : 'post',
            dataType: 'json',
        }
                      
    });


	 myCodeMirror = CodeMirror( document.getElementById("codemirror"), {
	       mode: 'xml',
	        lineNumbers: true,
	        theme: "ambiance",
	        extraKeys: {
	                "'>'": function(cm) { cm.closeTag(cm, '>'); },
	                "'/'": function(cm) { cm.closeTag(cm, '/'); },
	                "F11": function(cm) { setFullScreen(cm, !isFullScreen(cm));},
	                "Esc": function(cm) { if (isFullScreen(cm)) setFullScreen(cm, false);}
	        },
	        wordWrap: true,
	        lineWrapping : true,
	        
	        onCursorActivity: function() {
	        	$('#PROJECT_CONFIGURATION').val(myCodeMirror.getValue());
	        myCodeMirror.matchHighlight("CodeMirror-matchhighlight");
	      }
	});


	// CODEMIRROR FULLSCREEN
    function isFullScreen(cm) {
      return /\bCodeMirror-fullscreen\b/.test(cm.getWrapperElement().className);
    }
    function winHeight() {
      return window.innerHeight || (document.documentElement || document.body).clientHeight;
    }
    function setFullScreen(cm, full) {
      var wrap = cm.getWrapperElement(), scroll = cm.getScrollerElement();
      if (full) {
        wrap.className += " CodeMirror-fullscreen";
        scroll.style.height = winHeight() + "px";
        document.documentElement.style.overflow = "hidden";
      } else {
        wrap.className = wrap.className.replace(" CodeMirror-fullscreen", "");
        scroll.style.height = "";
        document.documentElement.style.overflow = "";
      }
      cm.refresh();
    }
    CodeMirror.connect(window, "resize", function() {
      var showing = document.body.getElementsByClassName("CodeMirror-fullscreen")[0];
      if (!showing) return;
      showing.CodeMirror.getScrollerElement().style.height = winHeight() + "px";
    });
    // END




    
	
	myCodeMirror.setValue($('#PROJECT_CONFIGURATION').val());




});