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
var mapChart,barChart,lineChart,myColor=["#2c9a42","#d08a00","#c23c33"];
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
				var data_tmp = [];
				for(var i=0;i<data.length;i++){
					data_tmp.push({
						name:data[i].name,
						value:(data[i].value/data[i].upper_limit*100).toFixed(2),
						traffic:data[i].value
					});
				}
				// 湖北地图
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
                    }
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

</script>