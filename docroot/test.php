<?php
require './vendor/autoload.php';
@require '/var/www/html/sites/default/settings.php';


$client = Elasticsearch\ClientBuilder::create()->build();

try{
$starttime = microtime(TRUE);
print "before connection : ". $starttime ."\n </br>";
$databaseArray = $databases['default']['default'];

$host= $databaseArray['host'];
$username=$databaseArray['username'];
$password = $databaseArray['password'];
$dbname = $databaseArray['database'];
$timedelta=4;

$options = array(
        PDO::MYSQL_ATTR_SSL_CA => '/etc/azencrypt/BaltimoreCyberTrustRoot.crt.pem'
    );
$db = new PDO("mysql:host=$host;port=3306;dbname=$dbname", "$username", "$password", $options);

$end_time = microtime(TRUE);
print "after Query : ". $end_time ."\n </br>";
$timeDiff = $end_time - $starttime;
//if( (int) $timeDiff>$timedelta){
    error_log("Slow Connection MYQL - TRLX (Time taken to connect) :  $timeDiff ");
//}
print "CONNECTION TIME Difference : ". ($end_time - $starttime) ."\n </br><br>";

$starttime = microtime(TRUE);
print "before query : ". $starttime ."\n </br>";
$databaseArray = $databases['default']['default'];

$statement = $db->query("Select * from users;");
$row = $statement->fetch(PDO::FETCH_ASSOC);

$end_time = microtime(TRUE);
$timeDiff = $end_time - $starttime;
//if((int) $timeDiff>$timedelta){
    error_log("Slow Connection MYQL - TRLX (Time taken to db query) :  $timeDiff ");
//}
print "after Query : ". $end_time ."\n </br>";
print "Query Time Difference : ". ($end_time - $starttime) ."\n </br>";
}
catch (\Exception $e){
    error_log("Slow Connection MYQL - TRLX (Error in mysql connection)");
}