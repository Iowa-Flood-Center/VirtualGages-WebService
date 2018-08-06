var virtualgages = virtualgages || {};

(function () {
  "use strict";
  
  virtualgages.util = virtualgages.util || {};
  
  var vgu = virtualgages.util;
  
  /**
   * Defines base URL relatively
   */
  (function(){
    var sub_url;
	sub_url = window.location.pathname.split("/");
	sub_url.splice(-1,1);
	sub_url = sub_url.join("/");
	vgu.BASE_URL = window.location.origin + sub_url + "/";
  })();
  
  /**
   *
   */
  vgu.load_and_plot = function(){
    $.when(vgu.load_thresholds(), vgu.load_csv())
      .then(function(thresholds, data){
        vgu.plot_graph(thresholds[0], data);
      });
  }

  /**
   *
   */
  vgu.load_thresholds = function(){
    // defines url
    var ws_url, cur_args;
	cur_args = vgu.get_all_url_params();
	ws_url = vgu.BASE_URL;
    ws_url += "thresholds.php";
	ws_url += "?ifis_id="+cur_args.ifis_id;
	
	// ajax call
    return($.getJSON(ws_url));
  }
  
  /**
   *
   * RETURN:
   */
  vgu.load_csv = function(){
    // defines url
    var ws_url, cur_args;
	cur_args = vgu.get_all_url_params();
	ws_url = vgu.BASE_URL;
    ws_url += "summary.php";
	ws_url += "?ifis_id="+cur_args.ifis_id;
	ws_url += "&forecast_id="+cur_args.forecast_id;
	ws_url += "&show_me=the_truth";
	
	// ajax call
    return($.ajax(ws_url)
      .then(vgu.parse_csv));
  }
  
  /**
   * 
   * date_str:
   * RETURN:
   */
  vgu.parse_date = function(date_str){
    var clean = date_str.replace("'", "").trim();
    var year   = parseInt(clean.substring(0, 4));
    var month  = parseInt(clean.substring(5, 7));
    var day    = parseInt(clean.substring(8, 10));
    var hour   = parseInt(clean.substring(11, 13));
    var minute = parseInt(clean.substring(14, 16));
    var second = parseInt(clean.substring(17, 19));
    return (new Date(year, month-1, day, hour, minute, second));
  }
  
  /**
   *
   * csv_text:
   * RETURN: Array with elements following the structure
   *  {
   *             timestamp:<INT>, 
   *             date_time:<DATE>, 
   *   water_elevation_mdl:<FLOAT>,
   *   water_elevation_obs:<FLOAT>,
   *                  flag:<STRING>,
   *			     alert:<STRING>
   *  }
   */
  vgu.parse_csv = function(csv_text){
	var csv_split = csv_text.split("\n");
        
    // read header
    var headers = csv_split.shift().split(',');
	var data_series = [];
        
    // start timeseries
	var tmst_idx = 1;
	var dttm_idx = 2;
	var elvm_idx = 3;
	var elvo_idx = 9;
	var alrt_idx = 5;
	var flag_idx = 6;
	var min_length = Math.max(tmst_idx, dttm_idx, elvm_idx, elvo_idx, alrt_idx, flag_idx) + 1;
        
    // read each following line
    var cur_line_split, cur_date, cur_data;
    for(var j = 0; j < (csv_split.length); j++){
      cur_line_split = csv_split[j].split(',');
	  if (cur_line_split.length < min_length) continue;
	  
      data_series.push({
                  'timestamp':cur_line_split[tmst_idx],
                  'date_time':vgu.parse_date(cur_line_split[dttm_idx]), 
        'water_elevation_mdl':parseFloat(cur_line_split[elvm_idx]),
        'water_elevation_obs':parseFloat(cur_line_split[elvo_idx]),
                       'flag':cur_line_split[flag_idx].replace("'", "").trim().replace("'", ""),
   	                  'alert':cur_line_split[alrt_idx].replace("'", "").trim().replace("'", "")
      });
    }
    return(data_series);
  }
  
  /**
   *
   * csv_parsed: Array of objects
   * RETURN:
   */
  vgu.plot_graph = function(thresholds, csv_parsed){
    console.log("Parsing "+csv_parsed.length+" elements.");

    var data = new google.visualization.DataTable();
    var all_rows = [];
	var cur_mdl_p, cur_obs_p, cur_mdl_f;
	
	var y_min = null;
	var y_max = null;
	
	var t_fl = thresholds["flood"] > 0 ? thresholds["flood"] : null;
	var t_mo = thresholds["moderate"] > 0 ? thresholds["moderate"] : null;
	var t_ma = thresholds["major"] > 0 ? thresholds["major"] : null;
	
	y_min = ((y_min==null)||(t_fl<y_max ))?t_fl:y_min;
	y_min = ((y_min==null)||(t_mo<y_max ))?t_mo:y_min;
	y_min = ((y_min==null)||(t_ma<y_max ))?t_ma:y_min;
    y_max = ((y_max==null)||(t_fl>y_max ))?t_fl:y_max;
	y_max = ((y_max==null)||(t_mo>y_max ))?t_mo:y_max;
	y_max = ((y_max==null)||(t_ma>y_max ))?t_ma:y_max;

	// find min/max y
	for(var i=0; i < csv_parsed.length; i++){
	  if(csv_parsed[i].flag == "past"){
        cur_mdl_p = csv_parsed[i].water_elevation_mdl;
        cur_mdl_f = null;
		y_min = ((y_min==null)||(cur_mdl_p<y_max ))?cur_mdl_p:y_min;
		y_max = ((y_max==null)||(cur_mdl_p>y_max ))?cur_mdl_p:y_max;

        if(csv_parsed[i].water_elevation_obs > -1){
          cur_obs_p = csv_parsed[i].water_elevation_obs;
          y_min = ((y_min==null)||(cur_obs_p<y_max ))?cur_obs_p:y_min;
          y_max = ((y_max==null)||(cur_obs_p>y_max ))?cur_obs_p:y_max;
        } else {
          cur_obs_p = null;
        }
      } else {
        cur_mdl_p = null;
        cur_obs_p = null;
        cur_mdl_f = csv_parsed[i].water_elevation_mdl;
		y_min = ((y_min==null)||(cur_mdl_f<y_max ))?cur_mdl_f:y_min;
        y_max = ((y_max==null)||(cur_mdl_f>y_max ))?cur_mdl_f:y_max;
      }
    }
	y_min = (y_min != null) ? y_min-1 : null;
	y_max = (y_max != null) ? y_max+1 : null;
	
	// build time series
    for(var i=0; i < csv_parsed.length; i++){
      if(csv_parsed[i].flag == "past"){
        cur_mdl_p = csv_parsed[i].water_elevation_mdl;
        cur_mdl_f = null;

        if(csv_parsed[i].water_elevation_obs > -1){
          cur_obs_p = csv_parsed[i].water_elevation_obs;
        } else {
          cur_obs_p = null;
        }
      } else {
        cur_mdl_p = null;
        cur_obs_p = null;
        cur_mdl_f = csv_parsed[i].water_elevation_mdl;
      }
	  all_rows.push([csv_parsed[i].date_time, 
	                 cur_mdl_p, cur_obs_p, cur_mdl_f,
					 t_fl, t_mo, t_ma,
					 y_min, y_max]);
    }

	//
	data.addColumn('date', "Date");
    data.addColumn('number', "Model past");
    data.addColumn('number', "Observed");
    data.addColumn('number', "Model forecast");
	data.addColumn('number', "FLOOD");
	data.addColumn('number', "MODERATE");
	data.addColumn('number', "MAJOR");
	data.addColumn('number', null);
	data.addColumn('number', null);
	
	data.addRows(all_rows);
    var chart = new google.visualization.AnnotationChart(document.getElementById('plot'));
    var options = {
      title: 'Company Performance',
      displayAnnotations: true,
	  legend: true,
      vAxis: {minValue: 1105, maxValue: 1115},
      colors: ['#0000FF', '#000000', '#00FF00', 'orange', 'red', 'purple', 'grey', 'grey']
    };
	
	// draw chart
    chart.draw(data, options);
  }
  
  /******************************** THIRD PARTY ********************************/
  
  /**
   * Credits: 'https://www.sitepoint.com/get-url-parameters-with-javascript/'
   * url:
   * RETURN:
   */
  vgu.get_all_url_params= function(url) {

    // get query string from url (optional) or window
    var queryString = url ? url.split('?')[1] : window.location.search.slice(1);

    // we'll store the parameters here
    var obj = {};
	var arr, a, paramNum, paramName, paramValue;

    // if query string exists
    if (queryString) {

      // stuff after # is not part of query string, so get rid of it
      queryString = queryString.split('#')[0];

      // split our query string into its component parts
      arr = queryString.split('&');

      for (var i=0; i<arr.length; i++) {
        // separate the keys and the values
        a = arr[i].split('=');

        // in case params look like: list[]=thing1&list[]=thing2
        paramNum = undefined;
        paramName = a[0].replace(/\[\d*\]/, function(v) {
          paramNum = v.slice(1,-1);
          return '';
        });

        // set parameter value (use 'true' if empty)
        paramValue = typeof(a[1])==='undefined' ? true : a[1];

        // (optional) keep case consistent
        paramName = paramName.toLowerCase();
        paramValue = paramValue.toLowerCase();

        // if parameter name already exists
        if (obj[paramName]) {
          // convert value to array (if still string)
          if (typeof obj[paramName] === 'string') {
            obj[paramName] = [obj[paramName]];
          }
          // if no array index number specified...
          if (typeof paramNum === 'undefined') {
            // put the value on the end of the array
            obj[paramName].push(paramValue);
          }
          // if array index number specified...
          else {
            // put the value at that index number
            obj[paramName][paramNum] = paramValue;
          }
        }
        // if param name doesn't exist yet, set it
        else {
          obj[paramName] = paramValue;
        }
      }
    }

    return obj;
  }
  
})();