<?php

// 这三个字典是重复利用的
$po = db_fetch_row("select tree_id,total_local_graph_id,system_region from plugin_overview limit 1");
// 选择平台归属省，地市
$regions = db_fetch_assoc("select * from region where level < 2 order by `code`");
// 修改回显 平台归属（省、地市） 下面的地区
$citys = db_fetch_assoc_prepared("select * from region where pcode = ? order by `code`",array($po['system_region']));
// 选择图形树
$trees = db_fetch_assoc("SELECT id,name FROM graph_tree ORDER BY name");

if(!empty($regions)){
    $user_region = array("code"=>"-1","name"=>"无地图区域");
    array_push($regions, $user_region);
}
?>
<style>
    .formRow.secondRow{background: #fff}
</style>
<div class="formRow">
	<div class="formColumnLeft" style="width: 30%">
		<div class="formFieldName">
			平台归属
		</div>
	</div>
	<div class="formColumnRight" style="width: 65%">
		<div class="formData">
			<select name="system_region" id="system_region">
				<?php 
				foreach ($regions as $region){
				?>
					<option value="<?php print $region["code"] ?>" <?php if($region["code"] == $po["system_region"]){
					    ?> selected="selected" <?php }?>><?php print $region["name"] ?></option>
				<?php   
				}
				?>
			</select>
			<span style="color: red">&nbsp;&nbsp;&nbsp;&nbsp;注意：不要轻易更改此项，会造成大屏显示出现问题</span>
		</div>
	</div>
</div>
<div class="formRow">
	<div class="formColumnLeft" style="width: 30%">
		<div class="formFieldName">
			总流量图形ID
		</div>
	</div>
	<div class="formColumnRight" style="width: 65%">
		<div class="formData">
			<input type="text" id="total_local_graph_id" value="<?php print $po["total_local_graph_id"] ?>" 
				placeholder="输入总出口流量图形ID" class="ui-state-default ui-corner-all" style="width: 188px">
		</div>
	</div>
</div>
<div class="formRow firstRow even">
	<div class="formColumnLeft" style="width: 30%">
		<div class="formFieldName">
			大屏使用的树
		</div>
	</div>
	<div class="formColumnRight" style="width: 65%">
		<div class="formData">
			<select name="tree_id" id="tree_id">
				<?php 
				foreach ($trees as $tree){
				    if($tree["id"] == $po["tree_id"]){
				?>
					<option value="<?php print $tree["id"] ?>" selected="selected"><?php print $tree["name"] ?></option>
				<?php         
				    }else{
				?>
					<option value="<?php print $tree["id"] ?>"><?php print $tree["name"] ?></option>
				<?php
				    }
				?>
				<?php     
				}
				?>
			</select>
		</div>
	</div>
</div>

<script type="text/javascript">
	var citys_json = <?php print json_encode($citys)?>;
	$(function(){
		loadCityGraph();
		// 下拉选择地市
		$("select#system_region").selectmenu({
			select: function( event, ui ) {loadCity();}
		});
		// 下拉选择树
		$("select#tree_id").selectmenu({
			select: function( event, ui ) {loadCityGraph();}
		});
	});

	// 切换地域的时候调用
	function loadCity(){
		var system_region = $("#system_region").val();
		$.ajax({
			dataType:"json",
			url:"overview.php?action=ajax_citys&system_region="+system_region,
			success: function(data){
				citys_json = data;
				loadCityGraph();
			}
		});

	}

	// 加载地市图形
	function loadCityGraph(){
		$.ajax({
			dataType:"json",
			url:"overview.php?action=ajax_graph&tree_id="+$("#tree_id").val(),
			success: function(data){
				$(".formRow.secondRow").remove();
				if(data && data.length > 0){
					for(var i=0;i<data.length;i++){
						var dt = data[i];
						var html = '';
						html += '<div class="formRow secondRow cityGraph" local_graph_id="'+dt.local_graph_id+'">';
							html += '<div class="formColumnLeft" style="width: 30%">';
								html += '<div class="formFieldName" style="float:right;margin-right:10px;">';
									html += dt.title_cache;
								html += '</div>';
							html += '</div>';
							html += '<div class="formColumnRight regionGraph" style="width: 65%">';
								html += '<div class="formData">';
									html += '<select class="citySelect">';
									for(var idx in citys_json){
										var city = citys_json[idx];
										html += '<option value="'+city["code"]+'" '
											+(dt.region_code == city["code"] ? "selected='selected'" : "")+'>'+city["name"]+'</option>';
									}
									html += '</select>';
								html += '</div>';
								html += '<div class="formData">';
									html += '<input type="text" value="'+(dt.region_url?dt.region_url:"")+'" placeholder="请输入子系统地址" size="50" class="cityInput ui-state-default ui-corner-all">';
								html += '</div>';
							html += '</div>';
						html += '</div>';
						var $h = $(html);
						//$h.find("select.searchableSelect").searchableSelect({width:168});
						$(".firstRow").after($h);
					}

					$("select").selectmenu({
						width: 200
					});
				}
				// 当前系统归属哪里，如果是 无地图区域的话，图形归属下拉框都不能选
				var system_region = $("#system_region").val();
				if(system_region == -1){
					$(".citySelect").selectmenu("disable");
					$(".cityInput").attr("disabled","disabled");
				}
			}
		});
	}

	function getFormJson(){
		var total_local_graph_id = $("#total_local_graph_id").val();
		var system_region = $("#system_region").val();
		var json = [],region_codes = [];
		$(".cityGraph").each(function(i,v){
			var local_graph_id = $(v).attr("local_graph_id");
			var region_code = "",region_url="";
			if(system_region != -1){ // 如果不是选择自定义省
				region_code = $(v).find("select").val();
    			region_url = $(v).find("input.ui-state-default").val();
    			if($.inArray(region_code,region_codes) > -1){ // 说明已经选择过该地域的图形
    				return true; // 相当于是 continue 
    			}
			}
			region_codes.push(region_code);
			json.push({
				local_graph_id:local_graph_id,
				region_code:region_code,
				region_url:region_url?region_url:"",
				total_local_graph_id:total_local_graph_id,
				system_region:system_region
			});
		});
		return json;
	}
</script>
<?php


