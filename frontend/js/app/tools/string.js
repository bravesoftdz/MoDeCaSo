/*
 * UPB-BTHESIS
 * Copyright (C) 2004-2014 Peter Folta. All rights reserved.
 *
 * Project:			UPB-BTHESIS
 * Version:			0.0.1
 *
 * File:            /frontend/js/app/tools/string.js
 * Created:			2014-11-03
 * Last modified:	2014-11-03
 * Author:			Peter Folta <mail@peterfolta.net>
 */

/**
 * get_error_title ( )
 *
 * Returns a random error title
 *
 * @returns string
 */
function get_error_title()
{
    var error_titles = [
        "Oh snap!",
        "Oh oh!",
        "Oh no!",
        "D’oh!",
        "Uh oh!"
    ];

    return error_titles[Math.floor(Math.random() * error_titles.length)].toString();
}