<?php
/**
 * Configuration file for snapshot WP CLI plugin
 *
 * Further notes can be found in the README file
 */

/*
 * Specify where the output of running 'wp snap shot' should go.
 * This is relative to current directory you run the command from.
 * This should not include a trailing slash.
 */
define('TARGET_FOLDER', './tmp');

/*
 * Should wp-snapshot attempt to find src attributes in the HTML source
 * captured and then also store these resources?
 *
 * Aims to capture images/CSS files and Javascript files. It will also make a
 * basic attempt at capturing resources referenced from the CSS files.
 */
define('COPY_SRC',      '1');

/*
 * Rewrite all references (in `href` attributes) from SRC_OLD to SRC_TO.
 * Useful for making a copy of your WordPress site which can be hosted
 * somewhere else straight away concurrently with your live site.
 */
define('REWRITE_LINKS', '1');
define('SRC_OLD',       'http://my-wordpress-site.com');
define('SRC_TO',       'http://backup.my-wordpress-site.com');
