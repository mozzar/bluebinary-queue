<?php

use App\Controllers\Coaster;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->GET('/', [Coaster::class, 'index']);
$routes->GROUP('/api/coasters', function ($routes) {
    /**
     * Rejestracja nowej kolejki
     * */
    $routes->POST("/", [Coaster::class, 'store']);
    $routes->group('(:num)/', function ($routes) {

        $routes->group("wagons/", function ($routes) {
            /**
             * Rejestracja nowego wagonu do kolejki górskiej
             */
            $routes->POST("",
                [Coaster::class, 'wagonStore/$1']
            );
            /**
             * Usunięcie wagonu z kolejki górskiej
             */
            $routes->DELETE('(:num)',
                [Coaster::class, 'wagonDestroy/$1/$2']
            );
        });
        /**
         * Zmiana kolejki górskiej
         * */
        $routes->PUT('', [Coaster::class, 'coasterUpdate/$1']);
    });


    $routes->DELETE('reset', [Coaster::class, 'reset']);

});