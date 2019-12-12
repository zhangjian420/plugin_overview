<style>
    .ui-dialog-titlebar{background:#2C9A42;color: #fff}
    #ov_bar1,#ov_bar2,#ov_bar3{height: 400px;width: 30%}
    #ov_bar1{margin-left: 100px;}
    #tj_con{background: #fff}
</style>
<div class="ov-row">
	<div class="ov-tj" id="ov_tj">
		<div class="tj-title">总流量</div>
		<div class="tj-con" id="tj_con">0</div>
	</div>
	<div class="ov-left" id="ov_bar1">
		显示柱形图1
	</div>
	<div class="ov-left" id="ov_bar2">
		显示柱形图2
	</div>
	<div class="ov-left" id="ov_bar3">
		显示柱形图2
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
var barChart1,barChart2,barChart3,tjLineChart,myColor=["#2c9a42","#d08a00","#c23c33"],data_tmp = [];
$(function(){
	$("#tj_dialog").dialog({
		autoOpen: false,
		modal: true,
		width:"auto",
		buttons: {
	        "确定": function() {
				$( this ).dialog( "close" );
			}
		}
    });

    $("#ov_tj").on( "click", function() {
    	$( "#tj_dialog" ).dialog( "open" );
    });
	
	//初始化echarts实例
    barChart1 = echarts.init(document.getElementById('ov_bar1'));
    barChart2 = echarts.init(document.getElementById('ov_bar2'));
    barChart3 = echarts.init(document.getElementById('ov_bar3'));
    lineChart = echarts.init(document.getElementById('ov_line'));
    tjLineChart = echarts.init(document.getElementById('tj_line'));

    setInterval(function(){
		renderBar();
    },13000);
    setInterval(function(){
    	renderLine();
    	renderTjLine();
    },14400000);

    renderBar();
	renderLine();
	renderTjLine();

});

function renderBar(){
	$.ajax({
		dataType:"json",
		url:"overview.php?action=ajax_qu_bar",
		success: function(data){
			if(data && data.length > 0){
				var tj = data.pop();
                if(tj != 0){
                	$("#tj_con").text(tj+"G");
                }
                var cata1=[],cata2=[],cata3=[],data1=[],data2=[],data3=[];
            	for(var i=0;i<data.length;i++){
                	if(i<10){
                		cata1.push(data[i].name);
                		data1.push(data[i]);
                    }else if(i>=10 && i<20){
                    	cata2.push(data[i].name);
                		data2.push(data[i]);
                    }else{
                    	cata3.push(data[i].name);
                		data3.push(data[i]);
                    }
            	}
            	renderF(barChart1,cata1,data1);
            	renderF(barChart2,cata2,data2);
            	renderF(barChart3,cata3,data3);
			}
		}
	});

	var renderF = function(var1,cata,data){
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
	                margin:8,
	                formatter: function (params){   //标签输出形式 ---请开始你的表演
		                return params.substring(0,10);
	                },
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
		var1.setOption(barOption);
	}
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
							}
							return html;
						}
				    },
				    legend: {
				    	type: 'scroll',
				    	width:"90%"
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

</script>