$(document).ready(function() {


    $('.validate').validate($.extend($.oValidOption,{

        submitHandler: function(form) {
          

            $(form).find('input[type=submit]').hide();
            $(form).ajaxSubmit(function(data) { 

                $.Interface.goHome();
                var sName = $(form).find('input[name=FIRSTNAME]').fieldValue();
                
                $.Interface.showMessage("User "+sName+" has been added !");
            });
        }

    }));

      


   $('#EMAIL').rules("add", {
        remote : {
            url : '/User/email_exist',
            type : 'post',
        },
        messages: {
            remote: "This Email is already taken :("
        }                
    });




     
        







});