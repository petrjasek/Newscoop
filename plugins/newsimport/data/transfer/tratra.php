#!/usr/bin/env php
<?php
// for making a fast transfer of (remotely taken) vimeo videos into Newscoop movie articles

global $Campsite;
if ((!isset($Campsite)) || empty($Campsite)) {
    $Campsite = array();
}
if (!isset($Campsite['db'])) {
    $Campsite['db'] = array();
}

$db_conf_path = 'conf/database_conf.php';
$sl_conf_path = 'conf/sqlite_conf.php';
require($db_conf_path);
require($sl_conf_path);

function transfer_trailers($p_sqlitePath, $p_mysqlConf)
{
    $table_name = 'trailers';
    $sqlite_name = $p_sqlitePath;

    $trailers = array();

    $db_sqlite = null;
    $db_mysql = null;

    $queryStr_sel = 'SELECT movie_key, vimeo_id, video_codec, video_width, video_height FROM ' . $table_name . ' WHERE vimeo_id != ""';
    $queryStr_upd = 'UPDATE Xscreening SET Fmovie_trailer_vimeo = ?, Fmovie_trailer_codec = ?, Fmovie_trailer_width = ?, Fmovie_trailer_height = ? WHERE Fmovie_key = ?';

    @$db = new PDO ('sqlite:' . $sqlite_name);
    $stmt = $db->prepare($queryStr_sel);
    $res = $stmt->execute();
    if (!$res) {
        return false;
    }
    while (true) {
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$res) {
            break;
        }
            $movie_key = '' . $res['movie_key'];
            $vimeo_id = '' . $res['vimeo_id'];
            $video_codec = '' . $res['video_codec'];
            $video_width = '' . $res['video_width'];
            $video_height = '' . $res['video_height'];

            if (empty($movie_key) || empty($vimeo_id)) {
                continue;
            }

            $trailers[$movie_key] = array('vimeo_id' => $vimeo_id, 'video_codec' => $video_codec, 'video_width' => $video_width, 'video_height' => $video_height);
    }

    $mysql_dsn = 'mysql:host=' . $p_mysqlConf['host'] . ';dbname=' . $p_mysqlConf['name'];
    $mysql_username = $p_mysqlConf['user'];
    $mysql_password = $p_mysqlConf['pass'];
    $mysql_options = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
    ); 

    @$db = new PDO($mysql_dsn, $mysql_username, $mysql_password, $mysql_options);

    $db->beginTransaction();
    $stmt = $db->prepare($queryStr_upd);

    ksort($trailers);
    foreach ($trailers as $movie_key => $trailer_info) {
        $br = $stmt->bindParam(5, $movie_key, PDO::PARAM_STR, 255);
        $br = $stmt->bindParam(1, $trailer_info['vimeo_id'], PDO::PARAM_STR, 255);
        $br = $stmt->bindParam(2, $trailer_info['video_codec'], PDO::PARAM_STR, 255);
        $br = $stmt->bindParam(3, $trailer_info['video_width'], PDO::PARAM_STR, 255);
        $br = $stmt->bindParam(4, $trailer_info['video_height'], PDO::PARAM_STR, 255);

        $res = $stmt->execute();
        if (!$res) {
            var_dump($movie_key);
            print_r($stmt->errorInfo());
        }

    }
    $db->commit();

    return;
}

transfer_trailers($trailers_path, $Campsite['db']);

?>
