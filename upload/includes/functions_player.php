<?php
function show_player($param): bool
{
    global $Cbucket;

    if (!$param['autoplay']) {
        $param['autoplay'] = config('autoplay_video');
    }

    // ) 
    // { 
    // ["subscribed"]=> int(1)
    //  ["expireDate"]=> string(19) "2025-04-21 03:07:41"
    //   ["uid"]=> int(1)
    //    ["playerConfig"]=> array(2) { 
    //     ["player"]=> array(7) {
    //         ["defaultWidth"]=> int(300)
    //          ["defaultHeight"]=> int(250)
    //           ["autoplayEmbed"]=> bool(true) 
    //           ["autoplay"]=> bool(true) 
    //           ["chromecastSupport"]=> bool(true) 
    //           ["subtitles"]=> bool(true) 
    //           ["defaultVideoResolution"]=> string(5) "1080p" 
    //         } 
    //         ["controls"]=> array(6) { 
    //             ["enableBarLogo"]=> bool(true)
    //              ["barLogoUrl"]=> string(1) "#" 
    //              ["disableContextualMenu"]=> bool(false)
    //              ["thumbnails"]=> bool(true) 
    //              ["support360Videos"]=> bool(true)
    //               ["hlsDefaultResolution"]=> string(4) "auto"
    //              } 
    //         }
    //      }
    // var_dump($video_files);
    // exit();
    $param['autoplay'] = $param['paramsInit']['playerConfig']['player']['autoplay'];

    assign('player_config', $param['paramsInit']);
    assign('player_params', $param);

    $funcs = $Cbucket->actions_play_video;

    if (!empty($funcs) && is_array($funcs)) {
        foreach ($funcs as $func) {
            if (is_array($func)) {
                $class = $func['class'];
                $method = $func['method'];
                if (method_exists($class, $method)) {
                    $player_code = $class::$method($param);
                }
            } else {
                if (function_exists($func)) {
                    $player_code = $func($param);
                }
            }
            if( !empty($player_code) && !is_bool($player_code) ) {
                assign('player_js_code', $player_code);
                Template(DirPath::get('player') . 'player.html', false);
                return false;
            }
        }
    }
    return false;
}
