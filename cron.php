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
            and dl.snmp_index = gl.snmp_index");


$d = strtotime(date('Y-m-d',time()));
//$d = strtotime("2019-09-10");
if (cacti_sizeof($local_datas)) {
    $graph_data_array = array("graph_start"=>$d-86400,"graph_end"=>$d,"export_csv"=>true);
    $xport_meta = array();
    foreach($local_datas as $local_data) {
        $save = array();
        if(empty($local_data["local_data_id"])){ // 说明是聚合图形
            $xport_array = rrdtool_function_xport($local_data["local_graph_id"], 0, $graph_data_array, $xport_meta);
            $save["value"] = getMaxTrafficOfDay($xport_array);
        }else{
            $ref_values = ov_get_ref_value($local_data, $d,86400);
            if(!empty($ref_values["traffic"])){
                $save["value"] = round($ref_values["traffic"] / 1000000000,2);
            }
        }
        
        // 如果上一天有数据，先删除，确保每天直插入一天.
        $insert_time = date('Y-m-d H:i:s', $d-86400);
        db_execute_prepared("delete from plugin_overview_history where local_graph_id = ? and insert_time = ?",
            array($local_data["local_graph_id"],$insert_time));
        $save["local_graph_id"] = $local_data["local_graph_id"];
        $save["local_data_id"] = $local_data["local_data_id"];
        $save["insert_time"] = $insert_time;
        $save["title_cache"] = $local_data["title_cache"];
        $save["region_name"] = $local_data["region_name"];
        $save["region_code"] = $local_data["region_code"];
        sql_save($save, 'plugin_overview_history');
    }
}

// 得到一天中最大的流量
function getMaxTrafficOfDay($xport_array){
    if (!empty($xport_array["data"])) {
        $max = 0;
        foreach ($xport_array["data"] as $data){
            $datas = array_values($data);
            $in = $data[sizeof($datas)-2];$out = end($datas);
            $cmax = $in > $out ? $in : $out;
            if($cmax > $max){
                $max = $cmax;
            }
        }
        return round($max / 1000000000,2);;
    }
}
