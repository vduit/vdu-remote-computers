<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'HomepageController@getHomepage')->middleware(['auth', 'locale'])->name('getHomepage');


Route::middleware(['auth', 'locale'])->group(function(){
    Route::get('/room/{room}', 'HomepageController@getComputerList')->name('getComputerList');

    Route::get('reserve_computer/{computer}', 'HomepageController@reserveComputer')->name('reserveComputer');

    Route::post('cancel_reservation/{computer}', 'HomepageController@cancelComputerReservation')->name('cancelReservation');

    Route::get('set_language/{lang}', 'HomepageController@setLanguage')->name('setLanguage');

    Route::post('cancel_all_reservations/{roomId}', 'HomepageController@cancelAllRoomReservations')->name('cancelAllReservations');

    Route::middleware(['permission' => 'permission:rdpis_statistics'])->group(function(){
        Route::get('get_reservation_statistics', 'StatisticsController@getReservationList')->name('getReservationList');

        Route::get('get_class_occupancy_statistics', 'StatisticsController@getClassOccupancy')->name('getClassOccupancy');
    });
});

Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::get('logout', 'Auth\LoginController@logout')->name('logout');
Route::post('logout', 'Auth\LoginController@logout');

