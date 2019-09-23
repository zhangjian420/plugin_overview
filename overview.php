<?php
$guest_account=true;

chdir('../../');
include_once('./include/auth.php');
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/overview/overview_functions.php');

switch(get_nfilter_request_var('action')) {
    case 'edit':
        top_header();
        overview_edit();
        bottom_footer();
        break;
    case 'save':
        overview_save();
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
    default:
        general_header();
        overview();
        bottom_footer();
        break;
}
exit;

function overview_edit(){
    global $config;
    form_start('overview.php', 'overview',true);
    html_start_box("监控大屏 [编辑]", '100%', true, '3', 'center', '');
    $city_fields = array(
        'spacer0' => array(
            'method' => 'spacer',
            'friendly_name' => "为树中图形选择对应的地市",
            'collapsible' => 'true'
            ),
    );
    draw_edit_form(
        array(
            'config' => array('no_form_tag' => true),
            'fields' => inject_form_variables($city_fields, array())
        )
    );
    
    $tree_id = db_fetch_cell_prepared("select tree_id from plugin_overview group by tree_id limit 1");
    if(empty($tree_id)){
        $tree_id = "1";
    }
    set_request_var("tree_id",$tree_id);
    include_once($config['base_path'] . '/plugins/overview/include/overview_cfg_html.php');
    html_end_box(true, true);
    form_save_buttons(array(
        array('id' => 'btn_ret', 'value' =>"取消"),
        array('id' => 'btn_save', 'value' =>"保存"),
    ),true);
    ?>
    <script type='text/javascript'>
    $(function() {
        $("input[id^='btn_']").click(function(){
            var value = $(this).attr("id").split("_")[1];
            if(value == "ret"){
                cactiReturnTo("overview.php?action=edit");
            }else{
                $("#overview").append("<input type='hidden' name='json' value='"+getFormJson()+"'>");
                $("#overview").submit();
            }
        });
    });
    </script>
        <?php
}

// 保存大屏配置
function overview_save(){
    $tree_id = get_request_var("tree_id");
    $json = get_request_var("json");
    if(!empty($tree_id) && !empty($json)){
        // 先删除，在添加
        db_execute("delete from plugin_overview");
        
        $arr = json_decode($json);
        foreach ($arr as $item){
            $save = array();
            $save["tree_id"] = $tree_id;
            $save["local_graph_id"] = $item->local_graph_id;
            $save["region_code"] = $item->region_code;
            $save["region_url"] = $item->region_url;
            sql_save($save, 'plugin_overview');
        }
    }
    raise_message(1);
    header('Location: overview.php?action=edit&tree_id=' .$tree_id);
}

// 加载某个树下的图形
function ajax_graph(){
    if (!empty(get_request_var('tree_id'))) {
        $graphs = db_fetch_assoc_prepared("select gtg.local_graph_id,gtg.`title_cache`
                from `graph_templates_graph` gtg
                left join graph_tree_items gti on gtg.`local_graph_id` = gti.`local_graph_id`
                where gti.graph_tree_id = ? and gti.local_graph_id != 0",array(get_request_var('tree_id')));
        $overviews = db_fetch_assoc_prepared("select local_graph_id,region_code from plugin_overview where tree_id = ?",array(get_request_var('tree_id')));
        
        $arr = array();
        foreach($graphs as $graph) {
            $tmp = array('local_graph_id' => $graph['local_graph_id'],'title_cache' => $graph['title_cache']);
            foreach ($overviews as $overview){
                if($graph["local_graph_id"] == $overview["local_graph_id"]){
                    $tmp["region_code"] = $overview["region_code"];
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
    $d = strtotime(date('Y-m-d H:i',time()))-60;
    $local_datas = get_local_data();
    if (cacti_sizeof($local_datas)) {
        $graph_data_array = array("graph_start"=>$d-300,"graph_end"=>$d,"export_csv"=>true);
        $xport_meta = array();
        foreach($local_datas as $local_data) {
            if(empty($local_data["local_data_id"])){ // 说明是聚合图形
                //cacti_log("聚合图形local_graph_id=".$local_data["local_graph_id"]);
                $xport_array = rrdtool_function_xport($local_data["local_graph_id"], 0, $graph_data_array, $xport_meta);
                //cacti_log("得到的结果=".json_encode($xport_array));
                
                if (!empty($xport_array["data"])) {
                    $data = array_values(end($xport_array["data"]));
                    //cacti_log("data = ".json_encode($data));
                    $in = $data[sizeof($data)-2];$out = end($data);
                    //cacti_log("in = $in ,out = $out");
                    $traffic = $in > $out ? $in : $out;
                    if(!empty($traffic)){
                        $ret[] = array("value"=>round($traffic/1000000000,2)
                            ,"name"=>$local_data["name"],"upper_limit"=>round($local_data["upper_limit"]/1000000000,2));
                    }
                }
            }else{ // 说明是普通图形
                $ref_values = ov_get_ref_value($local_data, $d,60);
                if(!empty($ref_values["traffic"])){
                    $ret[] = array("value"=>round($ref_values["traffic"]/1000000000,2)
                        ,"name"=>$local_data["name"],"upper_limit"=>round($local_data["upper_limit"]/1000000000,2));
                }
            }
        }
    }
    array_multisort(array_column($ret,'value'),SORT_DESC,$ret);
    print json_encode($ret);
}

// 曲线数据
function ajax_line(){
    // 先查询有多少个分类
    //$region_names = db_fetch_assoc("select r.name as region_name from plugin_overview po left join region r on po.region_code = r.code");
    
    // 查询有多少个日期
    $insert_times = db_fetch_assoc("select poh.insert_time from plugin_overview po 
            left join plugin_overview_history poh on po.local_graph_id = poh.local_graph_id
            where insert_time is not null group by insert_time order by insert_time asc limit 30
            ");
    $insert_time_array = array();
    foreach ($insert_times as $insert_time){
        $insert_time_array[] = date("Y-m-d",strtotime($insert_time["insert_time"]));
    }
    
    $histoys = db_fetch_assoc("SELECT poh.insert_time,`value`,`region_name` FROM plugin_overview po 
        LEFT JOIN plugin_overview_history poh ON po.local_graph_id = poh.local_graph_id where insert_time is not null
        ORDER BY region_name,insert_time ");
    
    $datas = array();
    foreach ($histoys as $histoy){
        $insert_time = date("Y-m-d",strtotime($histoy["insert_time"]));
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

function overview(){
    global $config;
    include_once($config['base_path'] . '/plugins/overview/include/overview_html.php');
    
}
