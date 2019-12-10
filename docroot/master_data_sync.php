<?php

// Executing the drush command to get the master data from OTM.

exec("./vendor/drush/drush/drush import:batch subRegion", $output);
exec("./vendor/drush/drush/drush import:batch country", $output);
exec("./vendor/drush/drush/drush import:batch brand", $output);