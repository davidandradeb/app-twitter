<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get("/login",['as'=>'login','uses'=>"TwitterController@login"]);
$router->get("/twitter/callback","TwitterController@oauth_twitter_callback");
$router->get("/cron/read/messages","TwitterController@read_messages");
$router->get("/cron/read/mentions","TwitterController@read_mentions");
$router->get("/test/twitter","TwitterController@test");
$router->get("/menu","TwitterController@menu");
$router->get("/profile","TwitterController@profile");

$router->group(
    ['middleware' => 'auth'],
    function () use ($router) {

    $router->post("/twitter/users","TwitterController@get_users");

   	$router->get("/twitter/recent/messages","TwitterController@recent_messages");
	$router->post("/twitter/recent/messages","TwitterController@recent_messages");

	$router->get("/twitter/direct/messages","TwitterController@get_direct_messages");
	$router->post("/twitter/direct/messages","TwitterController@get_direct_messages");

	$router->get("/twitter/direct/messages/create","TwitterController@post_direct_message_create");
	$router->post("/twitter/direct/messages/create","TwitterController@post_direct_message_create");
	
	$router->get("/twitter/recent/mentions","TwitterController@get_mentions");
	$router->post("/twitter/recent/mentions","TwitterController@get_mentions");
	$router->post("/twitter/mention/response","TwitterController@mention_response");

	$router->post("/twitter/mention/response","TwitterController@mention_response");

	
	$router->get("/twitter/delete/user","TwitterController@delete_user");
	$router->post("/twitter/delete/user","TwitterController@delete_user");



    }
);