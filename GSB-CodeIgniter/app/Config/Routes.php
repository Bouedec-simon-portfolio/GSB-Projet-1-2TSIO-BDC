<?php
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->setAutoRoute(false);
$routes->get('/', 'Auth::index');
$routes->get('connexion', 'Auth::index');
$routes->post('connexion', 'Auth::login');
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->post('deconnexion', 'Auth::logout');
    $routes->get('fiches', 'Frais::index');
    $routes->post('fiches', 'Frais::create');
    $routes->get('fiches/(:num)', 'Frais::show/$1');
    $routes->post('fiches/(:num)/forfait', 'Frais::forfait/$1');
    $routes->post('fiches/(:num)/hors-forfait', 'Frais::add/$1');
    $routes->post('fiches/(:num)/hors-forfait/(:num)/supprimer', 'Frais::delete/$1/$2');
    $routes->get('administration', 'Frais::admin');
});
