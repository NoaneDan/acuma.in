# Facebook Auth:
***
## Description:
Generate a new app access token for the Facebook Graph API. This will
give you access to most of the public information found on Facebook.
***
## Create a generator:
To create a generator you must call the constructor with the following
parameters:
+ **client_id** - int or string, the id of the app received from Facebook
Developer when you registered a new app.
+ **clinet_secret** - string, the secret of the app, also received from
Facebook Developer.
***
## Generate the access token:
To generate an access token the **generateAccessToken()** method must
be called.
This method makes a **GET** request to Facebook Graph API at
*'https://graph.facebook.com/oauth/access_token'*, with the following
query:

    `'query' => [
        'client_id' => $this->client_id,
        'client_secret' => $this->client_secret,
        'grant_type' => 'client_credentials'
    ]`
    
If everything is ok, the response will contain a string of the
form ***access_token={app access token}***.

The *parse_str* function is used to parse the response body. This method
creates new variables in the local scope as specified in the string.
In this case it will create a variable named *$access_token*.
The method will return only the access token, not the whole response
body.

The generated app access token expires in 60 days. When it expires all
the response from the API will have a status code of *400* and will
contain:

    `{
        "error": {
            "message": "Error validating access token: Session has expired at unix 
                        time SOME_TIME. The current unix time is SOME_TIME.", 
            "type": "OAuthException", 
            "code": 190
        }
    }`