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
parser.add_argument("-d", "--debug", help="Verbose output of processing information")
args = parser.parse_args()

print("Harvesting '{0}' to {1}.tgz as {2}.".format(args.baseURL, args.output, args.institution))
if args.newest:
  print("Getting newest issue")
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
  articleTitleChunk = article.find("td", {"class": "tocTitle"})
  if len(list(articleTitleChunk.descendants)) == 2:
    articleTitle = articleTitleChunk.a.contents[0]
  else:
    articleTitle = articleTitleChunk.contents[0]
  articleAuthor = article.find("td", {"class": "tocAuthors"}).contents[0].replace('\t', '').replace('\n', '').replace(u'\xa0', u'unspecified').split(",")
  articleFileType = article.find("td", {"class": "tocGalleys"}).a.contents[0]
  articleFileURL = article.find("td", {"class": "tocGalleys"}).a.get("href").replace('view', 'download')
  metadata.append({'title': articleTitle, 'authors': articleAuthor, 'fileType': articleFileType, 'fileURL': articleFileURL})

if os.path.exists('tmp'): shutil.rmtree('tmp')
os.mkdir('tmp')
issue_id = random.randint(10000000,99999999)
jpegs = []
for record in metadata:
  print("'{0}' by {1} ({2}) at {3}".format(record['title'], record['authors'], record['fileType'], record['fileURL']))

  article_id = random.randint(10000000,99999999)
  iid = "FSU_{0}_{1}".format(issue_id, article_id)

  remote_pdf = urllib.request.urlopen(record['fileURL'])
  if record['fileType'] == 'JPEG':
    local_pdf = open('tmp/{}.jpg'.format(article_id), 'wb')
    jpegs.append(article_id)
  else:
    local_pdf = open('tmp/{}.pdf'.format(article_id), 'wb')
  local_pdf.write(remote_pdf.read())
  remote_pdf.close()
  local_pdf.close()

  xml = open('tmp/{}.xml'.format(article_id), 'w')
  xml.write('<?xml version="1.0" encoding="UTF-8"?>\n')
  xml.write('<mods xmlns="http://www.loc.gov/mods/v3" xmlns:flvc="info:flvc/manifest/v1">\n')
  xml.write('\t<typeOfResource>text</typeOfResource>\n')
  xml.write('\t<genre>academic journal</genre>\n')
  xml.write('\t<identifier type="IID">{}</identifier>\n'.format(iid))
  xml.write('\t<extension><flvc:flvc>\n')
  xml.write('\t\t<flvc:owningInstitution>{}</flvc:owningInstitution>\n'.format(args.institution))
  xml.write('\t\t<flvc:submittingInstitution>{}</flvc:submittingInstitution>\n'.format(args.institution))
  xml.write('\t</flvc:flvc></extension>\n')
  xml.write('\t<titleInfo><title>{}</title></titleInfo>\n'.format(record['title']))
  for author in record['authors']:
    xml.write('\t<name type="personal"><namePart>{}</namePart></name>\n'.format(author))
  xml.write('</mods>')
  xml.close()


for jpeg in jpegs:
  os.system("convert tmp/{0}.jpg tmp/{1}.pdf".format(jpeg, jpeg))
  os.system("rm tmp/{}.jpg".format(jpeg))

os.system("cd tmp/; COPYFILE_DISABLE=1 tar -cvzf {}.tgz *".format(args.output))
os.system("mv tmp/{}.tgz .".format(args.output))
os.system("rm -rf tmp/")
