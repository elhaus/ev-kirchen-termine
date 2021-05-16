<?php

///////////////////////////
//
//  YouTube Live Check
//
///////////////////////////


function get_data_from_url(string $url, int $max_cache_time = 120, string $filebasename = NULL) {

    $error = false;

    # $filename = "./data/".urlencode($url);
    if(empty($filebasename)) {
        $filebasename = urlencode(substr($url, 9, 5).substr($url, -10))."_".md5($url);
    }

    $filename = plugin_dir_path( dirname(__FILE__) )."data/$filebasename.json";
    $filename_latest = plugin_dir_path( dirname(__FILE__) )."data/latest_$filebasename.json";

    if(file_exists($filename)) {
    	if( date("Y-m-d H:i:s", strtotime("-$max_cache_time minutes")) <  date("Y-m-d H:i:s", filemtime($filename)) ) {
            return file_get_contents($filename);
    	}
    }
    if(file_exists($filename_latest)) {
    	if( date("Y-m-d H:i:s", strtotime("-1 minutes")) <  date("Y-m-d H:i:s", filemtime($filename_latest)) ) {
            return file_get_contents($filename);
    	}
    }

    $content = "";
    try {
        $content = file_get_contents($url);

        $cache_file = fopen($filename_latest, 'w');
        fwrite($cache_file, $content);
        fclose($cache_file);

    }
    catch (Exception $e) {
        $error = true;
    }

    if(empty($content))
        $error = true;

    if($error)
        if(file_exists($filename))
            return file_get_contents($filename);
        else
            return "[]";

    $cache_file = fopen($filename, 'w');
	fwrite($cache_file, $content);
	fclose($cache_file);

    return $content;

}


function get_current_streaming_link() {

    $parameter["part"] = "snippet";
    $parameter["channelId"] = get_option("ev_kirchen_termine_yt_channel");
    $parameter["type"] = "video";
    $parameter["key"] = get_option("ev_kirchen_termine_yt_api_key");
    $parameter["eventType"] = "live";

    $url = 'https://www.googleapis.com/youtube/v3/search?'.http_build_query($parameter);

    if(!empty($parameter["key"]) && !empty($parameter["channelId"])) {

        //read json file from url in php
        $readJSONFile = get_data_from_url($url, 10, "yt_live_push");

        //convert json to array in php
        $videos = json_decode($readJSONFile, TRUE);

        if(!empty($videos["items"][0]["id"]["videoId"]))
            return "https://youtu.be/".$videos["items"][0]["id"]["videoId"];

    }

    return false;

}

if(get_current_streaming_link()) {

    $plugin_url = plugin_dir_url( __FILE__ );

    wp_enqueue_style( 'ev_kirchen_termine_yt_live_css', $plugin_url . 'yt_live.css' );

    wp_enqueue_script( 'ev_kirchen_termine_yt_live_js', $plugin_url . 'yt_live.js');

    $data = 'var youtube_link = "'.get_current_streaming_link().'";';
    wp_add_inline_script( 'ev_kirchen_termine_yt_live_js', $data, 'before' );

}
