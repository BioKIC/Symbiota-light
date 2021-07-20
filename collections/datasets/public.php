<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
header("Content-Type: text/html; charset=".$CHARSET);

// Datasets
$datasetid = array_key_exists('datasetid',$_REQUEST)?$_REQUEST['datasetid']:0;

if(!is_numeric($datasetid)) $datasetid = 0;

$datasetManager = new OccurrenceDataset();
$dArr = $datasetManager->getPublicDatasetMetadata($datasetid);
$searchUrl = '../../collections/list.php?datasetid='.$datasetid;
$tableUrl = '../../collections/listtabledisplay.php?datasetid='.$datasetid;
$taxaUrl = '../../collections/list.php?datasetid='.$datasetid.'&tabindex=0';
// $downloadUrl = '../../collections/download/index.php?datasetid='.$datasetid;
$ocArr = $datasetManager->getOccurrences($datasetid);
?>
<html>
	<head>
		<title>Dataset: <?php echo $dArr['name'] ;?></title>
    <?php
      $activateJQuery = false;
      if(file_exists($SERVER_ROOT.'/includes/head.php')){
        include_once($SERVER_ROOT.'/includes/head.php');
      }
      else{
        echo '<link href="'.$CLIENT_ROOT.'/css/jquery-ui.css" type="text/css" rel="stylesheet" />';
        echo '<link href="'.$CLIENT_ROOT.'/css/base.css?ver=1" type="text/css" rel="stylesheet" />';
        echo '<link href="'.$CLIENT_ROOT.'/css/main.css?ver=1" type="text/css" rel="stylesheet" />';
      }
    ?>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo $CLIENT_ROOT; ?>/index.php">Home</a> &gt;&gt;
			<b>Dataset: <?php echo $dArr['name'] ;?></b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">
    <h1>Dataset: <?php echo $dArr['name'] ;?></h1>
    <ul>
      <!-- Metadata -->
      <div><?php echo $dArr['description'] ;?></div>
      <!-- Occurrences Summary -->
      <p>This dataset includes <?php echo count($ocArr); ?> records.</p>
      
      <p><a class="btn" href="<?php echo $searchUrl ;?>">View and download samples in this Dataset (List view)</a></p>
      <p><a class="btn" href="<?php echo $tableUrl ;?>">View samples in this Dataset (Table view)</a></p>
      <p><a class="btn" href="<?php echo $taxaUrl ;?>">View list of taxa in this Dataset</a></p>
      <!-- <p><a href="#">Download this Dataset</a></p> -->
    </ul>
		</div>
		<?php
			include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
