# Instagram Import:
***
## Description:
Search for Instagram posts from a given location, both images and
videos. The location is given as an *id*.
***
## Create an importer:
To create a new importer you must call the constructor with the
following parameters:
+ **location_id** - int or string, the id of the location returned by
*InstagramLocationImport*.
+ **access token** - string, recevied from *InstagramAuth* (now on
10.10.10.10/index.php/subscribe).

The access token may expire at any time if Instagram decides so.
If this happens the response will contain an
***“error_type=OAuthAccessTokenError”***.
***
## Get posts:
To import posts you must call the **getPosts()** method. This makes
a **GET** request to the Instagram API at
***'https://api.instagram.com/v1/locations/{location_id}/media/recent'***.

The query string contains only the access token and the *mid_id* if
exists.

How it works:
1. If there are no previous posts in the database then the script
will make the GET request without the *min_id* and it will receive a
list with the recent posts from the location (not a fixed number of
posts).
2. If you already extracted some posts then it will take the id of
the newest post from the database and it will assign it to *min_id*.
This will tell Instagram to fetch only posts that are newer that
this.
The response from the API will contain all this posts, so only
a sigle request is required.


    `$min_id = ORM::for_table('ig_post')
        ->select_expr('MAX(instagram_id)', 'id')
        ->where('location_id', $this->location_id)
        ->find_one();
            
    if ($min_id !== False) {
        $params['query']['min_id'] = $min_id->id;
    }`
    
The posts are retuned as a **JSON** object which contains a list of
posts in the **data** member.
***
## Process posts:
After the posts are returned successfully **processPosts()** is called
by **getPosts()** with the **JSON** object which contains the posts.
The posts are contained in the **data** member of the object.

From every posts are collected only relevant informations grouped in
two categories *post* and *user*. *post* cotains info regarding the
post and *user* about the Instagram user that created the post.

Before saving the url address of the image or video, it checks the
type of the posts, because the url of an image is in the *images*
member and of a video is in the *videos* member.

    `if ($post->type === 'image') {
            $processedPost['post']['media_url'] = $post->images->standard_resolution->url;
    }
    else {
        $processedPost['post']['media_url'] = $post->videos->standard_resolution->url;
    }`
    
Every posts is saved as an array containig two keys *user* and *post*
each being another list with the corresponding informations inside.
***
## Add to database:
After being processed, **getPosts()** calls the **addToDB()** method
with the list of processed posts.

When inserting a post the script checks first if the post is not
already in the database. If so, it will skip it. This is needed
because if *min_id* is set then Instagram will return that post again,
alongside the newer posts.

Also, the user won't be inserted in the database if already exists.

Finally the *user_id* memeber of the post is assigned the id of the
user and the post is saved to the database.