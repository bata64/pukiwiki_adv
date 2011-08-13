// Copyright (c) 2009 sakuratan
// Released under the MIT License.
// http://www.opensource.org/licenses/mit-license.php

(function($) {
	var orig_ajax_func = $.ajax;
	var page_tracker = null;
	var options = {
	trackEventCategory: 'jQuery.ajax',
	autoTracking: true
	};
	var queue = [];

	$.ajaxGA = {};

	$.ajaxGA.getOption = function(name) {
	return options[name];
	}

	$.ajaxGA.setOption = function(name, value) {
	if (typeof(name) == 'string') {
		if (typeof(options[name]) != 'undefined') {
		options[name] = value;
		}
	} else {
		for (key in name) {
		if (typeof(options[key]) != 'undefined') {
			options[key] = name[key];
		}
		}
	}
	}

	$.ajaxGA.init = function(trackingId, callbackOrPageview) {
	$(function() {
		orig_ajax_func.apply($, [{
		type: 'GET',
		url: (document.location.protocol == "https:" ?
			  "https://ssl" : "http://www") +
			 '.google-analytics.com/ga.js',
		cache: true,
		dataType: 'script',
		success: function() {
			if (typeof(_gat) == 'undefined') {
			throw new Error('Missing _gat');
			}
			page_tracker = _gat._getTracker(trackingId);

			if (callbackOrPageview) {
			if ($.isFunction(callbackOrPageview)) {
				callbackOrPageview(page_tracker);
			} else {
				if (typeof(callbackOrPageview) == 'string') {
				page_tracker._trackPageview(callbackOrPageview);
				} else {
				page_tracker._trackPageview();
				}
			}
			}

			var q;
			while (q = queue.shift()) {
			q(page_tracker);
			}
		}
		}]);
	});
	};

	$.ajaxGA.doPageTrack = function(callback) {
	if (page_tracker == null) {
		queue.push(callback);
	} else {
		callback(page_tracker);
	}
	};

	function ajax_replacement(params) {
	if (options.autoTracking) {
		if (typeof(params.trackGA) != 'undefined' && !params.trackGA) {
		return orig_ajax_func.apply(this, [params]);
		}
	} else {
		if (!params.trackGA) {
		return orig_ajax_func.apply(this, [params]);
		}
	}

	var orig_complete = params.complete;
	var orig_error = params.error;

	params.complete = function(xhr, status) {
		if (orig_complete) {
		orig_complete(xhr, status);
		}

		if (params.trackGA) {
		if ($.isFunction(params.trackGA)) {
			$.ajaxGA.doPageTrack(params.trackGA);
		} else {
			$.ajaxGA.doPageTrack(function(t) {
			t._trackPageview(params.trackGA);
			});
		}
		} else {
		$.ajaxGA.doPageTrack(function(t) {
			t._trackEvent(options.trackEventCategory,
				  (params.type ? params.type : 'GET'),
				  params.url);
		});
		}

		params.complete = orig_complete;
		params.error = orig_error;
	};

	params.error = function(xhr, status, e) {
		if (orig_error) {
		orig_error(xhr, status, e);
		}
		params.complete = orig_complete;
		params.error = orig_error;
	};

	return orig_ajax_func.apply(this, [params]);
	}

	$.ajaxGA.enable = function() {
	$.extend({
		ajax: ajax_replacement
	});
	};

	$.ajaxGA.disable = function() {
	$.extend({
		ajax: orig_ajax_func
	});
	};

	$.ajaxGA.enable();
})(jQuery);