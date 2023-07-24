<?php

$DEBUG = false;

function test_all_posters(){
    $movies = [
	"Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
    "Boku no pico.2018.japanese.1080p.WEBRip.AAC2.0.x264-NOGRP",
	
    ];

    foreach($movies as $movie):
	      $poster = get_movie_poster($movie); ?>
    <img src="<?=$poster?>" width="200" height="300" style="float:left;">
<?php endforeach;

}

function get_movie_poster($movie_folder){
    $thumbnail_path = "stream_videos/". $movie_folder ."/medium-poster.jpg";
    // return $thumbnail_path;
    if(!file_exists($thumbnail_path)) { 
	$thumbnail_path = get_yts_movie_poster($movie_folder);
	if(!@file_get_contents($thumbnail_path)){
	    $thumbnail_path = get_wiki_movie_poster($movie_folder);
	    if(!$thumbnail_path) {
		$thumbnail_path = "riflix_thumb.png"; // default;		
	    } else {
		cache_wiki_poster($movie_folder);
	    }
	} else {
	    cache_yts_poster($movie_folder);		    
	}
    }
    return $thumbnail_path;
}

function get_movie_name_from_folder($value){
    preg_match("/[\w|\s]+/", $value, $array);
    $name = rtrim($array[0]);
    return $name;
}

function get_yts_movie_poster($movie_folder){
    $yts_string = get_ytsmovie_poster_string($movie_folder);
    return "https://img.yts.mx/assets/images/movies/" . $yts_string . "/medium-cover.jpg";
}

function get_ytsmovie_poster_string($movie_name){
    $matches = [];
    preg_match("/\(([^\]]*)\)/", $movie_name, $matches);
    count($matches) >= 2 ? $year = $matches[1] : $year = null;
    preg_match("/[\w|\s]+/", $movie_name, $matches);
    $name = rtrim($matches[0]);
    $yts_str = $name . " " . $year;
    return str_replace(" ", "_", $yts_str);
}


function get_wiki_movie_poster($movie_folder){
    $movie_name = get_movie_name_from_folder($movie_folder);
    $searched = search_wiki($movie_name);
    if(!$searched) return false;
    $best_match = get_best_match_string($movie_name, $searched[0]);

    /* echo "Best Match - " . $best_match . "<br>"; */

    $ch = curl_init();
    $api_link = "https://en.wikipedia.org/w/api.php?action=query&prop=images&format=json&titles=" . urlencode($best_match);

    /* echo "ImageInfoLink - " . $api_link . "<br>"; */

    curl_setopt($ch, CURLOPT_URL, $api_link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    $json_res = json_decode($output, true);

    $pages = $json_res["query"]["pages"];
    $page_id = array_keys($pages)[0];
    $page = $pages[$page_id];

    if(!array_key_exists("images", $page))
	return false;

    $wiki_images = $pages[$page_id]["images"];

    /* echo "<pre>"; */
    /* var_dump($wiki_images); */
    /* echo "</pre>"; */
    
    $image_filename = get_wiki_best_match_imagefile($movie_name, $wiki_images);
    $wiki_image = get_wiki_image_url($image_filename);

    if(!$wiki_image)
	return false;

    return $wiki_image;
}


function search_wiki($search){
    $ch = curl_init();
    $api_link = "https://en.wikipedia.org/w/api.php?format=json&origin=*&action=opensearch&search=" . urlencode($search);
    curl_setopt($ch, CURLOPT_URL, $api_link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    if(!$output){
	/* if($DEBUG) { 
	   $info = curl_info($output);
	   echo "<pre>";
	   var_dump($info);
	   echo "</pre>";
	   } */
	return false;
    }
    curl_close($ch);
    $json_res = json_decode($output, true);

    /* echo "<pre>";
     * var_dump($json_res);
     * echo "</pre>"; */

    // nothing matched
    if(count($json_res[1]) == 0) return false;

    $searched_items = [];
    array_push($searched_items, $json_res[1]);
    array_push($searched_items, $json_res[3]);

    return $searched_items;
}


function get_wiki_image_url($filename){
    // curl didn't work here, don't know 
    $api_link = "https://en.wikipedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiprop=url&titles=". urlencode($filename);
    $output = file_get_contents($api_link);
    if(!$output){
	if($DEBUG) {
	    echo "<pre>";
	    var_dump($info);
	    echo "</pre>";	    
	}
	return false;
    }

    $json_res = json_decode($output, true);
    $pages = $json_res["query"]["pages"];
    $page_id = array_keys($pages)[0]; // we don't bother here either
    $image_url = $pages[$page_id]["imageinfo"][0]["url"];

    return $image_url;
}

function get_wiki_clean_filename($file){
    return preg_replace("/^(File:)|\.\w+$/", '', $file);
}

// $array contained in from json {query { pages { [pageid] { images
function get_wiki_best_match_imagefile($match, $images){
    $len = count($images);
    if($len == 0) return false;


    $best_match = $images[0]["title"];
    $leven = leven_distance($images[0]["title"], $match);

    for($i = 1; $i < $len; $i++){
	$image = $images[$i];
	$nl = leven_distance($image["title"], $match);
	if($nl < $leven) {
	    $best_match = $image["title"];
	    $leven = $nl;
	}
    }
    return $best_match;
}


// $movie_folder equivalent to movie_name,
// any movie name is extracted through it's folder
function cache_yts_poster($movie_folder){
    $poster_url = get_yts_movie_poster($movie_folder);
    $save_path = "stream_videos/" . $movie_folder . "/medium-poster.jpg";
    $poster_file = load_network_file($poster_url);
    if($poster_file == null) return false;

    save_file($save_path, $poster_file);
    return true;
}


function cache_wiki_poster($movie_folder){
    $poster_url = get_wiki_movie_poster($movie_folder);
    $save_path = "stream_videos/" . $movie_folder . "/medium-poster.jpg";
    $poster_file = load_network_file($poster_url);
    if($poster_file == null) return false;
    save_file($save_path, $poster_file);
    return true;    
}



function get_best_match_string($match, $string_array){
    $len = count($string_array);
    if($len == 0) return false;

    /* echo "<pre>"; */
    /* var_dump($match); */
    /* var_dump($string_array); */
    /* echo "</pre>";     */

    $best_match = $string_array[0];
    $leven = leven_distance($string_array[0], $match);

    for($i = 1; $i < $len; $i++){
	$str = $string_array[$i];
	$nl = leven_distance($match, $str);
	/* echo $str . " - " . $nl . " | " . "<br>"; */
	if($nl < $leven) {
	    $best_match = $str;
	    $leven = $nl;
	}
    }

    return $best_match;
}


//TODO: need to understand this more throughly
function leven_distance($str1, $str2){
    $m = [];

    $len1 = strlen($str1);
    $len2 = strlen($str2);

    // make sure $str1.len > $str2.len to use O(min($len1, $len2)) space,???
    if($str1 < $str2) {
	$ts = $str1; $str1 = $str2; $str2 = $ts;
	$tl = $len1; $len1 = $len2; $len2 = $tl;
    }

    $m[0] = range(0, $len2);

    for($i = 1; $i <= $len1; $i++){
	$m[$i][0] = $i;
	for($j = 1; $j <= $len2; $j++){
	    $cost = 1;
	    if($str1[$i-1] == $str2[$j-1])
		$cost = 0;
	    $m[$i][$j] = min3($m[$i-1][$j] + 1, $m[$i][$j-1] + 1, $m[$i-1][$j-1] + $cost);
	}
    }

    // debugging
    /* echo "<pre>";
     * echo "str1: " . $str1 . "\n" . "str2: " . $str2 . "\n";
     * for($i = 0; $i <= $len1; $i++){
       for($j = 0; $j <= $len2; $j++){
       echo $m[$i][$j] . "  ";
       }
       echo "\n";
     * }
     * echo "</pre>"; */

    return $m[$len1][$len2];
}

/* echo "leven-distance: " . leven_distance("Alive (2020)", "Alive (2020 film)") . "<br>"; */
/* echo "leven-distance: " . leven_distance("Interstellar", "Interstellar film") . "<br>"; */
/* echo "leven-distance: " . leven_distance("Nobody", "Nobody 1"); */


function min3($x, $y, $z){
    if($x < $y && $x < $z) return $x;
    if($y < $x && $y < $z) return $y;
    else return $z;
}


?>

