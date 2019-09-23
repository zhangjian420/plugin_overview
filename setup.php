<?php
/*
 ex: set tabstop=4 shiftwidth=4 autoindent:
 +-------------------------------------------------------------------------+
 | Copyright (C) 2010-2017 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and overviewained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function plugin_overview_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/overview/INFO', true);
	return $info['info'];
}

function plugin_overview_install() {
    api_plugin_register_hook('overview', 'config_arrays', 'overview_config_arrays', 'setup.php');
    
    /* core plugin functionality */
    api_plugin_register_hook('overview', 'top_header_tabs', 'overview_show_tab', 'setup.php');
    api_plugin_register_hook('overview', 'top_graph_header_tabs', 'overview_show_tab', 'setup.php');
    
	api_plugin_register_hook('overview', 'config_arrays', 'overview_config_arrays', 'setup.php');
	api_plugin_register_hook('overview', 'draw_navigation_text', 'overview_draw_navigation_text', 'setup.php');
	
	// 加载css,js等
	api_plugin_register_hook('overview', 'page_head', 'overview_page_head', 'setup.php');
	
	api_plugin_register_realm('overview', 'overview.php', '监控大屏', 1);
	api_plugin_register_realm('overview', 'region.php', '地域管理', 1);

	overview_setup_database();
}

/**
 * 显示顶部选项卡
 */
function overview_show_tab() {
    global $config;
    if (api_user_realm_auth('overview.php')) {
        if (substr_count($_SERVER['REQUEST_URI'], 'overview.php')) {
            print '<a href="' . $config['url_path'] . 'plugins/overview/overview.php"><img src="' . $config['url_path'] . 'plugins/overview/images/tab_qunee_down.gif" alt="监控大屏"></a>';
        } else {
            print '<a href="' . $config['url_path'] . 'plugins/overview/overview.php"><img src="' . $config['url_path'] . 'plugins/overview/images/tab_qunee.gif" alt="监控大屏"></a>';
        }
    }
}

function plugin_overview_uninstall() {
}

function plugin_overview_check_config() {
	return true;
}

function plugin_overview_upgrade() {
	return false;
}

function overview_config_arrays() {
	global $menu;
	$menu[__('Management')]['plugins/overview/overview.php?action=edit'] = "大屏配置";
	$menu[__('Management')]['plugins/overview/region.php'] = "地域配置";
}

function overview_draw_navigation_text ($nav) {
	$nav['overview.php:'] = array('title' => "监控大屏", 'mapping' => '', 'url' => 'overview.php', 'level' => '1');
	$nav['overview.php:edit'] = array('title' => "监控大屏编辑", 'mapping' => 'index.php:', 'url' => 'overview.php', 'level' => '2');
	$nav['region.php:'] = array('title' => "地域管理", 'mapping' => '', 'url' => 'region.php', 'level' => '1');
	return $nav;
}

// 加载css,js等
function overview_page_head() {
    print get_md5_include_css('plugins/overview/include/css/overview.css') . PHP_EOL;
    print get_md5_include_js('plugins/overview/include/js/echarts.min.js') . PHP_EOL;
}

function overview_setup_database() {
	
}
