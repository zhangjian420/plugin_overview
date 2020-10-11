<?php
chdir(__DIR__);
chdir("../../");
require("./include/cli_check.php");
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/rrd_util_functions.php');


$end_time = strtotime(date('Y-m-d',time()));
$start_time = $end_time - 86400 * 2;

// $max1 = get_max_traffic_by_graph(187,$start_time,$end_time);
// cacti_log("max1 = " .$max1);
// $max2 = get_max_traffic_by_data(213,$start_time,$end_time);
// cacti_log("max2 = " .$max2);

/*$value1 = get_traffic_detail_by_data(213,$start_time,$end_time,300);
cacti_log("value1 = " . json_encode($value1));*/

// $value2 = get_traffic_detail_by_graph(187,$start_time,$end_time,300);
// cacti_log("value2 = " . json_encode($value2));


$value3 = get_traffics_by_graph(4170,1594094400,1594095300);
cacti_log("value3 = " . json_encode($value3));

/*
$new1 = get_new_traffic_by_graph(187);
cacti_log("new1 = " .$new1);
$new2 = get_new_traffic_by_data(213);
cacti_log("new2 = " .$new2);
*/