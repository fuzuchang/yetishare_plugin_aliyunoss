// JavaScript Document

/************************************************************************************ PRELOADER STARTS */

jQuery(window).load(function() {

    'use strict';

    $('#preloader').fadeOut('slow');

});

/************************************************************************************ PRELOADER ENDS */

/************************************************************************************ STICKY NAVIGATION STARTS */

$(window).load(function() {

    'use strict';

    $("#navigation").sticky({topSpacing: 0});

});

/************************************************************************************ STICKY NAVIGATION ENDS */

/************************************************************************************ ONEPAGE NAVIGATION STARTS */

$(document).ready(function() {

    'use strict';

    $('.nav').onePageNav({
        filter: ':not(.external)'
    });

});

/************************************************************************************ ONEPAGE NAVIGATION ENDS */

/************************************************************************************ PAGE ANIMATED ITEMS STARTS */

jQuery(document).ready(function($) {

    'use strict';

    $('.animated').appear(function() {
        var elem = $(this);
        var animation = elem.data('animation');
        if (!elem.hasClass('visible')) {
            var animationDelay = elem.data('animation-delay');
            if (animationDelay) {

                setTimeout(function() {
                    elem.addClass(animation + " visible");
                }, animationDelay);

            } else {
                elem.addClass(animation + " visible");
            }
        }
    });
});

/************************************************************************************ PAGE ANIMATED ITEMS ENDS */


/************************************************************************************ TO TOP STARTS */

$(document).ready(function() {

    'use strict';

    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('.scrollup').fadeIn();
        } else {
            $('.scrollup').fadeOut();
        }
    });

    $('.scrollup').click(function() {
        $("html, body").animate({scrollTop: 0}, 600);
        return false;
    });

});


/************************************************************************************ TO TOP ENDS */

function showUploaderPopup()
{
    jQuery('#fileUploadWrapper').modal('show', {backdrop: 'static'}).on('shown.bs.modal');
}

function createSlowGauge()
{
    var opts = {
        lines: 12, // The number of lines to draw
        angle: 0, // The length of each line
        lineWidth: 0.25, // The line thickness
        pointer: {
            length: 0.9, // The radius of the inner circle
            strokeWidth: 0.06, // The rotation offset
            color: '#000000' // Fill color
        },
        limitMax: 'false', // If true, the pointer will not go past the end of the gauge
        colorStart: '#CF0808', // Colors
        colorStop: '#DA0202', // just experiment with them
        strokeColor: '#E0E0E0', // to see which ones work best for you
        generateGradient: true
    };
    var target = document.getElementById('canvas-slow'); // your canvas element
    var gauge = new Gauge(target).setOptions(opts); // create sexy gauge!
    gauge.maxValue = 100; // set max gauge value
    gauge.animationSpeed = 32; // set animation speed (32 is default value)
    gauge.set(9); // set actual value
}


function createFastGauge()
{
    var opts = {
        lines: 12, // The number of lines to draw
        angle: 0, // The length of each line
        lineWidth: 0.25, // The line thickness
        pointer: {
            length: 0.9, // The radius of the inner circle
            strokeWidth: 0.06, // The rotation offset
            color: '#000000' // Fill color
        },
        limitMax: 'false', // If true, the pointer will not go past the end of the gauge
        percentColors: [[0.0, "#FF0000" ], [0.50, "#FFFF00"], [1.0, "#33CC33"]],
        strokeColor: '#E0E0E0', // to see which ones work best for you
        generateGradient: true
    };
    var target = document.getElementById('canvas-fast'); // your canvas element
    var gauge = new Gauge(target).setOptions(opts); // create sexy gauge!
    gauge.maxValue = 100; // set max gauge value
    gauge.animationSpeed = 32; // set animation speed (32 is default value)
    gauge.set(100); // set actual value
}

// smooth scrolling for anchor links
!function ($) {
	$(function(){
		var $root = $('html, body');
 
		$('.smooth-anchor-link').click(function() {
			var href = $.attr(this, 'href');
			$root.animate({
				scrollTop: $(href).offset().top
			}, 500, function () {
				window.location.hash = href;
			});
			return false;
		});
	})
}(window.jQuery);

function showSuccessNotification(title, content)
{
	alert(title+': '+content);
}

function showErrorNotification(title, content)
{
	alert(title+': '+content);
}