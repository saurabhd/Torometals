





(function( $ ) {
	'use strict';

	/**
	 * All of the code for your Dashboard-specific JavaScript source
	 * should reside in this file.
	 *
	 * Note that this assume you're going to use jQuery, so it prepares
	 * the $ function reference to be used within the scope of this
	 * function.
	 *
	 * From here, you're able to define handlers for when the DOM is
	 * ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * Or when the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and so on.
	 *
	 * Remember that ideally, we should not attach any more than a single DOM-ready or window-load handler
	 * for any particular page. Though other scripts in WordPress core, other plugins, and other themes may
	 * be doing this, we should try to minimize doing that in our own work.
	 */
	 $(document).ready(function(){
		 $('#cwa-form').on('submit', function(e){
		 	e.preventDefault();

		 	var flag = validateForm($(this).serializeArray());
		 	if(flag){
		 		//console.log();
		 		var form_array = {};
		 		$(this).serializeArray().map(function(item){
		 			var vl = [];
		 			if(item.value!==null)
		 				form_array[item.name] = item.value;
		 			
		 		});
		 		//if(checkId(form_array.cwa_id)){	
			 		$.post(ajaxurl,{'action': 'add_cwa', 'data': form_array}, function(data){ 
			 			console.log(data);
						reloadCwaTable();
						showCwaError(data);
						
						resetForm();
					 });	
		 		//}
		 	}
		 	
		 });

		$('#cwa-form input[name=cwa_name]').on('change', function(){
			var widget_id = $('#cwa-form input[name=cwa_id]'),
			cwaId = $(this).val().replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').replace(/ /g,"-");

			if(widget_id.val() === ''){
				widget_id.val(cwaId);
				checkId(widget_id,cwaId);
			}
				
		});
		$('#cwa-form input[name=cwa_id]').on('change', function(){
			var self = this;
			checkId(self, $(self).val());
		});
		
		
		$(document).on('click', '.cwa-delete-link', function(e){
			e.preventDefault();
			var id = $(this).data('id');

			$.post(ajaxurl,{'action': 'delete_cwa', 'data': {'cwa_id': id}}, function(data){ 
				console.log(data);
				showCwaError(data);
				
				reloadCwaTable();
			 });
		});

		$('#cwa-advance-btn').on('click', function(e){
			e.preventDefault();
			$('#cwa-form .advanced').toggle('show').toggleClass('hide');
			//console.log($('#cwa-form .advanced.hide'));
			if($('#cwa-form .advanced.hide').length===0)
				$(this).text('Basic');
			else
				$(this).text('Advanced');
		});

		runTooltip();
		 
	 });

	function validateForm(arr){
		window.xt = arr;
		return true;
	}
	function checkId(self, cwa_id){
		$.post(ajaxurl,{'action': 'check_cwa_id', 'data': {'cwa_id': cwa_id}}, function(data){ 
				//console.log(data);
				if(data.code === 0){
					$(self).next('.cwa-form-message').html("<label class='cwa-warning' style='padding-left: 5px;'>"+data.message+"</label>");
				}
				else{
					$(self).next('.cwa-form-message').html("<label class='cwa-success' style='padding-left: 5px;'>"+data.message+"</label>");
				}

				console.log(data);
			 });
	};
	function reloadCwaTable(){
		$.post(ajaxurl,{'action': 'reloadTable'}, function(data){ 

				$('#cwa-table-wrap').html(data);
				runTooltip();
				//console.log(data);
			 });
	}
	function showCwaError(obj){

		var type = (obj.code === 0)? "cwa-warning" : "cwa-success" ;
		console.log(obj.code === 0);
		var message = obj.message;
		$('.cwa-error').html(message).addClass(type).fadeIn();
		setTimeout(function(){
			$('.cwa-error').fadeOut().html("").removeClass(type);
		}, 5000);
	}
	function resetForm(){
		$('.cwa-form input[type="text"]' ).val('');
		$('.cwa-form  cwa-form-message' ).empty();
	}
	function runTooltip(){
		$('.tooltip').tooltipster({
		 		contentAsHTML: true,
		 	 	animation: 'fade',
			   	delay: 200,
			   	interactive: true,
			   	//theme: 'tooltipster-default',
			   	trigger: 'click'
		 });
	}
})( jQuery );
