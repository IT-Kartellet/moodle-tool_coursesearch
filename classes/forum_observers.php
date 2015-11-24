<?php

namespace tool_coursesearch;

require_once($CFG->dirroot . '/admin/tool/coursesearch/locallib.php');

class forum_observers {
	public static function post_created_handler(\core\event\base $event) {
        $post = $event->get_record_snapshot('forum_posts', $event->objectid);
        $doc = new \Apache_Solr_Document();

        $cm = forum_observers::get_cm_from_data($event->get_data());
        mod_forum_post_solr_document($doc, $post, $cm);

        try {
            $ob      = new \tool_coursesearch_locallib();
            $options = $ob->tool_coursesearch_solr_params();
            $solr    = new \tool_coursesearch_solrlib();

            if ($solr->connect($options, true)) {
                $solr->addDocument($doc);
                return true;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
	}

	public static function post_updated_handler(\core\event\base $event) {
        if (forum_observers::post_deleted_handler($event) && forum_observers::post_created_handler($event)) {
            return true;
        }
	}

	public static function post_deleted_handler(\core\event\base $event) {
        $ob      = new \tool_coursesearch_locallib();
        $options = $ob->tool_coursesearch_solr_params();

        $solr    = new \tool_coursesearch_solrlib();
        try {
            if ($solr->connect($options, true)) {
                $solr->deletebyquery('id:forum_post_' . $event->objectid);
                return true;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
	}

    public static function discussion_created_handler(\core\event\base $event) {
        $doc = new \Apache_Solr_Document();

        $discussion = $event->get_record_snapshot('forum_discussions', $event->objectid);
        $cm = forum_observers::get_cm_from_data($event->get_data());
        mod_forum_post_solr_document($doc, $discussion, $cm);

        try {
            $ob      = new \tool_coursesearch_locallib();
            $options = $ob->tool_coursesearch_solr_params();
            $solr    = new \tool_coursesearch_solrlib();

            if ($solr->connect($options, true)) {
                $solr->addDocument($doc);
                return true;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public static function discussion_deleted_handler(\core\event\base $event) {
        // When a discussion is deleted, post_deleted is not called, so we need to delete all posts
        $ob      = new \tool_coursesearch_locallib();
        $options = $ob->tool_coursesearch_solr_params();

        $solr    = new \tool_coursesearch_solrlib();
        try {
            if ($solr->connect($options, true)) {
                $solr->deletebyquery('metadata_discussionid:' . $event->objectid);
                return true;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    private static function get_cm_from_data($data) {
        return get_fast_modinfo($data['courseid'])->instances['forum'][$data['other']['forumid']];
    }
}