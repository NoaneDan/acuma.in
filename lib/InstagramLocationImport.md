# Instagram Location Import:
***
## Description:
Searches Instagram for locations in a given area. The area is
represented by a coordinate point and a radius in meters (maximum
750).
***
## Create a location importer:
To create a new importer you must call the constructor with the
following parameters:
+ **latitude**
+ **longitude**
+ **radius**
+ **access token** - string, recevied from InstagramAuth (now on
10.10.10.10/index.php/subscribe).

The access token may expire at any time if Instagram decides so.
If this happens the response will contain an
*“error_type=OAuthAccessTokenError”*.
***
## Get locations:
The **getLocations()** method must be called in order to extract the
locations from Instagram. This makes a **GET** request at *'https://api.instagram.com/v1/locations/search'*,
with the following query:

    `'query' => [
        'access_token' => $this->access_token,
        'lat' => $this->latitude,
        'lng' => $this->longitude,
        'distance' => $this->radius
    ]`
    
If everything is okay then the response body will contain a **JSON**
object which contains in the **data** member the list of locations.

The API will return all the locations in a single request.

After the request the locations will be saved to the database.
***
## Add to database:
**addToDB()** is called by **getLocations()** after the locations are
returned.
This method receives the JSON object returned by Instagram API.

Before adding a location the script checks if the id is *0*. Locations
with id 0 are *not valid*. That means we cannot search for post made at
these locations.

Also, it won't add the location if it already exists in the database.