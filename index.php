
<?php 
include_once '/inc/functions.inc.php';
include_once '/inc/db.inc.php';
$db = new PDO(DB_INFO, DB_USER, DB_PASS);
$id = (isset($_GET['id'])) ? (int) $_GET['id'] : NULL;

if(isset($_GET['page']))
{
	$page = htmlentities(strip_tags($_GET['page']));
}
else
{
	$page = 'blog';
}


$e= retrieveEntries($db,$page,$id);
$fulldisp = array_pop($e);
$e=sanitizeData($e);
?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type"
content="text/html;charset=utf-8" />
<link rel="stylesheet" href="css/default.css" type="text/css" />
<title> Simple Blog </title>
</head>
<body>
<h1> Simple Blog Application </h1>
<div id="entries">
<?php
if($fulldisp==1)
{
?>
<h2><?php echo $e['title']?></h2>
<p> <?php  echo $e['entry']?></p>
<?php if($page=='blog'): ?>
<p class="backlink">
	<a href="./">Back to Latest Entries</a>
</p>
<?php endif?>
<?php 
}
else 
{
foreach($e as $entry){
?>
<p>
	<a href="?id=<?php echo $entry['id']?>">
		<?php echo $entry['title']?>
	</a>
</p>
<?php 
}
}
?>
<?php if($page=='blog'): ?>
<p class="backlink">
<a href="admin.php">Post a New Entry</a>
<?php endif; ?>

</a>
</p>
</p>
</div>
</body>
</html>