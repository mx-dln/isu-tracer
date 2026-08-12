<?php

declare(strict_types=1);

namespace App\Middleware;

use Closure;

interface MiddlewareInterface
{
    public function handle(Closure $next): void;
}
