<?php
/**
 * Copyright 2016 [e-spres-oh]
 * This file is part of Acuma.in
 *
 * Acuma.in is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Acuma.in is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Acuma.in.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__."/../config.php";

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpFoundation\Response as Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Silex\Application;
use Underscore\Types\Arrays;
use Symfony\Component\HttpFoundation\Session\Session;
use AcumaIn\BlockLayout;
use AcumaIn\Auth\FacebookAuth;
use AcumaIn\Auth\TwitterAuth;
use AcumaIn\FacebookEventsProcessor;

$app = new Silex\Application();
$app['debug'] = $environment['debug'];

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->extend('twig', function ($twig) use($app, $environment) {
    
    $host = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://');
    $host .= $_SERVER['HTTP_HOST'];
    $twig->addGlobal('host', $host);
    
    function minimizer($assets, $debug, $revision, $configuration) {
        $hash            = md5($assets . $revision);
        $assets_paths    = explode(",", $assets);
        $prefix          = $configuration['prefix'];
        $format          = $configuration['format'];
        $minimize        = $configuration['minimize'];
        $minimizer_class = $configuration['class'];
        
        $cache_file = sprintf($prefix . $format, $hash, ($minimize ? 'yes' : 'no'));
        clearstatcache(); // see https://secure.php.net/clearstatcache
        if(false === file_exists($cache_file) || $debug) {
            $concatenated = "";
            foreach($assets_paths as $asset_path) {
                // prevent directory traversal https://stackoverflow.com/questions/4205141/preventing-directory-traversal-in-php-but-allowing-paths
                $target_asset = realpath($prefix. $asset_path);
                if(substr($target_asset, 0, strlen($prefix)) !== $prefix) {
                    // directory traversal attempt
                    continue;
                }
                
                $concatenated .= file_get_contents($target_asset);
            }
            
            
            // configuration based CSS minimization (should be false for dev for easier debugging)
            file_put_contents($cache_file, $minimize ?
                (new $minimizer_class($concatenated))->minify() :
                $concatenated
            );
        }
        
        // remove __DIR__ part (up to web/)
        return substr($cache_file, strlen(__DIR__));
    };
    
    /* @var $twig Twig_Environment */
    $twig->addFilter(new \Twig_SimpleFilter('minimize_js', function($assets) use($environment) {
        return minimizer($assets, $environment['caching']['revision'], $environment['debug'], [
            "class"        => \MatthiasMullie\Minify\JS::class,
            "minimize"     => $environment['caching']['minimize_js'],
            "format"       => "minimized-js-%s-%s.js",
            "prefix"       => __DIR__ . "/assets/js/"
        ]);
    }));
    
    $twig->addFilter(new \Twig_SimpleFilter('minimize_css', function($assets) use($environment) {
        return minimizer($assets, $environment['caching']['revision'], $environment['debug'], [
            "class"        => \MatthiasMullie\Minify\CSS::class,
            "minimize"     => $environment['caching']['minimize_css'],
            "format"       => "minimized-css-%s-%s.css",
            "prefix"       => __DIR__ . "/assets/styles/"
        ]);
    }));
    
    $twig->addExtension(new \nochso\HtmlCompressTwig\Extension());
    
    return $twig;
});


$app->get('/', function () use($app) {
    
    $subRequest = Request::create('/Oradea');
    $response = $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
    
    return $response;
    //
    //$cities = \ORM::for_table('city')
    //    ->find_array();
    //
    //return $app['twig']->render('homeMenu.twig', ['cities' => $cities]);
});


$app->get('/privacy', function () use ($app) {
    
    return $app['twig']->render('privacy.twig');
});


$app->get('/about', function () use ($app) {
    
    return $app['twig']->render('about.twig');
});


$app->get('/contact', function () use ($app) {
   
   return $app['twig']->render('contact.twig'); 
});


$app->post('/contact', function (Request $req) use ($app) {
   
    $message = json_decode($req->getContent());
    
    $error = '';
    if ($message->name == '') {
         $error .= "Your name is required!\n"; 
    }
    if (!preg_match('/.+@.+\..+/', $message->email)) {
         $error .= "A valid email address is required!\n";
    }
    if ($message->subject == '') {
         $error .= "A subject is required!\n";
    }
    if ($message->message == '') {
         $error .= "A message is required!\n";
    }
    
    if ($error !== '') {
        return Response::create($error, Response::HTTP_BAD_REQUEST);
    }
   
    $newMessage = \ORM::for_table('contact')
        ->create();
        
    $newMessage->name = substr($message->name, 0, 50);
    $newMessage->email = substr($message->email, 0, 200);
    $newMessage->subject = substr($message->subject, 0, 200);
    $newMessage->message = substr($message->message, 0, 10000);
    $newMessage->save();
    
    return '';
});

// LOGIN USER

$app->before(function (Request $req, Application $app) {
    
    if (preg_match('@/backend(?!/login$)@', $req->getUri())) {
        $user = $app['session']->get('user');
        if ($user === null) {
            return $app->redirect('/backend/login');
        }
    }
});


$app->get('/backend/login', function(Request $req) use($app) {
    
    return $app['twig']->render('login.twig');
});


$app->post("/backend/login", function(Request $request) use($app) {
    
    $username = $request->get('username');
    $password = $request->get('password');
    
    $user = \ORM::for_table('user')
        ->where('username', $username)
        ->find_one();
    
    if ($user && password_verify($password, $user->password)) {
        $app['session']->set('user', ['username' => $username]);
        return $app->redirect("/backend");
    }
    
    return $app->redirect("/backend/login");
});


$app->get("/backend/logout", function() use($app) {

    $app['session']->clear();
    
    return $app->redirect('/backend/login');
});


$app->get("/backend", function(Request $req) use($app) {
    
    $cities = \ORM::for_table('city')
        ->select('id')
        ->select('city')
        ->order_by_asc('city')
        ->find_array();
    
    return $app['twig']->render('menu.twig', ['cities' => $cities]);
});

// ADD USERS TO DATABASE 

$app->get('/backend/users', function(Request $request) use($app) {
    
    $users = \ORM::for_table('user')->select(['id', 'username'])->find_many();
    $err = $request->get('err');
    
    return $app['twig']->render('users.twig', ['users' => $users, 'err' => $err]);
});


$app->post("/backend/users", function(Request $request) use($app) {
    
    $newUser = json_decode($request->getContent());
    
    if ($newUser->username == '' || strlen($newUser->password) < 8) {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
    
    $user = \ORM::for_table('user')
        ->where('username', $newUser->username)
        ->find_one();
    
    if ($user) {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
    else {
        $user = \ORM::for_table('user')->create();
        $user->username = $newUser->username;
        $user->password = password_hash($newUser->password, PASSWORD_BCRYPT);
        $user->save();
        
        return '';
    }                         
});

// DELETE USER FROM DATABASE

$app->post("/backend/users/delete", function(Request $request) use($app) {
    
    $userID = json_decode($request->getContent());
    
    $user = \ORM::for_table('user')
        ->where('id', $userID)
        ->find_one();
        
    if ($user !== false) {
        $user->delete();
        
        return '';
    }
    else {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
});


$app->get('/backend/welcome', function () use ($app) {
    
    $user = $app['session']->get('user');
    
    return $app['twig']->render('welcome.twig', ['user' => $user['username']]); 
});


// ADD LOCATION DATA TO DATABASE 

$app->get('/backend/locations', function(Request $request) use($app) {
    
    $locations = ORM::for_table('city')
        ->find_many();
    
    $err = $request->get('err');
    
    return $app['twig']->render('locations.twig', ['locations' => $locations, 'err' => $err]);
});


$app->post("/backend/locations", function(Request $request) use($app) {
    
    $location = json_decode($request->getContent());
    
    if ($location->city == "" || $location->latitude == "" || $location->longitude == '' || $location->radius == '') {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
    
    $city = \ORM::for_table('city')
        ->where('city', $location->city)
        ->find_one();
    if ($city) {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
    else {
        $new_city = \ORM::for_table('city')
            ->create();
        
        $new_city->city = $location->city;
        $new_city->latitude = $location->latitude;
        $new_city->longitude = $location->longitude;
        $new_city->radiusInMeters = $location->radius;
        $new_city->save();
        
        return '';
    }                         
});


$app->post("/backend/locations/delete", function (Request $req) use ($app) {
   
    $locationID = json_decode($req->getContent());
    
    $location = \ORM::for_table('city')
        ->where('id', $locationID)
        ->find_one();
        
    if ($location !== false) {
        $location->delete();
        
        return '';
    }
    else {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
});


$app->get("/backend/subscribe", function() use ($app, $environment) {
    
    $client_id = $environment['instagram']['client_id'];
    $redirect_uri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://';
    $redirect_uri .= $_SERVER['HTTP_HOST'] . '/backend/successful';
    $redirect = sprintf('https://api.instagram.com/oauth/authorize/?client_id=%s&redirect_uri=%s&response_type=code&scope=public_content',
                        $client_id, $redirect_uri);
    
    return $app->redirect($redirect);
});


$app->get("/backend/successful", function (Request $req) use ($app, $environment) {
   
    if ($req->get('error')) {
        $error  = $req->get('error_reason') . '\n';
        $error .= $req->get('error_description') . '\n';
        
        return $error;
    }
    else {
        $redirect_uri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://';
        $redirect_uri .= $_SERVER['HTTP_HOST'] . '/backend/successful';
        
        $params = [
            'form_params' => [
                'client_id' => $environment['instagram']['client_id'],
                'client_secret' => $environment['instagram']['client_secret'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirect_uri,
                'code' => $req->get('code')
            ]
        ];
        
        $client = new Client(['base_uri' => 'https://api.instagram.com']);
        $response = $client->request('POST', '/oauth/access_token', $params);
        
        $data = json_decode((string) $response->getBody());
    
        $access_token = \ORM::for_table('access_tokens')
            ->where('platform', 'instagram')
            ->where('type', 'app')
            ->find_one();
            
        if ($access_token !== false) {
            // we don't want to replace the present access token with those
            // generated for the other users in sandbox mode.
            
            //$access_token->access_token = $data->access_token;
            //$access_token->save();
        }
        else {
            $new_access_token = \ORM::for_table('access_tokens')
                ->create();
                
            $new_access_token->platform = 'instagram';
            $new_access_token->type = 'app';
            $new_access_token->access_token = $data->access_token;
            $new_access_token->save();
        }
        
        return "Successful Authentication\n";
    }
});


$app->get('/backend/moderation/{state}/{cityID}', function ($state, $cityID, Request $req) use ($app) {
    
    $page = $req->get('page', 0);
    
    if (!in_array($state, ['accepted', 'pending', 'blacklisted'])) {
        return $app->redirect('/backend/moderation');
    }
    
    $limit = 30;
    $offset = ($page <= 0 ? 0 : $page * $limit);
    
    $users = \ORM::for_table('moderation_table')
        ->distinct()
        ->table_alias('mt')
        ->select('mt.*')
        ->join('timeline', "t.source like if(mt.platform = 'twitter', 'twitter%', 'ig_post') and t.source_user_id = mt.user_id and t.city_id = $cityID", 't')
        ->where('state', $state)
        ->offset($offset)
        ->limit($limit)
        ->find_array();
        
    $posts = [];
    $tweets = [];
    foreach ($users as &$user) {
        
        $usersTable = ($user['platform'] === 'twitter') ? 'twitter_user' : 'ig_user';
        $username = ($user['platform'] === 'twitter') ? 'screenname' : 'username';
        $user['username'] = \ORM::for_table($usersTable)
            ->select($username, 'username')
            ->where('id', $user['user_id'])
            ->find_one()
            ->username;
            
        if ($state === 'accepted') {
            continue;
        }
        
        $table = ($user['platform'] === 'twitter') ? 'twitter_post' : 'ig_post';
        $posts[$user['id']] = \ORM::for_table($table)
            ->where('user_id', $user['user_id'])
            ->order_by_desc('created_at')
            ->limit(10)
            ->find_many();
            
        if ($user['platform'] === 'twitter') {
            $tweets[$user['id']] = \ORM::for_table('twitter_tweet')
                ->where('user_id', $user['user_id'])
                ->order_by_desc('created_at')
                ->limit(10)
                ->find_many();
        }
    }
    
    $numberOfLastPage = ceil(count(\ORM::for_table('moderation_table')
        ->distinct('mt.id')
        ->table_alias('mt')
        ->select('mt.*')
        ->join('timeline', "t.source like if(mt.platform = 'twitter', 'twitter%', 'ig_post') and t.source_user_id = mt.user_id and t.city_id = $cityID", 't')
        ->where('state', $state)
        ->find_many()) / $limit);

    return $app['twig']->render("$state.twig", ['users' => $users, 'posts' => $posts, 'tweets' => $tweets, 'state' => $state, 'page' => $page, 'numberOfLastPage' => $numberOfLastPage]);
});


$app->post('/backend/moderation/{state}', function (Request $req) use ($app) {
    
    $state = $req->get('state');
    $id = $req->getContent();
    
    if (!in_array($state, ['accepted', 'pending', 'blacklisted'])) {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
    
    $user = \ORM::for_table('moderation_table')
        ->where('id', $id)
        ->find_one();
        
    if ($user === false) {
        return $app->redirect('/backend/moderation');
    }
    
    $user->state = $state;
    $user->save();
    
    $source = ($user->platform === 'twitter' ? 'twitter_' : 'ig_post');
    $posts = \ORM::for_table('timeline')
        ->where('source_user_id', $user->user_id)
        ->where_like('source', "$source%")
        ->find_many();
    foreach($posts as $post) {
        $post->blocked = ($state === 'accepted' ? 'no' : 'yes');
        $post->save();
    }
    
    return '';
});


$app->post('/report', function (Request $req) use ($app) {
   
    $sourceAndPostID = explode('-', $req->getContent());
    if (count($sourceAndPostID) !== 2) {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
    
    $source = $sourceAndPostID[0];
    $post_id = $sourceAndPostID[1];
    
    if (!in_array($source, ['ig_post', 'twitter_post', 'twitter_tweet'])) {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
    
    $post = \ORM::for_table($source)
        ->where('id', $post_id)
        ->find_one();
        
    $user = $app['session']->get('user');
    if ($user !== null) {
        $timeline_entry = \ORM::for_table('timeline')
            ->where('source', $source)
            ->where('source_id', $post->id)
            ->find_one();
            
        $timeline_entry->blocked = 'yes';
        $timeline_entry->save();
    }
    else {
        $found = \ORM::for_table('report_table')
            ->where('source', $source)
            ->where('user_id', $post->user_id)
            ->where('post_id', $post->id)
            ->find_one();
            
        if ($found === false) {
            $report = \ORM::for_table('report_table')
                ->create();
                
            $report->user_id = $post->user_id;
            $report->post_id = $post->id;
            $report->source = $source;
            $report->save();
        }
    }
    
    return '';
});


$app->post('/backend/report/approve', function (Request $req) use ($app) {
    
    $report_id = $req->getContent();
    
    $report = \ORM::for_table('report_table')
        ->where('id', $report_id)
        ->find_one();
        
    if ($report !== false) {
        $report->delete();
    }
    
    return '';
});


$app->post('/backend/report/block', function (Request $req) use ($app) {
    
    $report_id = $req->getContent();
    
    $report = \ORM::for_table('report_table')
        ->where('id', $report_id)
        ->find_one();
        
    if ($report !== false) {
        $timeline_entry = \ORM::for_table('timeline')
            ->where('source', $report->source)
            ->where('source_id', $report->post_id)
            ->where('source_user_id', $report->user_id)
            ->find_one();
            
        $timeline_entry->blocked = 'yes';
        $timeline_entry->save();
        
        $report->delete();
    }
    
    return '';
});


$app->get('/backend/reportedPosts/{cityID}', function (Request $req) use ($app) {
    
    $cityID = $req->get('cityID');
    
    $reported_posts = \ORM::for_table('report_table')
        ->table_alias('rt')
        ->select('rt.*')
        ->join('timeline', "rt.source = t.source and rt.post_id = t.source_id and t.city_id = $cityID", 't')
        ->find_array();
        
    foreach ($reported_posts as &$reported_post) {
        $usersTable = ($reported_post['source'] !== 'ig_post' ? 'twitter_user' : 'ig_user');
        $username = ($reported_post['source'] !== 'ig_post' ? 'screenname' : 'username');
        
        $reported_post['username'] = \ORM::for_table($usersTable)
            ->select($username, 'username')
            ->where('id', $reported_post['user_id'])
            ->find_one()
            ->username;
        
        $post = \ORM::for_table($reported_post['source'])
            ->where('id', $reported_post['post_id'])
            ->find_one();
        
        $urlColumn = ($reported_post['source'] === 'ig_post' ? 'instagram_url' : 'twitter_url');
        $reported_post['post'] = $post[$urlColumn];
    }
   
    return $app['twig']->render('report.twig', ['reports' => $reported_posts]);
});


$app->get('/backend/facebookLocations/{cityID}', function (Request $req) use ($app) {
    
    $cityID = $req->get('cityID');
    
    $locations = \ORM::for_table('fb_location')
        ->table_alias('fbl')
        ->select('fbl.*')
        ->select('c.city')
        ->join('city', ['fbl.city_id', '=', 'c.id'], 'c')
        ->where('fbl.city_id', $cityID)
        ->order_by_asc('c.city')
        ->order_by_desc('fbl.blocked')
        ->order_by_asc('fbl.name')
        ->find_array();
    
    return $app['twig']->render('facebookLocations.twig', ['locations' => $locations]);     
});


$app->post('/backend/facebookLocations/{action}', function (Request $req) use ($app) {
   
   $action = $req->get('action');
   $location_id = $req->getContent();
   
   $location = \ORM::for_table('fb_location')
        ->where('id', $location_id)
        ->find_one();
        
    if ($action === 'block') {
        $location->blocked = 'yes';
    }
    else {
        $location->blocked = 'no';
    }
    
    $location->save();
    
    return '';
});


$app->get('/backend/refreshTokens', function () use ($app) {
   
    return $app['twig']->render('refreshTokens.twig', ['err' => false, 'output' => '']);
});


$app->post('/backend/refreshTokens', function (Request $req) use ($app, $environment) {
   
    $platform = $req->get('platform');
    
    $output = [];
    $return_var = null;
    
    switch ($platform) {
        case 'Facebook':
            // create and run the generator
            $generator = new FacebookAuth($environment['facebook']['client_id'], $environment['facebook']['client_secret']);
            $generator->generateAccessToken();
            
            break;
        case 'Instagram':
            return $app->redirect('/backend/subscribe');
        case 'Twitter':
            $generator = new TwitterAuth($environment['twitter']['consumer_key'], $environment['twitter']['consumer_secret']);
            $generator->obtainBearerToken();
            
            break;
        default:
            return $app->redirect($req->headers->get('referer'));
    }
    
    return $app->redirect($req->headers->get('referer'));
});


$app->get('/backend/contact', function () use ($app) {
   
    $contacts = \ORM::for_table('contact')
        ->find_array();
        
    return $app['twig']->render('backendContact.twig', ['contacts' => $contacts]);
});


$app->post('/backend/contact/delete', function (Request $req) use ($app) {
    
    $contactID = json_decode($req->getContent());
    
    $contact = \ORM::for_table('contact')
        ->where('id', $contactID)
        ->find_one();
        
    if ($contact !== false) {
        $contact->delete();
        
        return '';
    }
    else {
        return Response::create('', Response::HTTP_BAD_REQUEST);
    }
});


$app->post('/load_more', function (Request $req) use ($app) {
    
    $request = json_decode($req->getContent());
    if (!isset($request->max_id)) {
        $request->max_id = 0;
    }
    
    $city_name = $app['session']->get('city');
    $queue = $app['session']->get('queue');
    
    $data = load_more($request, $city_name, $queue);
    
    $app['session']->set('queue', $data->queue);
    
    return json_encode($data->posts);
});


$app->post('/redirectUserToLocation', function (Request $req) use ($app) {
   
   $position = json_decode($req->getContent());
   
   $cities = \ORM::for_table('city')
        ->find_many();
        
    $min_distance = PHP_INT_MAX;
    foreach ($cities as $city) {
        
        $distance = sqrt(pow($position->latitude - $city->latitude, 2) + pow($position->longitude - $city->longitude, 2));
        if ($distance < $min_distance) {
            $closest_city = $city;
        }
    }
    
    return $req->headers->get('referer') . "$closest_city->city";
});


$app->get('/{city}', function(Request $req) use ($app, $environment) {
    
    $city_name = $req->get('city');
    $found = \ORM::for_table('city')
        ->where('city', $city_name)
        ->find_one();
        
    if ($found === false) {
        return $app->redirect('/');
    }
    
    $app['session']->set('queue', []);
    $app['session']->set('city', $city_name);
    $queue = $app['session']->get('queue');
    $data = load_more((object) ['max_id' => 0], $city_name, $queue);
    
    $app['session']->set('queue', $data->queue);
    
    $app['twig']->addGlobal('city', $city_name);
    return $app['twig']->render('home.twig', [
        'data' => $data->posts,
    ]); 
});


$app->get('/{city}/{eventSlug}', function (Request $req) use ($app) {
    
    $city = $req->get('city');
    $eventSlug = $req->get('eventSlug');
    
    $cityFound = \ORM::for_table('city')
        ->where('city', $city)
        ->find_one();
    
    if ($cityFound === false) {
        return $app->abort(404, "City not found!");
    }
    
    $dbEvent = \ORM::for_table('fb_event')
        ->where('slug', $eventSlug)
        ->find_one();
        
    if ($dbEvent === false) {
        return $app->abort(404, "Event not found!");
    }
    
    $timelineEvent = \ORM::for_table('timeline')
        ->where('source', 'fb_event')
        ->where('source_id', $dbEvent->id)
        ->find_array();
        
    $eventProcessor = new FacebookEventsProcessor($timelineEvent);
        
    $data = (object) [
        'posts' => ["fb_event-$eventSlug" => $eventProcessor->getProcessedElements()],
        'max_id' => 0
    ];
    
    $app['twig']->addGlobal('city', $city);
    return $app['twig']->render('home.twig', [
        'data' => $data,
        'hash' => true,
    ]); 
});


if (!$app['debug']) {
    $app->error(function (\Exception $e, Request $req, $code) use ($app) {
       
        switch ($code) {
            case 404:
                $message = 'The required page could not be found.';
                break;
            default:
                $message = 'Something went terribly wrong...';
        }
        
        return $app['twig']->render('error.twig', ['code' => $code, 'message' => $message]);
    });
}


$app->run();


function load_more($request, $city_name, $queue) {
    
    $city_id = \ORM::for_table('city')
        ->where('city', $city_name)
        ->find_one()
        ->id;
    
    $groupingFn = function ($element, $source) {
        switch ($source) {
            case 'ig_post':
                return md5($element['id']);
            case 'fb_event':
                return md5($element['name']);
            case 'twitter_post':
            case 'twitter_tweet':
                return md5($element['username']);
        }
    };
    $layout = new BlockLayout($groupingFn, $city_id, $request->max_id, $queue);
    
    $response = $layout->getLayoutElements();
    $queue = $layout->getElementsThatDontFit();
    $final_response = (object) [
        'posts' => $response,
        'queue' => $queue
    ];
    
    return $final_response;
}
