<?php

/*
 * MoDeCaSo - A Web Application for Modified Delphi Card Sorting Experiments
 * Copyright (C) 2014-2015 Peter Folta. All rights reserved.
 *
 * Project:         MoDeCaSo
 * Version:         1.0.0
 *
 * File:            /server/model/projects/participants.class.php
 * Created:         2015-01-13
 * Author:          Peter Folta <mail@peterfolta.net>
 */

namespace model;

use data\participant_statuses;
use Exception;
use main\database;
use Rhumsaa\Uuid\Uuid;

class participants
{

    private $database;

    public function __construct()
    {
        $this->database = database::get_instance();
    }

    public function add_participant($project_key, $first_name, $last_name, $email)
    {
        /*
         * Get project ID
         */
        $project_id = projects::get_project_id($project_key);

        /*
         * Get number of existing participants
         */
        $this->database->select("project_participants", null, "`project` = '".$project_id."'");
        $participant_count = $this->database->row_count();

        /*
         * Insert new participant into database
         */
        $this->database->insert("project_participants", array(
            'id'            => Uuid::uuid4()->toString(),
            'project'       => $project_id,
            'order'         => $participant_count + 1,
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'email'         => $email,
            'status'        => participant_statuses::ADDED,
            'created'       => $GLOBALS['timestamp'],
            'last_modified' => 0
        ));

        /*
         * Update project last modified timestamp
         */
        projects::update_last_modified($project_key);

        /*
         * Compute project status
         */
        projects::compute_project_status($project_key);

        $result = array(
            'error'         => false,
            'msg'           => "participant_created"
        );

        return $result;
    }

    public function delete_participant($project_key, $participant_id)
    {
        /*
         * Get Project ID
         */
        $project_id = projects::get_project_id($project_key);

        /*
         * Check if participant exists
         */
        $this->database->select("project_participants", null, "`id` = '".$participant_id."' AND `project` = '".$project_id."'");

        if ($this->database->row_count() == 1) {
            /*
             * Get participant's order
             */
            $participant_order = $this->database->result()[0]['order'];

            /*
             * Delete participant
             */
            $this->database->delete("project_participants", "`id` = '".$participant_id."'");

            /*
             * Get all participants with a higher order
             */
            $this->database->select("project_participants", null, "`order` > '".$participant_order."' AND `project` = '".$project_id."'");
            $participants = $this->database->result();

            foreach ($participants as $participant) {
                $data = array(
                    'order'         => $participant['order'] - 1
                );

                $this->database->update("project_participants", "`id` = '".$participant['id']."'", $data);
            }

            /*
             * Update project last modified timestamp
             */
            projects::update_last_modified($project_key);

            /*
             * Compute project status
             */
            projects::compute_project_status($project_key);

            $result = array(
                'error'         => false,
                'msg'           => "participant_deleted"
            );
        } else {
            throw new Exception("Invalid Participant");
        }

        return $result;
    }

    public function delete_all_participants($project_key)
    {
        /*
         * Get Project ID
         */
        $project_id = projects::get_project_id($project_key);

        /*
         * Delete all participants linked to this project from database
         */
        $this->database->delete("project_participants", "`project` = '".$project_id."'");

        /*
         * Update project last modified timestamp
         */
        projects::update_last_modified($project_key);

        /*
         * Compute project status
         */
        projects::compute_project_status($project_key);

        $result = array(
            'error'         => false,
            'msg'           => "all_participants_deleted"
        );

        return $result;
    }

    public function edit_participant($project_key, $participant_id, $first_name, $last_name, $email)
    {
        /*
         * Get Project ID
         */
        $project_id = projects::get_project_id($project_key);

        /*
         * Check if participant exists
         */
        $this->database->select("project_participants", null, "`id` = '".$participant_id."' AND `project` = '".$project_id."'");

        if ($this->database->row_count() == 1) {
            $data = array(
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'email'         => $email,
                'last_modified' => $GLOBALS['timestamp']
            );

            /*
             * Update participant in database
             */
            $this->database->update("project_participants", "`id` = '".$participant_id."'", $data);

            /*
             * Update project last modified timestamp
             */
            projects::update_last_modified($project_key);

            $result = array(
                'error'         => false,
                'msg'           => "card_edited"
            );
        } else {
            throw new Exception("Invalid Card");
        }

        return $result;
    }

    public function get_participant($project_key, $participant_id)
    {
        /*
         * Get Project ID
         */
        $project_id = projects::get_project_id($project_key);

        $this->database->select("project_participants", null, "`id` = '".$participant_id."' AND `project` = '".$project_id."'");

        if ($this->database->row_count() == 1) {
            $participant = $this->database->result()[0];

            $result = array(
                'error'         => false,
                'participant'   => $participant
            );
        } else {
            throw new Exception("Invalid Participant");
        }

        return $result;
    }

    public function export_participants($project_key)
    {
        /*
         * Get Project ID
         */
        $project_id = projects::get_project_id($project_key);

        $this->database->select("project_participants", null, "`project` = '".$project_id."'", null, null, "`order` ASC");
        $participants = $this->database->result();

        $export_data = "";

        foreach ($participants as $participant) {
            $export_data .= $participant['first_name']."|".$participant['last_name']."|".$participant['email'];

            $export_data .= "\n";
        }

        $export_data = trim($export_data);

        return $export_data;
    }

    public function import_participants($project_key, $import_file)
    {
        /*
         * Get Project ID
         */
        $project_id = projects::get_project_id($project_key);

        /*
         * Get number of existing participants
         */
        $this->database->select("project_participants", null, "`project` = '".$project_id."'");
        $participant_count = $this->database->row_count();

        if ($import_file['type'] != "text/plain") {
            throw new Exception("'".$import_file['name']."' is not a plain text file");
        }

        $file_handle = fopen($import_file['tmp_name'], "rb");

        $i = 1;

        while (($line = fgets($file_handle)) !== false) {
            $card = explode("|", $line);

            $first_name = trim($card[0]);
            $last_name = trim($card[1]);
            $email = trim($card[2]);

            if (!empty($first_name) && !empty($last_name) && !empty($email)) {
                $this->database->insert("project_participants", array(
                    'id'            => Uuid::uuid4()->toString(),
                    'project'       => $project_id,
                    'order'         => $participant_count + $i,
                    'first_name'    => $first_name,
                    'last_name'     => $last_name,
                    'email'         => $email,
                    'status'        => participant_statuses::ADDED,
                    'created'       => $GLOBALS['timestamp'],
                    'last_modified' => 0
                ));
            }

            $i++;
        }

        /*
         * Update project last modified timestamp
         */
        projects::update_last_modified($project_key);

        /*
         * Compute project status
         */
        projects::compute_project_status($project_key);

        return array(
            'error'         => false,
            'msg'           => "participants_imported"
        );
    }

    public function set_order($project_key, $order)
    {
        /*
         * Get Project ID
         */
        $project_id = projects::get_project_id($project_key);

        for ($i = 0; $i < count($order); $i++) {
            $data = array(
                'order'         => $i + 1
            );

            $this->database->update("project_participants", "`project` = '".$project_id."' AND `id` = '".$order[$i]."'", $data);
        }

        /*
         * Update project last modified timestamp
         */
        projects::update_last_modified($project_key);

        return array(
            'error'         => false,
            'msg'           => "participants_order_updated"
        );
    }

}