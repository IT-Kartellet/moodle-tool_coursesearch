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
 * methods that talks between Solr's instance and SOlrPhpClient Library
 *
 * @package    coursesearch
 * @copyright  2013 Shashikant Vaishnav  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once("SolrPhpClient/Apache/Solr/Service.php");
require_once("solrlib.php");
require_once("$CFG->dirroot/$CFG->admin/tool/coursesearch/SolrPhpClient/Apache/Solr/HttpTransport/Curl.php");
class tool_coursesearch_locallib
{
    public function tool_coursesearch_ping($options) {
        $arr  = array();
        $solr = new tool_coursesearch_solrlib();
        if ($solr->connect($options, true)) {
            $arr['status'] = 'ok';
        } else {
            $arr['status'] = 'error';
        }
        print(json_encode($arr));
        exit();
    }
    public function tool_coursesearch_index($prev) {
        $this->tool_coursesearch_load_all($this->tool_coursesearch_solr_params(), $prev);
        exit();
    }
    public function tool_coursesearch_deleteall() {
        $options = $this->tool_coursesearch_solr_params();
        $arr     = array();
        $solr    = new tool_coursesearch_solrlib();
        if ($solr->connect($options, true)) {
            if ($solr->deleteall()) {
                $arr['status'] = 'ok';
            } else {
                $arr['status']  = 'error';
                $arr['code']    = $solr->get_errorcode();
                $arr['message'] = $solr->get_errormessage();
            }
        } else {
            $arr['status']  = 'error';
            $arr['code']    = $solr->get_errorcode();
            $arr['message'] = $solr->get_errormessage();
        }
        print(json_encode($arr));
        exit();
    }
    public function tool_coursesearch_optimize() {
        $options = $this->tool_coursesearch_solr_params();
        $arr     = array();
        $solr    = new tool_coursesearch_solrlib();
        if ($solr->connect($options, true)) {
            if ($solr->optimize()) {
                $arr['status'] = 'ok';
            } else {
                $arr['status']  = 'error';
                $arr['code']    = $solr->get_errorcode();
                $arr['message'] = $solr->get_errormessage();
            }
        } else {
            $arr['status']  = 'error';
            $arr['code']    = $solr->get_errorcode();
            $arr['message'] = $solr->get_errormessage();
        }
        print(json_encode($arr));
        exit();
    }
    /**
     * Return the array of solr configuration
     * @return array of solr configuration values 
     */
    public function tool_coursesearch_solr_params() {
        $options              = array();
        $options['solr_host'] = get_config('tool_coursesearch', 'solrhost');
        $options['solr_port'] = get_config('tool_coursesearch', 'solrport');
        $options['solr_path'] = get_config('tool_coursesearch', 'solrpath');
        return $options;
    }
    /**
     * Return indexing statics in json
     *
     * @param array $options configuration of solr
     * @param string $prev previous id where solr need to start the index for very start its 1
     * @return string 
     */
    public function tool_coursesearch_load_all($options, $prev) {
        global $DB, $CFG;
        $documents   = array();
        $cnt         = 0;
        $batchsize   = 10;
        $last        = "";
        $found       = false;
        $end         = false;
        $percent     = 0;
        $sql         = 'SELECT id FROM mdl_course ORDER BY id';
        $courses     = $DB->get_records_sql($sql);
        $courses     = array_values($courses);
        $coursecount = count($courses);


        $solr = new tool_coursesearch_solrlib();
        $solr->connect($options);

        for ($idx = 1; $idx < $coursecount; $idx++) {
            set_time_limit(0);
            $courseid = $courses[$idx]->id;
            $last     = $courseid;
            $percent  = (floatval($idx) / floatval($coursecount - 1)) * 100;
            if ($prev && !$found) {
                if ($courseid === $prev) {
                    $found = true;
                }
                continue;
            }
            if ($idx === $coursecount - 1) {
                $end = true;
            }
            $documents[] = $this->tool_coursesearch_build_document($options, $this->tool_coursesearch_get_courses($courseid));

            $mod_info = get_fast_modinfo($courseid)->instances;
            foreach ($mod_info as $module => $instances) {
                foreach ($instances as $cm) {
                    $record = $DB->get_record($module, array('id'=>$cm->instance));

                    $documents = array_merge($documents, $this->tool_coursesearch_build_cm_documents($cm, $record, $solr));
                }
            }

            $cnt++;
            if ($cnt == $batchsize) {
                $this->tool_coursesearch_solr_course($options, $documents, false, false);
                $cnt       = 0;
                $documents = array();
                break;
            }
        }
        if ($documents) {
            $this->tool_coursesearch_solr_course($options, $documents, false, false);
        }
        if ($end) {
            $this->tool_coursesearch_solr_course($options, false, false, false);
            printf("{\"last\": \"%s\", \"end\": true, \"percent\": \"%.2f\"}", $last, $percent);
        } else {
            printf("{\"last\": \"%s\", \"end\": false, \"percent\": \"%.2f\"}", $last, $percent);
        }
    }
    /**
     * Return array of object containing the info about the particular course
     *
     * @param string courseid the course which needs to be indexed
     * @return Object  
     */
    public function tool_coursesearch_get_courses($courseid) {
        global $DB, $CFG;
        $courses = $DB->get_record('course', array(
            'id' => $courseid
        ), 'id,idnumber,fullname,shortname,summary,startdate,visible');
        return $courses;
    }
    /**
     * Return object of solr content to be indexed
     *
     * @param array $options configuration of solr
     * @param object $course_info having the other attributes about the particular course
     * @return object 
     */
    /* One course may have multiple attachments so we need use a random unique id
    unique id that is based on current macro time. */
    public function tool_coursesearch_build_document($options, $courseinfo) {
        global $DB, $CFG;
        $doc = new Apache_Solr_Document();

        $doc->setField('id', uniqid($courseinfo->id));
        $doc->setField('idnumber', $courseinfo->idnumber);
        $doc->setField('type', 'course');
        $doc->setField('courseid', $courseinfo->id);
        $doc->setField('fullname', $courseinfo->fullname);
        $doc->setField('summary', tool_coursesearch_locallib::tool_coursesearch_clean_summary($courseinfo->summary));
        $doc->setField('shortname', $courseinfo->shortname);
        $doc->setField('startdate', $this->tool_coursesearch_format_date($courseinfo->startdate));
        $doc->setField('visibility', $courseinfo->visible);
        $files = $this->tool_coursesearch_overviewurl($courseinfo->id);
        if (get_config('tool_coursesearch', 'overviewindexing')) {
            $solr = new tool_coursesearch_solrlib();
            if ($solr->connect($options, true)) {
                foreach ($files as $file) {
                    $url      = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/course/overviewfiles";
                    $filename = rawurlencode($file->get_filename());
                    $fileurl  = $url . $file->get_filepath() . $filename;
                    $solr->extract($fileurl, array(
                        'literal.id' => uniqid($courseinfo->id),
                        'literal.filename' => $filename,
                        'literal.courseid' => $courseinfo->id,
                        'literal.fullname' => $courseinfo->fullname,
                        'literal.summary' => $courseinfo->summary,
                        'literal.shortname' => $courseinfo->shortname,
                        'literal.startdate' => $this->tool_coursesearch_format_date($courseinfo->startdate),
                        'literal.visibility' => $courseinfo->visible
                    ));
                }
            }
        }
        return $doc;
    }

    public static function tool_coursesearch_clean_summary($summary) {
        return strip_tags($summary, '<br><br/>');
    }

    public function tool_coursesearch_build_cm_documents(cm_info $cm, $record, tool_coursesearch_solrlib $solr) {
        global $PAGE;
        $docs = array();
        $doc = new Apache_Solr_Document();
        $courseid = $cm->get_course()->id;

        // Prevent format module intro from complaining
        $PAGE->set_context(context_system::instance());
        $summary = format_module_intro($cm->modname, $record, $cm->id);

        $doc->setField('id', 'course_module_' . $cm->id);
        $doc->setField('type', 'course_module');
        $doc->setField('modname', $cm->modname);
        $doc->setField('modid', $cm->id);
        $doc->setField('courseid', $courseid);
        $doc->setField('summary', strip_tags($summary));
        $doc->setField('fullname', $cm->get_formatted_name());
        $doc->setField('visibility', $cm->visible);

        $cm_callback = component_callback_exists("mod_{$cm->modname}", 'alter_solr_document');
        if (is_string($cm_callback)) {
            $doc = $cm_callback($doc, $cm, $record, $solr);
        }
        if ($doc) {
            // If doc is false, the alter function already inserted - e.g. if using extract
            $docs[] = $doc;
        }

        $additional_callback = component_callback_exists("mod_{$cm->modname}", 'get_additional_solr_documents');

        if (is_string($additional_callback)) {
            $callback_documents = $additional_callback($cm, $record);
            $docs = array_merge($docs, $callback_documents);
        }

        return $docs;
    }

    /**
     * Return the date in proper format
     *
     * @param string to be formatted
     * @return string 
     */
    public function tool_coursesearch_format_date($thedate) {
        return gmdate("Y-m-d\TH:i:s\Z", $thedate); // Return timestamp in proper format.
    }
    /**
     * Return void
     *
     * @param array $options configuration of solr
     * @param object $documents documents attribtes to be served to solr
     * @param boolean $commit whether to commit or not?
     * @param boolean $optimize whether to optimize or not?
     * @return string 
     */
    public function tool_coursesearch_solr_course($options, $documents, $commit = true, $optimize = false) {
        try {
            $solr = new tool_coursesearch_solrlib();
            if ($solr->connect($options, true)) {
                if ($documents) {
                    $solr->adddocuments($documents);
                }
                if ($commit) {
                    $solr->commit();
                }
                if ($optimize) {
                    $solr->optimize();
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    /**
     * Return files array of all the overview files
     *
     * @param int courseid 
     * @return array 
     */
    public function tool_coursesearch_overviewurl($courseid) {
        $context = context_course::instance($courseid);
        $fs      = get_file_storage();
        $files   = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);
        return $files;
    }
    /**
     * Return filename @string of summary file.
     *
     * @param int courseid 
     * @return string filename 
     */
    public function tool_coursesearch_summaryfilename($courseid) {
        $context  = context_course::instance((int) $courseid);
        $fs       = get_file_storage();
        $files    = $fs->get_area_files($context->id, 'course', 'summary', false, 'filename', false);
        $filename = '';
        foreach ($files as $file) {
            $filename = $file->get_filename();
        }
        return $filename; // TODO Its doesn't looks relevent to add irrelevent file names. is it really ?.
    }
    public function tool_coursesearch_query($qry, $offset, $count, $fq, $sortby, $options) {
        global $CFG;
        $response = null;
        $options  = $this->tool_coursesearch_solr_params();
        $solr     = new tool_coursesearch_solrlib();
        if ($solr->connect($options, true)) {
            $params            = array();
            $params['defType'] = 'edismax';
            $params['qf']      = 'idnumber^10 fullname^8 shortname^5 summary^3.5 startdate^1.5 content filename';
            if (empty($qry) || $qry == '*' || $qry == '*:*') {
                $params['q.alt'] = "*:*";
                $qry             = '';
            }
            $params['fq']                         = $fq;
            $params['fl']                         = '*,score';
            $params['hl']                         = 'on';
            $params['hl.fl']                      = 'fullname,summary,content';
            $params['hl.snippets']                = '3';
            $params['hl.fragsize']                = '80';
            $params['hl.simple.pre']              = '<em class="highlight">';
            $params['sort']                       = $sortby;
            $params['spellcheck.onlyMorePopular'] = 'false';
            $params['spellcheck.extendedResults'] = 'false';
            $params['spellcheck.collate']         = 'true';
            $params['spellcheck.count']           = '1';
            $params['spellcheck']                 = 'true';

            $response                             = $solr->search($qry, $offset, $count, $params);
            if (!$response->getHttpStatus() == 200) {
                $response = null;
            }
        }
        return $response;
    }
    /**
     * takes the params offset and count from plugin & reponse to render
     *
     * @param array $array offset & count 
     * @return Apache_solr_response object
     */
    public function tool_coursesearch_search($array) {
        global $CFG;
        $config = $this->tool_coursesearch_solr_params();
        $qry    = stripslashes(optional_param('search', '', PARAM_TEXT));
        $offset = isset($array['offset']) ? $array['offset'] : 0;
        $count  = isset($array['limit']) ? $array['limit'] : 20; // TODO input from user how many results perpage.
        $sort   = optional_param('sortmenu', 'score', PARAM_TEXT);
        $order  = optional_param('order', 'desc', PARAM_TEXT);
        $type   = optional_param('type', 'all', PARAM_TEXT);
        $filtercheckbox = optional_param('filtercheckbox', '0', PARAM_TEXT);
        $isdym  = (isset($_GET['isdym'])) ? $_GET['isdym'] : 0;

        require_once("$CFG->dirroot/$CFG->admin/tool/coursesearch/coursesearch_resultsui_form.php");
        $mform = new coursesearch_resultsui_form();

        $fq = '';
        if ($type !== 'all') {
            $fq .= ' +type:' . $type . ' ';
        }
        if ($filtercheckbox === '0') {
            $searchfromtime = optional_param_array('searchfromtime', '', PARAM_TEXT);
            if (!empty($searchfromtime) && !empty($searchfromtime['enabled'])) {
                $searchfromtime = mktime(
                    $searchfromtime['hour'],
                    $searchfromtime['minute'],
                    0,
                    $searchfromtime['month'],
                    $searchfromtime['day'],
                    $searchfromtime['year']
                );
            }

            $searchtilltime = optional_param_array('$searchtilltime', '', PARAM_TEXT);
            if (!empty($searchtilltime) && !empty($searchtilltime['enabled'])) {
                $searchtilltime = mktime(
                    $searchtilltime['hour'],
                    $searchtilltime['minute'],
                    0,
                    $searchtilltime['month'],
                    $searchtilltime['day'],
                    $searchtilltime['year']
                );
            }
            $fq .= $this->tool_coursesearch_filterbydate((object)array(
                'searchfromtime' => $searchfromtime,
                'searchtilltime' => $searchtilltime,
            ));
        }

        $out = array();
        if (!$qry) {
            $qry = '';
        }
        if ($sort && $order) {
            $sortby = $sort . ' ' . $order;
        } else {
            $sortby = '';
            $order  = '';
        }
        if ($qry) {
            $results = self::tool_coursesearch_query($qry, $offset, $count, $fq, $sortby, $config);
            return $results;
        }
    }
    /**
     * gives the count of results. we filter the hidden course by iterating through courses.
     *
     * @param object Apache_solr_response
     * @return int count
     */
    public function tool_coursesearch_coursecount($response) {
        $count = $response->ngroups;
        foreach ($response->groups as $doclists => $doclist) {
            foreach ($doclist->doclist->docs as $doc) {
                $doc->id = $doc->courseid;
                foreach ($doc as $key => $value) {
                    $resultinfo[$key] = $value;
                }
                $obj[$doc->courseid] = json_decode(json_encode($resultinfo), false);
                if (($obj[$doc->courseid]->visibility) == '0') {
                    context_helper::preload_from_record($obj[$doc->courseid]);
                    if (!has_capability('moodle/course:viewhiddencourses', context_course::instance($doc->courseid))) {
                        $count -= 1;
                    }
                }
            }
        }
        return $count;
    }
    /**
     * Return the array of solr configuration
     * @return array of solr configuration values 
     */
    public function tool_coursesearch_autosuggestparams() {
        $config = array();
        $this->tool_coursesearch_pingsolr();
        $config[0] = get_config('tool_coursesearch', 'solrhost');
        if (get_config('tool_coursesearch', 'solrusername') && get_config('tool_coursesearch', 'solrpassword')) {
            $config[0] = get_config('tool_coursesearch', 'solrusername') . ':'
            .get_config('tool_coursesearch', 'solrpassword').'@'. get_config('tool_coursesearch', 'solrhost');
        }
        $config[1] = get_config('tool_coursesearch', 'solrport');
        $path      = get_config('tool_coursesearch', 'solrpath');
        stripos('/', $path) === true ? $path : $path = '/' . $path;
        $config[2] = $path;
        return $config;
    }
    /**
     * return true if ping to solr
     * succeed else return false.
     * @param void
     * @return boolean true/false
     */
    public function tool_coursesearch_pingsolr() {
        $obj = new tool_coursesearch_solrlib();
        if ($obj->connect($this->tool_coursesearch_solr_params(), true)) {
            return true;
        }
        return false;
    }
    /**
     * gives the fq query string. Need to be passed to solr for range query.
     *
     * @param StdClass Object from moodleform
     * @return String fq query.
     */
    public function tool_coursesearch_filterbydate($data) {
        if (!empty($data->searchfromtime) or !empty($data->searchtilltime)) {
            if (empty($data->searchfromtime)) {
                $data->searchfromtime = '*';
            } else {
                $data->searchfromtime = gmdate('Y-m-d\TH:i:s\Z', $data->searchfromtime);
            }
            if (empty($data->searchtilltime)) {
                $data->searchtilltime = '*';
            } else {
                $data->searchtilltime = gmdate('Y-m-d\TH:i:s\Z', $data->searchtilltime);
            }
            return 'startdate:[' . $data->searchfromtime . ' TO ' . $data->searchtilltime . ']';
        }
    }
}
/**
 * Return boolean
 *
 * Course create handler trigger when a course is created.
 * @param coursedata object
 */
function tool_coursesearch_course_created_handler($obj) {
    try {
        $ob      = new tool_coursesearch_locallib();
        $options = $ob->tool_coursesearch_solr_params();
        $doc     = $ob->tool_coursesearch_build_document($options, $obj);
        $solr    = new tool_coursesearch_solrlib();
        if ($solr->connect($options, true)) {
            if ($doc) {
                $solr->addDocument($doc);
                return true;
            }
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}
/**
 * Return boolean
 *
 * Course event handler trigger when a course is deleted.
 * @param coursedata object
 */
function tool_coursesearch_course_deleted_handler($obj) {
    try {
        $ob   = new tool_coursesearch_locallib();
        $solr = new tool_coursesearch_solrlib();
        if ($solr->connect($ob->tool_coursesearch_solr_params(), true)) {
            $solr->deletebyquery("courseid:{$obj->id} type:course");
            $solr->commit();
        }
        return true;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}
/**
 * Return boolean
 *
 * Course event handler. trigger when a course is updated.
 * @param coursedata object
 */
function tool_coursesearch_course_updated_handler($obj) {
    if (tool_coursesearch_course_deleted_handler($obj) && tool_coursesearch_course_created_handler($obj)) {
        return true;
    }
}

function mod_resource_alter_solr_document(Apache_Solr_Document $doc, cm_info $cm, $record, tool_coursesearch_solrlib $solr) {
    $context = context_module::instance($cm->id);

    $fs = get_file_storage();
    // TODO: this is not very efficient!! - none the less, this is how mod_resource/view does it
    $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

    $file = reset($files);
    unset($files);

    $content = $file->get_content();
    $solr->extractFromString($content, array(
        'literal.filename' => $file->get_filename()
    ), $doc, $file->get_mimetype());

    return false;
}

function mod_forum_get_additional_solr_documents(cm_info $cm, $record) {
    $docs = array();

    $discussions = forum_get_discussions($cm);

    foreach ($discussions as $discussion) {
        $doc = new Apache_Solr_Document();
        mod_forum_post_solr_document($doc, $discussion, $cm);
        $docs[] = $doc;

        $posts = forum_get_all_discussion_posts($discussion->id, 'p.created DESC');

        foreach ($posts as $post) {
            $doc = new Apache_Solr_Document();
            mod_forum_post_solr_document($doc, $post, $cm);
            $docs[] = $doc;
        }
    }

    return $docs;
}

function mod_forum_post_solr_document(Apache_Solr_Document $doc, $post, cm_info $cm) {
    if (!empty($post->created)) {
        $created = $post->created;
    } else {
        $created = $post->timemodified;
    }

    if (empty($post->discussion)) {
        $discussion = $post->id;
        $id = $post->firstpost;
    } else {
        $discussion = $post->discussion;
        $id = $post->id;
    }

    $doc->setField('id', 'forum_post_' . $id);
    $doc->setField('startdate', date('Y-m-d\TH:i:s\Z', $created));
    $doc->setField('type', 'forum_post');
    $doc->setField('courseid', $cm->course);
    $doc->setField('metadata_discussionid', $discussion);
    $doc->setField('metadata_postid', $id);
    $doc->setField('modid', $cm->id);
    $doc->setField('summary', tool_coursesearch_locallib::tool_coursesearch_clean_summary($post->message));
    $doc->setField('fullname', $post->subject);
    $doc->setField('visibility', 1);

    return $doc;
}

function mod_forum_get_additional_solr_types() {
    return array(
        'forum_post' => get_string('forumposts', 'mod_forum')
    );
}
