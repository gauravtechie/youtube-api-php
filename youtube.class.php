<?php
/**
 * Gaurav Mishra | http://phpcollection.com/
 * MSN: gauravtechie@hotmail.com
 * Email: gaurav@hostcurry.com
 * Started: 02/03/2011 19:44 (IST) 
 * Tested: Yes
 * PHP 4/5: 5
 * No warranty is given to code used
 */

/**
 * Youtube API
 * 
 * @package   Youtube API
 * @url       http://phpcollecttion.com/classes/youtube
 * @desc      Package which allows easy interaction with the Youtube v2 Data API 
 * @author    Gaurav Mishra
 * @copyright 2011
 * @version   0.0.1
 * @access    public
 * @license   GPL
 */

/**
 * Todo:-
 */

class Youtube
{
    /** Your API KEY */
    public $key;

    /** URI to the API , used for making REST call. */
    var $feed = "http://gdata.youtube.com/feeds/api/";

    /**
     * Youtube::__construct()
     * 
     * @param  mixed  $apikey   Your developer API Key
     * @param  bool   $check    Should we run checks to make sure all the extensions are loaded?
     * @return void
     */


    public function __construct($apikey = null, $partner_id = null, $check = true)
    {
        /** Construct class, runs on initialization **/
        if ($check) {
            /** If the check parametre is set **/
            if (!extension_loaded('curl') /** Is the cURL extension loaded? **/ ) {
                throw new Exception('You don\'t have cURL loaded.');
                /** If not, throw an exception **/
            }
            if (!extension_loaded('SimpleXML') /** Is the SimpleXML extension loaded? **/ ) {
                throw new Exception('You don\'t have the SimpleXML lib loaded.');
                /** If not, throw an exception **/
            }
        }


    }
    function check_youtube_db($profile_id, $watchUrl){
        global $db;
        $q = "SELECT * FROM `youtube_data` WHERE `profile_ID` = '$profile_id' AND `watchUrl` = '$watchUrl'";
        if($db->get_row($q) == null){
            return false;
        }else{
            return true;
        }
    }
	function top_videos($profile_id) {
		global $db;
		$q = "SELECT * from `youtube_data` WHERE `profile_ID` = '$profile_id' ORDER by `viewCount` DESC LIMIT 0, 7";
		//echo $q;
		$data = $db->get_results($q, ARRAY_A);
		return $data;
	}
	function get_youtube_data($profile_id, $to , $from) {
		global $db;
		$query = "SELECT * from `youtube_data` WHERE `profile_id` = '$profile_id' AND `created` BETWEEN '$from' AND '$to'";
		$data = $db->get_results($query, ARRAY_A);
		return $data;
	}
    /**
     * Youtube::search_videos()
     * 
     * @param  mixed  $   
     * @return array  $response		Array of data of results 
     */

    function search_videos($query, $orderby = null, $time = null, $start = 0, $max = 0)
    {
        
        $url = "";
        if ($start != null) {
            $url .= "&start-index=$start";
        }
        if ($max != null) {
            $url .= "&max-results=$max";
        }
        if ($orderby != null) {
            $url .= "&orderby=$orderby";
        }
        if ($time != null) {
            $url .= "&time=$time";
        }
	$query = urlencode($query);
        /** URI used for making REST call. Each Web Service uses a unique URL.*/
        $request = $this->feed . "videos?q=$query" . $url;
        echo "Link is being prepared for the keyword ".$query."\n";
		
        /** Get the response via CURL */
        $response = $this->get($request);
        //print_r($response);
        
		/** search data from the xml result*/
        $video_arrray = $this->get_search_data($response);
	//	print_r($video_array);
	//	exit();
        /** Arrayed data of videos*/
        return $video_arrray;
    }

function search_channel($query, $orderby = null, $time = null, $start = 0, $max = 0)
    {
        $url = "";
        if ($start != null) {
            $url .= "&start-index=$start";
        }
        if ($max != null) {
            $url .= "&max-results=$max";
        }
        if ($orderby != null) {
            $url .= "&orderby=$orderby";
        }
        if ($time != null) {
            $url .= "&time=$time";
        }
		$query = urlencode($query);
        /** URI used for making REST call. Each Web Service uses a unique URL.*/
        $request = $this->feed . "channels?q=$query" . $url;
        /** Get the response via CURL */
        $response = $this->get($request);
        /** search data from the xml result*/
        $video_arrray = $this->get_search_data($response);

        /** Arrayed data of videos*/
        return $video_arrray;
    }


    /**
     * Youtube::video_data()
     * 
     * @param  void   
     * @return array  $data		Array of data of results 
     */

    function get_video_insight_by_id($video_id)
    {
        /** URI used for making REST call. Each Web Service uses a unique URL.*/
        echo "video insight for $video_id is being called.\n";
        $request = $this->feed . "videos/$video_id?v=2";
        //print_r($request);
        /** Get the response via CURL */
        $response = $this->get($request);
        /** Arrayed data of video*/
        return $response;
    }

    function get_video_insight_by_url($request)
    {
	//echo $request;
        /** Get the response via CURL */
        $response = $this->get($request);
        //print_r($response);
        $response_data = $this->parseVideoEntry($response);
        /** Arrayed data of video*/
        return $response_data;
    }


    /**
     * Youtube::get()
     * 
     * @param  mixed  $request 	request data from the youtube gdata api !
     * @return array  $data		arrayed data from the request 
     */


    function get($url, $username = '', $password = '')
    {
	
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $xml = curl_exec($ch);
        $Headers = curl_getinfo($ch);
        curl_close($ch);
        //print_r($Headers);
        if ($Headers['http_code'] == 200) {
	
        //    $xml = file_get_contents($url);
            $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            //print_r($data);
            return $data;

        } else {
            return null;
        }
    }

    function get_search_data($xml)
    {
        $i = 0;
        /** Traverse XML tree and save desired values from child nodes */
        //print_r($xml->entry);
       
        if($xml->entry != ''){
        foreach ($xml->entry as $result) {
                //print_r($result);
            $data[$i]['id'] = (string )$result->id;
            $data[$i]['published'] = (string )$result->published;
            $data[$i]['updated'] = (string )$result->updated;
            $data[$i]['title'] = (string )$result->title;
            $data[$i]['content'] = (string )$result->content;
            $data[$i]['author'] = (string )$result->author->name;
            $data[$i]['author_uri'] = (string )$result->author->uri;
            $data[$i]['video_link'] = (string )$result->link[0]->attributes()->href[0];
            echo "A video with id=".$result->id." is being inserted in the Array\n";
            $i++;
        }
        }else{
            $data = array();
        }
        //print_r($data);
        return $data;
    }

    function parseVideoEntry($entry)
    {
        $obj = new stdClass;

        /** get nodes in media: namespace for media information */
        $media = $entry->children('http://search.yahoo.com/mrss/');
        //print_r($entry->published);
        //print_r($media->group->published);
        $obj->title = $media->group->title;
        $obj->description = $media->group->description;
        $obj->published = strtotime($entry->published);
        /** get video player URL */
        $attrs = $media->group->player->attributes();
        $obj->watchURL = $attrs['url'];

        // get video thumbnail
        $attrs = $media->group->thumbnail[0]->attributes();
        $obj->thumbnailURL = $attrs['url'];

        // get <yt:duration> node for video length
        $yt = $media->children('http://gdata.youtube.com/schemas/2007');
        $attrs = $yt->duration->attributes();
        $obj->length = $attrs['seconds'];

        // get <yt:stats> node for viewer statistics
        $yt = $entry->children('http://gdata.youtube.com/schemas/2007');
        $attrs = $yt->statistics->attributes();
        $obj->viewCount = $attrs['viewCount'];

        // get <gd:rating> node for video ratings
        $gd = $entry->children('http://schemas.google.com/g/2005');
        if ($gd->rating) {
            $attrs = $gd->rating->attributes();
            $obj->rating = $attrs['average'];
        } else {
            $obj->rating = 0;
        }

        // get <gd:comments> node for video comments
        $gd = $entry->children('http://schemas.google.com/g/2005');
        if ($gd->comments->feedLink) {
            $attrs = $gd->comments->feedLink->attributes();
            $obj->commentsURL = $attrs['href'];
            $obj->commentsCount = $attrs['countHint'];
        }

        // get feed URL for video responses
        $entry->registerXPathNamespace('feed', 'http://www.w3.org/2005/Atom');
        $nodeset = $entry->xpath("feed:link[@rel='http://gdata.youtube.com/schemas/
      2007#video.responses']");
        if (count($nodeset) > 0) {
            $obj->responsesURL = $nodeset[0]['href'];
        }

        // get feed URL for related videos
        $entry->registerXPathNamespace('feed', 'http://www.w3.org/2005/Atom');
        $nodeset = $entry->xpath("feed:link[@rel='http://gdata.youtube.com/schemas/
      2007#video.related']");
        if (count($nodeset) > 0) {
            $obj->relatedURL = $nodeset[0]['href'];
        }

        // return object to caller
        return $obj;
    }


}
?>