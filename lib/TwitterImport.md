# Twitter Import:
***
## Description:
Imports posts containing images from a given area. The area is given
as a coordinate point and a radius in km.
***
## Create an importer:
To create a new importer you must call the constructor with the
following arguments:
+ **latitude**
+ **longitude** 
+ **radius**
+ **access token** - string, recevied from TwitterAuth.

The access token does not expire at the moment, only if is cancelled
by a Twitter admin. If it happens to expire you should expect a
response of the type:

    `HTTP/1.1 401 Unauthorized
    Content-Type: application/json; charset=utf-8
    Content-Length: 61
    ...

    {"errors":[{"message":"Invalid or expired token","code":89}]}`
***
## Get posts:
To get posts from Twitter you must call the method **getPosts()**.
This method makes a **GET** request to the Twitter API at *'https://api.twitter.com/1.1/search/tweets.json'*.
The query that needs to be sent in order to receive the desired posts is:

    `'query' => [
        'q' => "filter:twimg",
        'geocode' => $geocode,
        'result_type' => 'recent',
        'count' => 100
    ]`
            
Where:
+ **q** - the query. **filter:twimg** tells Twitter that you want
only images.
+ **geocode** - where to search for posts. It should be
of the form **"{latitude},{longitude},{radius}km"**.
+ **result_type** - what kind of result you want. Recent means we
want only the most recent posts. Other values are *popular* and
*mixed*.
+ **count** - how many post you want to received in a single request.

How it works:
1. If you have not made any request yet, then the script will return
only *count* posts and it will stop after that.
2. If you already made a request in the past, the script will retrieve
from the db the id of the last post (the most recent). This will be
the **since id**. The **since id** tells Twitter API to return posts
with an id bigger that **since id** (newer posts). Since you can not
retrive more than *count* posts in a single request, you may need to
make multiple requests to get all the images that were posted since
**"since id"**. In the following requests you will need the **max id**
, too. This is the id of the last post received in the previous
request and indicates from where to start returning posts when you
make the next request.

A more detailed explanation is found [here](https://dev.twitter.com/rest/public/timelines).

If you substract 1 from the **max id** Twitter won't return
this posts again.

    `$max_id = $posts->statuses[count($posts->statuses) -1]->id - 1;`
***
## Process posts:
The **processPosts()** method is called automatically by
**getPosts()** after receiving the posts from a request to the Twitter
API.
The method receives a JSON object which contains the post in the
**statuses** memeber and extracts the relevant data concerning
the post and the user that created the post.

Each post is verified if is not a retweet. This is done by checking
if the **retweeted_status** member is set or if the post's text
starts with *RE @{someone}:*:

    `if (isset($post->retweeted_status) || preg_match('/^RE @(.)+:/', $post->text)) {
        continue;
    }`
    
Also, if the post dose not have the media member, then the image url
cannot be found so it's of no use:

    `if (!isset($post->entities->media)) {
        continue;
    }`
***
## Add to database:
Is called by **getPosts()** after the posts are processed. The posts
are given as an array. Each item is another array with two keys: *post*
and *user*. *post* contains data regarding a post and *user* the
Twitter user that made the post.

It searches for the user in the db. It if is found then it sets
the *user_id* member of the post object to the id of the user, else
it adds the user to the db first. After *user_id* is set, the script
saves the post to the db.