<?php
chdir(__DIR__);
chdir("../../");
require("./include/cli_check.php");
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/overview/overview_functions.php');

// 每天统计前一天的大屏使用的图形的峰值
$local_datas = db_fetch_assoc("select po.local_graph_id,dl.id as local_data_id,r.name as region_name,gtg.title_cache,po.region_code
            from plugin_overview po left join region r on po.region_code = r.code
            left join graph_templates_graph gtg on po.local_graph_id = gtg.local_graph_id 
            left join graph_local gl on po.local_graph_id = gl.id
            left join data_local dl on dl.host_id = gl.host_id and dl.snmp_query_id = gl.snmp_query_id 
            and dl.snmp_index = gl.snmp_index UNION ALL          
  
            SELECT gtg.local_graph_id,dl.id AS local_data_id,'','',''
            FROM graph_templates_graph gtg 
            LEFT JOIN graph_local gl ON gtg.local_graph_id = gl.id
            LEFT JOIN data_local dl ON dl.host_id = gl.host_id AND dl.snmp_query_id = gl.snmp_query_id 
            AND dl.snmp_index = gl.snmp_index
            WHERE gtg.`local_graph_id` = 
            (SELECT total_local_graph_id FROM `plugin_overview` LIMIT 1)");


$d = strtotime(date('Y-m-d',time()));

// rrdtool_function_xport 和 rrdtool_function_fetch 对比
$graph_data_array = array("graph_start"=>$d-86400,"graph_end"=>$d,"export_csv"=>true);
$xport_meta = array();
//聚合图形获取数据
$xport_array = rrdtool_function_xport("471", 0, $graph_data_array, $xport_meta);
print_r("rrdtool_function_xport=======");
print_r($xport_array);

$result = rrdtool_function_fetch("517", $d-86400, $d, 60);
print_r("rrdtool_function_fetch=======");
print_r($result);

// $all = get_graph_traffic_values("471",1573636200,1573636500);
// print_r("all=======");
// print_r($all);

// $ref_values = ov_get_ref_value("517", 1573636500,60);

// print_r("ref_values=======");
// print_r($ref_values);

$max = get_graph_max_traffic_value("536",$d-86400,$d);
print_r("max=======" . $max);
