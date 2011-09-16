<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    s"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <title>threshold</title>
    
    <link type="text/css" href="css/jquery-ui-1.8.12.custom.css" rel="stylesheet" />
    <style>
        
        html {
            cursor: default;
        }
        
        div.plot_window {
            margin: 40px 0px;
            width: 900px;
            height: 240px;
            float: left;
        }
        
        #percentage {
            color: white;
        }
        
        .slider {
            height: 270px;
            float: right;
        }
    
    </style>
    
    <!--[if IE]><script src="js/excanvas.js"></script><![endif]-->
    <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.12.custom.min.js"></script>
    <script type="text/javascript" src="js/jquery.flot.js"></script>
    <script type="text/javascript" src="js/jquery.flot.threshold.js"></script>
    <script type="text/javascript" src="js/date.js"></script>
    <script type="text/javascript" src="js/geopod.js"></script>
    
    <script type="text/javascript">
        
        $(document).ready(function() {
            var points = [<?php echo join(", ", array_map(function($x) { return '"' . $x["id"] . '"'; }, $points)); ?>];
            var start = '<?php echo $start ?>';
            var end = '<?php echo $end ?>';
            var plot;
            var slider;
            var data_series = [];
            var sorted_series = [];
            
            function drawGraph(graph_points, start, end) {
                
                var query_string_dict = {
                    subdomain: '<?php echo $geopod["Geopod"]["subdomain"] ?>',
                    points: graph_points,
                    start: start,
                    end: end
                };
                
                $.get('/threshold/data/', query_string_dict, function(data) {
                    var start = Date.parse(data.start_date).getTime() - data.utc_offset;
                    var end = Date.parse(data.end_date + " 23:59:59").getTime() - data.utc_offset;
                    var min = Infinity;
                    var max = -Infinity;
                    
                    for( var i=0, len=data.series.length; i<len; ++i ) {
                        for( var j=0; j<data.series[i].data.length; ++j ) {
                            data.series[i].data[j][0] = data.series[i].data[j][0] * 1000 - data.utc_offset;
                            
                            min = data.series[i].data[j][1] < min ? data.series[i].data[j][1] : min;
                            max = data.series[i].data[j][1] > max ? data.series[i].data[j][1] : max;
                        }
                        
                        sorted_series.push(data.series[i].data.slice(0));
                        sorted_series[i].sort(function(a, b) {
                            return a[1] - b[1];
                        })
                        
                        data_series.push({
                            label: data.series[i].name,
                            data: data.series[i].data,
                            hoverable: false,
                            clickable: false,
                            series_id: data.series[i].point_id,
                            unit: data.series[i].unit
                        });
                    }
                    
                    var options = {
                        xaxis: {
                            mode: "time",
                            twelveHourClock: false,
                            min:  start,
                            max: end
                        },
                        yaxis: {
                            labelWidth: 75,
                            
                        },
                        grid: {
                            tickColor: "#252525",
                            color: "#FFFFFF",
                            borderWidth: 0,
                            hoverable: true,
                            clickable: true
                        },
                        legend: {
                            show: true,
                            backgroundOpacity: 0,
                            noColumns: 8,
                        },
                        series: {
                            threshold: {
                                below: -5.0,
                                color: "red"
                            }
                        }
                    };
                    
                    $('#plot_container').append('<div class="plot_window"></div><div class="slider"></div>');
                    slider = $( ".slider" ).slider({
                        orientation: "vertical",
                        range: "min",
                        min: min,
                        max: max,
                        step: 0.1,
                        value: -5.0,
                        slide: function( event, ui ) {
                            var limit = ui.value;
                            var count = 0;
                            
                            for( var i=0; i<data_series.length; ++i ) {
                                data_series[i].threshold = {
                                    below: limit,
                                    color: "red"
                                };
                                
                                for( var j=0; j<sorted_series[i].length; ++j ) {
                                    if( sorted_series[i][j][1] > limit )
                                        break;
                                    count += 1;
                                }
                            }
                            $('#threshold').val(ui.value);
                            $('#percentage').html(Math.round((count / sorted_series[0].length) * 10000)/100 + "%");
                            
                            if( plot ) {
                                plot.setData(data_series);
                                plot.setupGrid();
                                plot.draw();
                            }
                        }
                    });
                    
                    var placeholder = $('div#plot_container div.plot_window');
                    plot = $.plot(placeholder, data_series, options);
                    
                    //var height = $(document.body).height() + 50;
                    //geopod.setSize({height:height});
                    
                }, "json");
            }
            
            $('#threshold').change(function() {
                var limit = parseFloat($(this).val());
                var count = 0;
                
                for( var i=0; i<data_series.length; ++i ) {
                    data_series[i].threshold = {
                        below: limit,
                        color: "red"
                    };
                    
                    for( var j=0; j<sorted_series[i].length; ++j ) {
                        if( sorted_series[i][j][1] > limit )
                            break;
                        count += 1;
                    }
                }
                slider.slider("value", limit);
                
                $('#percentage').html(Math.round((count / sorted_series[0].length) * 10000)/100 + "%");
                
                if( plot ) {
                    plot.setData(data_series);
                    plot.setupGrid();
                    plot.draw();
                }
            });
            
            drawGraph(points, start, end);
        });
        
    </script>
    
</head>

<body>
    <input id="threshold" type="text"/>
    <p id="percentage"></p>
    <div id="plot_container"></div>
    <a href="#" onclick="geopod.setSize({height:400}); return false;">resize</a>
</body>
</html>
