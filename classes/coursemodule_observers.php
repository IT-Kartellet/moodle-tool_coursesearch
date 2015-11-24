<?php

namespace tool_coursesearch;

require_once($CFG->dirroot . '/admin/tool/coursesearch/locallib.php');

class coursemodule_observers {
	public static function created_handler(\core\event\base $event) {
		$data = $event->get_data();

		try {
	        $ob      = new \tool_coursesearch_locallib();
	        $options = $ob->tool_coursesearch_solr_params();

	        $cm = coursemodule_observers::get_cm_from_data($data);
	        $record = $event->get_record_snapshot($data['other']['modulename'], $data['other']['instanceid']);
            $solr    = new \tool_coursesearch_solrlib();

            if ($solr->connect($options, true)) {
                $docs     = $ob->tool_coursesearch_build_cm_documents($cm, $record, $solr);
                $solr->addDocuments($docs);
                return true;
            }

        } catch (Exception $e) {
	        echo $e->getMessage();
	        return false;
	    }
	}

	public static function updated_handler(\core\event\base $event) {
        if (coursemodule_observers::deleted_handler($event) && coursemodule_observers::created_handler($event)) {
            return true;
        }
	}

	public static function deleted_handler(\core\event\base $event) {
		$ob      = new \tool_coursesearch_locallib();
        $options = $ob->tool_coursesearch_solr_params();

        $cm = coursemodule_observers::get_cm_from_data($event->get_data());

        $solr    = new \tool_coursesearch_solrlib();
		try {
            if ($solr->connect($options, true)) {
                $solr->deletebyquery('id:course_module_' . $cm->id);
                return true;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
	}

    private static function get_cm_from_data($data) {
        return get_fast_modinfo($data['courseid'])->instances[$data['other']['modulename']][$data['other']['instanceid']];
    }
}