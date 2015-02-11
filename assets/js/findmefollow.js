//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//

//Agent Quick Select
$("[id^='qsagents']").change(function(){
	var taelm = $(this).data('for');
	console.log($('#'+taelm));
	$('#'+taelm).append($(this).val()+"\n");
});
//FixedCID
$("#changecid").change(function(){
	if($(this).val() == 'fixed'){
		$("#fixedcid").attr('disabled',false);
	}else{
		$("#fixedcid").attr('disabled',true);
	}
});

$(document).ready(function(){
	$("#changecid").change(function(){
				state = (this.value == "fixed" || this.value == "extern") ? "" : "disabled";
		if (state == "disabled") {
			$("#fixedcid").attr("disabled",state);
		} else {
			$("#fixedcid").removeAttr("disabled");
		}
	});
});

$(document).ready(function(){
	$("#changecid").change(function(){
        state = (this.value == "fixed" || this.value == "extern") ? "" : "disabled";
    if (state == "disabled") {
      $("#fixedcid").attr("disabled",state);
    } else {
      $("#fixedcid").removeAttr("disabled");
    }
	});
});
//Below are functions moved here from page.findmefollow.php

function insertExten() {
	exten = document.getElementById('insexten').value;

	grpList=document.getElementById('grplist');
	if (grpList.value[ grpList.value.length - 1 ] == "\n") {
		grpList.value = grpList.value + exten;
	} else {
		grpList.value = grpList.value + '\n' + exten;
	}

	// reset element
	document.getElementById('insexten').value = '';
}

function checkGRP(theForm) {
	var msgInvalidExtList = _('Please enter an extension list.');
	var msgInvalidTime = _('Invalid time specified');
	var msgInvalidGrpTimeRange = _('Time must be between 1 and 60 seconds');
	var msgInvalidRingStrategy = _('Only ringall, ringallv2, hunt and the respective -prim versions are supported when confirmation is checked');
	var msgInvalidCID =  _('Invalid CID Number. Must be in a format of digits only with an option of E164 format using a leading "+"');

	// set up the Destination stuff
	setDestinations(theForm, 1);

	// form validation
	defaultEmptyOK = false;
	if (isEmpty(theForm.grplist.value))
		return warnInvalid(theForm.grplist, msgInvalidExtList);

	if (!theForm.fixedcid.disabled) {
		fixedcid = $.trim(theForm.fixedcid.value);
		if (!fixedcid.match('^[+]{0,1}[0-9]+$')) {
			return warnInvalid(theForm.fixedcid, msgInvalidCID);
		}
	}

	if (!isInteger(theForm.grptime.value)) {
		return warnInvalid(theForm.grptime, msgInvalidTime);
	} else {
		var grptimeVal = theForm.grptime.value;
		if (grptimeVal < 1 || grptimeVal > 60)
			return warnInvalid(theForm.grptime, msgInvalidGrpTimeRange);
	}

	if (theForm.needsconf.checked && (theForm.strategy.value.substring(0,7) != "ringall" && theForm.strategy.value.substring(0,4) != "hunt")) {
		return warnInvalid(theForm.needsconf, msgInvalidRingStrategy);
	}

	defaultEmptyOK = true;

	if (!validateDestinations(theForm, 1, true))
		return false;

	return true;
}
$("[id^='fmtoggle']").change(function(){
	var fmstate = "";
	var exten = $(this).data('for');
	if($(this).is(':checked')){
		fmstate = "disable";
	}else{
		fmstate = "enable";
	}
	$.get("config.php?display=findmefollow&action=toggleFM&extdisplay="+exten+"&state="+fmstate);
});