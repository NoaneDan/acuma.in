$(document).ready(function(){

	$('.sub-menu').hide();

	// show sub-menu
	$("span.roller").toggle(function(){
	
		$(this).parent().children("ul.sub-menu").first().slideDown();
		$(this).parent().addClass('deschis');
	}, function() {
		$(this).parent().children("ul.sub-menu").first().slideUp();
		$(this).parent().removeClass('deschis');
	});

	$loader = $ ('<div></div>')
		.attr('id', 'loader')
		.append(
			$('<img/>')
				.attr('class', 'ajax-loader')
				.attr('src', '/assets/images/ajax-loader.gif')
		);


	$('li.menu a, li.sub-menu a').on('click', function(e){
		if ($(this).attr('id') === 'logout') {
			return;
		}
		
		e.preventDefault();
		var where_to = $(this).attr('href');

		getContent(where_to, true);
	});
	
	if(window.location.hash.length > 0) {
		getContent(window.location.hash.replace('#','/backend'), false);
	}
	else {
		window.location.hash = '#/welcome';
		getContent('/backend/welcome', false);
	}
});


// Adding popstate event listener to handle browser back button  
window.addEventListener("popstate", function() {
	
	if(window.location.hash.length > 0) {
		getContent(window.location.hash.replace('#','/backend'), false);
	}
});


function getContent(url, addEntry) {
	
	$('#page-content').html($loader);
    $.get(url).done(function( data ) {
			 
		// Updating Content on Page
		$('#page-content').html(data);

		admin_hover();
		init_action_buttons();
		init_locations_button();
		init_users_button();
		init_nav_button();
		 
		if(addEntry === true) {
			// Add History Entry using pushState
			history.pushState(null, null, url.replace('/backend','/backend#')); 
		}
    });
}

function admin_hover() {
	var trigger = $('.hover-holder');

	trigger.each(function() {
		var target = $(this).find('.box');
		
		$(this).hover(function() {
			var iframe = target.find('iframe'),
				iframe_src = iframe.attr('data-src');
			iframe.attr('src',iframe_src).show();
		}, function() {
			var iframe = target.find('iframe'),
				iframe_src = iframe.attr('data-src');
			iframe.hide().attr('src','');
		});
	});
}


function init_action_buttons() {
	
	$('.action-button').click(function () {
		
		var row = $(this).parent().parent();
		var button = $(this);
		
		$.ajax({
			type: "POST",
			url: "/backend" + $(this).attr('name'),
			data: $(this).attr('value'),
			dataType: "text",
			success: function () {
				
				if (button.attr('name') == '/facebookLocations/block') {
					button.attr('name', '/facebookLocations/unblock');
					button.text('unblock');
					
					return;
				}
				else if (button.attr('name') == '/facebookLocations/unblock') {
					button.attr('name', '/facebookLocations/block');
					button.text('block');
					
					return;
				}
				
				row.fadeOut({
					duration: 500,
					complete: function () {
						$(this).remove();
					}
				});
			}
		});
	});
}

// Delet user from database
function show_confirm(){
	return confirm("Are you sure you want to delete this user?");
}


function init_locations_button() {
	
	$('#location-submit').click(function () {
		
		var request = {
			city: $('#city').val(),
			latitude: $('#latitude').val(),
			longitude: $('#longitude').val(),
			radius: $('#radius').val()
		};
		
		$.ajax({
			type: "POST",
			url: "/backend/locations",
			data: JSON.stringify(request),
			dataType: "text",
			success: function () {
				
				$('#location-submit').notify(
					'Location successfully added!',
					'success',
					{ position: "right" }
				);
				
				$('#city,#latitude,#longitude,#radius').val('');
			},
			error: function () {
				
				$('#location-submit').notify(
					'Failed to add location!',
					'error',
					{ position: "right" }
				);
			}
		});
	});
}


function init_users_button() {
	
	$('#user-submit').click(function () {
		
		var request = {
			username: $('#username').val(),
			password: $('#password').val()
		};
		
		$.ajax({
			type: "POST",
			url: "/backend/users",
			data: JSON.stringify(request),
			dataType: "text",
			success: function () {
				
				$('#user-submit').notify(
					'User successfully added!',
					'success',
					{ position: "right" }
				);
				
				$('#username,#password').val('');
			},
			error: function () {
				
				$('#user-submit').notify(
					'Failed to add user!',
					'error',
					{ position: "right" }
				);
			}
		});
	});
}


function init_nav_button() {
	
	$('.nav-button').click(function () {
		
		var button = $(this);
		
		$.ajax({
			type: "GET",
			url: "/backend/moderation" + button.attr('name'),
			data: "page=" + $(button).attr('value'),
			dataType: "text",
			success: function (data) {
				
				$('#page-content').html(data);
				
				admin_hover();
				init_nav_button();
			},
			error: function (err) {
				return console.log(err);
			}
		});
	});
}