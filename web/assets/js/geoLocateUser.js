
(function geoLocate() {
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(redirectUserToLocation);
    }
})();

function redirectUserToLocation(position) {
    
    userPosition = {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
    };
    
    $.ajax({
        type: "POST",
        url: "/redirectUserToLocation",
        data: JSON.stringify(userPosition),
        dataType: "text",
        success: function (url) {
            window.location = url;
        }
    });
}