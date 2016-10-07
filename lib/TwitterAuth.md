# Twitter Auth:
***
## Description:
Generate a new app access token for the Twitter API. This will
give you access to all the public information found on Twitter.
You could use this tool to invalidate a token, too.
***
## Create a generator:
To create a generator you must call the constructor with the following
parameters:
+ **consumer_key** - int or string, the key of the app received from Twitter
API when you registered a new app.
+ **consumer_secret** - string, the secret of the app, also received from
Twitter API.
+ **bearer_token** - string, only if you want to invalidate the token.
***
## Get bearer token:
To generate the access token the **obtainBearerToken()** method must
be called.
This method makes a **POST** request to the Twitter API at
*'https://api.twitter.com/oauth2/token'*, with the following
query:

    `$params = [
        'form_params' => [
            'grant_type' => 'client_credentials'
        ],
        'headers' => [
            'Authorization' => "Basic $credentials",
            'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            'User-Agent' => 'Twitter Test App'
        ]
    ];`
    
**$credentials** is a string of the form *{consumer_key}:{consumer_secret}* after being
encoded using *base64*.

    `base64_encode(urlencode($this->consumer_key) . ':' . urlencode($this->consumer_secret))`

If everything is ok, the response will contain the *access token*.

The generated app access token doesn't haven an expiration date.
Still, it could be cancelled by a Twitter admin at any time.
If this happens the response from the API will have a status code of *401* and will
contain:

    `HTTP/1.1 401 Unauthorized
    Content-Type: application/json; charset=utf-8
    Content-Length: 61
    ...

    {"errors":[{"message":"Invalid or expired token","code":89}]}`
***
## Invalidate bearer token:
The **invalidateBearerToken()** method can be used to invalidate an access token.

This method makes a **POST** request to the Twitter API at
*'https://api.twitter.com/oauth2/invalidate_token'*, with the following
query:

    `$params = [
        'form_params' => [
            'access_token' => '$this->bearer_token'
        ],
        'headers' => [
            'Authorization' => "Basic $credentials",
            'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            'User-Agent' => 'Twitter Test App',
            'Accept' => '*/*'
        ]
    ];`