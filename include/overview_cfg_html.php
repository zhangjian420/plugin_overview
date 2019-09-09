<?php

$citys = db_fetch_assoc("select * from region where level = 1 order by `code`");
$trees = db_fetch_assoc("SELECT id,name FROM graph_tree ORDER BY name");

?>

<div class="formRow firstRow even">
	<div class="formColumnLeft" style="width: 30%">
		<div class="formFieldName">
			大屏使用的树
		</div>
	</div>
	<div class="formColumnRight" style="width: 65%">
		<div class="formData">
			<div class="citygraphs">
    			<select class="searchableSelect" name="tree_id">
    				<?php 
    				foreach ($trees as $tree){
    				    if($tree["id"] == get_request_var("tree_id")){
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
</div>

<script type="text/javascript">
	$(function(){
		loadCityGraph();
		
		$("select[name='tree_id']").searchableSelect({
			width:168,show_srch:true,afterItemClick:loadCityGraph
		});
	});

	function loadCityGraph(){
		$.ajax({
			dataType:"json",
			url:"overview.php?action=ajax_graph&tree_id="+$("select[name='tree_id']").val(),
			success: function(data){
				if(data && data.length > 0){
					$(".formRow:not(.firstRow)").remove();
					for(var i=0;i<data.length;i++){
						var dt = data[i];
						var html = '';
						html += '<div class="formRow cityGraph" local_graph_id="'+dt.local_graph_id+'">';
							html += '<div class="formColumnLeft" style="width: 30%">';
								html += '<div class="formFieldName" style="float:right;margin-right:10px;">';
									html += dt.title_cache;
								html += '</div>';
							html += '</div>';
							html += '<div class="formColumnRight" style="width: 65%">';
								html += '<div class="formData">';
									html += '<select class="searchableSelect">';
									<?php foreach ($citys as $city){?>
									if(dt.region_code == <?php print $city["code"]?>){
    									html += '<option value="<?php print $city["code"] ?>" selected="selected"><?php print $city["name"] ?></option>';
    								}else{
    									html += '<option value="<?php print $city["code"] ?>"><?php print $city["name"] ?></option>';
        							}
				    				<?php } ?>
									html += '</select>';
								html += '</div>';
							html += '</div>';
						html += '</div>';
						var $h = $(html);
						$h.find("select.searchableSelect").searchableSelect({width:168});
						$(".firstRow").after($h);
					}
				}
			}
		});
	}

	function getFormJson(){
		var json = [],region_codes = [];
		$(".cityGraph").each(function(i,v){
			var local_graph_id = $(v).attr("local_graph_id");
			var region_code = $(v).find("select").val();
			if($.inArray(region_code,region_codes) > -1){ // 说明已经选择过该地域的图形
				return true; // 相当于是 continue 
			}
			region_codes.push(region_code);
			json.push({
				local_graph_id:local_graph_id,
				region_code:region_code
			});
		});
		return JSON.stringify(json);
	}
</script>
<?php


