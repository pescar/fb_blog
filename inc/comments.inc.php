<?php
include_once 'db.inc.php';

class Comments
{
	public $db;
	public $comments;
	
	public function __construct()
	{
		$this->db= new PDO(DB_INFO, DB_USER, DB_PASS);
	}
	
	//display form for new comm
	public function showCommentForm($blog_id)
	{
		$errors = array(
				1 => '<p class="error">Something went wrong while '
				. 'saving your comment. Please try again!</p>',
				2 => '<p class="error">Please provide a valid '
				. 'email address!</p>',
				3 => '<p class="error">Please answer the anti-spam '
				.'question correctly!</p>'
		);
		if(isset($_SESSION['error']))
		{
			$error = $errors[$_SESSION['error']];
		}
		else
		{
			$error = NULL;
		}
		if(isset($_SESSION['c_name']))
		{
			$n=$_SESSION['c_name'];
		}
		else
		{
			$n=NULL;
		}
		if(isset($_SESSION['c_email']))
		{
			$e = $_SESSION['c_email'];
		}
		else
		{
			$e = NULL;
		}
		if(isset($_SESSION['c_comment']))
		{
			$c = $_SESSION['c_comment'];
		}
		else
		{
			$c = NULL;
		}
		$n = isset($_SESSION['n']) ? $_SESSION['n'] : NULL;
		$e = isset($_SESSION['e']) ? $_SESSION['e'] : NULL;
		$c = isset($_SESSION['c']) ? $_SESSION['c'] : NULL;
		$challenge=$this->generateChallenge();
		return <<<FORM
<form action="/fb_blog/inc/update.inc.php"
	method="post" id="comment-form">
	<fieldset>
		<legend>Post a Comment</legend>$error
		<label>Name
			<input type="text" name="name" maxlength="75"
				value="$n" />
		</label>
		<label>Email
			<input type="text" name="email" maxlength="150" 
				value="$e"/>
		</label>
		<label>Comment
			<textarea rows="10" cols="45" name="comment">$c</textarea>
		</label>$challenge
		<input type="hidden" name="blog_id" value="$blog_id" />
		<input type="submit" name="submit" value="Post Comment" />
		<input type="submit" name="submit" value="Cancel" />
	</fieldset>
</form>
FORM;
			}
	
	//save comments in db
	public function saveComment($p)
	{
		// Save the comment information in a session
		$_SESSION['c_name'] = htmlentities($p['name'], ENT_QUOTES);
		$_SESSION['c_email'] = htmlentities($p['email'], ENT_QUOTES);
		$_SESSION['c_comment'] = htmlentities($p['comment'],ENT_QUOTES);
		
		if($this->validateEmail($p['email'])==FALSE)
		{
			$_SESSION['error']=2;
			return;
		}
		if(!$this->verifyResponse($p['s_q'],$p['s_1'],$p['s_2']))
		{
			$_SESSION['error']=3;
			return;
		}
				
		//sanitize data and store in variables
		$blog_id=htmlentities(strip_tags($p['blog_id']),ENT_QUOTES);
		$name = htmlentities(strip_tags($p['name']),ENT_QUOTES);
		$email = htmlentities(strip_tags($p['email']),ENT_QUOTES);
		$comment = htmlentities(strip_tags($p['comment']),ENT_QUOTES);
		//keep formatting of comments and remove extra whitespace
		$comment = nl2br(trim($comment));
		
		$sql = "INSERT INTO comments (blog_id, name, email, comment)
				VALUES (?, ?, ?, ?)";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->execute(array($blog_id,$name,$email,$comment));
			$stmt->closeCursor();
			// Destroy the comment information to empty the form
			unset($_SESSION['c_name'], $_SESSION['c_email'],
					$_SESSION['c_comment'], $_SESSION['error']);
			return;
		}
		else 
		{
			$_SESSION['error'] = 1;
			return;	
		}
	}
	private function validateEmail($email)
	{
		$p = '/^[\w-]+(\.[\w-]+)*@[a-z0-9-]+'
			 .'(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i';
		
		return (preg_match($p, $email)) ? TRUE : FALSE;
	}
	public function retrieveComments($blog_id)
	{
		$sql = "SELECT id,name,email,comment,date
				FROM comments
				where blog_id=?
				ORDER BY date DESC";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($blog_id));
		while($comment = $stmt->fetch())
		{
			$this->comments[] = $comment;
		}
		if(empty($this->comments))
		{
			$this->comments[]= array(
				'id' => NULL,
				'name'=>NULL,
				'email'=>NULL,
				'comment'=>"There are no comments on this entry",
				'date'=> NULL	
			
			);
		}
	}
	public function showComments($blog_id)
	{	
		$display=NULL;
		$this->retrieveComments($blog_id);
		
		foreach($this->comments as $c)
		{
			if(!empty($c['date'])&& !empty($c['name']))
			{
				$format = "F j, Y \a\\t g:iA";
				$date = date($format, strtotime($c['date']));
				$byline = "<span><strong>$c[name]</strong>
							[Posted on $date]</span>";
				//delete link
				if(isset($_SESSION['loggedin'])&& $_SESSION['loggedin']==1)
				{
				$admin = "<a href =\"/fb_blog/inc/update.inc.php"
						."?action=comment_delete&id=$c[id]\""
						."class=\"admin\">delete</a>";
				}
				else 
				{
					$admin=NULL;
				}
			}
			else {
				//if we get here,no comments exists
				$byline = NULL;
				$admin=NULL;
			}
			//assemble the pieces into formatted comment
			$display .="<p class = \"comment\">$byline$c[comment]$admin</p>";
		}
		//return all formated comments as a string
		return $display;
	}
	public function confirmDelete($id)
	{
		//store the entry url if available
		if(isset($_SERVER['HTTP_REFERER']))
		{
			$url = $_SERVER['HTTP_REFERER'];
		}
		//otherwise use the default view
		else {
			$url="../";
		}
		return <<<FORM
<html>
<head>
<title>Please Confirm Your Decision</title>
<link rel="stylesheet" type="text/css"
	href="/fb_blog/css/default.css" />
</head>
<body>
<form action="/fb_blog/inc/update.inc.php" method="post">
	<fieldset>
		<legend>Are You Sure?</legend>
		<p>
			Are you sure you want to delete this comment?
		</p>
		<input type="hidden" name="id" value="$id" />
		<input type="hidden" name="action" value="comment_delete" />
		<input type="hidden" name="url" value="$url" />
		<input type="submit" name="confirm" value="Yes" />
		<input type="submit" name="confirm" value="No" />
	</fieldset>
</form>
</body>
</html>
FORM;
	}
	public function deleteComment($id)
	{
		$sql="DELETE FROM comments
			  WHERE id=?
				LIMIT 1";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->execute(array($id));
			$stmt->closeCursor();
			return TRUE;
		}
		else 
		{
			return FALSE;
		}
	}
	private function generateChallenge()
	{
		// Store two random numbers in an array
		$numbers = array(mt_rand(1,4), mt_rand(1,4));
		// Store the correct answer in a session
		$_SESSION['challenge'] = $numbers[0] + $numbers[1];
		// Convert the numbers to their ASCII codes
		$converted = array_map('ord', $numbers);
		// Generate a math question as HTML markup
		return "
		<label>&#87;&#104;&#97;&#116;&#32;&#105;&#115;&#32;
		&#$converted[0];&#32;&#43;&#32;&#$converted[1];&#63;
		<input type=\"text\" name=\"s_q\" />
		</label>";
	}
	private function verifyResponse($resp)
	{
		// Grab the session value and destroy it
		$val = $_SESSION['challenge'];
		unset($_SESSION['challenge']);
		// Returns TRUE if equal, FALSE otherwise
		return $resp==$val;
	}
}
