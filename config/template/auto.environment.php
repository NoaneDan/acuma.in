<?php

return [
    "mysql" => [
        "username" => MYSQL_USERNAME,
        "password" => MYSQL_PASSWORD,
        "database" => MYSQL_DATABASE
    ],
    "caching" => [
        "revision"     => GIT_REVISION,
        "minimize_css" => MINIMIZE_CSS,
        "minimize_js"  => MINIMIZE_JS
    ],
    "debug" => DEBUG_MODE,
    "instagram" => [
        "client_id" => INSTAGRAM_CLIENT_ID,
        "client_secret" => INSTAGRAM_CLIENT_SECRET
    ],
    "facebook" => [
        "client_id" => FACEBOOK_CLIENT_ID,
        "client_secret" => FACEBOOK_CLIENT_SECRET
    ],
    "twitter" => [
        "consumer_key" => TWITTER_CONSUMER_KEY,
        "consumer_secret" => TWITTER_CONSUMER_SECRET
    ]
];