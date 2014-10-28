# ojsh
Open Journal System Harvester

**USAGE: ./ojsh.php -j journal-url [-o output-filename] [-i FLVC-institution-name] [-np]**

## About ojsh
ojsh is a command line script for harvesting entire issues from Open Journal System instances. Feed it a base url for an OJS site (for instance the journal HEAL has the base url 'http://journals.fcla.edu/heal/'), and ojsh will provide you with a list of available issues from the 'ARCHIVES' page (for instance, 'http://journals.fcla.edu/heal/issue/archive'). After selecting an issue, ojsh will then output a zipped file containing a MODS record and content file (PDF, JPG, etc.) for every article in the issue selected.

## How it works
ojsh retrieves content by modifying urls, making curl requests and using regular expressions on the HTML it gets back to pull out links and metadata. It first modifies the base url to make a curl request to the 'ARCHIVES' page of an OJS site and scrapes the resuls to present the user with a list of issue titles. Once the user has selected an issue, it makes another curl request to the table of contents page for that issue and scrapes the results to pull out the title, author, and OJS ID (the internal number OJS uses to name each article) for each article, as well as a link to the content file for said article. Using this data, ojsh can create a minimal MODS record for each article and save it alongside the content file of the article itself, with both files named using the article's OJS ID.

## Command line options
Harvests are configured via command line options passed to ojsh, with the only required option being '-j' followed by the base url for the journal you want to harvest, for instance:
```bash
./ojsh.php -j http://journals.fcla.edu/heal/
```


