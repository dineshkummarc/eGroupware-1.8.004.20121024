function initViewDeviceData()
{
	// any inititalization should be done here
}

function syncml_deleteDevicesHistory() {
	if (document.forms["userDefinedDeviceList"].elements.length > 0) {
		var deviceData = new Array();

		for(i=0; i<document.forms["userDefinedDeviceList"].elements.length; i++) {
			if(document.forms["userDefinedDeviceList"].elements[i].checked) {
				deviceData.push(document.forms["userDefinedDeviceList"].elements[i].value);
			}
		}

		if(deviceData.length > 0) {
			if(confirm(lang_reallyDeleteDevicesHistory)) {
				xajax_doXMLHTTP("syncml.ajaxsyncml.deleteDevicesData", deviceData.join(","));
			}
		}
	}
}

function syncml_deleteDataStoreHistory() {
	if (document.forms["userDefinedDeviceView"].elements.length > 0) {
		var dataStores = new Array();

		for(i=0; i<document.forms["userDefinedDeviceView"].elements.length; i++) {
			if(document.forms["userDefinedDeviceView"].elements[i].checked) {
				dataStores.push(document.forms["userDefinedDeviceView"].elements[i].value);
			}
		}

		if(dataStores.length > 0) {
			if(confirm(lang_reallyDeleteDataStoreHistory)) {
				xajax_doXMLHTTP("syncml.ajaxsyncml.deleteDataStoreHistory", dataStores.join(","));
			}
		}
	}
}

function syncm_refreshDeviceDataTable() {
	xajax_doXMLHTTP("syncml.ajaxsyncml.refreshDeviceDataTable");
}

function syncm_refreshDataStoreTable() {
	xajax_doXMLHTTP("syncml.ajaxsyncml.refreshDataStoreTable");
}
