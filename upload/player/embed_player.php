<?php
define('THIS_PAGE', 'watch_video');
include(dirname(__FILE__, 2) . '/includes/config.inc.php');

User::getInstance()->hasPermissionOrRedirect('view_video');

if(empty($_GET['vid'])){
    exit(lang('class_vdo_exist_err'));
}

$paramsInit = [];
$paramsInit['vid'] = explode('?cf=', $_GET['vid'])[0];
$paramsInit['cf'] = explode('?cf=', $_GET['vid'])[1] ?? '';
try{
    $paramsInit['cfdecode'] = json_decode(base64_decode($paramsInit['cf']), true);
}catch(Exception $e){
    exit("Invalid video link");
}

// var_dump($paramsInit);
// exit();
if(empty($paramsInit['cfdecode'])){
    exit("Invalid video link");
}else{
    if(!empty($paramsInit['cfdecode']['subscribed']) && !empty($paramsInit['cfdecode']['uid'])){
        if($paramsInit['cfdecode']['expireDate'] < date('Y-m-d H:i:s')){
            exit("Invalink time");
        }
    }else{
        exit("Invalink");
    }
}

$params = [];
$params['videokey'] = $paramsInit['vid']; //$_GET['vid'];
$params['exist'] = true;
$video_exists = Video::getInstance()->getOne($params);

if(!$video_exists){
    exit(lang('class_vdo_exist_err'));
}

unset($params['exist']);
$video = Video::getInstance()->getOne($params);

if(!$video){
    exit(lang('video_not_available'));
}

$autoplay = $_GET['autoplay'] ?? false;

assign('video', $video);
assign('autoplay', $autoplay);

assign('paramsInit', $paramsInit['cfdecode']);

Template('embed_player.html');
