/*
// Scripts for WP JV Post Reading Groups
// @version: 1.3
*/

jQuery(document).ready(function($){
	/************************************************************************************************************/
	/* Refresh WP_JV_PRG_List_Table whenever a change occur */
	/************************************************************************************************************/
	function RefreshRGList() {	
		data = {
				action	: 'RefreshRGList',
				url		: ajaxurl,
				type	: 'POST'
			};
		$.post(ajaxurl, data, function(response){				
			// WP_JV_PRG_List_Table::ajax_response() returns json
			var response = $.parseJSON( response );

			// Add the requested rows
			if ( response.rows.length ) 
			$('#the-list').html( response.rows );			    				
		});
	};

	/************************************************************************************************************/
	/* Add new RG */
	/************************************************************************************************************/
    $('#btnAddNewRG').click(function(){
		//Disable button and show loading icon		
		$('#btnAddNewRG').attr('disabled', true);
		$('#spnAddRG').show();		
		
		var newrg = document.getElementById("jv-new-reading-group-text").value; 		
		
		//Save new Reading Group to database
		data = {
                action			: 'AddNewRGtoDB',
                url				: ajaxurl,
                type			: 'POST',
                dataType		: 'text',
                'newrg' 		: newrg,
				wp_jv_rg_nonce	: wp_jv_prg_obj.wp_jv_rg_nonce
            };		
		
		$.post(ajaxurl, data, function(response){
			if (response.error) {				
				alert(response.error_msg+'\n\n[Error: '+response.error_code+']');
				//to debug uncomment the following line
				//alert('Action: '+response.action+'\nNewRGName: '+ response.newRG);					
			}
			else {						
				//to debug uncomment the following line
				//alert('Action: '+response.action+'\nNewRGName: '+ response.newRG);

				//If saving was successful then refresh WP_JV_PRG_List_Table
				RefreshRGList();			   
			}			
		});	
		//Disable loading icon and enable button
		$('#spnAddRG').hide();
		$('#btnAddNewRG').attr('disabled', false);		
		document.getElementById("jv-new-reading-group-text").value = '';								
	});		
	
		
	/************************************************************************************************************/
	/* Edit RG */
	/************************************************************************************************************/
	$('.lnkEdit').live('click',function(event){		
		event.preventDefault();	
		
		//Clean up any other open Edit input - no save
		$.ajaxSetup({async:false});
		RefreshRGList();		
		$.ajaxSetup({async:true});
		
		//Find out which RG we need to edit
		var editRG = $(this).attr('data-RG');
		
		//Display an edit "form"
		$('.RenameDiv-'+editRG).html('<input type="text" id="renamed-reading-group-text" style="width:100%" value="'+$('div.ItemDiv-'+editRG).text()+'">'+
		'<br><button class="btnCancel button-secondary">Cancel</button>'+
		'<button style="float:right" class="btnSave button-primary" data-rename-RG="'+editRG+'">Save</button>'
		); 		
	});
	
	//Edit RG - Cancel button pressed
	$('.btnCancel').live('click',function(event){
		event.preventDefault();
		RefreshRGList();
	});
	
	//Edit RG - Save button pressed
	$('.btnSave').live('click',function(event){
		event.preventDefault();
		
		//disable button
		$('.btnSave').attr('disabled', true);

		var NewRGName = document.getElementById("renamed-reading-group-text").value;
		var RGToRename = $(this).attr('data-rename-RG');		
		
		//Save new Reading Group to database
		data = {
                action			: 'SaveRenamedRGtoDB',
                url				: ajaxurl,
                type			: 'POST',
                dataType		: 'text',
				'RGToRename'	: RGToRename,
                'NewRGName' 	: NewRGName
            };		
		
		$.post(ajaxurl, data, function(response){
			if (response.error) {
				alert(response.error_msg+'\n\n[Error: '+response.error_code+']');
				//to debug uncomment the following line
				//alert('Action: '+response.action+'\nRGToRename: '+ response.RGToRename+'\nNewRGName: '+ response.NewRGName);
			}
			else {						
				//to debug uncomment the following line
				//alert('Action: '+response.action+'\nRGToRename: '+ response.RGToRename+'\nNewRGName: '+ response.NewRGName);
				
				//If saving was successful then refresh WP_JV_PRG_List_Table				
				RefreshRGList();			   
			}			
		});	
		//enable button	
		$('.btnSave').attr('disabled', false);
	});
	
	/************************************************************************************************************/
	/* Delete RG */
	/************************************************************************************************************/
	$('.lnkDelete').live('click',function(event){				
		event.preventDefault();		
		var delurl = jQuery(this).attr('href');				
		data = {
				action 		: 'DeleteRG',
				url			: ajaxurl,
				type		: 'POST',
				'delurl'	: delurl
			   };
		$.post(ajaxurl, data, function(response){		
			if ( response.error ) {				
				alert(response.error_msg+'\n\n[Error: '+response.error_code+']');								
				//to debug uncomment the following line
				//alert('Action: '+response.action+'\nItem: '+ response.rg+'\njv_prg_nonce: '+response.jv_prg_nonce);
			}
			else {
			    //to debug uncomment the following line
				//alert('Action: '+response.action+'\nItem: '+ response.rg+'\njv_prg_nonce: '+response.jv_prg_nonce);
				
				//If saving was successful then refresh WP_JV_PRG_List_Table			
				RefreshRGList();				
				}
			
		});
	});
	
});