<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // docker-compose up OR down OR build
    // docker-compose run diploma bash
    // php composer.phar dumpautoload --ignore-platform-reqs
    // php artisan optimize

    // php artisan migrate
    // php artisan db:seed
    // Dangerous: // php artisan migrate:fresh --seed

    // docker exec -i fitness_db_1 mysql -uroot -proot fitness < bulktrac_prod.sql

    // php composer.phar install --ignore-platform-reqs
}
