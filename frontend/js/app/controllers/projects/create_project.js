/*
 * MoDeCaSo - A Web Application for Modified Delphi Card Sorting Experiments
 * Copyright (C) 2014-2015 Peter Folta. All rights reserved.
 *
 * Project:         MoDeCaSo
 * Version:         1.0.0
 *
 * File:            /frontend/js/app/controllers/projects/create_project.js
 * Created:         2014-11-19
 * Author:          Peter Folta <mail@peterfolta.net>
 */

controllers.controller(
    "create_project_controller",
    [
        "$rootScope",
        "$scope",
        "$http",
        "session_service",
        function($rootScope, $scope, $http, session_service)
        {
            $scope.flash = {
                show:       false,
                type:       null,
                message:    null
            };

            $scope.project = {
                title:      null,
                key:        null
            };

            $scope.key_modified = false;

            $scope.generate_key = function()
            {
                if (!$scope.key_modified) {
                    if ($scope.project.title != undefined) {
                        $scope.project.key = $scope.project.title.toUpperCase().replace(/[^A-Z0-9]/g, "").substr(0, 10);
                    } else {
                        $scope.project.key = null;
                    }
                }
            };

            $scope.create_project = function()
            {
                /*
                 * Disable form elements to prevent duplicate requests
                 */
                $("#create_project_submit_button").prop("disabled", true);
                $("#create_project_cancel_button").prop("disabled", true);

                $http({
                    method:     "post",
                    url:        "/server/projects/create_project",
                    data:       {
                        title:      $scope.project.title,
                        key:        $scope.project.key
                    }
                }).then(
                    function(response)
                    {
                        /*
                         * Enable form elements
                         */
                        $("#create_project_submit_button").prop("disabled", false);
                        $("#create_project_cancel_button").prop("disabled", false);

                        $scope.flash.show = true;
                        $scope.flash.type = "alert-success";
                        $scope.flash.message = "<span class='glyphicon glyphicon-ok-sign'></span> <strong>" + get_success_title() + "</strong> The project has been successfully created.";

                        /*
                         * Disable submit button and change Cancel button to show "Close" instead
                         */
                        $("#create_project_submit_button").prop("disabled", true);
                        $("#create_project_cancel_button").html("Close");

                        $("#create_project_cancel_button").on(
                            "click",
                            function()
                            {
                                go_to_url("/projects/" + $scope.project.key);
                            }
                        );
                        $("#create_project_close_button").on(
                            "click",
                            function()
                            {
                                go_to_url("/projects/" + $scope.project.key);
                            }
                        );

                        $rootScope.$broadcast("load_projects");
                    },
                    function(response)
                    {
                        /*
                         * Enable form elements
                         */
                        $("#create_project_submit_button").prop("disabled", false);
                        $("#create_project_cancel_button").prop("disabled", false);

                        $scope.flash.show = true;
                        $scope.flash.type = "alert-danger";
                        $scope.flash.message = "<span class='glyphicon glyphicon-exclamation-sign'></span> <strong>" + get_error_title() + "</strong> The project could not be created.";

                        shake_element($("#create_project_flash"));
                    }
                );
            }
        }
    ]
);