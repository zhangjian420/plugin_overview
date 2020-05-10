<?php

// 获取数据源
function get_local_data(){
    $local_datas = db_fetch_assoc("select po.total_local_graph_id,po.local_graph_id,
            gtg.upper_limit,gtg.title_cache,dl.id as local_data_id,po.region_code,r.name,po.region_url 
            from plugin_overview po left join region r on po.region_code = r.code
            left join graph_templates_graph gtg on po.local_graph_id = gtg.local_graph_id
            left join graph_local gl on po.local_graph_id = gl.id
            left join data_local dl on dl.host_id = gl.host_id and dl.snmp_query_id = gl.snmp_query_id 
            and dl.snmp_index = gl.snmp_index");
    return $local_datas;
}

/**
 * 根据图形ID获取一段时间内的流量数据，返回这段时间内 按照图形间隔 的每次流量值（出口或者入口流量，谁大取谁），流量单位是G
 * @param String $local_graph_id
 * @param int $start_time
 * @param int $end_time
 * @return number[]
 */
function get_graph_traffic_values($local_graph_id,$start_time,$end_time){
    $graph_data_array = array("graph_start"=>$start_time,"graph_end"=>$end_time,"export_csv"=>true);
    $xport_meta = array();
    // 聚合图形获取数据
    $xport_array = rrdtool_function_xport($local_graph_id, 0, $graph_data_array, $xport_meta);
    $ret = array();
    if (!empty($xport_array["data"])) {
        foreach ($xport_array["data"] as $data){
            $traffic = max(array_values($data));
            if(!empty($traffic)){
                $ret[] = round($traffic/1000000000,2);
            }
        }
    }
    return $ret;
}

/**
 * 获取 图形一段时间内最大的流量值，没有获取到返回0
 * @param String $local_graph_id
 * @param int $start_time
 * @param int $end_time
 * @return number|mixed
 */
function get_graph_max_traffic_value($local_graph_id,$start_time,$end_time){
    $datas  = get_graph_traffic_values($local_graph_id,$start_time,$end_time);
    if (empty($datas)) {
        return 0;
    }
    return max($datas);
}

/**
 *  获取 图形 的最新值，没有获取到返回0
 * @param String $local_graph_id
 * @return number|mixed
 */
function get_graph_new_traffic_value($local_graph_id){
    $d = strtotime(date('Y-m-d H:i',time()))-60;
    $datas  = get_graph_traffic_values($local_graph_id,$d-60,$d);
    if (empty($datas)) {
        return 0;
    }
    return end($datas);
}


