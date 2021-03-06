<?php
session_start();

include_once 'functions.inc.php';
include_once 'images.inc.php';

if($_SERVER['REQUEST_METHOD']=='POST'
&& $_POST['submit']=='Save Entry'
&& !empty($_POST['page'])
&& !empty($_POST['title'])
&& !empty($_POST['entry']))
{

	$url = makeUrl($_POST['title']);
	if(isset($_FILES['image']['tmp_name']))
	{
		try 
		{
		$img = new ImageHandler("/fb_blog/images/");
		$img_path = $img->processUploadedImage($_FILES['image']);	
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}
	}
	else 
	{
		//no img uploaded
		$img_path = NULL;	
	}

	include_once 'db.inc.php';
	$db = new PDO(DB_INFO, DB_USER, DB_PASS);
	if(!empty($_POST['id']))
	{
		$sql = "update entries
				set 
				title=?,
				image=?,
				entry=?,
				url=?
				where id=?
				limit 1";
		$stmt = $db->prepare($sql);
		$stmt->execute(
				array(
						$_POST['title'],
						$img_path,
						$_POST['entry'],
						$url,
						$_POST['id']
				)
		);
		$stmt->closeCursor();
		}
	else 
	{
	$sql = "INSERT INTO entries (page, title,image, entry, url)
		VALUES (?, ?, ?, ?, ?)";
	$stmt = $db->prepare($sql);
	$stmt->execute(
					array(
							$_POST['page'], 
							$_POST['title'], 
							$img_path,
							$_POST['entry'], 
							$url
							)
					);
	$stmt->closeCursor();
	}
	$page = htmlentities(strip_tags($_POST['page']));
	$id_obj = $db->query("SELECT LAST_INSERT_ID()");
	$id = $id_obj->fetch();
	$id_obj->closeCursor();
	// Send the user to the new entry
	header('Location: /fb_blog/'.$page.'/'.$url);
	exit;

}
else if ($_SERVER['REQUEST_METHOD']== 'POST'&& $_POST['submit'] =='Post Comment')
{
	include_once 'comments.inc.php';
	$comments = new Comments();
	//save the comm
	$comments->saveComment($_POST);
		if(isset($_SERVER['HTTP_REFERER']))
		{
			$loc= $_SERVER['HTTP_REFERER'];
		}
		else
		{
			$loc = '../';
		}
		header('Location:'.$loc);
		exit;

}
else if ($_GET['action']=='comment_delete')
{
	include_once 'comments.inc.php';
	$comments = new Comments();
	echo $comments->confirmDelete($_GET['id']);
	exit;
}
else if($_SERVER['REQUEST_METHOD']=="POST" && $_POST['action']=='comment_delete')
{
//store the entry from witch we came
$loc = isset($_POST['url']) ? $_POST['url'] : '../';
if($_POST['confirm']== "Yes")
{
	include_once 'comments.inc.php';
	$comments=new Comments();
	if($comments->deleteComment($_POST['id']))
	{
		header('Location:'.$loc);
		exit;
	}
	//if del fails
	else
	{
		exit('Could not delete the comment');
	}
}
else //if user clicked "No"
{
	header('Location: '.$loc);
	exit;
}
}
else if($_SERVER['REQUEST_METHOD'] == 'POST'
		&& $_POST['action'] == 'login'
		&& !empty($_POST['username'])
		&& !empty($_POST['password']))
	{
	// Include database credentials and connect to the database
	include_once 'db.inc.php';
	$db = new PDO(DB_INFO, DB_USER, DB_PASS);
	$sql = "SELECT COUNT(*) AS num_users
			FROM admin
			WHERE username=?
			AND password=SHA1(?)";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_POST['username'], $_POST['password']));
	$response = $stmt->fetch();
	if($response['num_users'] > 0)
	{
		$_SESSION['loggedin'] = 1;
	}
	else 
	{
		$_SESSION['loggedin']=NULL;
	}
	header('Location:/fb_blog/');
	exit;
}
else if($_SERVER['REQUEST_METHOD']=='POST'
		&& $_POST['action']=='createuser'
		&& !empty($_POST['username'])
		&& !empty($_POST['password']))
{
	include_once 'db.inc.php';
	$db = new PDO(DB_INFO,DB_USER,DB_PASS);
	$sql = "INSERT INTO admin(username,password)
			values(?,SHA1(?))";
	$stmt=$db->prepare($sql);
	$stmt->execute(array($_POST['username'],$_POST['password']));
	header('Location: /fb_blog/');
	exit;
}
else if($_GET['action']=='logout')
{
	session_destroy();
	header('Location: ../');
	exit;
}
else
{
unset($_SESSION['c_name'], $_SESSION['c_email'],
			$_SESSION['c_comment'], $_SESSION['error']);
header('Location: ../');
}
?>
