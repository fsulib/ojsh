#!/usr/bin/env php
<?php

$options = getopt("j:n");

if (!isset($options['j'])) {
  echo "ERROR: No journal specified.\n";
  echo "USAGE: ./ojsh.php -j baseurl [-n]\n";
  exit(0);
} else {
  $journal = $options['j'];
}

if (isset($options['n'])) {
  $newest = TRUE;
} else {
  $newest = FALSE;
}

//Set short names for common journals
switch($journal) {
  case heal:
    $journal = "http://journals.fcla.edu/heal";
    break;
  case ital:
    $journal = "http://ejournals.bc.edu/ojs/index.php/ital";
    break;
  case post:
    $journal = "http://www.equinoxpub.com/journals/index.php/POST";
}

function get_clean_html($url) {  
  $urlcurl = curl_init();
  curl_setopt($urlcurl, CURLOPT_URL, $url);
  curl_setopt($urlcurl, CURLOPT_RETURNTRANSFER, TRUE);
  $urltext = curl_exec($urlcurl);
  $tidytext = tidy_parse_string($urltext);
  tidy_clean_repair($tidytext);
  $output = trim($tidytext);
  $output = preg_replace('/\n/', '', $output);
  return $output;
}

$archurl = "$journal/issue/archive";
$archtext = get_clean_html($archurl);
preg_match_all('/<h4><a\ href="(.+?)">(.+?)<\/a\><\/h4>/', $archtext, $archresults);

if (!$newest) { 
  $i = 1;
  foreach($archresults[2] as $issue) {
    echo "$i. $issue\n";
    $i++;
  }
  echo "Enter an ID number: ";
  $answer = (int)fgets(STDIN);
  $issueurl = $archresults[1][$answer - 1];
} else {
  $issueurl = $archresults[1][0];
}

$tocurl = "$issueurl/showToc";
$toctext = get_clean_html($tocurl);
preg_match_all('/<table\ class="tocArticle".*?<\/table>/', $toctext, $tocresults);

//echo $toctext;
print_r($tocresults);




/*
tocset = re.findall('<table class="tocArticle" width="100%">.*?</table>', tocdata)
  articlepattern = '<table class="tocArticle" width="100%"><tr valign="top"><td class="tocTitle">(<a href="(.*)">)?(.+?)(</a>)?</td><td class="tocGalleys"><a href="(.+?)" class="file">(.+?)</a></td></tr><tr><td class="tocAuthors">(.+?)</td><td class="tocPages"></td></tr></table>'
  articlepattern = '<table class="tocArticle" width="100%"><tr valign="top"><td class="tocTitle">(<a href="(.*)">)?(.+?)(</a>)?</td><td class="tocGalleys"><a href="(.+?)" class="file">PDF</a></td></tr><tr><td class="tocAuthors">(.+?)</td><td class="tocPages"></td></tr></table>'
 pdf url string: http://journals.fcla.edu/heal/article/download/82860/79774/pdf
 */
?>
