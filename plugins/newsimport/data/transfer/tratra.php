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

var_dump($Campsite['db']);
var_dump($trailers_path);

function transfer_trailers($p_sqlitePath, $p_mysqlConf)
{
    $table_name = 'trailers';
    $sqlite_name = $p_sqlitePath;

    $trailers = array();

    $db_sqlite = null;
    $db_mysql = null;

    $queryStr_sel = 'SELECT movie_key, vimeo_id, video_codec, video_width, video_height FROM ' . $table_name . ' WHERE vimeo_id != ""';
    $queryStr_upd = 'UPDATE Xscreening SET vimeo_id = %vimeo_id, video_codec = %video_codec, video_width = %video_width, video_height = %video_height WHERE movie_key = %movie_key';

    @$db = new PDO ('sqlite:' . $sqlite_name);
    $stmt = $db->prepare($queryStr_sel);
    $res = $stmt->execute();
    if ($res) {
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
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
    }


    $mysql_access = implode(', ', $p_mysqlConf)
    @$db = new PDO ('mysql:' . $mysql_access);

    $db->beginTransaction();
    $stmt = $db->prepare($queryStr_upd);
    foreach ($trailers as $movie_key => $trailer_info) {

        $stmt->bindParam(':movie_key', $trailer_info['movie_key'], PDO::PARAM_STR);
        $stmt->bindParam(':vimeo_id', $trailer_info['vimeo_id'], PDO::PARAM_STR);
        $stmt->bindParam(':video_codec', $trailer_info['video_codec'], PDO::PARAM_STR);
        $stmt->bindParam(':video_width', $trailer_info['video_width'], PDO::PARAM_STR);
        $stmt->bindParam(':video_height', $trailer_info['video_height'], PDO::PARAM_STR);

        $res = $stmt->execute();

    }
    $db->commit();

    return;
}

transfer_trailers($trailers_path, $Campsite['db']);

?>
