# Instagram Facebook Location Import:
***
## Description:
Searches for the equivalent Instagram location of a location from
Facebook.
***
## Create a location importer:
To create a new importer you must call the constructor with the
following parameters:
+ **locations_id** - int or string. The Facebook ID of the location
received from FacebookLocationImport.
+ **access_token** - string, recevied from InstagramAuth (now on
10.10.10.10/index.php/subscribe).

The access token may expire at any time if Instagram decides so.
If this happens the response will contain an
***“error_type=OAuthAccessTokenError”***.
***
## Get locations:
The **getLocation()** method must be called in order to extract
locations from Instagram. This makes a **GET** request at *'https://api.instagram.com/v1/locations/search'*,
with the following query:

    `'query' => [
        'access_token' => $this->access_token,
        'facebook_places_id' => $this->location_id
    ]`
    
If everything is okay that the response body will contain a **JSON**
object which contains in the **data** member a list in which is the
location you searched for.

After the request the location will be saved to the database.
***
## Add to database:
**addToDB()** is called by **getLocation()** after the location is
returned.
This method receives the JSON object returned by Instagram API.

Before adding a location the script checks if the id is *0*. Locations
with id 0 are *not valid*. That means we cannot search for post made at
these locations.

If the locations is already in the database it will update it by
setting the *facebook_id* field to the id of the Facebook location
from the *fb_location* table.