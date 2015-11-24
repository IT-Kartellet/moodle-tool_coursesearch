<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * 
 *
 * @package    tool
 * @subpackage coursesearch
 * @copyright  2013 Shashikant Vaishnav  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['searchlabel']           = 'Course Search:';
$string['coursesearchintro']     = 'Advanced Course Search plugin replaces the default Moodle Course search with powerfull Solr search.';
$string['pluginname']            = 'Advance course search';
$string['solrheading']           = 'Apache solr configuration';
$string['coursesearchsettings']  = 'Course Search settings';
$string['defaulttext']           = 'Enter your Search query here:';
$string['settings']              = 'Course Search settings';
$string['solrhost']              = 'Solr Host';
$string['solrhost_help']         = 'Host name of your Solr server, e.g. localhost or example.com.';
$string['solrport']              = 'Solr Port';
$string['solrport_help']         = 'Port on which the Solr server listens. The Jetty example server is 8983, while Tomcat is 8080 by default.';
$string['solrpath']              = 'Solr Path';
$string['solrpath_help']         = 'Path that identifies the Solr request handler to be used.';
$string['solrusername']          = 'Solr Username';
$string['solrusername_help']     = 'If you\'re using http authetication give your your solr username otherwise leave blank.';
$string['solrpassword']          = 'Solr Password';
$string['solrpassword_help']     = 'If you\'re using http authetication give your your solr password otherwise leave blank.';
$string['solrconfig']            = 'Advance course search settings';
$string['enablespellcheck']      = 'Enable spellchecker & suggestions';
$string['enablespellcheck_help'] = 'Enable spellchecker and get word suggestions. Also known as the "Did you mean ... ?" feature.';
$string['didyoumean']            = 'Did you mean&nbsp;';
$string['options']               = 'Options';
$string['actions']               = 'Actions';
$string['pingstatus']            = 'Check Solr instance settings';
$string['loadcontent']           = 'Index courses';
$string['optimize']              = 'Optimize index';
$string['delete']                = 'Delete all';
$string['savesettings']          = 'Save settings';
$string['overviewindexing']      = 'Index course overview files ';
$string['summaryindexing']       = 'Index course summary files ';
$string['overviewindexing_help'] = 'Index course overview files and search into those filenames and their content.';
$string['summaryindexing_help']  = 'Index course summary files and search into those filenames';
$string['changessaved']          = 'Configuration saved !';
$string['pluginsettings']        = 'Course search settings';
$string['solrpingerror']         = 'Apache Solr: Your site was unable to contact the
                                    Apache Solr server. Search results will appear from core moodle search.';
$string['solrerrormessage']      = 'On failure';
$string['solrerrormessage_help'] = 'What to display if Apache Solr search is not available.';
$string['showerrormessageyes']   = 'Show error message: Yes';
$string['showerrormessageno']    = 'Show error message: No';
$string['emptyqueryfield']       = 'Query should not be empty !';
$string['searchcourses']         = 'Search Courses:';
$string['search']                    = 'Search';
$string['searchfromtime']        = 'Starting from :';
$string['searchfromtime_help']   = 'Filter your search results from the course start date. Select the approximate starting date range of your course.';
$string['searchtilltime']        = 'Starting to :';
$string['searchtilltime_help']   = 'Filter your search results from the course start date. Select the approximate starting date range of your course.';
$string['filterresults']         = 'filter results';
$string['searchurl']             = 'Course search settings';
$string['sortby']                = 'Sort by:';
$string['sortheading']           = 'sort results';
$string['filtercheckbox']        = 'Disable all the filters';
$string['filtercheckbox_help']   = 'Disable all the filters';
$string['sortmenu']              = 'Sort results';
$string['sortmenu_help']         = 'Sort results with different aspects';
$string['pluginsettings']        = 'Course search settings';
$string['advancecoursesearch']   = 'Advance Course search Help';
$string['advancecoursesearch_help']      = '
<pre><b> 1. Autosuggestion with searchbox </b>:- Start typing with your  search keyword and you will have autocomplete suggestions. Suggestions fields are idnumber, course fullname, shortname.
<br/><br/></pre>
<pre><b> 2. Wildcard search </b>:- you can use wild cards(?,*) while searching for courses.
Example :- Moodle* searches for all the courses starting from the word "moodle".
<br/><br/></pre>
<pre><b> 3. Proximity matching </b>:- you can search by words from courses that are within a specific distance away.
Example :- Search for "foo bar" within 4 words from each other.
"foo bar"~4
<br/><br/></pre>
<pre><b> 4. Keyword matching (searching within specific field) </b>:- you may limit your search within specific field by using solr keyword matching feature.
Example:- Search for word "PHP" in fullname field and "object oriented" in summary field.
fullname:"PHP" AND summary:"object oriented"
<br/><br/> </pre>
<pre><b> 5. Filtering results by startdate </b>:- you can filter your search results by selecting the approximate date  range of courses.
<br/><br/> </pre>
<pre><b> 6. Sorting results </b>:- By default results are sorted by relevance(score). you can sort results by relevance, startdate, shortname.
<br/><br/></pre>';
