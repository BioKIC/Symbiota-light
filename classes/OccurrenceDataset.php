<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');

class OccurrenceDataset {

	private $conn;
	private $collArr = array();
	private $datasetId = 0;
	private $errorArr = array();

	public function __construct($type = 'write'){
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

  public function getPublicDatasets(){
    // Tests if field `category` exists in table
    $sqlFields = 'SHOW COLUMNS FROM omoccurdatasets LIKE "category"';
    $fields = $this->conn->query($sqlFields);
    $catExists = $fields->num_rows?TRUE:FALSE;
    $fields->free();
    $retArr = array();
    $sql = '';
    if ($catExists) {
      $sql = 'SELECT datasetid, category, name, notes, description, uid, sortsequence, initialtimestamp, ispublic FROM omoccurdatasets WHERE ispublic=1 ORDER BY category,name';

    } else {
      $sql = 'SELECT datasetid, name, notes, description, uid, sortsequence, initialtimestamp, ispublic FROM omoccurdatasets WHERE ispublic=1 ORDER BY name';
    }
    $rs = $this->conn->query($sql);
    while($r = $rs->fetch_assoc()){
      $retArr[] = $r;
    }
    $rs->free();
    return $retArr;
  }

	public function getPublicDatasetMetadata($dsid){
		$retArr = array();
		if($dsid){
			//Get and return individual dataset
			$sql = 'SELECT datasetid, name, notes, description, uid, sortsequence, initialtimestamp FROM omoccurdatasets WHERE (datasetid = '.$dsid.') AND ispublic=1';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['name'] = $r->name;
				$retArr['notes'] = $r->notes;
				$retArr['description'] = $r->description;
				$retArr['uid'] = $r->uid;
				$retArr['sort'] = $r->sortsequence;
				$retArr['ts'] = $r->initialtimestamp;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getDatasetMetadata($dsid){
		$retArr = array();
		if($GLOBALS['SYMB_UID'] && $dsid){
			//Get and return individual dataset
			$sql = 'SELECT datasetid, name, notes, description, uid, sortsequence, initialtimestamp, ispublic FROM omoccurdatasets WHERE (datasetid = '.$dsid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['name'] = $r->name;
				$retArr['notes'] = $r->notes;
				$retArr['description'] = $r->description;
				$retArr['uid'] = $r->uid;
				$retArr['sort'] = $r->sortsequence;
				$retArr['ts'] = $r->initialtimestamp;
        $retArr['ispublic'] = $r->ispublic;
			}
			$rs->free();
			//Get roles for current user
			$sql1 = 'SELECT role FROM userroles WHERE (tablename = "omoccurdatasets") AND (tablepk = '.$dsid.') AND (uid = '.$GLOBALS['SYMB_UID'].') ';
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$retArr['roles'][] = $r1->role;
			}
			$rs1->free();
		}
		return $retArr;
	}

	public function getDatasetArr(){
		$retArr = array();
		if($GLOBALS['SYMB_UID']){
			$sql = 'SELECT datasetid, name, notes, description, sortsequence, initialtimestamp, ispublic FROM omoccurdatasets WHERE (uid = '.$GLOBALS['SYMB_UID'].') ORDER BY sortsequence,name';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['owner'][$r->datasetid]['name'] = $r->name;
				$retArr['owner'][$r->datasetid]['notes'] = $r->notes;
				$retArr['owner'][$r->datasetid]['description'] = $r->description;
				$retArr['owner'][$r->datasetid]['sort'] = $r->sortsequence;
				$retArr['owner'][$r->datasetid]['ts'] = $r->initialtimestamp;
				$retArr['owner'][$r->datasetid]['ispublic'] = $r->ispublic;
			}
			$rs->free();

			//Get shared datasets
			$sql1 = 'SELECT d.datasetid, d.name, d.notes, d.description, d.sortsequence, d.ispublic, d.initialtimestamp, r.role '.
				'FROM omoccurdatasets d INNER JOIN userroles r ON d.datasetid = r.tablepk '.
				'WHERE (r.uid = '.$GLOBALS['SYMB_UID'].') AND (r.role IN("DatasetAdmin","DatasetEditor","DatasetReader")) '.
				'ORDER BY sortsequence,name';
			//echo $sql1;
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$retArr['other'][$r1->datasetid]['name'] = $r1->name;
				$retArr['other'][$r1->datasetid]['role'] = $r1->role;
				$retArr['other'][$r1->datasetid]['notes'] = $r1->notes;
				$retArr['other'][$r1->datasetid]['description'] = $r1->description;
				$retArr['other'][$r1->datasetid]['sort'] = $r1->sortsequence;
				$retArr['other'][$r1->datasetid]['ts'] = $r1->initialtimestamp;
				$retArr['other'][$r1->datasetid]['ispublic'] = $r1->ispublic;
			}
			$rs1->free();
		}
		return $retArr;
	}

	public function editDataset($dsid,$name,$notes,$description,$ispublic){
		$sql = 'UPDATE omoccurdatasets SET name = "'.$this->cleanInStr($name).'", notes = "'.$this->cleanInStr($notes).'", description = "'.$this->cleanInStr($description).'", ispublic = '.$this->cleanInStr($ispublic).' WHERE datasetid = '.$dsid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] = 'ERROR saving dataset edits: '.$this->conn->error;
			return false;
		}
		return true;
	}

	public function createDataset($name,$notes,$description,$ispublic,$uid){
		$sql = 'INSERT INTO omoccurdatasets (name,notes,description,ispublic,uid) VALUES("'.$this->cleanInStr($name).'",'.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.($description?'"'.$this->cleanInStr($description).'"':'NULL').','.($ispublic?'"'.$this->cleanInStr($ispublic).'"':'"0"').','.$uid.') ';
		if($this->conn->query($sql)){
			$this->datasetId = $this->conn->insert_id;
		}
		else{
			$this->errorArr[] = 'ERROR creating new dataset: '.$this->conn->error;
			return false;
		}
		return true;
	}

	public function mergeDatasets($targetArr){
		$targetDsid = array_shift($targetArr);
		//Rename target
		$sql1 = 'UPDATE omoccurdatasets SET name = CONCAT(name," (merged)") WHERE datasetid = '.$targetDsid;
		if($this->conn->query($sql1)){
			//Push occurrences to target
			$sql2 = 'UPDATE IGNORE omoccurdatasetlink SET datasetid = '.$targetDsid.' WHERE datasetid IN('.implode(',',$targetArr).')';
			if($this->conn->query($sql2)){
				//Delete dataset, including linked occurrences that failed to transfer due to being linked multiple times within the group
				$sql3 = 'DELETE FROM omoccurdatasets WHERE datasetid IN('.implode(',',$targetArr).')';
				if(!$this->conn->query($sql3)){
					$this->errorArr[] = 'WARNING: Unable to remove extra datasets: '.$this->conn->error;
					return false;
				}
			}
			else{
				$this->errorArr[] = 'FATAL ERROR: Unable to transfer occurrence records into target dataset: '.$this->conn->error;
				return false;
			}
		}
		else{
			$this->errorArr[] = 'FATAL ERROR: Unable to rename target dataset in prep for merge: '.$this->conn->error;
			return false;
		}
		return true;
	}

	public function cloneDatasets($targetArr,$uid){
		$status = true;
		$sql = 'SELECT datasetid, name, notes, description, sortsequence FROM omoccurdatasets WHERE datasetid IN('.implode(',',$targetArr).')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			//Create new name and ensure it doesn't already exist for owner
			$newName = $r->name.' - Copy';
			$newNameTemp = $newName;
			$cnt = 1;
			do{
				$sql1 = 'SELECT datasetid FROM omoccurdatasets WHERE name = "'.$newNameTemp.'" AND uid = '.$uid;
				$nameExists = false;
				$rs1 = $this->conn->query($sql1);
				while($rs1->fetch_object()){
					$newNameTemp = $newName.' '.$cnt;
					$nameExists = true;
					$cnt++;
				}
				$rs1->free();
			}while($nameExists);
			$newName = $newNameTemp;
			//Add to database
			$sql2 = 'INSERT INTO omoccurdatasets(name, notes, description, sortsequence, uid) VALUES("'.$newName.'","'.$r->notes.'","'.$r->description.'",'.($r->sortsequence?$r->sortsequence:'""').','.$uid.')';
			if($this->conn->query($sql2)){
				$this->datasetId = $this->conn->insert_id;
				//Duplicate all records wtihin new dataset
				$sql3 = 'INSERT INTO omoccurdatasetlink(occid, datasetid, notes, description) '.
					'SELECT occid, '.$this->datasetId.', notes, description FROM omoccurdatasetlink WHERE datasetid = '.$r->datasetid;
				if(!$this->conn->query($sql3)){
					$this->errorArr[] = 'ERROR: Unable to clone dataset links into new datasets: '.$this->conn->error;
					$status = false;
				}
			}
			else{
				$this->errorArr[] = 'ERROR: Unable to create new dataset within clone method: '.$this->conn->error;
				$status = false;
			}
		}
		$rs->free();
		return $status;
	}

	public function deleteDataset($dsid){
		//Delete users
		$sql1 = 'DELETE FROM userroles WHERE (role IN("DatasetAdmin","DatasetEditor","DatasetReader")) AND (tablename = "omoccurdatasets") AND (tablepk = '.$dsid.') ';
		//echo $sql;
		if(!$this->conn->query($sql1)){
			$this->errorArr[] = 'ERROR deleting user: '.$this->conn->error;
			return false;
		}

		//Delete datasets
		$sql2 = 'DELETE FROM omoccurdatasets WHERE datasetid = '.$dsid;
		if(!$this->conn->query($sql2)){
			$this->errorArr[] = 'ERROR: Unable to delete target datasets: '.$this->conn->error;
			return false;
		}
		return true;

		//Delete dataset records
		$sql3 = 'DELETE FROM omoccurdatasetlink WHERE datasetid = '.$dsid;
		if(!$this->conn->query($sql3)){
			$this->errorArr[] = 'ERROR: Unable to delete target datasets: '.$this->conn->error;
			return false;
		}
		return true;
	}

	public function getUsers($datasetId){
		$retArr = array();
		$sql = 'SELECT u.uid, r.role, CONCAT_WS(", ",u.lastname,u.firstname) as username '.
			'FROM userroles r INNER JOIN users u ON r.uid = u.uid '.
			'WHERE r.role IN("DatasetAdmin","DatasetEditor","DatasetReader") '.
			'AND (r.tablename = "omoccurdatasets") AND (r.tablepk = '.$datasetId.')';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->role][$r->uid] = $r->username;
		}
		$rs->free();
		return $retArr;
	}

	public function addUser($datasetID,$uid,$role){
		if(is_numeric($uid)){
			$sql = 'INSERT INTO userroles(uid,role,tablename,tablepk,uidassignedby) VALUES('.$uid.',"'.$this->cleanInStr($role).'","omoccurdatasets",'.$datasetID.','.$GLOBALS['SYMB_UID'].')';
			if(!$this->conn->query($sql)){
				$this->errorArr[] = 'ERROR adding new user: '.$this->conn->error;
				return false;
			}
		}
		return true;
	}

	public function deleteUser($datasetID,$uid,$role){
		$status = true;
		$sql = 'DELETE FROM userroles WHERE (uid = '.$uid.') AND (role = "'.$role.'") AND (tablename = "omoccurdatasets") AND (tablepk = '.$datasetID.') ';
		if(!$this->conn->query($sql)){
			$this->errorArr[] = 'ERROR deleting user: '.$this->conn->error;
			return false;
		}
		return $status;
	}

	public function getOccurrences($datasetId){
		$retArr = array();
		if($datasetId){
			$sql = 'SELECT o.occid, o.catalognumber, o.occurrenceid ,o.othercatalognumbers, '.
				'o.sciname, o.family, o.recordedby, o.recordnumber, o.eventdate, '.
				'o.country, o.stateprovince, o.county, o.locality, o.decimallatitude, o.decimallongitude, dl.notes '.
				'FROM omoccurrences o INNER JOIN omoccurdatasetlink dl ON o.occid = dl.occid '.
				'WHERE dl.datasetid = '.$datasetId;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->catalognumber) $retArr[$r->occid]['catnum'] = $r->catalognumber;
				elseif($r->occurrenceid) $retArr[$r->occid]['catnum'] = $r->occurrenceid;
				elseif($r->othercatalognumbers) $retArr[$r->occid]['catnum'] = $r->othercatalognumbers;
				else $retArr[$r->occid]['catnum'] = '';
				$sciname = $r->sciname;
				if($r->family) $sciname .= ' ('.$r->family.')';
				$retArr[$r->occid]['sciname'] = $sciname;
				$collStr = $r->recordedby.' '.$r->recordnumber;
				if($r->eventdate) $collStr .= ' ['.$r->eventdate.']';
				$retArr[$r->occid]['coll'] = $collStr;
				$retArr[$r->occid]['loc'] = trim($r->country.', '.$r->stateprovince.', '.$r->county.', '.$r->locality,', ');
			}
			$rs->free();
		}
		return $retArr;
	}

	public function removeSelectedOccurrences($datasetId, $occArr){
		$status = true;
		if($datasetId && $occArr){
			$sql = 'DELETE FROM omoccurdatasetlink WHERE (datasetid = '.$datasetId.') AND (occid IN('.implode(',',$occArr).'))';
			if(!$this->conn->query($sql)){
				$this->errorArr[] = 'ERROR deleting selected occurrences: '.$this->conn->error;
				return false;
			}
		}
		return $status;
	}

	public function addSelectedOccurrences($datasetId, $occArr){
		$status = false;
		if(is_numeric($datasetId)){
			if(is_numeric($occArr)) $occArr = array($occArr);
			foreach($occArr as $v){
				if(is_numeric($v)){
					$sql = 'INSERT IGNORE INTO omoccurdatasetlink(occid,datasetid) VALUES("'.$v.'",'.$datasetId.') ';
					if($this->conn->query($sql)) $status = true;
					else{
						$this->errorArr[] = 'ERROR adding occurrence ('.$v.'): '.$this->conn->error;
						$status = false;
					}
				}
			}
		}
		return $status;
	}

	//General setters and getters
	public function getUserList($term){
		$retArr = array();
		$sql = 'SELECT u.uid, CONCAT(CONCAT_WS(", ",u.lastname, u.firstname)," - ",l.username," [#",u.uid,"]") AS username '.
			'FROM users u INNER JOIN userlogin l ON u.uid = l.uid '.
			'WHERE u.lastname LIKE "%'.$this->cleanInStr($term).'%" OR l.username LIKE "%'.$this->cleanInStr($term).'%" '.
			'ORDER BY u.lastname,u.firstname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()) {
			$retArr[] = array('id'=>$r->uid,'label'=>$r->username);
		}
		$rs->free();
		return $retArr;
	}

	public function getCollName($collId){
		$collName = '';
		if($collId){
			if(!$this->collArr) $this->setCollMetadata($collId);
			$collName = $this->collArr['collname'].' ('.$this->collArr['instcode'].($this->collArr['collcode']?':'.$this->collArr['collcode']:'').')';
		}
		return $collName;
	}

	private function setCollMetadata($collId){
		$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype '.
			'FROM omcollections WHERE collid = '.$collId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$this->collArr['instcode'] = $r->institutioncode;
				$this->collArr['collcode'] = $r->collectioncode;
				$this->collArr['collname'] = $r->collectionname;
				$this->collArr['colltype'] = $r->colltype;
			}
			$rs->free();
		}
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	public function getErrorMessage(){
		return implode('; ',$this->errorArr);
	}

	public function getDatasetId(){
		return $this->datasetId;
	}

	//Misc functions
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>