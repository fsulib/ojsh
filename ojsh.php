#!/usr/bin/env php
<?php

$options = getopt("j:n");

if (!isset($options['j'])) {
  echo "ERROR: No journal specified.\n";
  echo "USAGE: ./ojsh.php -j baseurl [-n]\n";
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
  $output = trim(preg_replace('/\s+/', ' ', $tidytext));
  $output = preg_replace('/>\s+</', '><', $output);
  return $output;
}

$archurl = "$journal/issue/archive";
$archtext = get_clean_html($archurl);


/*
  selecturl = re.search('<h4><a href="(.+?)">(.+?)</a></h4>', archivedata).group(1)
  archiveset = re.findall("<h4><a href=.*?</a></h4>", archivedata)
    link = re.search('<h4><a href="(.+?)">(.+?)</a></h4>', match)
tocurl = selecturl + "/showToc"
tocset = re.findall('<table class="tocArticle" width="100%">.*?</table>', tocdata)
  articlepattern = '<table class="tocArticle" width="100%"><tr valign="top"><td class="tocTitle">(<a href="(.*)">)?(.+?)(</a>)?</td><td class="tocGalleys"><a href="(.+?)" class="file">(.+?)</a></td></tr><tr><td class="tocAuthors">(.+?)</td><td class="tocPages"></td></tr></table>'
  articlepattern = '<table class="tocArticle" width="100%"><tr valign="top"><td class="tocTitle">(<a href="(.*)">)?(.+?)(</a>)?</td><td class="tocGalleys"><a href="(.+?)" class="file">PDF</a></td></tr><tr><td class="tocAuthors">(.+?)</td><td class="tocPages"></td></tr></table>'
 pdf url string: http://journals.fcla.edu/heal/article/download/82860/79774/pdf
*/
?>
