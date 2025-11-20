<?php

use Daikazu\Robotstxt\Controllers\RobotsTextController;

Route::get('robots.txt', RobotsTextController::class)
    ->name('robots.txt');
