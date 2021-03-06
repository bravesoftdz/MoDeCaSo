/*
 * MoDeCaSo - A Web Application for Modified Delphi Card Sorting Experiments
 * Copyright (C) 2014-2015 Peter Folta. All rights reserved.
 *
 * Project:         MoDeCaSo
 * Version:         1.0.0
 *
 * File:            /frontend/js/app/controllers/projects/overview.js
 * Created:         2014-11-24
 * Author:          Peter Folta <mail@peterfolta.net>
 */

controllers.controller(
    "project_overview_controller",
    [
        "$scope",
        "$rootScope",
        "$http",
        "session_service",
        function($scope, $rootScope, $http, session_service)
        {
            $scope.filter = null;
            $scope.order_predicate = "id";
            $scope.order_reverse = false;

            $scope.flash = {
                "show":     false,
                "type":     null,
                "message":  null
            };

            $scope.get_label_class = function (status)
            {
                switch (status) {
                    case "CREATED":
                        return "label-default";
                    case "READY":
                        return "label-primary";
                    case "RUNNING":
                        return "label-warning";
                    case "FINISHED":
                        return "label-success";
                }
            };

            $scope.load_projects = function()
            {
                $http({
                    method:     "get",
                    url:        "/server/projects/get_project_list"
                }).then(
                    function(response)
                    {
                        $scope.flash.show = false;

                        $scope.projects = response.data.projects;
                    },
                    function(response)
                    {
                        $scope.flash.show = true;
                        $scope.flash.type = "alert-danger";
                        $scope.flash.message = "<span class='glyphicon glyphicon-exclamation-sign'></span> <strong>" + get_error_title() + "</strong> Error loading projects.";

                        shake_element($("#project_overview_flash"));
                    }
                );
            };

            $scope.$on(
                "load_projects",
                function(event, args)
                {
                    $scope.load_projects();
                }
            );

            $rootScope.$broadcast("load_projects");
        }
    ]
);