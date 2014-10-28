ojsh
====
Open Journal System Harvester

**USAGE: ./ojsh.php -j journal-url [-o output-filename] [-i FLVC-institution-name] [-np]**

OJSH is a command line script for harvesting entire volumes/issues from Open Journal System instances. It works by making curl requests to specific URLs and scraping the results with regular expressions to pull out the relevant data & links, and outputs a zipped directory containing a MODS record and content file (PDF, JPG, etc.) for each article with the article ID number as the filename for both.
