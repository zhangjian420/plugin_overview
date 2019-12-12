<?php
// 地市
$city_regions = db_fetch_assoc("select * from region where level = 1 order by `code`");
$dis_regions = db_fetch_assoc("select * from region where pcode = $system_region order by `code`");
?>
<style>
    .ui-dialog-titlebar{background:#2C9A42;color: #fff}
</style>
<div class="ov-row" id="ov_srch">
	<div class="spacer formHeader" id="row_spacer1" style="float: none;">
		<div class="formHeaderText">全省流量汇总图<div class="formHeaderAnchor" onclick="toggCol(this)">
			<i class="fa fa-angle-double-down"></i></div>
		</div>
	</div>
	<div id="row_name" class="formRow odd" style="display: none">
		<span style="margin-left: 20px">全省流量汇总图： </span>
		<span>地市：<select name="city_region" id="city_region" <?php if($level == 1){?>disabled="disabled"<?php }?>>
			<?php 
			foreach ($city_regions as $region){
			?>
				<option value="<?php print $region["code"] ?>" <?php if($region["code"] == $system_region){?>selected="selected"<?php }?>><?php print $region["name"] ?></option>
			<?php   
			}
			?>
		</select></span>
		<span>
			区/县：<select name="dis_region" id="dis_region" <?php if($level == 0){?>disabled="disabled"<?php }?>>
			<?php 
			foreach ($dis_regions as $region){
			?>
				<option value="<?php print $region["code"] ?>"><?php print $region["name"] ?></option>
			<?php   
			}
			?>
		</select>
		</span>
		<span>
			<input type="button" class="save ui-button ui-corner-all ui-widget" value="执行" onclick="runBtn()">
		</span>
	</div>
</div>
<div class="ov-row">
	<div class="ov-tj" id="ov_tj">
		<div class="tj-title">总流量</div>
		<div class="tj-con" id="tj_con">0</div>
	</div>
	<div class="ov-left" id="ov_map">
		显示地图
	</div>
	<div class="ov-right" id="ov_bar">
		显示柱形图
	</div>
    <div class="ov-clearfix"></div>
</div>
<div class="ov-row" id="ov_line">
	显示曲线图
</div>
<div id="tj_dialog" title="近30日总流量趋势图">
	<div id="tj_line">
		
	</div>
</div>
<script>
var mapChart,barChart,lineChart,tjLineChart,myColor=["#2c9a42","#d08a00","#c23c33"],data_tmp = [];
$(function(){
	$("#tj_dialog").dialog({
		autoOpen: false,
		modal: true,
		width:"auto",
		buttons: {
	        "确定": function() {
				$( this ).dialog( "close" );
				//$("#tj_line").empty();
			}
		}
    });

    $("#ov_tj").on( "click", function() {
    	$( "#tj_dialog" ).dialog( "open" );
    });
	
	//初始化echarts实例
    mapChart = echarts.init(document.getElementById('ov_map'));
    barChart = echarts.init(document.getElementById('ov_bar'));
    lineChart = echarts.init(document.getElementById('ov_line'));
    tjLineChart = echarts.init(document.getElementById('tj_line'));

    setInterval(function(){
		renderMap();
    },13000);
    setInterval(function(){
    	renderLine();
    	renderTjLine();
    },14400000);

    $.getJSON("./include/js/<?php print $system_region?>.geoJson", function(geoJson) {
		echarts.registerMap('hubei', geoJson);
    });
	
	renderMap();
	renderLine();
	renderTjLine();

	mapChart.on('click', function(params){
		var data = params.data;
		if(data.url && data.url.length > 10){
			window.open(data.url);
		}else{
			alert("["+data.name+"]没有配置子系统，请核实！");
		}
	});

});

function renderMap(){
	$.ajax({
		dataType:"json",
		url:"overview.php?action=ajax_map",
		success: function(data){
			if(data && data.length > 0){
				var tj = data.pop();
                if(tj != 0){
                	$("#tj_con").text(tj+"G");
                }
                data_tmp = [];
				for(var i=0;i<data.length;i++){
					data_tmp.push({
						name:data[i].name,
						value:(data[i].value/data[i].upper_limit*100).toFixed(2),
						traffic:data[i].value,
						code:data[i].code,
						url:data[i].url
					});
				}
				// 湖北省或者地市地图
				var mapOption = {
					title:{
						text:"全省宽带业务流量监控大屏",
						right:"20%"
					},
					tooltip: {
				        show: true,
				        formatter: function(params) {
					        if(params && params.name && params.data){
		    		            return params.name + '：通道容量' + params.data['value'] + '% ，流量' + params.data['traffic'] + "G"
						    }
				        },
				    },
				    visualMap: {
		                type: 'piecewise',
		                pieces: [{
		                	lte:80,
	                        label: '正常',
	                        color: myColor[0]
						},{
							gt: 80,
							lt: 90,
	                        label: '告警',
	                        color: myColor[1]
						},{
							gte: 90,
	                        label: '严重',
	                        color: myColor[2]
						}]
		            },
			        series: [{
		            	name: '湖北',
		                type: 'map',
		                top:10,
		                bottom:10,
		                mapType: 'hubei',
		                label: {
		                    normal: {
		                        show: true,
		                        formatter: function(params) {
									return params.name + (params.value ? ("\n" + params.value + "%") : "");
		                        },
		                        padding: [2, 2],
		                        position: 'inside',
		                        backgroundColor: '#fff',
		                        color: '#333'
		                    },
		                    emphasis: {
		                        show: true
		                    }
		                },
		                data:data_tmp
		            }]
		        };
				mapChart.setOption(mapOption);
				renderBar(data);
			}
		}
	});
}

function renderBar(data){
	var cata = [];
	for(var i=0;i<data.length;i++){
		cata.push(data[i].name);	
	}
	// 柱形图排名
	var barOption = {
		tooltip: {
	        formatter: '{b} {c}G',
	        trigger: 'axis',
	        axisPointer: {
	            type: 'shadow'
	        }
	    },
	    grid:{
	    	left : '7%',
	        right:"10%",
	        top : '5%',
	        bottom : '2%',
	        containLabel: true
	    },
	    xAxis: {
	       show:false
	    },
	    yAxis: {
	        type: 'category',
	        inverse: true,
	        axisLabel: {
                margin:8
            },
	        data: cata
	    },
	    series: [{
            name: '地市',
            type: 'bar',
            barWidth: '45%',
            itemStyle: {
            	normal: {
            		barBorderRadius: 2,
            		color: function(params) {
                		var data = params.data;
                        var rate = (data.value/data.upper_limit*100).toFixed(2);
						if(rate<=80){
							return myColor[0];
						}else if(rate > 80 && rate < 90){
							return myColor[1];
						}else{return myColor[2]}   
                    },
                    shadowBlur: 15,
                    shadowColor: 'rgba(40, 40, 40, 0.5)'
            	}
            },
            label: {
                normal: {
                    show: true,
                    position: "right",
                    formatter: '{c}G'
                }
            },
            data: data
	    }]
	};
	barChart.setOption(barOption);	
}

function renderLine(){
	$.ajax({
		dataType:"json",
		url:"overview.php?action=ajax_line",
		success: function(data){
			if(data){
				var series = [];
				for(var key in data.datas){
					series.push({
			            name:key,
			            smooth:true, //平滑
			            symbol:'circle',
			            type:'line',
			            data:data.datas[key]
					});
				}
				var lineOption = {
				    tooltip: {
				        trigger: 'axis',
				        formatter:function(params){
				        	params.sort(compare("value"));
					        var html = params[0].name;
							for(var i=0;i<params.length;i++){
								html += ("<br/>" + params[i].seriesName + ": " + params[i].value + "G");
// 								html += ("<br/><span style='color:"+params[i].color+"'>" 
// 											+ params[i].seriesName + ": " + params[i].value + "G</span>");
							}
							return html;
						}
				    },
				    legend: {
						show:true
				    },
				    grid : {
				        left : '5%',
				        right:"5%",
				        top : '12%',
				        bottom : '12%'
				    },
				    xAxis: {
				        type: 'category',
				        boundaryGap: false,
				        data: data.times,
				        axisLabel: {
				            show: true,
				            textStyle: {
				                color: '#666'
				            }
				        }
				    },
				    yAxis: {
				        type: 'value',
				        axisLine: {
				            show: false
				        },
				        splitLine: {
				            lineStyle: {
				                type: 'dashed',
				                color: '#DDD'
				            }
				        },
				        axisLabel: {
				            color: '#666',
				            formatter: '{value}G',
				        }
				    },
				    series: series
				};
				lineChart.setOption(lineOption);
			}
		}
	});
}

function renderTjLine(){
	$.ajax({
		dataType:"json",
		url:"overview.php?action=ajax_tj_line",
		success: function(data){
			if(data){
				var lineOption = {
					tooltip: {
				        trigger: 'axis',
				        formatter:"{b0}<br>{c0}G"
				    },
				    legend: {
						show:true
				    },
				    grid : {
				        left : '5%',
				        right:"5%",
				        top : '12%',
				        bottom : '12%'
				    },
				    xAxis: {
				        type: 'category',
				        boundaryGap: false,
				        data: data.times,
				        axisLabel: {
				            show: true,
				            textStyle: {
				                color: '#666'
				            }
				        }
				    },
				    yAxis: {
				        type: 'value',
				        axisLine: {
				            show: false
				        },
				        splitLine: {
				            lineStyle: {
				                type: 'dashed',
				                color: '#DDD'
				            }
				        },
				        axisLabel: {
				            color: '#666',
				            formatter: '{value}G',
				        }
				    },
				    series: [{
			            smooth:true, //平滑
			            symbol:'circle',
			            type:'line',
			            data:data.datas
					}]
				};
				tjLineChart.setOption(lineOption);
			}
		}
	});
	
}

function compare(propertyName) {
    return function(object1, object2) {
        var value1 = object1[propertyName];
        var value2 = object2[propertyName];
        value1 = parseFloat(value1);
        value2 = parseFloat(value2);
        if(value2 < value1) {
            return -1;
        } else if(value2 > value1) {
            return 1;
        } else {
            return 0;
        }
    }
}

function toggCol(me){
	var icls = $(me).find("i");
	if(icls.hasClass("fa-angle-double-up")){
		$("#row_name").hide();
		icls.removeClass('fa-angle-double-up').addClass('fa-angle-double-down');		
	}else{
		$("#row_name").show();
		icls.removeClass('fa-angle-double-down').addClass('fa-angle-double-up');		
	}	
}

function runBtn(){
	<?php if($level == 0){
	?>
	var cityRegion = $("#city_region").val();
	<?php     
	}?>
	<?php if($level == 1){
	?>
	var cityRegion = $("#dis_region").val();
	<?php     
	}?>
	for(var i=0;i<data_tmp.length;i++){
		var code = data_tmp[i].code;
		if(cityRegion == code){
			if(data_tmp[i].url && data_tmp[i].url.length > 10){
				window.open(data_tmp[i].url);
			}else{
				alert("地市["+data_tmp[i].name+"]没有配置子系统，请核实！");
			}
		}
	}
}

</script>