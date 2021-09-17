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

$router->get("/menu","TwitterController@menu");
$router->get("/twitter/mensajes/directos","TwitterController@get_mensajes_directos");
$router->get("/twitter/perfil","TwitterController@perfil");
$router->get("/cron/leer/mensajes","TwitterController@leer_mensajes");
$router->get("/cron/leer/mensiones","TwitterController@leer_mensiones");




$router->get("/test/twitter","TwitterController@test");


