<?php
$guest_account=true;

chdir('../../');
include_once('./include/auth.php');

switch(get_nfilter_request_var('action')) {
    case 'save':
        overview_cfg_save();
        break;
    default:
        top_header();
        overview_cfg_edit();
        bottom_footer();
        break;
}
exit;

function overview_cfg_edit(){
    global $config;
    form_start('overview_cfg.php', 'overview',true);
    html_start_box("监控大屏 [编辑]", '100%', true, '3', 'center', '');
    $city_fields = array(
        'spacer0' => array(
            'method' => 'spacer',
            'friendly_name' => "为树中图形选择对应的地市",
            'collapsible' => 'false'
            ),
    );
    draw_edit_form(
        array(
            'config' => array('no_form_tag' => true),
            'fields' => inject_form_variables($city_fields, array())
        )
    );

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
                cactiReturnTo("overview_cfg.php");
            }else{
                var json = getFormJson();
                var total_local_graph_id = $("#total_local_graph_id").val();
                for(var i in json){
					if(json[i]["local_graph_id"] == total_local_graph_id){
						alert("总流量图形ID不能和树中图形重复，请重新填写总流量图形ID！");
						return;
					}
                }
                $("#overview").append("<input type='hidden' name='json' value='"+JSON.stringify(json)+"'>");
                $("#overview").submit();
            }
        });
    });
    </script>
        <?php
}

// 保存大屏配置
function overview_cfg_save(){
    $tree_id = get_request_var("tree_id");
    $json = get_request_var("json");
    if(!empty($tree_id) && !empty($json)){
        // 先删除，在添加
        db_execute("delete from plugin_overview");
        
        $arr = json_decode($json);
        foreach ($arr as $item){
            $save = array();
            $save["tree_id"] = $tree_id;
            $save["total_local_graph_id"] = $item->total_local_graph_id;
            $save["local_graph_id"] = $item->local_graph_id;
            $save["region_code"] = $item->region_code;
            $save["region_url"] = $item->region_url;
            $save["system_region"] = $item->system_region;
            sql_save($save, 'plugin_overview');
        }
    }
    raise_message(1);
    header('Location: overview_cfg.php');
}
