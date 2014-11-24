/*
 * UPB-BTHESIS
 * Copyright (C) 2004-2014 Peter Folta. All rights reserved.
 *
 * Project:			UPB-BTHESIS
 * Version:			0.0.1
 *
 * File:            /frontend/js/app/controllers/projects/delete_user.js
 * Created:			2014-11-24
 * Last modified:	2014-11-24
 * Author:			Peter Folta <pfolta@mail.uni-paderborn.de>
 */

controllers.controller(
    "delete_project_controller",
    [
        "$scope",
        "$rootScope",
        "$http",
        "session_service",
        "key",
        function($scope, $rootScope, $http, session_service, key)
        {
            $scope.key = key;

            $scope.flash = {
                "show":     false,
                "type":     null,
                "message":  null
            };

            $scope.delete_project = function()
            {
                /*
                 * Disable form elements to prevent duplicate requests
                 */
                $("#delete_project_submit_button").prop("disabled", true);
                $("#delete_project_cancel_button").prop("disabled", true);

                $http({
                    method:     "post",
                    url:        "/server/projects/delete_project",
                    data:       {
                        key:            $scope.key
                    },
                    headers:    {
                        "X-API-Key":    session_service.get("api_key")
                    }
                }).then(
                    function(response)
                    {
                        /*
                         * Enable form elements
                         */
                        $("#delete_project_submit_button").prop("disabled", false);
                        $("#delete_project_cancel_button").prop("disabled", false);

                        $scope.flash.show = true;
                        $scope.flash.type = "alert-success";
                        $scope.flash.message = "<span class='glyphicon glyphicon-ok-sign'></span> <strong>" + get_success_title() + "</strong> The project with the key <strong>" + $scope.key + "</strong> has been successfully deleted.";

                        /*
                         * Disable submit button and change Cancel button to show "Close" instead
                         */
                        $("#delete_project_submit_button").prop("disabled", true);
                        $("#delete_project_cancel_button").html("Close");

                        $("#delete_project_cancel_button").on(
                            "click",
                            function()
                            {
                                $rootScope.$broadcast("load_projects");
                            }
                        );
                        $("#delete_project_close_button").on(
                            "click",
                            function()
                            {
                                $rootScope.$broadcast("load_projects");
                            }
                        );
                    },
                    function(response)
                    {
                        /*
                         * Enable form elements
                         */
                        $("#delete_project_submit_button").prop("disabled", false);
                        $("#delete_project_cancel_button").prop("disabled", false);

                        $scope.flash.show = true;
                        $scope.flash.type = "alert-danger";
                        $scope.flash.message = "<span class='glyphicon glyphicon-exclamation-sign'></span> <strong>" + get_error_title() + "</strong> The project could not be deleted.";

                        shake_element($("#delete_project_flash"));
                    }
                );
            }
        }
    ]
);