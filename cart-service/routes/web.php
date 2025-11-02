
<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/cart', 'CartController@index');
$router->get('/cart/{id}', 'CartController@show');
$router->post('/cart', 'CartController@store');
$router->put('/cart/{id}', 'CartController@update');
$router->delete('/cart/{id}', 'CartController@destroy');

