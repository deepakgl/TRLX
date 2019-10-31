<?php

// Executing the drush command to get the queue list.

exec("./vendor/drush/drush/drush queue-list", $output);
var_dump($output);
exec("./vendor/drush/drush/drush queue-run import_notification_data_frequently --time-limit=200", $output);
var_dump($output);
