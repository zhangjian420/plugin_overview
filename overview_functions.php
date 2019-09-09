<?php

// 获取数据源
function get_local_data(){
    $local_datas = db_fetch_assoc("select po.local_graph_id,gtg.upper_limit,dl.id as local_data_id,po.region_code,r.name 
            from plugin_overview po left join region r on po.region_code = r.code
            left join graph_templates_graph gtg on po.local_graph_id = gtg.local_graph_id
            left join graph_local gl on po.local_graph_id = gl.id
            left join data_local dl on dl.host_id = gl.host_id and dl.snmp_query_id = gl.snmp_query_id 
            and dl.snmp_index = gl.snmp_index");
    return $local_datas;
}

/**
 * 返回的格式： 单位是 bit
 * array(
        "traffic" => value1,
        "unit" => 
    );
 * @return number[]
 */
function ov_get_ref_value($local_data, $ref_time, $time_range,$alarm_mod = 0.9){
    if (empty($local_data["local_data_id"])) {
        return array();
    }
    $result = rrdtool_function_fetch($local_data["local_data_id"], $ref_time-$time_range, $ref_time-1, $time_range); // 单位是字节，返回时要转行成bit
//     cacti_log("result = " .json_encode($result));
    $idx_in = array_search("traffic_in", $result['data_source_names']);
    $idx_out = array_search("traffic_out", $result['data_source_names']);
//     cacti_log("idx_in = " .json_encode($idx_in) .",idx_out = " .json_encode($idx_out));
//     cacti_log("idx_in_v = " .json_encode($result['values'][$idx_in]) .",idx_out_v = " .json_encode($result['values'][$idx_out]));
//     cacti_log("idx_in_max = " .json_encode(max($result['values'][$idx_in])) .",idx_out_v = " .json_encode(max($result['values'][$idx_out])));
    
    if (!isset($result['values'][$idx_in]) || count($result['values'][$idx_in]) == 0) {
        $iv = 0;
    }else {
        $iv = max($result['values'][$idx_in]) * 8;
    }
    if (!isset($result['values'][$idx_out]) || count($result['values'][$idx_out]) == 0) {
        $ov = 0;
    }else{
        $ov = max($result['values'][$idx_out]) * 8;
    }
    
    if($iv == 0 && $ov == 0){
        return array();
    }
    
    return array("traffic"=>$iv > $ov ? $iv : $ov);
}

