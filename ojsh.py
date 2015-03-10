#!/usr/bin/env python3

import os
import sys
import random
import shutil
import time
import urllib.request
import argparse
from bs4 import BeautifulSoup as bs
from lxml import etree

parser = argparse.ArgumentParser()
parser.add_argument("-o", "--output", required=True, help="Name of output file")
parser.add_argument("-b", "--baseURL", required=True, help="Base URL for the journal to be harvested")
parser.add_argument("-i", "--institution", default="FSU", help="Owning/Submitting institution")
parser.add_argument("-n", "--newest", help="Get the newest issue", action="store_true")
parser.add_argument("-p", "--pdfconvert", help="Owning/Submitting institution", action="store_true")
parser.add_argument("-d", "--debug", help="Verbose output of processing information")
args = parser.parse_args()

print("Harvesting '{0}' to {1}.tgz as {2}.".format(args.baseURL, args.output, args.institution))
if args.newest:
  print("Getting newest issue")
if args.pdfconvert:
  print("Converting images to PDF")
if args.debug:
  print("Running in debug mode")
print("--------------------------------------------------------\n")

shortList = {
    "heal": "http://journals.fcla.edu/heal",
    "owl": "http://journals.fcla.edu/owl",
    "jafl": "http://journals.fcla.edu/jafl",
    "ital": "http://ejournals.bc.edu/ojs/index.php/ital",
}
if args.baseURL in shortList:
  url = shortList[args.baseURL]
else:
  url = args.baseURL
archive = bs(urllib.request.urlopen(url + "/issue/archive").read())
issues = archive.find_all("div", {"id": "issue"})
issue_menu = []
for issue in issues:
  issueURL = issue.h4.a.get('href')
  issueTitle = issue.h4.a.contents[0]
  issue_menu.append({'url': issueURL, 'title': issueTitle})
if args.newest:
  harvest = issue_menu[0]
else:
  for index, issue in enumerate(issue_menu):
    print("{0}: {1} ({2})".format(index, issue['title'], issue['url']))
  harvest = issue_menu[int(input("Select an issue to harvest: "))]
print("\nYou selected '{0}' at {1}, commencing harvest.\n".format(harvest['title'], harvest['url']))

toc = bs(urllib.request.urlopen(harvest['url'] + "/showToc").read())
articles = toc.find_all("table", {"class": "tocArticle"})
metadata = []
for index, article in enumerate(articles):
  articleTitle = article.find("td", {"class": "tocTitle"}).a.contents[0]
  articleAuthor = article.find("td", {"class": "tocAuthors"}).contents[0].replace('\t', '').replace('\n', '').replace(u'\xa0', u'unspecified')
  articleFileType = article.find("td", {"class": "tocGalleys"}).a.contents[0]
  articleFileURL = article.find("td", {"class": "tocGalleys"}).a.get("href").replace('view', 'download')
  metadata.append({'title': articleTitle, 'authors': articleAuthor, 'fileType': articleFileType, 'fileURL': articleFileURL})

if os.path.exists('tmp'): shutil.rmtree('tmp')
os.mkdir('tmp')
issue_id = random.randint(10000000,99999999)
for record in metadata:

  article_id = random.randint(10000000,99999999)
  iid = "FSU_{0}_{1}".format(issue_id, article_id)
  #print("'{0}' by {1} ({2}) at {3}".format(record['title'], record['authors'], record['fileType'], record['fileURL']))
  print('.',end="",flush=True)

  remote_pdf = urllib.request.urlopen(record['fileURL'])
  local_pdf = open('tmp/{}.pdf'.format(article_id), 'wb')
  local_pdf.write(remote_pdf.read())
  remote_pdf.close()
  local_pdf.close()
  print('.',end="",flush=True)

  header = '<?xml version="1.0" encoding="UTF-8"?>'
  root = etree.Element('root')
  xml = open('tmp/{}.xml'.format(article_id), 'w')
  


