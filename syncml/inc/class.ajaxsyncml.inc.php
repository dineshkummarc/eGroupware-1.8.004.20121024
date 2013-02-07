<?php
/**
 * eGroupWare - SyncML
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage preferences
 * @author Joerg Lehrke <jlehrke@noc.de>
 * @copyright (c) 2009 by Joerg Lehrke <jlehrke@noc.de>
 * @version $Id: class.ajaxsyncml.inc.php 28325 2009-11-16 08:04:18Z jlehrke $
 */

	require_once(EGW_INCLUDE_ROOT.'/syncml/inc/class.devices.inc.php');

	class ajaxsyncml
	{
		var $syncmlDevices;
		var $_debug = false;


		function ajaxsyncml()
		{
			if ($this->_debug) error_log("ajaxsyncml::ajaxsyncml");
			$this->syncmlDevices	=& CreateObject('syncml.devices');
		}


                function refreshDeviceDataTable()
                {
                        if ($this->_debug) error_log("syncml::refreshDeviceDataTable");
                        $response = new xajaxResponse();
                        $response->addAssign('userDefinedDeviceTable', 'innerHTML', $this->syncmlDevices->createDeviceDataTable());
                        return $response->getXML();
                }


		function deleteDevicesData($deviceIDs)
                {
                        if ($this->_debug) error_log("syncml::deleteDevicesData");
                        $deviceData = explode(",", $deviceIDs);
			foreach ($deviceData as $device) {
				$this->syncmlDevices->deleteDeviceHistory($device);
			}
			return $this->refreshDeviceDataTable();
                }

                function refreshDataStoreTable()
                {
                        if ($this->_debug) error_log("syncml::refreshDataStoreTable");
                        $response = new xajaxResponse();
                        $response->addAssign('userDefinedDetailsTable', 'innerHTML', $this->syncmlDevices->createDeviceDetailView());
                        return $response->getXML();
                }

		function deleteDataStoreHistory($dataStores)
                {
                        if ($this->_debug) error_log("syncml::deleteDataStoreHistory");
                        $deviceData = explode(",", $dataStores);
			foreach ($deviceData as $storePath) {
				$this->syncmlDevices->deleteDataStoreHistory($storePath);
			}
			return $this->refreshDataStoreTable();
                }
	}
