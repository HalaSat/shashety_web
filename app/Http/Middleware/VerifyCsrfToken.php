<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/bot/admin/new/movie/movieapi',
        '/bot/admin/new/movie/movievideo',
        '/bot/admin/new/movie/moviesubtitle'
    ];
}
