
<?php 

session_start();

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
$url = (isset($_GET['url'])) ? $_GET['url'] : NULL;

$e = retrieveEntries($db, $page, $url);

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
<link rel="stylesheet" href="/fb_blog/css/default.css" type="text/css" />
<link rel="alternate" type="application/rss+xml"
	title="My simple blog - RSS 2.0"
	href="/fb_blog/feeds/rss.php" />
<title> Simple Blog </title>
</head>
<body>
<h1> Simple Blog Application </h1>
<ul id="menu">
<li><a href="/fb_blog/blog/">Blog</a></li>
<li><a href="/fb_blog/about/">About the Author</a></li>
</ul>
<?php if(isset($_SESSION['loggedin'])&& $_SESSION['loggedin']==1):?>
<p id="control_panel">
	You are logged in!
	<a href="/fb_blog/inc/update.inc.php?action=logout">Log Out</a>
</p>
<?php endif;?>
<div id="entries">
<?php

if($fulldisp==1)
{
	$url= (isset($url)) ? $url : $e['url'];
	
	if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)
	{
		$admin= adminLinks($page,$url);
	}
	else
	{
		$admin= array('edit'=>NULL,'delete'=>NULL);
	}

	$img=formatImage($e['image'],$e['title']);
	if($page=='blog')
	{
		// Load the comment object
		include_once 'inc/comments.inc.php';
		$comments = new Comments();
		$comment_disp = $comments->showComments($e['id']);
		$comment_form = $comments->showCommentForm($e['id']);
		// Generate a Post to Twitter link
		$twitter = postToTwitter($e['title']);
	}
	else
	{
		$comment_form = NULL;
		$twitter = NULL;
	}
	?>
	<h2><?php echo $e['title']?></h2>
	<p> <?php  echo $img,'<br />',$e['entry']?></p>
	<p>
		<?php echo $admin['edit']?>
		<?php if($page=='blog') echo $admin['delete']?>
	</p>
	<?php if($page=='blog'): ?>
	<p class="backlink">
	<a href="<?php echo $twitter ?>">Post to Twitter</a><br />
	<a href="./">Back to Latest Entries</a>
	</p>
	<?php echo $comment_disp, $comment_form;endif;?>
	<?php 
}
else 
{
foreach($e as $entry){
?>
<p>
	<a href="/fb_blog/<?php echo $entry['page'] ?>/<?php echo $entry['url']?>">
		<?php echo $entry['title']?>
	</a>
</p>
<?php 

}
}
?>

<p class="backlink">
<?php if($page=='blog'&& isset($_SESSION['loggedin'])&& $_SESSION['loggedin']==1): ?>
<a href="/fb_blog/admin/<?php echo $page ?>">Post a New Entry</a>
<?php endif; ?>
</p>
<p>
<a href="/fb_blog/feeds/rss.php">
Subscribe via RSS!
</a>
</p>
</div>
</body>
</html>