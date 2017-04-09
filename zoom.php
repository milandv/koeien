
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<html> 
 <head> 
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
    <title>Flot Examples</title> 
    <link href="layout.css" rel="stylesheet" type="text/css"> 
    <!--[if lte IE 8]><script language="javascript" type="text/javascript" src="../excanvas.min.js"></script><![endif]--> 
    <script language="javascript" type="text/javascript" src="flot/jquery.js"></script> 
    <script language="javascript" type="text/javascript" src="flot/jquery.flot.js"></script> 
    <script language="javascript" type="text/javascript" src="flot/jquery.flot.selection.js"></script> 
 </head> 
    <body> 
    <h1>Flot Examples</h1> 
 
    <div style="float:left"> 
      <div id="placeholder" style="width:500px;height:300px"></div> 
    </div> 
    
    <div id="miniature" style="float:left;margin-left:20px"> 
      <div id="overview" style="width:166px;height:100px"></div> 
 
      <p id="overviewLegend" style="margin-left:10px"></p> 
    </div> 
 
    <p style="clear:left">The selection support makes it easy to
    construct flexible zooming schemes. With a few lines of code, the
    small overview plot to the right has been connected to the large
    plot. Try selecting a rectangle on either of them.</p> 
 
<script id="source"> 
$(function () {
    // setup plot
    function getData(x1, x2) {
        var d = [];
        for (var i = 0; i <= 100; ++i) {
            var x = x1 + i * (x2 - x1) / 100;
            d.push([x, Math.sin(x * Math.sin(x))]);
        }
 
        return [
            { label: "sin(x sin(x))", data: d }
        ];
    }
 
    var options = {
        legend: { show: false },
        series: {
            lines: { show: true },
            points: { show: true }
        },
        yaxis: { ticks: 10 },
        selection: { mode: "xy" }
    };
 
    var startData = getData(0, 3 * Math.PI);
    
    var plot = $.plot($("#placeholder"), startData, options);
 
    // setup overview
    var overview = $.plot($("#overview"), startData, {
        legend: { show: false, container: $("#overviewLegend") },
        series: {
            lines: { show: true, lineWidth: 1 },
            shadowSize: 0
        },
        xaxis: { ticks: 4 },
        yaxis: { ticks: 3, min: -2, max: 2 },
        grid: { color: "#999" },
        selection: { mode: "x" }
    });
 
    // now connect the two
    
    $("#placeholder").bind("plotselected", function (event, ranges) {
        // clamp the zooming to prevent eternal zoom
        if (ranges.xaxis.to - ranges.xaxis.from < 0.00001)
            ranges.xaxis.to = ranges.xaxis.from + 0.00001;
        if (ranges.yaxis.to - ranges.yaxis.from < 0.00001)
            ranges.yaxis.to = ranges.yaxis.from + 0.00001;
        
        // do the zooming
        plot = $.plot($("#placeholder"), getData(ranges.xaxis.from, ranges.xaxis.to),
                      $.extend(true, {}, options, {
                          xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to },
                          yaxis: { min: ranges.yaxis.from, max: ranges.yaxis.to }
                      }));
        
        // don't fire event on the overview to prevent eternal loop
        overview.setSelection(ranges, true);
    });
    $("#overview").bind("plotselected", function (event, ranges) {
        plot.setSelection(ranges);
    });
});
</script> 
 
 </body> 
</html> 
