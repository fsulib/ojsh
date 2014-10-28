#!/usr/bin/env php
<?php

$options = getopt("j:o:i:np");

if (!isset($options['j'])) {
  echo "ERROR: No journal specified.\n";
  echo "USAGE: ./ojsh.php -j baseurl [-n]\n";
  exit(0);
} else {
  $journal = $options['j'];
}

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

if (isset($options['o'])) {
  $output = $options['o'];
} else {
  date_default_timezone_set('EST5EDT');
  $output = date('Y-m-d_H-i-s');
}

if (file_exists("./output")){
  shell_exec("rm -rf output");
}
shell_exec("mkdir output");

// Set FLVC custom extensions for owning/submitting institution
if (isset($options['i'])) {
  $inst = $options['i'];
}

if (isset($options['n'])) {
  $newest = TRUE;
} else {
  $newest = FALSE;
}

if (isset($options['p'])) {
  $convert2pdf = TRUE;
} else {
  $convert2pdf = FALSE;
}

function get_clean_html($url) {  
  $urlcurl = curl_init();
  curl_setopt($urlcurl, CURLOPT_URL, $url);
  curl_setopt($urlcurl, CURLOPT_RETURNTRANSFER, TRUE);
  $urltext = curl_exec($urlcurl);
  $tidytext = tidy_parse_string($urltext);
  tidy_clean_repair($tidytext);
  $output = trim($tidytext);
  $output = preg_replace('/\n/', ' ', $output);
  $output = preg_replace('/>\s</', '><', $output);
  $output = preg_replace('/=\s"/', '="', $output);
  $output = html_entity_decode($output, ENT_COMPAT, 'ISO-8859-1');
  return $output;
}

$iidbasenum = rand(1000000000, 9999999999);

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

foreach($tocresults[0] as $result) {
  preg_match('/<td class="tocTitle">(<a href=".*?">)?(.*?)(<\/a>)?<\/td>/', $result, $titlearray);
  $title = str_replace('"', '', $titlearray[2]);
  preg_match('/<td class="tocAuthors">(.*?)<\/td>/', $result, $authorarray);
  $author = $authorarray[1];
  preg_match('/<td class="tocGalleys"><a href="(.*)" class="file">(.*)<\/a>/', $result, $filearray);
  $fileurl = $filearray[1];
  $filetype = $filearray[2];
  preg_match('/(\d)+/', $fileurl, $fileidarray);
  $fileid = $fileidarray[0];
  $article = array(
    "id" => $fileid,
    "title" => $title,
    "author" => $author,
    "url" => str_replace("view", "download", $fileurl),
    "ext" => strtolower($filetype),
  );

  $mods_template = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods 
xmlns="http://www.loc.gov/mods/v3" 
xmlns:flvc="info:flvc/manifest/v1">
</mods>
XML;
  $mods = new SimpleXMLElement($mods_template);

  if (isset($inst)) {
    $iid = "{$inst}_{$iidbasenum}_{$article['id']}";
    $mods->addChild('identifier', $iid);
    $mods->identifier->addAttribute('type', 'IID');
    $mods->addChild('extension');
    $flvc = $mods->extension->addChild('flvc', '', "info:flvc/manifest/v1");
    $flvc->addChild('owningInstitution', $inst, "info:flvc/manifest/v1");
    $flvc->addChild('submittingInstitution', $inst, "info:flvc/manifest/v1");
  }

  $mods->addChild('titleInfo');
  $title_re = "/^A |^The /";
  if (preg_match($title_re, $article['title'])) {
    preg_match_all($title_re, $article['title'], $narray);
    $nonsort = trim($narray[0][0]);
    $tarray = preg_split($title_re, $article['title']);
    $stitle = $tarray[1];
    $mods->titleInfo->addChild('nonSort', $nonsort);
    $mods->titleInfo->addChild('title', $stitle);
  } else {
    $mods->titleInfo->addChild('title', $title);
  }   

  $mods->addChild('name');
  $mods->name->addAttribute('type', 'personal');
  $mods->name->addChild('namePart', $article['author']);
  $mods->addChild('typeOfResource', 'text');
  $mods->addChild('genre', 'academic journal');

  $modsfilename = "./output/".$article['id'].".xml";
  file_put_contents($modsfilename, $mods->asXML());
  shell_exec("xmllint --format $modsfilename --output $modsfilename");

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $article['url']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  $obj = curl_exec($ch);

  $objfilename = "./output/".$article['id'].".".$article['ext'];
  file_put_contents($objfilename, $obj);

  if ($convert2pdf) {
    if ($article['ext'] != 'pdf') {
      shell_exec("convert {$objfilename} ./output/{$article['id']}.pdf");
      shell_exec("rm {$objfilename}");
    }
  }

  echo "{$article['id']} harvested.\n";
}

shell_exec("cd ./output; zip ../$output.zip *");
shell_exec("rm -rf ./output")

?>
