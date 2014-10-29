#!/usr/bin/env php
<?php

// Establish all valid options
$options = getopt("j:o:i:npd");

// User must specify a journal URL (or short name)
if (!isset($options['j'])) {
  echo "ERROR: No journal specified.\n";
  echo "USAGE: ./ojsh.php -j baseurl [-n]\n";
  exit(0);
} else {
  $journal = $options['j'];
}

// Establish short names for journals frequently harvested
// To add a new short name, just copy a case statement
// and swap out the name and URL of the journal
// Uncomment lines 28-30 and update the text to make your own
switch($journal) {
  case heal:
    $journal = "http://journals.fcla.edu/heal";
    break;
  case ital:
    $journal = "http://ejournals.bc.edu/ojs/index.php/ital";
    break;
  /*
  case shortname:
    $journal = "http://example.com/your/base/url";
    break;
  */
}

// Allow user to name output file
// If not specified, use a timestamp
if (isset($options['o'])) {
  $output = $options['o'];
} else {
  date_default_timezone_set('EST5EDT');
  $output = date('Y-m-d_H-i-s');
}

// Use ./output as temp storage of downloaded items
if (file_exists("./output")){
  shell_exec("rm -rf output");
}

// Set FLVC custom extensions for owning/submitting institution
if (isset($options['i'])) {
  $inst = $options['i'];
}

// -n triggers automatic harvesting of newest issue
// This skips the part where it asks the user what issue they want
if (isset($options['n'])) {
  $newest = TRUE;
} else {
  $newest = FALSE;
}

// -p Triggers conversion of all non-PDF content files to PDFs
if (isset($options['p'])) {
  $convert2pdf = TRUE;
} else {
  $convert2pdf = FALSE;
}

// Secret debugging option -d
if (isset($options['d'])) {
  $debug = TRUE;
} else {
  $debug = FALSE;
}

if (!$debug) {
  shell_exec("mkdir output");   
}

// Fire off a curl request and handle encoding issues on the result
function get_clean_html($url) {  
  $urlcurl = curl_init();
  curl_setopt($urlcurl, CURLOPT_URL, $url);
  curl_setopt($urlcurl, CURLOPT_RETURNTRANSFER, TRUE);
  $urltext = curl_exec($urlcurl);
  $tidytext = tidy_parse_string($urltext);
  tidy_clean_repair($tidytext);
  $output = trim(str_replace("&acirc;&euro;&trade;", "'", $tidytext));
  $output = preg_replace('/\n/', ' ', $output);
  $output = preg_replace('/>\s</', '><', $output);
  $output = preg_replace('/=\s"/', '="', $output);
  $output = html_entity_decode($output, ENT_COMPAT, 'ISO-8859-1');
  $output = preg_replace('/&/', 'and', $output);
  if ($debug) {
    echo "$output\n";
  }
  return $output;
}

// Set a base number used in the IID
// This only matters if the -i option is invoked
$iidbasenum = rand(1000000000, 9999999999);

// Get the 'ARCHIVES' page from OJS instance and put all issues in array $archresults
$archurl = "$journal/issue/archive";
$archtext = get_clean_html($archurl);
preg_match_all('/<h4><a\ href="(.+?)">(.+?)<\/a\><\/h4>/', $archtext, $archresults);

// If -n, get first result 
// Otherwise, ask user which one they want
if (!$newest) { 
  $i = 1;
  foreach($archresults[2] as $issue) {
    echo "$i. $issue\n";
    $i++;
  }
  echo "Select an issue: ";
  $answer = (int)fgets(STDIN);
  $issueurl = $archresults[1][$answer - 1];
} else {
  $issueurl = $archresults[1][0];
}

// Get the table of contents from issue selected and put all articles in array $tocresults
$tocurl = "$issueurl/showToc";
$toctext = get_clean_html($tocurl);
preg_match_all('/<table\ class="tocArticle".*?<\/table>/', $toctext, $tocresults);

// For every article found in $tocresults, run a bunch of processing
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

  if ($debug) {
    print_r($article);
  } else {
    // Begin building MODS record for article
    $mods_template = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods 
xmlns="http://www.loc.gov/mods/v3" 
xmlns:flvc="info:flvc/manifest/v1">
</mods>
XML;
    $mods = new SimpleXMLElement($mods_template);

    // FLVC specific processing triggered by -i option
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

    // Write article to temp storage and format results with xmllint
    $modsfilename = "./output/".$article['id'].".xml";
    file_put_contents($modsfilename, $mods->asXML());
    shell_exec("xmllint --format $modsfilename --output $modsfilename");

    // Get the content file for the article and write to temp storage
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $article['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $obj = curl_exec($ch);
    $objfilename = "./output/".$article['id'].".".$article['ext'];
    file_put_contents($objfilename, $obj);

    // If -p is triggered, use ImageMagick `convert` to switch file to PDF
    if ($convert2pdf) {
      if ($article['ext'] != 'pdf') {
        shell_exec("convert {$objfilename} ./output/{$article['id']}.pdf");
        shell_exec("rm {$objfilename}");
      }
    }

  // Let user know that the script is still busy
  echo ".";

  }
}

if ($debug) {
  exit(0);
} else {

  echo "\n";

  // Zip up results and kill temp storage
  shell_exec("cd ./output; zip ../$output.zip *");
  shell_exec("rm -rf ./output");

}

?>
