<?php

// AUTH
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');

$router->get('/register', 'AuthController@registerForm');
$router->post('/register', 'AuthController@register');

$router->get('/logout', 'AuthController@logout');


// DASHBOARD
$router->get('/dashboard', 'DashboardController@index');


// APOSTAS
$router->get('/bets', 'BetsController@index');
$router->get('/duels/quick', 'DuelsController@quick');


// COUPONS
$router->get('/coupons', 'CouponsController@index');
$router->get('/coupons/create', 'CouponsController@create');
$router->get('/coupon/{id}', 'CouponsController@show');
$router->get('/coupon/{id}/ranking', 'RankingController@show');
$router->get('/coupon/{id}/picks', 'CouponPicksController@index');
$router->get('/coupon/{id}/pick-stats', 'PickStatsController@show');
$router->get('/coupon/{id}/pick-percentages', 'PickStatsController@percentages');


// BETS
$router->post('/bet', 'BetController@store');
$router->get('/bet/{id}', 'BetController@show');


// DUELS
// DUELS
$router->get('/duels', 'DuelsController@index');

$router->get('/duels/create', 'DuelsController@create');
$router->post('/duels/store', 'DuelsController@store');

$router->post('/duels/challenge', 'DuelsController@challenge');
$router->get('/duels/challenge/{username}', 'DuelsController@challengeUser');

$router->get('/duels/{id}/accept', 'DuelsController@accept');

$router->get('/duels/generate-matches', 'DuelsController@generateMatches');

$router->get('/duels/h2h/{u1}/{u2}', 'DuelsController@headToHead');

$router->get('/duels/ranking', 'DuelsController@ranking');
$router->get('/duels/ranking-weekly', 'DuelsController@rankingWeekly');

// DUEL CHAT
$router->post('/duels/chat/send', 'DuelChatController@send');
$router->get('/duels/chat/{id}', 'DuelChatController@list');


// ACCOUNT / USERS
$router->get('/account', 'AccountController@index');
$router->get('/user/{username}', 'UsersController@profile');
$router->get('/user/{username}/history', 'UsersController@history');
$router->get('/challenge/{username}', 'DuelsController@challengeUser');


// ACTIVITY
$router->get('/activity', 'ActivityController@index');


// NOTIFICATIONS
$router->get('/notifications', 'NotificationsController@index');
$router->get('/notifications/read/{id}', 'NotificationsController@markRead');

// ADMIN

$router->get('/admin', 'AdminDashboardController@index');

$router->get('/admin/coupons', 'AdminCouponsController@index');

$router->get('/admin/import', 'AdminImportController@index');
$router->post('/admin/import/competitions', 'AdminImportController@importCompetitions');
$router->post('/admin/import/teams', 'AdminImportController@importTeams');
$router->post('/admin/import/matches', 'AdminImportController@importMatches');

$router->get('/admin/system-logs', 'AdminLogsController@index');
$router->get('/admin/cron-status', 'AdminCronController@index');
$router->get('/admin/system', 'AdminSystemController@index');

$router->get('/admin/health', 'AdminHealthController@index');
$router->get('/admin/economy','AdminEconomyController@index');