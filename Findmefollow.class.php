<?php
namespace FreePBX\modules;
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
class Findmefollow implements \BMO {

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}

	public function doConfigPageInit($page) {
		global $amp_conf;
		$dispnum = 'findmefollow'; //used for switch on config.php
		$request = $_REQUEST;
		isset($request['action'])?$action = $request['action']:$action='';
		//the extension we are currently displaying
		isset($request['extdisplay'])?$extdisplay=$request['extdisplay']:$extdisplay='';
		isset($request['account'])?$account = $request['account']:$account='';
		isset($request['grptime'])?$grptime = $request['grptime']:$grptime=$amp_conf['FOLLOWME_TIME'];
		isset($request['grppre'])?$grppre = $request['grppre']:$grppre='';
		isset($request['strategy'])?$strategy = $request['strategy']:$strategy=$amp_conf['FOLLOWME_RG_STRATEGY'];
		isset($request['annmsg_id'])?$annmsg_id = $request['annmsg_id']:$annmsg_id='';
		isset($request['dring'])?$dring = $request['dring']:$dring='';
		isset($request['needsconf'])?$needsconf = $request['needsconf']:$needsconf='';
		isset($request['remotealert_id'])?$remotealert_id = $request['remotealert_id']:$remotealert_id='';
		isset($request['toolate_id'])?$toolate_id = $request['toolate_id']:$toolate_id='';
		isset($request['ringing'])?$ringing = $request['ringing']:$ringing='';
		isset($request['pre_ring'])?$pre_ring = $request['pre_ring']:$pre_ring=$amp_conf['FOLLOWME_PRERING'];
		isset($request['changecid'])?$changecid = $request['changecid']:$changecid='default';
		isset($request['fixedcid'])?$fixedcid = $request['fixedcid']:$fixedcid='';
		isset($request['rvolume'])?$rvolume = $request['rvolume']:$rvolume='';

		if (isset($request['ddial'])) {
			$ddial =	$request['ddial'];
		}	else {
			$ddial = isset($request['ddial_value']) ? $request['ddial_value'] : ($amp_conf['FOLLOWME_DISABLED'] ? 'CHECKED' : '');
		}

		if (isset($request['goto0']) && isset($request[$request['goto0']."0"])) {
			$goto = $request[$request['goto0']."0"];
		} else {
			$goto = "ext-local,$extdisplay,dest";
		}

		if (isset($request["grplist"])) {
			$grplist = explode("\n",$request["grplist"]);

			if (!$grplist) {
				$grplist = null;
			}

			foreach (array_keys($grplist) as $key) {
				//trim it
				$grplist[$key] = trim($grplist[$key]);

				// remove invalid chars
				$grplist[$key] = preg_replace("/[^0-9#*+]/", "", $grplist[$key]);

				//Dont allow self to be a local channel
				if ($grplist[$key] == ltrim($extdisplay,'GRP-').'#') {
					$grplist[$key] = rtrim($grplist[$key],'#');
				}

				// remove blanks
				if ($grplist[$key] == "") unset($grplist[$key]);
			}

			// check for duplicates, and re-sequence
			$grplist = array_values(array_unique($grplist));
		}

		// do if we are submitting a form
		if(isset($request['action'])){
			//check if the extension is within range for this user
			if (isset($account) && !checkRange($account)){
				echo "<script>javascript:alert('". _("Warning! Extension")." ".$account." "._("is not allowed for your account").".');</script>";
			} else {
				//add group
				if ($action == 'addGRP') {
					findmefollow_add($account,$strategy,$grptime,implode("-",$grplist),$goto,$grppre,$annmsg_id,$dring,$needsconf,$remotealert_id,$toolate_id,$ringing,$pre_ring,$ddial,$changecid,$fixedcid,$rvolume);

					needreload();
					redirect_standard();
				}

				//del group
				if ($action == 'delGRP') {
					findmefollow_del($account);
					needreload();
					redirect_standard();
				}

				//edit group - just delete and then re-add the extension
				if ($action == 'edtGRP') {
					findmefollow_del($account);
					findmefollow_add($account,$strategy,$grptime,implode("-",$grplist),$goto,$grppre,$annmsg_id,$dring,$needsconf,$remotealert_id,$toolate_id,$ringing,$pre_ring,$ddial,$changecid,$fixedcid,$rvolume);

					needreload();
					redirect_standard('extdisplay', 'view');
				}
			}
		}

	}

	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {

	}

	public function getQuickCreateDisplay() {
		$fm = $this->FreePBX->Config->get('FOLLOWME_AUTO_CREATE');
		if($fm) {
			return array();
		}
		return array(
			1 => array(
				array(
					'html' => load_view(__DIR__.'/views/quickCreate.php',array("fmfm" => !$this->FreePBX->Config->get('FOLLOWME_DISABLED')))
				)
			)
		);
	}

	/**
	 * Delete user function, it's run twice because of scemantics with
	 * old freepbx but it's harmless
	 * @param  string $extension The extension number
	 * @param  bool $editmode  If we are in edit mode or not
	 */
	public function delUser($extension, $editmode=false) {
		if(!$editmode) {
			if(!function_exists('findmefollow_destinations')) {
				$this->FreePBX->Modules->loadFunctionsInc('findmefollow');
			}
			findmefollow_del($extension);
		}
	}

	/**
	 * Quick Create hook
	 * @param string $tech      The device tech
	 * @param int $extension The extension number
	 * @param array $data      The associated data
	 */
	public function processQuickCreate($tech, $extension, $data) {
		if($this->FreePBX->Config->get('FOLLOWME_AUTO_CREATE')) {
			if(!function_exists('findmefollow_add')) {
				include __DIR__."/functions.inc.php";
			}
			$ddial = $this->FreePBX->Config->get('FOLLOWME_DISABLED') ? 'CHECKED' : '';
			findmefollow_add($extension, $this->FreePBX->Config->get('FOLLOWME_RG_STRATEGY'), $this->FreePBX->Config->get('FOLLOWME_TIME'),$extension, 'ext-local,'.$extension.',dest', "", "", "", "", "", "","", $this->FreePBX->Config->get('FOLLOWME_PRERING'), $ddial,'default','');
		} elseif(!empty($data['fmfm']) && $data['fmfm'] == "yes") {
			if(!function_exists('findmefollow_add')) {
				include __DIR__."/functions.inc.php";
			}
			findmefollow_add($extension, 'ringallv2', '10', $extension, 'ext-local,'.$extension.',dest', "", "", "", "", "", "","", '20', "",'default','');
		}
	}

	/*
	 * Gets Follow Me Confirmation Setting
	 *
	 * @param string $exten Extension to get information about
	 * @return bool True is confirmed, False is not
	 */
	function getConfirm($exten) {
		$response = $this->FreePBX->astman->database_get("AMPUSER","$exten/followme/grpconf");
		return preg_match("/ENABLED/",$response);
	}

	/*
	 * Sets Follow Confirmation Setting
	 *
	 * @param string $exten Extension to modify
	 * @param bool $follow_me_cofirm Follow Me Confirm Setting
	 */
	function setConfirm($exten,$follow_me_confirm) {
		$value = ($follow_me_confirm)?'ENABLED':'DISABLED';
		$this->FreePBX->astman->database_put('AMPUSER', "$exten/followme/grpconf", $value);
	}

	/*
	 * Sets Follow Me List
	 *
	 * @param $exten Extension to modify
	 * @param $follow_me_list Follow Me List
	 */
	function setList($exten,$follow_me_list) {

		$clean_follow_me_list = array();
		foreach($follow_me_list as $value) {
			$value = $this->lookupSetExtensionFormat($exten, $value);
			if ($value) {
				array_push($clean_follow_me_list, $value);
			}
		}

		$follow_me_list = implode("-", $clean_follow_me_list);
		$this->FreePBX->astman->database_put('AMPUSER', "$exten/followme/grplist", $follow_me_list);
	}

	/**
	 * Lookup extension format
	 * This should be depreciated eventually
	 * @param {int} $grp The FMFM Group
	 * @param {int} $exten The Phone Number
	 */
	function lookupSetExtensionFormat($grp, $exten) {

		$hadPound = preg_match("/#$/",$exten);
		$exten = preg_replace("/[^0-9*+]/", "", $exten);

		// Rem: This was moved from above to catch also cases of lines containing only bogus stuf
		if (trim($exten) == "") {
			return null;
		};

		//Dont allow self to be a local channel
		if($hadPound && $exten != $grp) {
			return $exten.'#';
		}

		$result = $this->FreePBX->Core->getUser($exten);
		if (empty($result)) {
			return $exten.'#';
		} else {
			return $exten;
		}
	}

	/*
	 * Gets Follow Me List if set
	 *
	 * @param $exten Extension to get information about
	 * @return $data follow me list if set
	 */
	function getList($exten) {
		$response = $this->FreePBX->astman->database_get("AMPUSER","$exten/followme/grplist");
		return preg_replace("/[^0-9#*\-+]/", "", $response);
	}

	/*
	 * Sets Follow Me List Ring Time
	 *
	 * @param $exten Extension to modify
	 * @param $follow_me_listring_time List Ring Time to ring
	 */
	function setListRingTime($exten,$follow_me_listring_time) {
		$this->FreePBX->astman->database_put('AMPUSER', "$exten/followme/grptime", $follow_me_listring_time);
	}

	/*
	 * Gets Follow Me List-Ring Time if set
	 *
	 * @param $exten Extension to get information about
	 * @return $number follow me list-ring time returned if set
	 */
	function getListRingTime($exten) {
		$response = $this->FreePBX->astman->database_get("AMPUSER","$exten/followme/grptime");
		return is_numeric($response) ? $response : '';
	}

	/*
	 * Sets Follow Me Pre-Ring Time
	 *
	 * @param $exten Extension to modify
	 * @param $follow_me_prering_time Pre-Ring Time to ring
	 */
	function setPreRingTime($exten,$follow_me_prering_time) {
		$this->FreePBX->astman->database_put('AMPUSER', "$exten/followme/prering", $follow_me_prering_time);
	}

	/*
	 * Gets Follow Me Pre-Ring Time if set
	 *
	 * @param $exten Extension to get information about
	 * @return $number follow me pre-ring time returned if set
	 */
	function getPreRingTime($exten) {
		$response = $this->FreePBX->astman->database_get("AMPUSER","$exten/followme/prering");
		return is_numeric($response) ? $response : '';
	}

	/*
	 * Sets Follow Ddial Setting
	 *
	 * @param $exten Extension to modify
	 * @param $follow_me_ddial Follow Me Ddial Setting
	 */
	function setDDial($exten,$follow_me_ddial) {
		$value_opt = ($follow_me_ddial)?'DIRECT':'EXTENSION';
		$response = $this->FreePBX->astman->database_put('AMPUSER',"$exten/followme/ddial",$value_opt);

		// Now that we have set the state (DIRECT is enabled, EXTENSION is disabled)
		// Get the devices associated with this user first and then we will set them all as needed
		//
		//
		if ($this->FreePBX->Config->get_conf_setting('USEDEVSTATE')) {
			$value_opt = ($follow_me_ddial)?'BUSY':'NOT_INUSE';
			$devices = $this->FreePBX->astman->database_get("AMPUSER",$exten."/device");
			$device_arr = explode('&',$devices);
			foreach ($device_arr as $device) {
				$this->FreePBX->astman->set_global($this->FreePBX->Config->get_conf_setting('AST_FUNC_DEVICE_STATE') . "(Custom:FOLLOWME$device)", $value_opt);
			}
		}
		return $response;
	}

	/*
	 * Gets Follow Me Ddial Setting
	 *
	 * @param $exten Extension to get information about
	 * @return $data follow me ddial setting
	 */
	function getDDial($exten) {
		$response = $this->FreePBX->astman->database_get("AMPUSER",$exten."/followme/ddial");
		if (trim($response) == 'EXTENSION') {
			return true;
		} elseif (trim($response) == 'DIRECT') {
			return false;
		} else {
			// If here then followme must not be set so use default
			return $this->FreePBX->Config->get_conf_setting('FOLLOWME_DISABLED') ? true : false;
		}
	}

	/*
	 * Sets Follow-Me Settings in FreePBX MySQL Database
	 *
	 * @param $exten Extension to modify
	 * @param $follow_me_prering_time Pre-Ring Time to ring
	 * @param $follow_me_listring_time List Ring Time to ring
	 * @param $follow_me_list Follow Me List
	 * @param $follow_me_list Follow Me Confirm Setting
	 *
	 */
	function setMySQL($exten, $follow_me_prering_time, $follow_me_listring_time, $follow_me_list, $follow_me_confirm) {
		$db = $this->db;

		//format for SQL database
		$follow_me_confirm = ($follow_me_confirm)?'CHECKED':'';

		$sql = "UPDATE findmefollow SET grptime = '" . $follow_me_listring_time . "', grplist = '".
		$db->escapeSimple(trim($follow_me_list)) . "', pre_ring = '" . $follow_me_prering_time .
		"', needsconf = '" . $follow_me_confirm . "' WHERE grpnum = $exten LIMIT 1";
		$results = $db->query($sql);


		return 1;
	}

	function listAll() {
		$list = findmefollow_list();
		return !empty($list) ? $list : array();
	}

	function addSettingById($grpnum,$setting,$value='') {
		return $this->addSettingsById($grpnum, array($setting => $value));
	}

	function addSettingsById($grpnum,$settings) {
		$valid = array('strategy','grptime','grppre','grplist','annmsg_id','postdest','dring','needsconf','remotealert_id','toolate_id','ringing','pre_ring','ddial','changecid','fixedcid');

		$settings = array_intersect_key($settings, array_flip($valid));

		if (count($settings) == 0) {
			return false;
		}

		$ret = true;

		foreach ($settings as $setting => $value) {
			//TODO This should just be one query.
			$sql = "INSERT INTO findmefollow (grpnum,$setting) VALUES (:grpnum,:value) ON DUPLICATE KEY UPDATE $setting = :value";
			$sth = $this->db->prepare($sql);

			switch($setting) {
				case 'strategy':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
				break;
				case 'grptime':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
					$this->setListRingTime($grpnum,$value);
				break;
				case 'grppre':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
				break;
				case 'grplist':
					$this->setList($grpnum,$value);
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => implode("-",$value)));
				break;
				case 'annmsg_id':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
				break;
				case 'postdest':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
				break;
				case 'dring':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
				break;
				case 'needsconf':
					$val = ($value) ? 'CHECKED' : '';
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $val));
					$val = ($value) ? 'ENABLED' : 'DISABLED';
					$this->FreePBX->astman->database_put("AMPUSER",$grpnum."/followme/grpconf",$val);
				break;
				case 'remotealert_id':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
				break;
				case 'toolate_id':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
				break;
				case 'ringing':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
				break;
				case 'pre_ring':
					$sth->execute(array(':grpnum' => $grpnum, ':key' => $setting, ':value' => $value));
					$this->setPreRingTime($grpnum,$value);
				break;
				case 'ddial':
					//(DIRECT is enabled, EXTENSION is disabled)
					$ddialstate = ($value) ? 'NOT_INUSE' : 'BUSY';
					$val = ($value) ? 'EXTENSION' : 'DIRECT';
					$this->FreePBX->astman->database_put("AMPUSER",$grpnum."/followme/ddial",$val);
					if ($this->FreePBX->Config->get_conf_setting('USEDEVSTATE')) {
						$devices = $this->FreePBX->astman->database_get("AMPUSER", $grpnum . "/device");
						$device_arr = explode('&', $devices);
						foreach ($device_arr as $device) {
							$this->FreePBX->astman->set_global($this->FreePBX->Config->get_conf_setting('AST_FUNC_DEVICE_STATE') . "(Custom:FOLLOWME$device)", $ddialstate);
						}
					}
					if(!$value) {
						$sql = "INSERT INTO findmefollow (grpnum,grptime,grplist) VALUES (:grpnum,20,:grpnum)";
						$sth = $this->db->prepare($sql);
						//wrapped into a try/catch incase the find me is already defined, then we won't do the additional steps.
						try {
							$sth->execute(array(':grpnum' => $grpnum));
							//these are the additional steps
							$this->setListRingTime($grpnum,20);
							$this->setList($grpnum,array($grpnum));
						} catch(\Exception $e) {}
					}
				break;
				case 'changecid':
					$this->FreePBX->astman->database_put("AMPUSER",$grpnum."/followme/changecid",$value);
				break;
				case 'fixedcid':
					$value = preg_replace("/[^0-9\+]/" ,"", trim($value));
					$this->FreePBX->astman->database_put("AMPUSER",$grpnum."/followme/fixedcid",$value);
				break;
				default:
					$ret = false;
				break;
			}
		}

		return $ret;
	}

	function getSettingsById($grpnum, $check_astdb=0) {
		$db = $this->db;
		$sql = "SELECT grpnum, strategy, grptime, grppre, grplist, annmsg_id, postdest, dring, needsconf, remotealert_id, toolate_id, ringing, pre_ring, voicemail FROM findmefollow INNER JOIN `users` ON `extension` = `grpnum` WHERE grpnum = ?";
		$sth = $db->prepare($sql);
		$sth->execute(array($grpnum));
		$results = $sth->fetch(\PDO::FETCH_ASSOC);

		if (empty($results)) {
			//defaults
			return array(
				"ddial" => true,
				"needsconf" => false,
				"grplist" => $grpnum,
				"pre_ring" => '',
				"grpnum" => $grpnum,
				"annmsg_id" => '',
				"remotealert_id" => '',
				"toolate_id" => '',
				"grptime" => '20'
			);
		}

		if (!isset($results['voicemail'])) {
			$sql = "SELECT `voicemail` FROM `users` WHERE `extension` = ?";
			$sth = $db->prepare($sql);
			$sth->execute(array($grpnum));
			$results['voicemail'] = $sth->fetchColumn();
		}

		if (!isset($results['strategy'])) {
			$results['strategy'] = $this->FreePBX->Config->get_conf_setting('FOLLOWME_RG_STRATEGY');
		}

		if ($check_astdb) {
			if ($this->FreePBX->astman->Connected()) {
				$astdb_prering = $this->getPreRingTime($grpnum);
				$astdb_grptime = $this->getListRingTime($grpnum);
				$astdb_grplist = $this->getList($grpnum);
				$astdb_grpconf = $this->getConfirm($grpnum);

				$astdb_changecid = strtolower($this->FreePBX->astman->database_get("AMPUSER",$grpnum."/followme/changecid"));
				switch($astdb_changecid) {
					case 'default':
					case 'did':
					case 'forcedid':
					case 'fixed':
					case 'extern':
					break;
					default:
						$astdb_changecid = 'default';
				}
				$results['changecid'] = $astdb_changecid;
				$fixedcid = $this->FreePBX->astman->database_get("AMPUSER",$grpnum."/followme/fixedcid");
				$results['fixedcid'] = preg_replace("/[^0-9\+]/" ,"", trim($fixedcid));
			}
			$astdb_ddial = $this->getDDial($grpnum);
			// If the values are different then use what is in astdb as it may have been changed.
			// If sql returned no results for pre_ring/grptime then it's not configued so we reset
			// the astdb defaults as well
			//
			$changed=0;
			if (!isset($results['pre_ring'])) {
				$results['pre_ring'] = $astdb_prering = $this->FreePBX->Config->get_conf_setting('FOLLOWME_PRERING');
			}
			if (!isset($results['grptime'])) {
				$results['grptime'] = $astdb_grptime = $this->FreePBX->Config->get_conf_setting('FOLLOWME_TIME');
			}
			if (!isset($results['grplist'])) {
				$results['grplist'] = '';
			}
			if (!isset($results['needsconf'])) {
				$results['needsconf'] = '';
			}
			if (($astdb_prering != $results['pre_ring']) && ($astdb_prering >= 0)) {
				$results['pre_ring'] = $astdb_prering;
				$changed=1;
			}
			if (($astdb_grptime != $results['grptime']) && ($astdb_grptime > 0)) {
				$results['grptime'] = $astdb_grptime;
				$changed=1;
			}

			if ((trim($astdb_grplist) != trim($results['grplist'])) && (trim($astdb_grplist) != '')) {
				$results['grplist'] = $astdb_grplist;
				$changed=1;
			}

			$confvalue = ($astdb_grpconf) ? 'CHECKED' : '';
			if ($confvalue != trim($results['needsconf'])) {
				$results['needsconf'] = $confvalue;
				$changed=1;
			}

			$results['ddial'] = $astdb_ddial;

			if ($changed) {
				$sql = "UPDATE findmefollow SET grptime = ?, grplist = ?, pre_ring = ?, needsconf = ? WHERE grpnum = ? LIMIT 1";
				$sth = $db->prepare($sql);
				$sth->execute(array($results['grptime'],$results['grplist'],$results['pre_ring'],$results['needsconf'],$results['grpnum']));
			}
		} // if check_astdb
		$results['needsconf'] = ($results['needsconf'] == "CHECKED") ? true : false;
		return $results;
	}

	public function getActionBar($request) {
		if (empty($request['extdisplay']) || empty($request['view']) || $request['view'] != 'form') {
			return null;
		}
		switch($request['display']) {
			case 'findmefollow':
				$buttons = array(
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					)
				);
				break;
		}
		return $buttons;
	}
	function add($grpnum,$strategy,$grptime,$grplist,$postdest,$grppre='',$annmsg_id='',$dring,$needsconf,$remotealert_id,$toolate_id,$ringing,$pre_ring,$ddial,$changecid='default',$fixedcid='') {
		$astman = $this->FreePBX->astman;
		$dbh = $this->db;
		$conf = $this->FreePBX->Config();
		if (empty($postdest)) {
			$postdest = "ext-local,$grpnum,dest";
		}

		//Follow Me auto # on external number.
		//http://code.freepbx.org/cru/FREEPBX-51#CFR-111
		$users = \findmefollow_allusers();
		$users = is_array($users) ? $users : array();
		foreach ($users as $user) {
			$extens[$user[0]] = $user[1];
		}

		$list = !is_array($grplist) ? explode("-", $grplist) : $grplist;
		foreach (array_keys($list) as $key) {
			// remove invalid chars
			$hadPound = preg_match("/#$/",$list[$key]);
			$list[$key] = preg_replace("/[^0-9*+]/", "", $list[$key]);

			if ($list[$key] == "") {
				unset($list[$key]);
				continue;
			}

			if($hadPound) {
				$list[$key].= '#';
				continue;
			}

			if (empty($extens[$list[$key]])) {
				/* Extension not found.  Must be an external number. */
				$list[$key].= '#';
			}
		}
		$grplist = implode("-", $list);

		$sql = "INSERT INTO findmefollow (grpnum, strategy, grptime, grppre, grplist, annmsg_id, postdest, dring, needsconf, remotealert_id, toolate_id, ringing, pre_ring) VALUES (:grpnum, :strategy, :grptime, :grppre, :grplist, :annmsg_id, :postdest, :dring, :needsconf, :remotealert_id, :toolate_id, :ringing, :pre_ring)";
		$insertarr = array(':grpnum' => $grpnum , ':strategy' => $strategy , ':grptime' => $grptime , ':grppre' => $grppre , ':grplist' => $grplist , ':annmsg_id' => $annmsg_id , ':postdest' => $postdest , ':dring' => $dring , ':needsconf' => $needsconf , ':remotealert_id' => $remotealert_id , ':toolate_id' => $toolate_id , ':ringing' => $ringing , ':pre_ring' => $pre_ring);
		$stmt = $dbh->prepare($sql);
		$results = $stmt->execute($insertarr);
		if ($astman) {
			$astman->database_put("AMPUSER",$grpnum."/followme/prering",isset($pre_ring)?$pre_ring:'');
			$astman->database_put("AMPUSER",$grpnum."/followme/grptime",isset($grptime)?$grptime:'');
			$astman->database_put("AMPUSER",$grpnum."/followme/grplist",isset($grplist)?$grplist:'');
			$astman->database_put("AMPUSER",$grpnum."/followme/grppre",isset($grppre)?$grppre:'');

			$needsconf = isset($needsconf)?$needsconf:'';
			$confvalue = ($needsconf == 'CHECKED')?'ENABLED':'DISABLED';
			$astman->database_put("AMPUSER",$grpnum."/followme/grpconf",$confvalue);

			$ddial      = isset($ddial)?$ddial:'';
			$ddialvalue = ($ddial == 'CHECKED')?'EXTENSION':'DIRECT';
			$astman->database_put("AMPUSER",$grpnum."/followme/ddial",$ddialvalue);
			if ($conf->get('USEDEVSTATE')) {
				$ddialstate = ($ddial == 'CHECKED')?'NOT_INUSE':'BUSY';

				$devices = $astman->database_get("AMPUSER", $grpnum . "/device");
				$device_arr = explode('&', $devices);
				foreach ($device_arr as $device) {
					$astman->set_global($conf->get('AST_FUNC_DEVICE_STATE') . "(Custom:FOLLOWME$device)", $ddialstate);
				}
			}

			$astman->database_put("AMPUSER",$grpnum."/followme/changecid",$changecid);
			$fixedcid = preg_replace("/[^0-9\+]/" ,"", trim($fixedcid));
			$astman->database_put("AMPUSER",$grpnum."/followme/fixedcid",$fixedcid);
		} else {
			\fatal("Cannot connect to Asterisk Manager with ".$conf->get("AMPMGRUSER")."/".$conf->get("AMPMGRPASS"));
		}
	}

	public function del($grpnum) {
		$astman = $this->FreePBX->astman;
		$dbh = $this->db;
		$conf = $this->FreePBX->Config();

		$sql= "DELETE FROM findmefollow WHERE grpnum = :grpnum";
		$stmt = $dbh->prepare($sql);
		$results = $stmt->execute(array(':grpnum' => $grpnum));
		if ($astman) {
			$astman->database_deltree("AMPUSER/".$grpnum."/followme");
		} else {
			\fatal("Cannot connect to Asterisk Manager with ".$conf->get("AMPMGRUSER")."/".$conf->get("AMPMGRPASS"));
		}
	}
	// Only check astdb if check_astdb is not 0. For some reason, this fails if the asterisk manager code
	// is included (executed) by all calls to this function. This results in silently not generating the
	// extensions_additional.conf file. page.findmefollow.php does set it to 1 which means that when running
	// the GUI, any changes not reflected in SQL will be detected and written back to SQL so that they are
	// in sync. Ideally, anything that changes the astdb should change SQL. (in some ways, these should both
	// not be here but ...
	//
	// Need to go back and confirm at some point that the $check_astdb error is still there and deal with it.
	// as variables like $ddial get introduced to only be in astdb, the result array will not include them
	// if not able to get to astdb. (I suspect in 2.2 and beyond this may all be fixed).
	//
	public function get($grpnum, $check_astdb=0) {
		$astman = $this->FreePBX->astman;
		$dbh = $this->db;
		$conf = $this->FreePBX->Config();
		$user = $this->FreePBX->Core->getUser($grpnum);
		$sql = 'SELECT grpnum, strategy, grptime, grppre, grplist, annmsg_id, postdest, dring, needsconf, remotealert_id, toolate_id, ringing, pre_ring, voicemail FROM findmefollow INNER JOIN `users` ON `extension` = `grpnum` WHERE grpnum = :grpnum LIMIT 1';
		$stmt = $dbh->prepare($sql);
		$stmt->execute(array(':grpnum'=> $grpnum));
		$results = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (empty($results)) {
			return array();
		}
		if (!isset($results['voicemail'])) {

			$results['voicemail'] = isset($user['voicemail'])?$user['voicemail']:'novm';
		}
		if (!isset($results['strategy'])) {
			$results['strategy'] = $conf->get('FOLLOWME_RG_STRATEGY');
		}

		if ($check_astdb) {
			if ($astman) {
				$astdb_prering = $astman->database_get("AMPUSER",$grpnum."/followme/prering");
				$astdb_grptime = $astman->database_get("AMPUSER",$grpnum."/followme/grptime");
				$astdb_grplist = $astman->database_get("AMPUSER",$grpnum."/followme/grplist");
				$astdb_grpconf = $astman->database_get("AMPUSER",$grpnum."/followme/grpconf");

				$astdb_changecid = strtolower($astman->database_get("AMPUSER",$grpnum."/followme/changecid"));
				switch($astdb_changecid) {
					case 'default':
					case 'did':
					case 'forcedid':
					case 'fixed':
					case 'extern':
						break;
					default:
						$astdb_changecid = 'default';
				}
				$results['changecid'] = $astdb_changecid;
				$fixedcid = $astman->database_get("AMPUSER",$grpnum."/followme/fixedcid");
				$results['fixedcid'] = preg_replace("/[^0-9\+]/" ,"", trim($fixedcid));
			} else {
				fatal("Cannot connect to Asterisk Manager with ".$conf->get('AMPMGRUSER')."/".$conf->get('AMPMGRPASS'));
			}
			$astdb_ddial   = $astman->database_get("AMPUSER",$grpnum."/followme/ddial");
			// If the values are different then use what is in astdb as it may have been changed.
			// If sql returned no results for pre_ring/grptime then it's not configued so we reset
			// the astdb defaults as well
			//
			$changed=0;
			if (!isset($results['pre_ring'])) {
				$results['pre_ring'] = $astdb_prering = $conf->get('FOLLOWME_PRERING');
			}
			if (!isset($results['grptime'])) {
				$results['grptime'] = $astdb_grptime = $conf->get('FOLLOWME_TIME');
			}
			if (!isset($results['grplist'])) {
				$results['grplist'] = '';
			}
			if (!isset($results['needsconf'])) {
				$results['needsconf'] = '';
			}
			if (($astdb_prering != $results['pre_ring']) && ($astdb_prering >= 0)) {
				$results['pre_ring'] = $astdb_prering;
				$changed=1;
			}
			if (($astdb_grptime != $results['grptime']) && ($astdb_grptime > 0)) {
				$results['grptime'] = $astdb_grptime;
				$changed=1;
			}
			if ((trim($astdb_grplist) != trim($results['grplist'])) && (trim($astdb_grplist) != '')) {
				$results['grplist'] = $astdb_grplist;
				$changed=1;
			}

			if (trim($astdb_grpconf) == 'ENABLED') {
				$confvalue = 'CHECKED';
			} elseif (trim($astdb_grpconf) == 'DISABLED') {
				$confvalue = '';
			} else {
				//Bogus value, should not get here but treat as disabled
				$confvalue = '';
			}
			if ($confvalue != trim($results['needsconf'])) {
				$results['needsconf'] = $confvalue;
				$changed=1;
			}

			// Not in sql so no sanity check needed
			//
			if (trim($astdb_ddial) == 'EXTENSION') {
				$ddial = 'CHECKED';
			} elseif (trim($astdb_ddial) == 'DIRECT') {
				$ddial = '';
			} else {
				// If here then followme must not be set so use default
				$ddial = $conf->get('FOLLOWME_DISABLED') ? 'CHECKED' : '';
			}
			$results['ddial'] = $ddial;

			if ($changed) {
				$sql = "UPDATE findmefollow SET grptime = '".$results['grptime']."', grplist = '".
					$db->escapeSimple(trim($results['grplist']))."', pre_ring = '".$results['pre_ring'].
					"', needsconf = '".$results['needsconf']."' WHERE grpnum = '".$db->escapeSimple($grpnum)."' LIMIT 1";
				$sql_results = sql($sql);
			}
		} // if check_astdb

		return $results;
	}

	public function update($grpnum,$settings) {
		$old = $this->get($grpnum);
		if(!empty($old)) {
			$this->del($grpnum);
			$old['grplist'] = explode("-",$old['grplist']);
			$settings = array_merge($old,$settings);
		}
		extract($settings);
		$this->add($grpnum,$strategy,$grptime,$grplist,$postdest,$grppre,$annmsg_id,$dring,$needsconf,$remotealert_id,$toolate_id,$ringing,$pre_ring,$ddial,$changecid,$fixedcid);
	}

	public function bulkhandlerGetHeaders($type) {
		switch ($type) {
		case 'extensions':
			$headers = array(
				'findmefollow_enabled' => array(
					'description' => _('Follow Me Enabled [Blank to disable]')
				),
				'findmefollow_grplist' => array(
					'description' => _('Follow Me List'),
				),
				'findmefollow_postdest' => array(
					'description' => _('Follow Me No Answer Destination'),
					"display" => false,
					"type" => "destination"
				),
			);

			return $headers;
			break;
		}
	}

	public function bulkhandlerImport($type, $rawData) {
		$ret = NULL;

		switch ($type) {
		case 'extensions':
			foreach ($rawData as $data) {
				$extension = $data['extension'];

				foreach ($data as $key => $value) {
					if (substr($key, 0, 13) == 'findmefollow_') {
						$settingname = substr($key, 13);
						switch ($settingname) {
							case 'grplist':
								$settings[$settingname] = explode('-', $value);
							break;
							case 'enabled':
								//reversy and backwards yeah. I know.
								//ITS THE CODE FROM 7 YEARS AGO
								//:'(
								$value = trim($value);
								$settings['ddial'] = (!empty($value)) ? false : true;
							break;
							default:
								$settings[$settingname] = $value;
							break;
						}
					}
				}

				if (!empty($settings) && count($settings) > 0) {
					$this->addSettingsById($extension, $settings);
				}
			}

			$ret = array(
				'status' => true,
			);

			break;
		}

		return $ret;
	}

	public function bulkhandlerExport($type) {
		$data = NULL;

		switch ($type) {
		case 'extensions':
			$extensions = $this->listAll();

			foreach ($extensions as $extension) {
				$settings = $this->getSettingsById($extension, true);
				$psettings = array();
				foreach ($settings as $key => $value) {
					switch ($key) {
					case 'grpnum':
						break;
					case 'ddial':
						$psettings['findmefollow_' . 'enabled'] = ($value) ? 'yes' : '';
						break;
					default:
						$psettings['findmefollow_' . $key] = $value;
						break;
					}
				}
				$data[$extension] = $psettings;
			}

			break;
		}

		return $data;
	}
	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case 'toggleFM':
			case 'getJSON':
				return true;
			break;
			default:
				return false;
			break;
		}
	}
	public function ajaxHandler(){
		switch ($_REQUEST['command']) {
			case 'toggleFM':
				$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';
				$state = '';
				if($_REQUEST['state'] == 'enable'){
					$state = true;
				}
				if($_REQUEST['state'] == 'disable'){
					$state = false;
				}
				if($state === '' || empty($extdisplay)){
					return array('toggle' => 'invalid');
				}
				$ret = $this->setDDial($extdisplay,$state);
				return array('toggle' => 'received', 'return' => $ret );
			break;
			case 'getJSON':
				switch ($_REQUEST['jdata']) {
					case 'grid':
						$ret = array();
						foreach($this->listFollowme() as $fm){
							$ret[] = array('ext'=>$fm[0]);
						}
						return array_values($ret);
					break;

					default:
						return false;
					break;
				}
			break;

			default:
				return false;
			break;
		}
	}
	public function listFollowme($get_all=false){
		$sql = "SELECT grpnum FROM findmefollow ORDER BY CAST(grpnum as UNSIGNED)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$results = $stmt->fetchall();
		if (isset($results)) {
			foreach($results as $result) {
				if ($get_all || checkRange($result)){
					$grps[] = $result;
				}
			}
		}
		if (isset($grps)) {
			return $grps;
		}
		else {
			return array();
		}
	}

	public function getAllFollowmes() {
		$sql = "SELECT grpnum, strategy, grptime, grppre, grplist, annmsg_id, postdest, dring, needsconf, remotealert_id, toolate_id, ringing, pre_ring, voicemail FROM findmefollow INNER JOIN `users` ON `extension` = `grpnum`";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$results = $sth->fetchall(\PDO::FETCH_ASSOC);
		return $results;
	}


	public function getRightNav($request) {
		if(isset($request['view'])&& $request['view'] == 'form'){
			return load_view(__DIR__."/views/bootnav.php",array('request' => $request));
		}
	}
	//Core extension Hooks
	public function addUser($extension,$post,$editmode){
		$conf = $this->FreePBX->Config();
		$ext = isset($post['extdisplay'])?$post['extdisplay']:null;
		$extn = isset($post['extension'])?$post['extension']:null;

		if ($ext=='') {
			$extdisplay = $extn;
		} else {
			$extdisplay = $ext;
		}
		$settings = array();
		foreach($post as $key => $value) {
			if(preg_match("/^fmfm_(.*)/",$key,$matches)) {
				$settings[$matches[1]] = $value;
			}
		}
		if(!empty($settings)) {
			$settings['ddial'] = ($settings['ddial'] == "enabled") ? "" : "CHECKED";
			if(isset($settings['needsconf'])) {
				$settings['needsconf'] = ($settings['needsconf'] == "enabled") ? "CHECKED" : "";
			}

			if(isset($post[$post[$settings['goto']]."fmfm"])) {
				$settings['postdest'] = $post[$post[$settings['goto']]."fmfm"];
			} else {
				$settings['postdest'] = "ext-local,$extdisplay,dest";
			}
			unset($settings['quickpick']);

			if (!isset($settings['fixedcid'])) {
				$settings['fixedcid'] = '';
			}

			//check destination. make sure it is valid
			$settings['postdest'] = ($settings['postdest'] == 'ext-local,,dest') ? 'ext-local,'.$extdisplay.',dest' : $settings['postdest'];
			//dont let group list be empty. ever.
			$settings['grplist'] = empty($settings['grplist']) ? $extdisplay : $settings['grplist'];
			$settings['grplist'] = explode("\n",$settings['grplist']);
			if($editmode){
				$this->update($extdisplay, $settings);
			}else{
				$this->add($extdisplay, $settings['strategy'], $settings['grptime'],
				$settings['grplist'], $settings['postdest'], $settings['grppre'], $settings['annmsg_id'], $settings['dring'],
				$settings['needsconf'], $settings['remotealert_id'], $settings['toolate_id'], $settings['ringing'], $settings['pre_ring'],
				$settings['ddial'], $settings['changecid'], $settings['fixedcid']);
			}
		} elseif($conf->get('FOLLOWME_AUTO_CREATE')) {
			$ddial = $conf->get('FOLLOWME_DISABLED') ? 'CHECKED' : '';
			$this->add($extdisplay, $conf->get('FOLLOWME_RG_STRATEGY'), $conf->get('FOLLOWME_TIME'),
			$extdisplay, 'ext-local,'.$extdisplay.',dest', "", "", "", "", "", "","", $conf->get('FOLLOWME_PRERING'), $ddial,'default','');
		}

	}

}
