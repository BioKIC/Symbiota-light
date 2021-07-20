<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/datasets/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitize input variables
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$datasetManager = new OccurrenceDataset();

$statusStr = '';
if($action == 'createNewDataset'){
	if(!$datasetManager->createDataset($_POST['name'],$_POST['notes'],$_POST['description'],$_POST['ispublic'],$SYMB_UID)){
		$statusStr = implode(',',$datasetManager->getErrorArr());
	}
}
elseif($action == 'addSelectedToDataset'){
	$datasetID = $_POST['datasetid'];
	if(!$datasetID && $_POST['name']) $datasetManager->createDataset($_POST['name'],'',$SYMB_UID);
}
elseif($action == 'addAllToDataset'){

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
			function validateAddForm(f){
				if(f.adduser.value == ""){
					alert("Enter a user (login or last name)");
					return false
				}
				if(f.adduser.value.indexOf(" [#") == -1){
					$.ajax({
						url: "rpc/getuserlist.php",
						dataType: "json",
						data: {
							term: f.adduser.value
						},
						success: function(data) {
							if(data && data != ""){
								f.adduser.value = data;
								alert("Located login: "+data);
								f.submit();
							}
							else{
								alert("Unable to locate user");
							}
						}
					});
					return false;
				}
				return true;
			}
		</script>
		<style>
			fieldset{ padding:15px;margin:15px; }
			legend{ font-weight: bold; }
			.dataset-item{ margin-bottom: 10px }
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
		<a href="index.php"><b>Dataset Listing</b></a>
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
		$dataSetArr = $datasetManager->getDatasetArr();
		?>
		<div>
		<div style="float:right;margin:10px;" title="Create New Dataset" >
	 		<a href="#" onclick="toggle('adddiv');return false;"><img src="../../images/add.png" style="width:14px;" /></a>
		</div>
		<h2>Occurrence Dataset Management</h2>
		<div>These tools will allow you to define and manage datasets profiles. Once a profile is created, you can link occurrence records via the occurrence search and display pages.</div>
		<div id=adddiv style="display:none">
			<fieldset>
				<legend><b>Create New Dataset</b></legend>
				<form name="adminform" action="index.php" method="post" onsubmit="return validateEditForm(this)">
					<div>
						<p><b>Name</b></p>
						<input name="name" type="text" style="width:90%" />
					</div>
          <div>
            <p>
              <input type="checkbox" name="ispublic" id="ispublic" value="1" />
            <b>Publicly Visible</b>
            </p>
          </div>          
					<div>
						<p><b>Notes (Internal usage, not displayed publicly)</b></p>
						<input name="notes" type="text" style="width:90%;" />
					</div>
          <div>
            <p><b>Description (Displayed publicly)</p>
            <textarea name="description" id="description" cols="100" rows="10" width="90%"></textarea>
          </div>
					<div style="margin:15px">
						<button name="submitaction" type="submit" value="createNewDataset">Create New Dataset</button>
					</div>
				</form>
			</fieldset>
		</div>
		<?php
		if($dataSetArr){
			?>
			<fieldset>
				<legend><b>Owned by You</b></legend>
				<?php
				if(array_key_exists('owner',$dataSetArr)){
					$ownerArr = $dataSetArr['owner'];
					unset($dataSetArr['owner']);
					foreach($ownerArr as $dsid => $dsArr){
						?>
						<div class="dataset-item">
							<div>
								<a href="datasetmanager.php?datasetid=<?php echo $dsid; ?>" title="Manage and edit dataset">
									<?php
									echo '<b>'.$dsArr['name'].' (#'.$dsid.')</b>';
									?>
								</a>
							</div>
							<div style="margin-left:15px;">
								<?php
								echo ($dsArr['notes']?'<div>'.$dsArr['notes'].'</div>':'');
								echo '<div>Created: '.$dsArr["ts"].'</div>';
								?>
							</div>
						</div>
						<?php
					}
				}
				else{
					echo '<div style="font-weight:bold;">There are no datasets owned by you</div>';
				}
				?>
			</fieldset>
			<fieldset>
				<legend>Shared with You</legend>
				<?php
				if(array_key_exists('other',$dataSetArr)){
					$otherArr = $dataSetArr['other'];
					foreach($otherArr as $dsid => $dsArr){
						?>
						<div>
							<a href="datasetmanager.php?datasetid=<?php echo $dsid; ?>" title="Access Dataset">
								<?php
								$role = 'Dataset reader';
								if($dsArr['role'] == 'DatasetAdmin') $role = 'Dataset Administator';
								elseif($dsArr['role'] == 'DatasetEditor') $role = 'Dataset Editor';
								echo '<b>'.$dsArr["name"].' (#'.$dsid.')</b> - '.$role;
								?>
							</a>
						</div>
						<div style="margin-left:15px;">
							<?php
							echo ($dsArr["notes"]?$dsArr["notes"].'<br/>':'');
							echo 'Created: '.$dsArr["ts"];
							?>
						</div>
						<?php
					}
				}
				else echo '<div style="font-weight:bold;">There are no datasets shared with you</div>';
				?>
			</fieldset>
			<?php
		}
		else{
			?>
			<div style="margin:20px">
				<div style="font-weight:bold">There are no datasets associated to your login</div>
				<div style="margin-top:15px"><a href="#" onclick="toggle('adddiv');">Create a New Dataset</a></div>
			</div>
			<?php
		}
		?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
	</body>
</html>