<?php

/*
 * UPB-BTHESIS
 * Copyright (C) 2004-2014 Peter Folta. All rights reserved.
 *
 * Project:			UPB-BTHESIS
 * Version:			0.0.1
 *
 * File:			/server/model/administration/user_management.class.php
 * Created:			2014-11-12
 * Last modified:	2014-11-17
 * Author:			Peter Folta <mail@peterfolta.net>
 */

namespace model;

use data\user_roles;
use Exception;

use main\config;
use main\database;
use tools\mail;

class user_management
{

    private $config;
    private $database;

    public function __construct()
    {
        $this->config = config::get_instance();
        $this->database = database::get_instance();
    }

    public function add_user($username, $first_name, $last_name, $email, $role, $status)
    {
        /*
         * Check if username already exists
         */
        $this->database->select("users", null, "username = '".$username."'");

        if ($this->database->row_count() == 0) {
            /*
             * Auto generate password
             */
            $password = $this->generate_password(10);
            $password_hash = password_hash($password, PASSWORD_BCRYPT, array(
                'cost'          => $this->config->get_config_value("auth", "password_cost")
            ));

            /*
             * Insert new user into database
             */
            $this->database->insert("users", array(
                'username'      => $username,
                'password'      => $password_hash,
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'email'         => $email,
                'role'          => $role,
                'status'        => $status,
                'created'       => time(),
                'last_modified' => time()
            ));

            /*
             * Notify new user via email
             */
            $message = "Dear ".$first_name." ".$last_name.",

a new user account has been created for you.

You can find your credentials below.
Username: ".$username."
Password: ".$password."

Please log in to MoDeCaSo and change your password.";

            $mail = new mail($this->config->get_config_value("email", "sender_address"), $first_name." ".$last_name." <".$email.">", "Account Registration", $message);
            $mail->send();

            $result = array(
                'error'         => false,
                'msg'           => "user_added"
            );
        } else {
            $result = array(
                'error'         => true,
                'msg'           => "username_already_exists"
            );
        }

        return $result;
    }

    public function delete_user($username)
    {
        /*
         * Check if user exists
         */
        $this->database->select("users", null, "username = '".$username."'");

        if ($this->database->row_count() == 1) {
            /*
             * Delete user
             */
            $this->database->delete("users", "username = '".$username."'");

            $result = array(
                'error'         => false,
                'msg'           => "user_deleted"
            );
        } else {
            /*
             * Invalid username provided
             */
            $result = array(
                'error'         => true,
                'msg'           => "invalid_username",
            );
        }

        return $result;
    }

    public function edit_user($username, $password, $first_name, $last_name, $email, $role, $status)
    {
        /*
         * Check if user exists
         */
        $this->database->select("users", null, "username = '".$username."'");

        if ($this->database->row_count() == 1) {
            $data = array(
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'email'         => $email,
                'role'          => $role,
                'status'        => $status,
                'last_modified' => time()
            );

            if (!is_null($password)) {
                /*
                 * Hash password
                 */
                $password_hash = password_hash($password, PASSWORD_BCRYPT, array(
                    'cost'          => $this->config->get_config_value("auth", "password_cost")
                ));

                $data['password'] = $password_hash;
            }

            /*
             * Update user in database
             */
            $this->database->update("users", "username = '".$username."'", $data);

            $result = array(
                'error'         => false,
                'msg'           => "user_edited"
            );
        } else {
            $result = array(
                'error'         => true,
                'msg'           => "invalid_username"
            );
        }

        return $result;
    }

    public function get_user($username)
    {
        $this->database->select("users", "id, username, first_name, last_name, email, role, status, created, last_modified", "username = '".$username."'");

        if ($this->database->row_count() == 1) {
            $user = $this->database->result()[0];

            $result = array(
                'error'         => false,
                'user'          => $user
            );
        } else {
            /*
             * Invalid username provided
             */
            $result = array(
                'error'         => true,
                'msg'           => "invalid_username"
            );
        }

        return $result;
    }

    public function get_user_list()
    {
        $this->database->select("users", "id, username, first_name, last_name, email, role, status, created, last_modified");
        $users = $this->database->result();

        for ($i = 0; $i < count($users); $i++) {
            $users[$i]['role'] = user_roles::$values[$users[$i]['role']];

            if ($users[$i]['status'] == 1) {
                $users[$i]['status'] = "active";
            } else {
                $users[$i]['status'] = "inactive";
            }
        }

        return $users;
    }

    private function generate_password($length)
    {
        if ($length < 1 || $length > 40) {
            throw new Exception("The password length must be between 1 and 40.");
        }

        return substr(sha1(uniqid(rand(), true)), 0, $length);
    }

}