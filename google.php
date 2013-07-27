<?php
$keyword = (isset($_POST["k"])) ? $_POST["k"] : '';
$domain  = (isset($_POST["d"])) ? $_POST["d"] : '';

if(empty($keyword) || empty($domain)){
	echo 'Please enter your domain and keyword(s) to search';
	exit();
}

// try to find this domain name (make sure it is a valid domain name)
if(!dns_get_record ($domain)){
	echo 'We could not find the domain name '.$domain.' please verify the name.';
	exit();	
}

// this might take a while depending on how many pages we search
set_time_limit(120);

require_once('library/simple_html_dom.php');

/*  stupid godaddy vps server is configured wrong...so we need to use the ip address
	of google instead of the domain name :(
*/
$ip = '';

$d = dns_get_record('google.com');
foreach($d as $entry){
	if(isset($entry["ip"])){
		$ip = $entry["ip"];	
		break;
	}
}

if(empty($ip)){
	echo 'Unable to reach google at this time, please try again later.';
	exit();	
}
$base_url = "http://".$ip."/search?q=";

/* this doesn't really need to be an array right now, but it is intended for future use
   that we might be searching multiple keywords some day */
$keywords = array($keyword);

$pages_to_go_through = 10;
$current_record = 1;

$html = new simple_html_dom(); 

$search_results = '';
$how_many_search_results = NULL;

$keyword_found = false;

foreach($keywords as $keyword){
	$keyword_found = false;
	$how_many_search_results = NULL;
	$current_record = 1;
	for($i=0;$i<$pages_to_go_through;$i++){
		$html->clear();
		$html->load_file($base_url.urlencode($keyword).'&start='.$i*10);
		$results = $html->find('#resultStats',0);

		$how_many_search_results = (is_null($how_many_search_results)) ? $results->innertext : $how_many_search_results;
		
		// results
		$results = $html->find('.r');

		foreach($results as $record){
			if(strpos($record,$domain) !== false){
				$keyword_found = true;
				break 2; // get out of both loops	
			}
			$current_record++;
		}
		sleep(5);
	}
	$search_results .= '<tr><td>'.$keyword.'</td><td>'.$how_many_search_results.'</td>';
	$search_results .= (!$keyword_found) ? '<td>N/A</td><td>N/A</td>' : '<td>'.$current_record.'</td><td><a target="_blank" href="'.$base_url.urlencode($keyword).'&start='.(($i+1)*10-10).'" class="jq-dialog" title="View on Google" data-keyword="'.$keyword.'" data-page="'.($i+1).'">'.($i+1).'</a></td>';
	$search_results .= '</tr>';
}

echo '<fieldset><legend>Your search results are listed below. If your website was not found you will see <b>N/A</b> under Google Spot and Search Result Page</legend>';
echo '<table width="100%">
<tr><th>Keyword Searched</th><th># of Possible Results</th><th>Google Spot</th><th>Search Result Page</th></tr>';
echo $search_results;
echo '</table>';
echo '</fieldset>';
exit();
?>