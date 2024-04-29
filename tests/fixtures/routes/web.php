<?php
use Mantle\Facade\Route;

Route::get( '/example-router', fn () => 'Hello World' )->without_middleware([
	// TODO: Remove need for this.
	\Mantle\Http\Routing\Middleware\Setup_WordPress::class,
	\Mantle\Http\Routing\Middleware\Substitute_Bindings::class,
	\Mantle\Http\Routing\Middleware\Wrap_Template::class,
]);
