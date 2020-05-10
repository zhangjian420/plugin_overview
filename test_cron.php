<?php
chdir(__DIR__);
chdir("../../");
require("./include/cli_check.php");
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/rrd_util_functions.php');


$end_time = strtotime(date('Y-m-d',time()));
$start_time = $end_time - 86400;

$max1 = get_max_traffic_by_graph(187,$start_time,$end_time);
cacti_log("max1 = " .$max1);
$max2 = get_max_traffic_by_data(213,$start_time,$end_time);
cacti_log("max2 = " .$max2);

$new1 = get_new_traffic_by_graph(187);
cacti_log("new1 = " .$new1);
$new2 = get_new_traffic_by_data(213);
cacti_log("new2 = " .$new2);