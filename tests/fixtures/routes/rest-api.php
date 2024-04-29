<?php

use Mantle\Facade\Route;

Route::rest_api( '/example-namespace', function () {
	Route::get( '/example-router', fn () => 'Hello World' );
} );
