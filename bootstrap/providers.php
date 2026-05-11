<?php

use App\Providers\AppServiceProvider;
use App\Providers\ObserverServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    ObserverServiceProvider::class,
];
