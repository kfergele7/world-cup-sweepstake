<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntrantResultsController;
use App\Http\Controllers\JoinSweepstakeController;
use App\Http\Controllers\PrizeController;
use App\Http\Controllers\SweepstakeController;
use App\Http\Controllers\SweepstakeDrawController;
use App\Http\Controllers\SweepstakeMemberController;
use App\Http\Controllers\SweepstakeTeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/sweepstakes', [SweepstakeController::class, 'store'])->name('sweepstakes.store');
    Route::get('/sweepstakes/{sweepstake}', [SweepstakeController::class, 'show'])->name('sweepstakes.show');
    Route::patch('/sweepstakes/{sweepstake}/settings', [SweepstakeController::class, 'update'])->name('sweepstakes.settings.update');
    Route::post('/sweepstakes/{sweepstake}/members', [SweepstakeMemberController::class, 'store'])->name('sweepstakes.members.store');
    Route::patch('/sweepstakes/{sweepstake}/members/{member}', [SweepstakeMemberController::class, 'update'])->name('sweepstakes.members.update');
    Route::patch('/sweepstakes/{sweepstake}/members/{member}/payment', [SweepstakeMemberController::class, 'updatePayment'])->name('sweepstakes.members.payment.update');
    Route::delete('/sweepstakes/{sweepstake}/members/{member}', [SweepstakeMemberController::class, 'destroy'])->name('sweepstakes.members.destroy');
    Route::patch('/sweepstakes/{sweepstake}/teams', [SweepstakeTeamController::class, 'bulkUpdate'])->name('sweepstakes.teams.bulk.update');
    Route::patch('/sweepstakes/{sweepstake}/teams/{sweepstakeTeam}', [SweepstakeTeamController::class, 'update'])->name('sweepstakes.teams.update');
    Route::post('/sweepstakes/{sweepstake}/prizes', [PrizeController::class, 'store'])->name('sweepstakes.prizes.store');
    Route::post('/sweepstakes/{sweepstake}/draw', [SweepstakeDrawController::class, 'store'])->name('sweepstakes.draw.store');
    Route::post('/sweepstakes/{sweepstake}/draw/rerun', [SweepstakeDrawController::class, 'rerun'])->name('sweepstakes.draw.rerun');
});

Route::get('/join/{joinCode}', [JoinSweepstakeController::class, 'show'])->name('join.show');
Route::post('/join/{joinCode}', [JoinSweepstakeController::class, 'store'])->name('join.store');
Route::get('/entrants/{joinToken}', EntrantResultsController::class)->name('entrants.show');
