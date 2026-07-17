<?php
/**
 * Plugin Name:       Query Loop Filters
 * Plugin URI:        https://github.com/creativeslice/query-filter
 * Description:       Filter blocks for the query loop, built on the WordPress Interactivity API. Creative Slice fork adds button display with single or multi-select, a custom dropdown style, and taxonomy options scoped to the posts the loop actually covers.
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Version:           26.07.16
 * Forked From:       humanmade/query-filter 0.2.4
 * Author:            Human Made Limited, forked by Creative Slice
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       query-filter
 *
 * @package           query-filter
 */
namespace HM\Query_Loop_Filter;

const PLUGIN_FILE = __FILE__;
const ROOT_DIR = __DIR__;

require_once __DIR__ . '/includes/namespace.php';

bootstrap();
