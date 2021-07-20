<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/datasets/datasetmanager.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$datasetId = $_REQUEST['datasetid'];
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitation
if(!is_numeric($datasetId)) $datasetId = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$datasetManager = new OccurrenceDataset();

$mdArr = $datasetManager->getDatasetMetadata($datasetId);
$role = '';
$roleLabel = '';
$isEditor = 0;
if($SYMB_UID == $mdArr['uid']){
	$isEditor = 1;
	$role = 'owner';
}
elseif(isset($mdArr['roles'])){
	if(in_array('DatasetAdmin',$mdArr['roles'])){
		$isEditor = 1;
		$role = 'administrator';
	}
	elseif(in_array('DatasetEditor',$mdArr['roles'])){
		$isEditor = 2;
		$role = 'editor';
		$roleLabel = 'Can add and remove occurrences only';
	}
	elseif(in_array('DatasetReader',$mdArr['roles'])){
		$isEditor = 3;
		$role = 'read access only';
	}
}
elseif($IS_ADMIN){
	$isEditor = 1;
	$role = 'SuperAdmin';
}

$statusStr = '';
if($isEditor){
	if($isEditor < 3){
		if($action == 'Remove Selected Occurrences'){
			if($datasetManager->removeSelectedOccurrences($datasetId,$_POST['occid'])){
				//$statusStr = 'Selected occurrences removed successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
	}
	if($isEditor == 1){
		if($action == 'Save Edits'){
      $isPublic = (isset($_POST['ispublic'])&&is_numeric($_POST['ispublic'])?1:0);
			if($datasetManager->editDataset($_POST['datasetid'],$_POST['name'],$_POST['notes'],$_POST['description'],$isPublic)){
				$mdArr = $datasetManager->getDatasetMetadata($datasetId);
				$statusStr = 'Success! Dataset edits saved. ';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'Merge'){
			if($datasetManager->mergeDatasets($_POST['dsids[]'])){
				$statusStr = 'Datasets merged successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'Clone (make copy)'){
			if($datasetManager->cloneDatasets($_POST['dsids[]'])){
				$statusStr = 'Datasets cloned successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'Delete Dataset'){
			if($datasetManager->deleteDataset($_POST['datasetid'])){
				header("Location: index.php");
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'addUser'){
			if($datasetManager->addUser($datasetId,$_POST['uid'],$_POST['role'])){
				$statusStr = 'User added successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'DelUser'){
			if($datasetManager->deleteUser($datasetId,$_POST['uid'],$_POST['role'])){
				$statusStr = 'User removed successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
	}
}

?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE; ?> Occurrence Dataset Manager</title>
		<?php
		$activateJQuery = true;
		if(file_exists($SERVER_ROOT.'/includes/head.php')){
			include_once($SERVER_ROOT.'/includes/head.php');
	    }
		else{
			echo '<link href="'.$CLIENT_ROOT.'/css/jquery-ui.css" type="text/css" rel="stylesheet" />';
			echo '<link href="'.$CLIENT_ROOT.'/css/base.css?ver=1" type="text/css" rel="stylesheet" />';
			echo '<link href="'.$CLIENT_ROOT.'/css/main.css?ver=1" type="text/css" rel="stylesheet" />';
		}
		?>
		<script type="text/javascript" src="../../js/jquery.js"></script>
		<script type="text/javascript" src="../../js/jquery-ui.js"></script>
		<script type="text/javascript" src="../../js/symb/shared.js"></script>
    <script type="text/javascript" src="../../js/tinymce/tinymce.min.js"></script>
    <script>
     // Adds WYSIWYG editor to description field
      tinymce.init({
        selector: '#description',
        plugins: 'link lists image',
        menubar: '',
        toolbar: [
          'undo redo | bold italic underline | link | alignleft aligncenter alignright | formatselect | bullist numlist | indent outdent | blockquote | image',
        ],
        branding: false
      });
    </script>
		<script type="text/javascript">
			var isDownloadAction = false;
			$(document).ready(function() {
				var dialogArr = new Array("schemanative","schemadwc");
				var dialogStr = "";
				for(i=0;i<dialogArr.length;i++){
					dialogStr = dialogArr[i]+"info";
					$( "#"+dialogStr+"dialog" ).dialog({
						autoOpen: false,
						modal: true,
						position: { my: "left top", at: "center", of: "#"+dialogStr }
					});

					$( "#"+dialogStr ).click(function() {
						$( "#"+this.id+"dialog" ).dialog( "open" );
					});
				}

				$('#tabs').tabs({
					active: <?php echo $tabIndex; ?>,
					beforeLoad: function( event, ui ) {
						$(ui.panel).html("<p>Loading...</p>");
					}
				});

				$( "#userinput" ).autocomplete({
					source: "rpc/getuserlist.php",
					minLength: 3,
					autoFocus: true,
					select: function( event, ui ) {
						$('#uid-add').val(ui.item.id);
					}
				});

			});

			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function validateDataSetForm(f){
				var dbElements = document.getElementsByName("dsids[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
				alert("Please select at least one dataset!");

				var confirmStr = '';
				if(f.submitaction.value == "Merge"){
					confirmStr = 'Are you sure you want to merge selected datasets?';
				}
				else if(f.submitaction.value == "Clone (make copy)"){
					confirmStr = 'Are you sure you want to clone selected datasets?';
				}
				else if(f.submitaction.value == "Delete"){
					confirmStr = 'Are you sure you want to delete selected datasets?';
				}
				if(confirmStr == '') return true;
				return confirm(confirmStr);
			}

			function validateEditForm(f){
				if(f.name.value == ''){
					alert("Dataset name cannot be null");
					return false;
				}
				return true;
			}

			function validateOccurForm(f){
				var occidChecked = false;
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked){
						occidChecked = true;
						break;
					}
				}
				if(!occidChecked){
				   	alert("Please select at least one specimen!");
				   	return false;
				}
				if(isDownloadAction){
					f.action = "../download/index.php";
					targetDownloadPopup(f);
				}
			  	return true;
			}

			function validateUserAddForm(f){
				if(f.uid.value == ""){
					alert("Select a user from the list");
					return false;
				}
				return true;
			}

			function openIndPopup(occid){
				openPopup("../individual/index.php?occid="+occid);
			}

			function openPopup(urlStr){
				var wWidth = 900;
				if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
				if(wWidth > 1200) wWidth = 1200;
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				newWindow.focus();
				return false;
			}

			function targetDownloadPopup(f) {
				window.open('', 'downloadpopup', 'left=100,top=50,width=900,height=700');
				f.target = 'downloadpopup';
			}
		</script>
		<style>
			.section-title{ margin:0px 15px; font-weight:bold; text-decoration:underline; }
		</style>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_datasets_indexMenu)?$collections_datasets_indexMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt;
		<a href="../../profile/viewprofile.php?tabindex=1">My Profile</a> &gt;&gt;
		<a href="index.php">
			Return to Dataset Listing
		</a> &gt;&gt;
		<b>Dataset Manager</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($statusStr){
			$color = 'green';
			if(strpos($statusStr,'ERROR') !== false) $color = 'red';
			elseif(strpos($statusStr,'WARNING') !== false) $color = 'orange';
			elseif(strpos($statusStr,'NOTICE') !== false) $color = 'yellow';
			echo '<div style="margin:15px;color:'.$color.';">';
			echo $statusStr;
			echo '</div>';
		}
		if($datasetId){
			echo '<div style="margin:10px 0px 5px 20px;font-weight:bold;font-size:130%;">'.$mdArr['name'].'</div>';
			if($role) echo '<div style="margin-left:20px" title="'.$roleLabel.'">Role: '.$role.'</div>';
			if($isEditor){
				?>
				<div id="tabs" style="margin:10px;">
					<ul>
						<li><a href="#occurtab"><span>Occurrence List</span></a></li>
						<?php
						if($isEditor == 1){
							?>
							<li><a href="#admintab"><span>General Management</span></a></li>
							<li><a href="#accesstab"><span>User Access</span></a></li>
							<?php
						}
						?>
					</ul>
					<div id="occurtab">
						<?php
						if($occArr = $datasetManager->getOccurrences($datasetId)){
							?>
							<form name="occurform" action="datasetmanager.php" method="post" onsubmit="return validateOccurForm(this)">
								<div style="float:right;margin-right:10px">
									<b>Count: <?php echo count($occArr); ?> records</b>
								</div>
								<table class="styledtable" style="font-family:Arial;font-size:12px;">
									<tr>
										<th><input name="" value="" type="checkbox" onclick="selectAll(this);" title="Select/Deselect all Specimens" /></th>
										<th>catalog #</th>
										<th>Collector</th>
										<th>Scientific Name</th>
										<th>Locality</th>
									</tr>
									<?php
									$trCnt = 0;
									foreach($occArr as $occid => $recArr){
										$trCnt++;
										?>
										<tr <?php echo ($trCnt%2?'class="alt"':''); ?>>
											<td>
												<input type="checkbox" name="occid[]" value="<?php echo $occid; ?>" />
											</td>
											<td>
												<?php echo $recArr['catnum']; ?>
												<a href="#" onclick="openIndPopup(<?php echo $occid; ?>); return false;">
													<img src="../../images/info.png" style="width:15px;" />
												</a>
											</td>
											<td>
												<?php echo $recArr['coll']; ?>
											</td>
											<td>
												<?php echo $recArr['sciname']; ?>
											</td>
											<td>
												<?php echo $recArr['loc']; ?>
											</td>
										</tr>
										<?php
									}
									?>
								</table>
								<div style="margin: 15px;">
									<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
									<?php
									if($occArr && $isEditor < 3){
										?>
										<button type="submit" name="submitaction" value="Remove Selected Occurrences">Remove Selected Occurrences</button>
										<?php
									}
									?>
								</div>
							</form>
							<div style="margin: 15px;">
								<form name="exportAllForm" action="../download/index.php" method="post" onsubmit="targetDownloadPopup(this)">
									<input name="searchvar" type="hidden" value="datasetid=<?php echo $datasetId; ?>" />
									<input name="dltype" type="hidden" value="specimen" />
									<button type="submit" name="submitaction" value="exportAll">Export Dataset</button>
								</form>
							</div>
							<?php
						}
						else{
							?>
							<div style="font-weight:bold; margin:15px">There are not yet any occurrences linked to this dataset</div>
							<div style="margin:15px">You can link occurrences via the <a href="../index.php">occurrence search page</a> or via any of the the occurrence profile pages</div>
							<?php
						}
						?>
					</div>
					<?php
					if($isEditor == 1){
						?>
						<div id="admintab">
							<fieldset style="padding:15px;margin:15px;">
								<legend><p><b>Editor</b></p></legend>
								<form name="editform" action="datasetmanager.php" method="post" onsubmit="return validateEditForm(this)">
									<div>
										<p><b>Name</p>
										<input name="name" type="text" value="<?php echo $mdArr['name']; ?>" style="width:70%" />
									</div>
                  <div>
                  <p>
                  <input type="checkbox" name="ispublic" id="ispublic" value="1" <?php echo ($mdArr['ispublic']?'CHECKED':''); ?> />
                  <b>Publicly Visible</b>
                  </p>
                  </div>
                  <div>
										<p><b>Notes (Internal usage, not displayed publicly)</b></p>
										<input name="notes" type="text" value="<?php echo $mdArr['notes']; ?>" style="width:70%" />
									</div>
									<div>
										<p><b>Description</p>
                    <textarea name="description" id="description" cols="100" rows="10" width="70%"><?php echo $mdArr['description']; ?></textarea>
									</div>
									<div style="margin:15px;">
										<input name="tabindex" type="hidden" value="1" />
										<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
										<input name="submitaction" type="submit" value="Save Edits" />
									</div>
								</form>
							</fieldset>
							<fieldset style="padding:15px;margin:15px;">
								<legend><b>Delete Dataset</b></legend>
								<form name="editform" action="datasetmanager.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this dataset?')">
									<div style="margin:15px;">
										<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
										<input name="tabindex" type="hidden" value="1" />
										<input name="submitaction" type="submit" value="Delete Dataset" />
									</div>
								</form>
							</fieldset>
						</div>
						<div id="accesstab">
							<div style="margin:25px 10px;">
								<?php
								$userArr = $datasetManager->getUsers($datasetId);
								$roleArr = array('DatasetAdmin' => 'Full Access Users','DatasetEditor' => 'Read/Write Users','DatasetReader' => 'Read Only Users');
								foreach($roleArr as $roleStr => $labelStr){
									?>
									<div class="section-title"><?php echo $labelStr; ?></div>
									<div style="margin:15px;">
										<?php
										if(array_key_exists($roleStr,$userArr)){
											?>
											<ul>
												<?php
												$uArr = $userArr[$roleStr];
												foreach($uArr as $uid => $name){
													?>
													<li>
														<?php echo $name; ?>
														<form name="deluserform" method="post" action="datasetmanager.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove <?php echo $name; ?>')">
															<input name="submitaction" type="hidden" value="DelUser" />
															<input name="role" type="hidden" value="<?php echo $roleStr; ?>" />
															<input name="uid" type="hidden" value="<?php echo $uid; ?>" />
															<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
															<input name="tabindex" type="hidden" value="2" />
															<input name="submitimage" type="image" src="../../images/drop.png" />
														</form>
													</li>
													<?php
												}
												?>
											</ul>
											<?php
										}
										else echo '<div style="margin:15px;">None Assigned</div>';
										?>
									</div>
									<?php
								}
								?>
							</div>
							<div style="margin:15px;">
								<fieldset>
									<legend><p><b>Add User</b></legend>
									<form name="addform" action="datasetmanager.php" method="post" onsubmit="return validateUserAddForm(this)">
										<div title="Type login or last name and then select from list">
											Login/Last Name:
											<input id="userinput" type="text" style="width:400px;" />
											<input id="uid-add" name="uid" type="hidden" value="" />
										</div>
										Role:
										<select name="role">
											<option value="DatasetAdmin">Full Access</option>
											<option value="DatasetEditor">Read/Write Access</option>
											<option value="DatasetReader">Read-Only Access</option>
										</select>
										<div style="margin:10px;">
											<input name="tabindex" type="hidden" value="2" />
											<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
											<button type="submit" name="submitaction" value="addUser">Add User</button>
										</div>
									</form>
								</fieldset>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			else echo '<div style="margin:30px">You are not authorized to view this dataset</div>';
		}
		else echo '<div><b>ERROR: dataset id not identified</b></div>';
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
	</body>
</html>