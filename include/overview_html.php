<?php
?>
<div class="ov-row">
	<div class="ov-left" id="ov_map">
		显示地图
	</div>
	<div class="ov-right" id="ov_bar">
		显示柱形图
	</div>
</div>
<div class="ov-clearfix"></div>
<div class="ov-row" id="ov_line">
	显示曲线图
</div>
<script>
var mapChart,barChart,lineChart;
$(function(){
	//初始化echarts实例
    mapChart = echarts.init(document.getElementById('ov_map'));
    barChart = echarts.init(document.getElementById('ov_bar'));
    lineChart = echarts.init(document.getElementById('ov_line'));

    setInterval(function(){
		renderMap();
    },13000);
    setInterval(function(){
    	renderLine();
    },14400000);

    $.getJSON("./include/js/420000.geoJson", function(geoJson) {
		echarts.registerMap('hubei', geoJson);
    });
	
	renderMap();
	renderLine();
});

function renderMap(){
	$.ajax({
		dataType:"json",
		url:"overview.php?action=ajax_map",
		success: function(data){
			if(data && data.length > 0){
				// 湖北地图
				var mapOption = {
					title:{
						text:"各地市流量实时统计排名",
						right:"20%"
					},
					tooltip: {
				        show: true,
				        formatter: function(params) {
					        if(params && params.name && params.data){
		    		            return params.name + '：' + params.data['value'] + 'G'
						    }
				        },
				    },
			        series: [{
		            	name: '湖北',
		                type: 'map',
		                top:10,
		                bottom:10,
		                mapType: 'hubei',
		                selectedMode : 'multiple',
		                label: {
		                    normal: {
		                        show: true
		                    },
		                    emphasis: {
		                        show: true
		                    }
		                },
		                data:data
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
	    	left : '10%',
	        right:"10%",
	        top : '12%',
	        bottom : '12%',
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
            		barBorderRadius: 2
            	}
            },
            label: {
                normal: {
                    show: true,
                    position: "insideRight",
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
			console.info(data);
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
				        trigger: 'axis'
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

</script>