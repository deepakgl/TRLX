<?php

/**
 * @file
 * Application Routes.
 *
 * Here is where you can register all of the routes for an application.
 *
 * It is a breeze. Simply tell Lumen the URIs it should respond to
 *
 * and give it the Closure to call when that URI is requested.
 */

$router->get('/', function () use ($router) {
  return $router->app->version();
});

// LRS Routes.
$router->group(['prefix' => 'v1/slrsa'], function () use ($router) {
  $router->options('{arg1}[/{arg2}]', 'LrsAgentController@option');
  $router->get('{arg1}[/{arg2}]', 'LrsAgentController@get');
  $router->put('{arg1}[/{arg2}]', 'LrsAgentController@put');
  $router->delete('{arg1}[/{arg2}]', 'LrsAgentController@delete');
});

$router->group(
  ['middleware' => 'jwt.auth'], function () use ($router) {

    // Leaderboard.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->get('userLeaderBoard', 'LeaderboardController@userLeaderBoard');
      $router->get('currentUserRank', 'LeaderboardController@currentUserRank');
    });

    // Content.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->post('setTermsNodeData', 'ContentController@setTermsNodeData');
      $router->get('getIntereactiveLevelTermStatus', 'ContentController@intereactiveLevelTermStatus');
      $router->get('purgeElasticUser', 'ContentController@purgeElasticUser');
      $router->get('purgeElasticNode', 'ContentController@purgeElasticNodeData');
      $router->post('deleteTermsNodeData', 'ContentController@deleteTermsNodeData');
    });

    // Points.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->get('getUserPoints', 'PointsController@getUserPoints');
    });

    // Badges.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->get('getUserBadges', 'BadgesController@userBadges');
      $router->get('getUserBadgesByUid', 'BadgesController@userBadgesByUid');
      $router->get('allocateBadge', 'BadgesController@allocateBadge');
      $router->get('allocateInspirationBadge', 'BadgesController@allocateInspirationBadge');
    });

    // Flag.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->post('flag', 'FlagController@setFlag');
      $router->get('myFlags', 'FlagController@myFlags');
      $router->post('contentViewFlag', 'FlagController@contentViewFlag');
    });

    // Search.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->get('search', 'SearchController@search');
      $router->get('productSearch', 'ArProductSearchController@productSearch');
    });

    // Quiz.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->post('quiz', 'QuizController@quiz');
    });

    // User Activity.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->get('getUserActivities', 'UserActivitiesController@getUserActivities');
      $router->get('userActivities', 'UserActivitiesController@userActivities');
      $router->get('getUserLevelActivities', 'UserActivitiesController@getUserLevelActivities');
      $router->get('userActivitiesLevel', 'UserActivitiesController@userActivitiesLevel');
      $router->get('userActivitiesLevel', 'UserActivitiesController@userActivitiesLevel');
      $router->post('update/user/elastic/index', 'UserActivitiesController@updateUserElasticBody');
    });

    // User Rank.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->get('userProfileRank', 'UserRankController@userProfileRank');
    });

    // Global activity.
    $router->group(['prefix' => 'v2'], function () use ($router) {
      $router->get('userActivity', 'UserActivitiesController@userActivity');
      $router->get('globalActivity', 'UserActivitiesController@globalActivity');
    });

    // Notification Routes.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->get('notification', 'NotificationController@getByUserId');
      $router->post('notification/status/save', 'NotificationController@updateNotificationsFlag');
    });

    // Index users in elastic.
    $router->group(['prefix' => 'v1'], function () use ($router) {
      $router->post('users', 'UserController@updateUsersIndex');
    });
  });

// Index users in elastic.
$router->group(['prefix' => 'v1'], function () use ($router) {
  $router->post('users', 'UserController@updateUsersIndex');
  $router->get('users', 'UserController@getUsersListing');
});
