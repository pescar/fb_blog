<?php
include_once 'functions.inc.php';

if($_SERVER['REQUEST_METHOD']=='POST'
&& $_POST['submit']=='Save Entry'
&& !empty($_POST['page'])
&& !empty($_POST['title'])
&& !empty($_POST['entry']))
{

	$url = makeUrl($_POST['title']);
	
	//$title = $_POST['title'];
	//$entry = $_POST['entry'];
	include_once 'db.inc.php';
	$db = new PDO(DB_INFO, DB_USER, DB_PASS);
	$sql = "INSERT INTO entries (page, title, entry, url)
		VALUES (?, ?, ?, ?)";
	$stmt = $db->prepare($sql);
	$stmt->execute(
					array(
							$_POST['page'], 
							$_POST['title'], 
							$_POST['entry'], 
							$url)
);
	$stmt->closeCursor();
	$page = htmlentities(strip_tags($_POST['page']));
	$id_obj = $db->query("SELECT LAST_INSERT_ID()");
	$id = $id_obj->fetch();
	$id_obj->closeCursor();
	// Send the user to the new entry
	header('Location: /fb_blog/'.$page.'/'.$url);
	exit;

}

else
{
header('Location: ../');
}
?>
