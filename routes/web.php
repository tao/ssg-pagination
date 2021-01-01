<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// News (Main) with pagination pages
Route::statamic('news/{current_page?}', 'news.index')->where('current_page', '^[0-9]{1,3}$');

// News (Year) pages that defaults to first page
Route::statamic('news/{year}', 'news.year', [
  'current_page' => 1,
])->where([
  'year' => '^[0-9]{4}$'
]);

// News (Year) page for pagination pages
Route::statamic('news/{year}/{current_page?}', 'news.year')->where([
  'current_page' => '^[0-9]{1,3}$',
  'year' => '^[0-9]{4}$'
]);
