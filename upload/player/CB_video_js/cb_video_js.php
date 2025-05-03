<?php
/*
	Player Name: VideoJS
	Description: Official CBV5 player
	Author: Oxygenz
    Author Website: https://clipbucket.oxygenz.fr/
	Version: 2.1.1
    Released: 2025-02-24
    Website: https://github.com/MacWarrior/clipbucket-v5
 */

class CB_video_js
{
    /**
     * @throws Exception
     */
    private function load_dependancies()
    {
        $player_name = self::class;

        $min_suffixe = in_dev() ? '' : '.min';
        ClipBucket::getInstance()->addAllJS([
            $player_name.'/js/license.js' => 'player'
            ,$player_name.'/js/video'.$min_suffixe.'.js' => 'player'
            ,$player_name.'/lang/'.get_current_language().'.js' => 'player'
            ,$player_name.'/plugin/clipbucket/videojs-clipbucket'.$min_suffixe.'.js' => 'player'
            ,$player_name.'/plugin/playinline/iphone-inline-video'.$min_suffixe.'.js' => 'player'
            ,$player_name.'/plugin/resolution/videojs-resolution'.$min_suffixe.'.js' => 'player'
            ,$player_name.'/plugin/hls-quality-selector/videojs-hls-quality-selector'.$min_suffixe.'.js' => 'player'
            ,$player_name.'/js/nuevo'.$min_suffixe.'.js' => 'player'
            // ,$player_name.'/plugin/resume/store.min.js' => 'player'
            // ,$player_name.'/plugin/resume/videojs-resume'.$min_suffixe.'.js' => 'player'
        ]);

        ClipBucket::getInstance()->addAllCSS([
            $player_name.'/css/video-js'.$min_suffixe.'.css' => 'player'
            ,$player_name.'/plugin/clipbucket/videojs-clipbucket'.$min_suffixe.'.css' => 'player'
            ,$player_name.'/plugin/resolution/videojs-resolution'.$min_suffixe.'.css' => 'player'
            
            // ,$player_name.'/plugin/resume/videojs-resume'.$min_suffixe.'.css' => 'player'
        ]);

        $current_url = $_SERVER['REQUEST_URI'];
        if(preg_match('/embed_player/i', $current_url)){
            ClipBucket::getInstance()->addJS([
                $player_name.'/css/nuevo-style.css' => 'player'
            ]);
        }

        if( config('chromecast') == 'yes' ){
            ClipBucket::getInstance()->addAllJS([
                $player_name.'/plugin/chromecast/cast_sender.js' => 'player'
                ,$player_name.'/plugin/chromecast/videojs-chromecast'.$min_suffixe.'.js' => 'player'
            ]);
            ClipBucket::getInstance()->addAllCSS([$player_name.'/plugin/chromecast/videojs-chromecast'.$min_suffixe.'.css' => 'player']);
        }

        if( config('player_thumbnails') == 'yes' ){
            ClipBucket::getInstance()->addAllJS([$player_name.'/plugin/thumbnails/videojs-thumbnails'.$min_suffixe.'.js' => 'player']);
            ClipBucket::getInstance()->addAllCSS([$player_name.'/plugin/thumbnails/videojs-thumbnails'.$min_suffixe.'.css' => 'player']);
        }

        if( config('enable_360_video') == 'yes' ){
            ClipBucket::getInstance()->addAllJS([
                $player_name.'/plugin/vr/videojs-vr'.$min_suffixe.'.js' => 'player'
            ]);
            ClipBucket::getInstance()->addAllCSS([$player_name.'/plugin/vr/videojs-vr'.$min_suffixe.'.css' => 'player']);
        }
    }

    private function register_actions_play_video()
    {
        register_actions_play_video('load_player', self::class);
    }

    private static function formatVideoTitle($title) {
        // Find year pattern (4 digits)
        if (!preg_match('/(?:19|20)\d{2}/', $title, $yearMatch)) {
            return $title;
        }

        // Get the year
        $year = $yearMatch[0];
        
        // Replace dots and underscores with spaces
        $formattedTitle = str_replace(['.', '_'], ' ', $title);
        
        // Split by spaces and find the year index
        $parts = explode($year, $formattedTitle);
        
        // Return everything before the year plus the year
        return trim($parts[0]) . ' ' . $year;
    }

    /**
     * @throws Exception
     */
    public static function load_player($data): bool
    {
        $vdetails = $data['vdetails'];
        $vquality = [];
        $vaudio = [];

        $video_play = get_video_files($vdetails,true);
        if ($vdetails['file_type'] != 'mp4'){
            $vurl = $video_play[0];
            $content_url = file_get_contents(rtrim(config('base_url'), '/').$vurl);
            if(preg_match('/360p/i', $content_url)){
                $vquality[] = '360p';
            }elseif(preg_match('/240p/i', $content_url)){
                $vquality[] = '240p';
            }elseif(preg_match('/1080p/i', $content_url)){
                $vquality[] = '1080p';
            }

            if(preg_match_all('/#EXT-X-MEDIA:TYPE=AUDIO.*?URI="(audio_\d+\.m3u8)"/i', $content_url, $matches)){
                $vaudio = array_merge($vaudio, $matches[1]);
            }
        }
        $vdetails['title'] = self::formatVideoTitle($vdetails['title']);
        // var_dump($vaudio);
        // exit();
        assign('video_files', $video_play);
        assign('v_quality', $vquality);
        assign('v_audio', $vaudio);
        assign('vdata',$vdetails);
        assign('anonymous_id', userquery::getInstance()->get_anonymous_user());
        Template(DirPath::get('player') . self::class .DIRECTORY_SEPARATOR . 'cb_video_js.html',false);
        return true;
    }

    public static function getVideoQualityFromFilePath($filepath): string
    {
        $quality = explode('-', basename($filepath));
        $quality = explode('.',end($quality));
        return $quality[0];
    }

    /**
     * @throws Exception
     */
    public static function getVideoResolutionTitleFromFilePath($filepath): string
    {
        global $myquery;
        $quality = self::getVideoQualityFromFilePath($filepath);
        return $myquery->getVideoResolutionTitleFromHeight($quality);
    }

    /**
     * @throws Exception
     * @used-by cb_video_js.html
     */
    public static function getDefaultVideo($video_files)
    {
        global $myquery;
        if (!empty($video_files) && is_array($video_files)) {
            $res = [];
            foreach ($video_files as $file) {
                $res[] = self::getVideoQualityFromFilePath($file);
            }

            $player_default_resolution = config('player_default_resolution');

            if (in_array($player_default_resolution, $res)){
                return $myquery->getVideoResolutionTitleFromHeight($player_default_resolution);
            }
            if ($player_default_resolution > max($res)) {
                return 'high';
            }
           return 'low';
        }
        return false;
    }

    /**
     * @throws Exception
     */
    function __construct(){
        $this->load_dependancies();
        $this->register_actions_play_video();
    }
}

new CB_video_js();
