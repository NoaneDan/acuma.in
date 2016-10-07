


$('document').ready(function(){
    
    $('#Contact').submit(function (e) {
        // The form is needed only for the validator to work.
        // The request is made through ajax.
        
        e.preventDefault(); 
    });
    
    $('#Contact').validate({
        submitHandler: function () {
            
            var request = {
                name: getName(),
                email: getEmail(),
                subject: getSubject(),
                message: getMessage()
            };
        
            $.ajax({
                type: "POST",
                url: "/contact",
                data: JSON.stringify(request),
                dataType: "text",
                success: function () {
                    
                    $('#submit').notify(
                        'Message successfully sent!',
                        'success',
                        { position: "right" }
                    );
                    
                    resetForm();
                },
                error: function (err) {
                    
                    $('#submit').notify(
                        err.responseText,
                        'error',
                        { position: "top right" }
                    );
                }
            });
        }
    });
});



function getName() {
    
    return $('#name').val();
}


function getEmail() {
    
    return $('#email').val();
}


function getSubject() {
    
    return $('#subject').val();
}


function getMessage() {
    
    return $('#message').val();
}


function resetForm() {
    
    $('#name').val('');
    $('#email').val('');
    $('#subject').val('');
    $('#message').val('');
}