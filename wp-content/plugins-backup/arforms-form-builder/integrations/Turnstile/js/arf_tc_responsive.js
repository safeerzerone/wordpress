/* window.addEventListener("message", function (e) {
	if (e.data.event !== "init") {
		return;
	}

	const iframes = document.querySelectorAll('iframe[id^="cf-chl-widget-"]');

	iframes.forEach(iframe => {
		console.log( 'Applying the width' );
		iframe.style.width = '210px';
	});
  
  

	function tc_set_width(x)
	{
		let tc_lrg_width = document.getElementById("cf-chl-widget-" + e.data.widgetId);
		if(!tc_lrg_width){
			return;
		}
		if (x.matches)
		{
			tc_lrg_width.style.width = "300px";
		} else {
			tc_lrg_width.style.width = "250px";
		}
	}

	function tc_md_set_width(y)
	{
		let tc_md_width = document.getElementById("cf-chl-widget-" + e.data.widgetId);
		if(!tc_md_width){
			return;
		}
		if (y.matches)
		{
			tc_md_width.style.width = "250px";
		} else {
			tc_md_width.style.width = "210px";
		}
	}

	function tc_sml_set_width(z)
	{
		let turnstileIframe = document.getElementById("cf-chl-widget-" + e.data.widgetId);
		if(!turnstileIframe){
			return;
		}
		if (z.matches)
		{
			turnstileIframe.style.width = "210px";
		} else {
			turnstileIframe.style.width = "170px";
		}
	}
	var x = window.matchMedia("(min-width: 417px)")
	var y = window.matchMedia("(min-width: 364px)")
	var z = window.matchMedia("(min-width: 314px)")
	tc_sml_set_width(z);
	tc_md_set_width(y);
	tc_set_width(x);
	x.addEventListener("change", function() {
		tc_set_width(x);
	});
	y.addEventListener("change", function() {
		tc_md_set_width(y);
	});
	z.addEventListener("change", function() {
		tc_sml_set_width(z);
	});
}); */