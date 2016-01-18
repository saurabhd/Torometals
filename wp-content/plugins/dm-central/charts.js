// chart functions
jQuery(document).ready(function($) {
	// retrieves and graphs the spot price history
	// 'start' and 'end' set the period of history we're interested in
	// 'metal' selects the correct precious metal - gold, silver, platinum, or palladium
	// 'series' select the data we want - intraday, close, or ratio
	$.fn.graphSpotHistory = function (start, end, metal, series) {
		// select string format for tick labels
		var domain = end - start;
		if (domain < 7200000) { // two hours
			var timeFormat = '%H:%M';
			var tooltipTimeFormat = '%H:%M:%S';
		}
		else if (domain < 172800000) { // two days
			var timeFormat = '%a %H:%M';
			var tooltipTimeFormat = '%A %H:%M';
		}
		else if (domain < 5259600000) { // two months
			var timeFormat = '%b %#d';
			var tooltipTimeFormat = '%B %#d';
		}
		else if (start.getFullYear() >= new Date().getFullYear()) { // start is sometime this year
			var timeFormat = '%b';
			var tooltipTimeFormat = '%B %#d';
		}
		else {
			var timeFormat = '%b %Y'
			var tooltipTimeFormat = '%B %#d, %Y';
		}
		
		var valFormat = (series == 'ratio' ? '' : currencySymbol) + '#,###.00';  // currencySymbol set in catalog.php
		
		var target = $(this);
		
		$.ajax({
			type: "GET",
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			url: ajaxurl,
			data: { 'action':'spot_history',
					'series':series,
					'start':start.getTime(),
					'end':end.getTime(),
					'metal':metal },
			success: function (response) {
				var data = [];
				var debug = '';
				for (var i=0; i<response.length; i++) {				
					// data comes from server as an array of [[epoch milliseconds, metal value]]
					var time = new Date(response[i].timestamp)
					data.push([time, response[i].value]);
					debug += '['+ time.getHours() + ':' + time.getMinutes() + ',' + response[i].value + ']\n';
				}
				//console.log(debug);
				target.jqplot([data], {
					series:[{ 
						showMarker:false,
						color:'rgb(217,171,69)',
						lineWidth:2
					}],
					axesDefaults:{ 
						tickRenderer:$.jqplot.CanvasAxisTickRenderer,
						tickOptions:{ 
							textColor:'rgb(151,147,144)'
						} 
					},
					axes:{ 
						xaxis:{ 
							renderer:$.jqplot.DateAxisRenderer,
							min:start,
							max:end,
							drawMajorGridlines:false,
							drawMinorGridlines:false,
							tickOptions:{
								markSize:6,
								formatString: timeFormat
							}
						},
						yaxis:{
							tickOptions:{ 
								markSize:36
							}
						}
					},
					grid:{
						background:'transparent',
						drawBorder:false,
						shadow:false
					},			
					cursor:{ 
						show:true,
						showTooltip:false,
						zoom:true 
					},			
					highlighter:{
						show:true,
						tooltipContentEditor: function(str, seriesIndex, pointIndex, plot) {
							var date = new Date(plot.data[seriesIndex][pointIndex][0]);
							var value = plot.data[seriesIndex][pointIndex][1];
							var html = '<div class="graph-tooltip">';
							html += $.jsDate.strftime(date, tooltipTimeFormat) + '<br/>';
							html += $.formatNumber(value, {format:valFormat, locale:"us"});
							html += '</div>';
							
							return html;
						}
					}				
				});
			}
		});
	}
	
	$('.graph-container .btn-group a, .graph-control .btn-group a').click(function () {
		// TODO: figure out how to redraw the plot if we don't 
		// need new data
		// if ($(this).hasClass('active')) {
			// $('#chartContent').replot();
			// return;
		// }
		var context = $(this).closest('.graph-container'); // home page graph
		if (context.length == 0)
			context = $(this).closest('.widget-container'); // price history page graph
		
		$(this).siblings().removeClass("active btn-primary");
		$(this).addClass("active btn-primary");
		//addSpinner('#spinnerZoneIntraDay', 'Updating');
		
		// get selected series
		var series = $('#chart-select .active', context).attr('id').slice(6);  // slice removes 'chart-' from the id
				
		// get selected metal
		var metal = $('#metal-select .active', context).attr('id').slice(6);
		
		// adjust metal button row
		if (series == 'ratio') {
			if (metal == 'gold')
			{
				$('#chart-silver', context).addClass("active btn-primary");  // gold is going away, so set another metal active
				$('#chart-gold', context).removeClass("active btn-primary");
				metal = 'silver';
			}
			$('#chart-gold', context).hide();
			$('#gold-to', context).show();
		}
		else {
			// restore gold button if not present
			$('#gold-to', context).hide();
			$('#chart-gold', context).show();
		}
		
		// adjust date zoom row
		if (series == 'intraday') {
			$('#zoom a', context).hide();  // this code doesn't apply to Price History page - using .widget-container excludes that
			$('#chart-hour,#chart-day,#chart-all', context).show();
			if (!$('#zoom .active', context).is(':visible')) {
				// pick a default selection from the visible buttons
				$('#zoom .active', context).removeClass("active btn-primary");
				$('#chart-day', context).addClass("active btn-primary");
			}
		}
		else {
			$('#zoom a', context).hide();
			$('#chart-month,#chart-3month,#chart-6month,#chart-ytd,#chart-year,#chart-all', context).show();
			if (!$('#zoom .active', context).is(':visible')) {
				// pick a default selection from the visible buttons
				$('#zoom .active', context).removeClass("active btn-primary");
				$('#chart-3month', context).addClass("active btn-primary");
			}
		}
		
		// get selected date range
		var start = new Date();
		switch ($('#zoom .active', context).attr('id')) {
			case 'chart-hour':
				start.setHours(start.getHours() - 1);
				series = 'intraday';
				break;
			case 'chart-day':
				start.setDate(start.getDate() - 1);
				series = 'intraday';
				break;
			case 'chart-month':
				start.setMonth(start.getMonth() - 1);
				if (series != 'ratio') 
					series = 'close';
				break;
			case 'chart-3month':
				start.setMonth(start.getMonth() - 3);
				if (series != 'ratio') 
					series = 'close';
				break;
			case 'chart-6month':
				start.setMonth(start.getMonth() - 6);
				if (series != 'ratio') 
					series = 'close';
				break;
			case 'chart-ytd':
				// start = Jan. 1 00:00:00
				start.setMonth(0);start.setDate(1);start.setHours(0);start.setMinutes(0);start.setSeconds(0);
				if (series != 'ratio') 
					series = 'close';
				break;
			case 'chart-year':
				start.setFullYear(start.getFullYear() - 1);
				if (series != 'ratio') 
					series = 'close';
				break;
			//case 'chart-all':
				//start = new Date(0); // beginning of epoch time
		}
		var end = new Date();
		
		$('.chartContent', context).html('');
		$('.chartContent', context).graphSpotHistory(start, end, metal, series);
		
		//removeSpinner('#spinnerZoneIntraDay');
	});	

	// default graph
	$('#chart-select .active').click();	
});