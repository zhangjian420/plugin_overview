<?php
$guest_account=true;

chdir('../../');
include_once('./include/auth.php');
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/overview/overview_functions.php');

switch(get_nfilter_request_var('action')) {
    case 'ajax_citys':
        ajax_citys();
        break;
    case 'ajax_graph':
        ajax_graph();
        break;
    case 'ajax_map':
        ajax_map();
        break;
    case 'ajax_line':
        ajax_line();
        break;
    case 'ajax_tj_line':
        ajax_tj_line();
        break;
    case 'ajax_qu_bar':
        ajax_qu_bar();
        break;
    default:
        general_header();
        overview();
        bottom_footer();
        break;
}
exit;

function ajax_citys(){
    if (!empty(get_request_var('system_region'))) {
        $system_regions = db_fetch_assoc_prepared("select * from region where pcode = ? order by `code` ",array(get_request_var('system_region')));
        print json_encode($system_regions);
    }
}

// 加载某个树下的图形
function ajax_graph(){
    if (!empty(get_request_var('tree_id'))) {
        $graphs = db_fetch_assoc_prepared("select gtg.local_graph_id,gtg.`title_cache`
                from `graph_templates_graph` gtg
                left join graph_tree_items gti on gtg.`local_graph_id` = gti.`local_graph_id`
                where gti.graph_tree_id = ? and gti.local_graph_id != 0",array(get_request_var('tree_id')));
        $overviews = db_fetch_assoc_prepared("select local_graph_id,region_code,region_url from plugin_overview where tree_id = ?",array(get_request_var('tree_id')));
        
        $arr = array();
        foreach($graphs as $graph) {
            $tmp = array('local_graph_id' => $graph['local_graph_id'],'title_cache' => $graph['title_cache']);
            foreach ($overviews as $overview){
                if($graph["local_graph_id"] == $overview["local_graph_id"]){
                    $tmp["region_code"] = $overview["region_code"];
                    $tmp["region_url"] = $overview["region_url"];
                    break;
                }
            }
            $arr[] = $tmp;
        }
        print json_encode($arr);
    }
}

// 每隔13秒返回大屏中的数据
function ajax_map(){
    // 1、先查询大屏的使用的图形
    $ret = array();
    $tj = 0;
    $local_datas = get_local_data();
    if (cacti_sizeof($local_datas)) {
        $total_local_graph_id = 0;
        foreach($local_datas as $local_data) {
            $total_local_graph_id = $local_data["total_local_graph_id"];
            //$traffic_value = ov_get_traffic_value($local_data["local_graph_id"],$local_data["local_data_id"],$graph_data_array,$xport_meta,$d);
            $traffic_value = get_graph_new_traffic_value($local_data["local_graph_id"],300);
            if(!empty($traffic_value)){
                $ret[] = array("value"=>$traffic_value,"name"=>$local_data["name"],
                    "code"=>$local_data["region_code"],"url"=>$local_data["region_url"],
                    "upper_limit"=>round($local_data["upper_limit"]/1000000000,2));
            }
        }
        // 统计图形的流量值
        if(!empty($total_local_graph_id)){
            //$traffic_value = ov_get_traffic_value($local_data["local_graph_id"],$local_data["local_data_id"],$graph_data_array,$xport_meta,$d);
            $traffic_value = get_graph_new_traffic_value($total_local_graph_id);
            if(!empty($traffic_value)){
                $tj = $traffic_value;
            }
        }
    }
    array_multisort(array_column($ret,'value'),SORT_DESC,$ret);
    $ret[] = $tj;
    print json_encode($ret);
}

// 曲线数据
function ajax_line(){
    // 先查询有多少个分类
    //$region_names = db_fetch_assoc("select r.name as region_name from plugin_overview po left join region r on po.region_code = r.code");
    
    // 查询有多少个日期
    $insert_times = db_fetch_assoc("select poh.insert_time from plugin_overview po 
            left join plugin_overview_history poh on po.local_graph_id = poh.local_graph_id
            where insert_time is not null group by insert_time order by insert_time desc limit 30
            ");
    $insert_times = array_reverse($insert_times);
    $insert_time_array = array();
    foreach ($insert_times as $insert_time){
        $insert_time_array[] = date("Y-m-d",strtotime($insert_time["insert_time"]));
    }
    
    $histoys = db_fetch_assoc("SELECT poh.insert_time,`value`,
        IF(poh.region_name IS NULL,poh.title_cache,poh.region_name) AS region_name FROM plugin_overview po 
        LEFT JOIN plugin_overview_history poh ON po.local_graph_id = poh.local_graph_id where insert_time is not null
        ORDER BY region_name,insert_time ");
    
    $datas = array();
    foreach ($histoys as $histoy){
        $insert_time = date("Y-m-d",strtotime($histoy["insert_time"]));
        if (!in_array($insert_time, $insert_time_array)) {
            continue;
        }
        if(!empty($histoy["region_name"]) && array_key_exists($histoy["region_name"], $datas)){
            $region_datas = $datas[$histoy["region_name"]]; // 如果此key存在，得到该key拥有的数组
            $region_datas[$insert_time] = $histoy["value"];
            $datas[$histoy["region_name"]] = $region_datas;
        }else{ // 没有找到的话，创建一个数组
            $datas[$histoy["region_name"]] = array($insert_time=>$histoy["value"]);
        }
    }
    //cacti_log("all times = " . json_encode($insert_time_array));
    foreach ($datas as $key => $data){
        $my_insert_times = array_keys($data);
        //cacti_log("key = " . $key .", 插入前 = " . json_encode($data));
        $diff_times = array_diff($insert_time_array, $my_insert_times);
        //cacti_log("key = " . $key .", diff_times = " . json_encode($diff_times));
        $idvs = array();
        foreach ($diff_times as $idv){
            $idvs[$idv] = "0";
        }
        $data = array_merge($data,$idvs);
        //ksort($data);
        array_multisort(array_keys($data),SORT_ASC,$data);
        //cacti_log("key = " . $key .", 插入后 = " . json_encode($data));
        $datas[$key] = array_values($data);
    }
    //cacti_log("filled after= " . json_encode($datas));
    
    $ret = array("datas"=>$datas,"times"=>$insert_time_array);
    print json_encode($ret);
}

// 大屏总流量曲线
function ajax_tj_line(){
    $ret = array();
    $histoys = db_fetch_assoc("SELECT poh.insert_time,`value` FROM plugin_overview_history poh 
        where local_graph_id = (select total_local_graph_id from plugin_overview limit 1)
        ORDER BY insert_time desc limit 30");
    $histoys = array_reverse($histoys);
    foreach ($histoys as $histoy){
        $insert_time = date("Y-m-d",strtotime($histoy["insert_time"]));
        $ret["times"][] = $insert_time;
        $ret["datas"][] = $histoy["value"];
    }
    print json_encode($ret);
}

// 区县时候显示柱形图
function ajax_qu_bar(){
    // 1、先查询大屏的使用的图形
    $ret = array();
    $tj = 0;
    $local_datas = get_local_data();
    if (cacti_sizeof($local_datas)) {
        $total_local_graph_id = 0;
        foreach($local_datas as $local_data) {
            $total_local_graph_id = $local_data["total_local_graph_id"];
            //$traffic_value = ov_get_traffic_value($local_data["local_graph_id"],$local_data["local_data_id"],$graph_data_array,$xport_meta,$d);
            $traffic_value = get_graph_new_traffic_value($local_data["local_graph_id"]);
            if(!empty($traffic_value)){
                $ret[] = array("value"=>$traffic_value,"name"=>$local_data["title_cache"],
                    "upper_limit"=>round($local_data["upper_limit"]/1000000000,2));
            }
        }
        // 统计图形的流量值
        if(!empty($total_local_graph_id)){
            //$traffic_value = ov_get_traffic_value($local_data["local_graph_id"],$local_data["local_data_id"],$graph_data_array,$xport_meta,$d);
            $traffic_value = get_graph_new_traffic_value($total_local_graph_id);
            if(!empty($traffic_value)){
                $tj = $traffic_value;
            }
        }
    }
    array_multisort(array_column($ret,'value'),SORT_DESC,$ret);
    $ret[] = $tj;
    print json_encode($ret);
}

function overview(){
    global $config;
    // 当前系统是哪个地域
    $region = db_fetch_row("SELECT system_region,r.`level` FROM plugin_overview po LEFT JOIN region r ON po.`system_region` = r.`code` LIMIT 1");
    $system_region = $region["system_region"];
    $level = $region["level"];
    if(empty($region) || empty($system_region)){
        header('Location: overview_cfg.php');
        return;
    }
    if($system_region == -1){ // 加载区县的显示
        include_once($config['base_path'] . '/plugins/overview/include/overview_qu_html.php');
    }else{
        include_once($config['base_path'] . '/plugins/overview/include/overview_html.php');
    }
    
}
