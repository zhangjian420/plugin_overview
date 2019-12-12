<?php
$guest_account=true;

chdir('../../');
include_once('./include/auth.php');

switch(get_nfilter_request_var('action')) {
    case 'ajax_add':
        ajax_add();
        break;
    case 'ajax_edit':
        ajax_edit();
        break;
    case 'ajax_delete':
        ajax_delete();
        break;
    default:
        top_header();
        regions();
        bottom_footer();
        break;
}
exit;

function ajax_add(){
    if (!empty(get_request_var('code')) && !empty(get_request_var('name')) && !empty(get_request_var('pcode'))) {
        $level = db_fetch_cell_prepared("select level from region where code = ? limit 1",array(get_request_var('pcode')));
        db_execute_prepared("insert into region values(?,?,?,?)",
            array(get_request_var('code'),get_request_var('name'),get_request_var('pcode'),$level+1));
        $ret = array("state"=>"ok");
        print json_encode($ret);
    }
}

function ajax_edit(){
    if (!empty(get_request_var('code')) && !empty(get_request_var('name'))) {
        db_execute_prepared("update region set name = ? where code = ?",array(get_request_var('name'),get_request_var('code')));
        $ret = array("state"=>"ok");
        print json_encode($ret);
    }
}

function ajax_delete(){
    if (!empty(get_request_var('code'))) {
        db_execute_prepared("delete from region where code = ? or pcode = ?",array(get_request_var('code'),get_request_var('code')));
        $ret = array("state"=>"ok");
        print json_encode($ret);
    }
}

function regions(){
    global $config;
    form_start('region.php', 'region',true);
    html_start_box("地域管理 [编辑]", '100%', true, '3', 'center', '');
    $region_arr = db_fetch_assoc("select code as id,CONCAT(NAME,\"(\",CODE,\")\") as text,if(pcode=0,'#',pcode) as parent 
            from region order by level,code");
    foreach ($region_arr as &$data){
        if($data["parent"] == "#"){
            $data["state"]["opened"] = true;
        }
    }
    //cacti_log(json_encode($region_arr));
    ?>
<div class="formRow cityGraph">
	<div class="formColumnLeft" style="width: 30%">
		<div id="region_tree">
		</div>
	</div>
	<div class="formColumnRight" style="width: 65%">
		<div class="formRow">
    		<div class="formColumnLeft" style="width: 30%">
        		<div class="formFieldName">地域编码</div>
        	</div>
        	<div class="formColumnRight" style="width: 65%">
        		<div class="formData">
        			<input type="text" name="code" size="30" class="ui-state-default ui-corner-all">
				</div>
        	</div>
    	</div>
    	<div class="formRow">
    		<div class="formColumnLeft" style="width: 30%">
        		<div class="formFieldName">地域名称</div>
        	</div>
        	<div class="formColumnRight" style="width: 65%">
        		<div class="formData">
        			<input type="text" name="name" size="30" class="ui-state-default ui-corner-all">
				</div>
        	</div>
    	</div>
    	<div class="formRow">
    		<div class="formColumnLeft" style="width: 30%">
        		<div class="formFieldName">父节点编码
            		<div class="formTooltip">
            			<div class="cactiTooltipHint fa fa-question-circle">
            				<span style="display:none;">请填写编码</span>
            			</div>
    				</div>
        		</div>
        	</div>
        	<div class="formColumnRight" style="width: 65%">
        		<div class="formData">
        			<input type="text" name="pcode" size="30" class="ui-state-default ui-corner-all">
				</div>
        	</div>
    	</div>
    	<div class="formRow">
    		<div class="formColumnLeft" style="width: 30%">
        		&nbsp;
        	</div>
        	<div class="formColumnRight" style="width: 65%">
        		<div class="edit-if" style="display: none">
            		<input type="button" class="ui-button ui-corner-all ui-widget" value="修改" id="edit_btn">
            		<input type="button" class="ui-button ui-corner-all ui-widget" value="删除" id="del_btn">
            		<input type="button" class="ui-button ui-corner-all ui-widget" value="重新添加" onclick="addif()">
        		</div>
        		<div class="add-if">
        			<input type="button" class="ui-button ui-corner-all ui-widget" value="添加"  id="add_btn">
        		</div>
        	</div>
    	</div>
	</div>
</div>
<script type="text/javascript">
$(function(){
	$('#region_tree').jstree({ 'core' : {
		'check_callback' : true,
	    'data' : <?php echo json_encode($region_arr)?>
	} });
	$('#region_tree').on('select_node.jstree', function(e, data) {
		var node = data.node;
		if(node.parent == "#"){
			addif();
		}else{
			editif(node);
		}
	});

	$("#del_btn").click(function(){
		if(confirm("删除节点会级联删除子节点，确定删除？")){
			var ref = $('#region_tree').jstree(true);
			var sel = ref.get_selected();
			if(!sel.length){
		        alert("请先选择一个节点");
		        return;
		    }
		    $.get("region.php?action=ajax_delete&code="+sel[0],function(result){
		    	result = $.parseJSON(result);
				if(result.state == "ok"){
					ref.delete_node(sel);
					addif();
				}
			});
		}
	});

	$("#edit_btn").click(function(){
		var code = $("input[name='code']").val();
		var name = $("input[name='name']").val();
		var pcode = $("input[name='pcode']").val();

		var ref = $('#region_tree').jstree(true);
		var sel = ref.get_selected();
		if(!sel.length){
	        alert("请先选择一个节点");
	        return;
	    }
		$.get("region.php?action=ajax_edit&code="+sel[0]+"&name="+name,function(result){
			result = $.parseJSON(result);
			if(result.state == "ok"){
				ref.set_text(sel, name + "(" + code + ")");
				ref.select_node(sel[0]);
			}
		});
	});

	$("#add_btn").click(function(){
		var code = $("input[name='code']").val();
		var name = $("input[name='name']").val();
		var pcode = $("input[name='pcode']").val();
		if(code == "#" || code == "420000"){
			alert("地域编码不能为#或者420000");
			return;
		}
		var ref = $('#region_tree').jstree(true);
		$.get("region.php?action=ajax_add&code="+code+"&name="+name+"&pcode="+pcode,function(result){
			result = $.parseJSON(result);
			if(result.state == "ok"){
				ref.create_node(pcode,{
					"id":code,
					"text":name + "(" + code + ")"
				},"last");
				ref.select_node(code);
				addif();
			}
		});
	});
});
function addif(){
	$("input[name='code']").removeAttr("readonly");
	$("input[name='pcode']").removeAttr("readonly");
	$(".edit-if").hide();
	$(".add-if").show();
	$("#region")[0].reset();

	var ref = $('#region_tree').jstree(true);
	ref.deselect_all(); //全不选择
}
function editif(node){
	$("input[name='code']").val(node.id).attr("readonly","readonly");
	$("input[name='name']").val(node.text.split("(")[0]);
	$("input[name='pcode']").val(node.parent).attr("readonly","readonly");
	$(".edit-if").show();
	$(".add-if").hide();
}

</script>
<?php 
    html_end_box(true, true);
}
