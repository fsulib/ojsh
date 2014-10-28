# ojsh
Open Journal System Harvester

**USAGE: ./ojsh.php -j journal-url [-o output-filename] [-i FLVC-institution-name] [-np]**

## About ojsh
ojsh is a command line script for harvesting entire issues from Open Journal System instances. Feed it a base url for an OJS site (for instance the journal HEAL has the base url 'http://journals.fcla.edu/heal/'), and ojsh will provide you with a list of available issues from the 'ARCHIVES' page (for instance, 'http://journals.fcla.edu/heal/issue/archive'). After selecting an issue, ojsh will then output a zipped file containing a MODS record and content file (PDF, JPG, etc.) for every article in the issue selected.

## How it works
ojsh retrieves content by modifying urls, making curl requests and using regular expressions on the HTML it gets back to pull out links and metadata. It first modifies the base url to make a curl request to the 'ARCHIVES' page of an OJS site and scrapes the resuls to present the user with a list of issue titles. Once the user has selected an issue, it makes another curl request to the table of contents page for that issue and scrapes the results to pull out the title, author, and OJS ID (the internal number OJS uses to name each article) for each article, as well as a link to the content file for said article. Using this data, ojsh can create a minimal MODS record for each article and save it alongside the content file of the article itself, with both files named using the article's OJS ID.

## Command line options
### -j
Harvests are configured via command line options passed to ojsh, with the only required option being '-j' followed by the base url for the journal you want to harvest, for instance:
```bash
./ojsh.php -j http://journals.fcla.edu/heal/
```
This command would present the user with a numbered list of available issues. After selecting an issue, all output would be saved as a timestamped zip file in the user's current working directory.

### -o
The '-o' option allows the user to name the zipped output file instead of having it automatically be a timestamp. Please note that '.zip' is automatically appended to the name passed to the '-o' option, so specifying '-o output.zip' on invocation will result in an output file named 'output.zip.zip'. The '-o' option should be used in the following way:
```bash
./ojsh.php -j http://journals.fcla.edu/heal/ -o testoutputname
```
This would result in an output file named 'testoutputname.zip'.

### -i
The '-i' option is specifically for FLVC instituitions, and is used to pass a 3 character institution ID to ojsh in order to generate FLVC specific extensions on the resulting MODS records, such 'owningInstitution', 'submittingInstitution', and an FLVC-style IID identifier. This is purely optional, and if the '-i' is not present all FLVC specific info will be left out of the resulting MODS records. Use the '-i' option in the following way:
```bash
./ojsh.php -j http://journals.fcla.edu/heal/ -i FSU
```

### -n
The '-n' option takes no arguments and simply specifies that the user wants the newest issue available from the OJS instance (at the top of the 'ARCHIVES' page). Using this option makes ojsh noninteractive so that it can be fired off as part of a larger shell script.

### -p
The '-p' option tells OJS to convert all non-PDF content files (such as images) into PDFs using `convert`, an ImageMagick command line utility. This will only work if you have ImageMagic utilities installed on your machine.

### Putting it all together
If you want to archive the newest issue of the HEAL journal, add FLVC specific extensions to the metadata as Florida State University, convert all the content to PDF files and name the output 'foo.zip', you would enter the following command:
```bash
./ojsh.php -j http://journals.fcla.edu/heal/ -o foo -i FSU -np
```

## Adding 'short' journal names
Since you will most likely be harvesting a few individual journals frequently, ojsh has a switch statement [starting on line 14](https://github.com/fsulib/ojsh/blob/master/ojsh.php#L14)
