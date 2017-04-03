<?php

#connect to the db
function connect() {
	$server = "localhost";
	$user = "root";
	$password = "KiyaoTHwzBzdaw26";
	$db = "forum";

	if (!mysqli_connect($server, $user, $password, $db)) {
		exit ('Error: Could not connect to the database!');
	}
	global $forum;
	$forum = mysqli_connect($server, $user, $password, $db) 
		or die(mysql_error());
}
 
# call the header and the db connecttion function
function initialize() {
	connect();
	display_header();
}

# create the header function and the link for the CSS file
function display_header() {
	echo '<!DOCTYPE html>
	<html>
	<head>
		<title>My forum</title>
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Quicksand:300,700" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="forumstyle.css">
	</head>
	<body>

	    <div id="forum">
	      <h1>My Forum</h1>
	      <h2>Welcome on the page!</h2>
	    </div>';
	session_start();
}

# title for every page
function display_title($title){
	echo '<div class="main"><h2>' . "$title" . '</h2></div>';
}

# user already logged in or not? + Logout button
function display_signedin() {
	if (!isset($_SESSION['signed_in'])) {
		header('Location: index.php');
	}
	echo '<form action="signout.php"><div class="userin">Welcome ' . $_SESSION['signed_in'] . "! Have fun!" . '<input type="submit" name="logout" value="Log out"/></div></form>';
}

# post method for the login process
function display_login_form() {
	echo '<div class="main">
		<div>
			<form action="" method="POST">
			 	<h3>If you have an account, please log in here!</h3>
			    <p>Username: <input type="text" name="userlog"> </p>
			    <p>Password: <input type="password" name="userpass"> </p>
			    <input type="submit" value="Login">
			</form>
		</div>

		<h3>If you do not have an account, please click <a href="index.php?page=signup"> here </a> for the registration!</h3>';
}

$errors = array();

# check the requirements for the log in
function login_check($forum) { 
	global $errors;
	if(isset($_POST['userlog']) && isset($_POST['userpass'])) {
		if (empty($_POST['userlog']) or empty($_POST['userpass'])) {
			array_push($errors, "Username and Password box must be filled in!");
		}
		else {
			$sql = "select * from users
					 where user_name = '" . mysqli_escape_string($forum, $_POST['userlog']) . "' and user_pass = '" . sha1($_POST['userpass']) . "'";
			$result = $forum->query($sql);
			if ($result->num_rows == 0) {
				array_push($errors, "Wrong Username and password combination!");
			}
		}
	}
}

# function to display the errors
function display_error(&$errors){
	if(count($errors) > 0) {
		foreach ($errors as $error) {
			echo '<li class="main">' . $error . '</li>';
		}
	}
}

# post method for the registration process
function display_signup(){
	display_title("Registration");
	echo '<div class="main">
		    <form action=""  method="post">
				<h3>If you would like to go back to the login page, please click <a href="index.php">here!</a></h3>
				<p>Enter your Username:  <input type="text" name="user1"> </p>
				<p>Enter your password:  <input type="password" name="pass"> </p>
				<p>Password again:  <input type="password" name="pass_check"> </p>
				<p>Enter your mail address:  <input type="email" name="mail"> </p>
				<input type="submit" value="Sign up">
			</form>
		</div>';
}

# check the requirements for the registration
function signup_check($forum){
	global $errors;
	if(isset($_POST['user1']) && isset($_POST['pass']) && isset($_POST['mail']) && isset($_POST['pass_check'])) {
		if (empty($_POST['user1']) or empty($_POST['pass']) or empty($_POST['pass_check'] or empty($_POST['mail']))) {
			array_push($errors, "All the fields must be filled!");
		}
		else {
			$sql = "select * from users where user_name = '" . mysqli_escape_string($forum, $_POST['user1']) . "'";
			$result = mysqli_query($forum, $sql);
			if ($result === false) {
				die(mysqli_error($forum));
			}
			if (mysqli_num_rows($result) > 0) {
				array_push($errors, "The Username is already taken!");
			}
			if (!ctype_alnum($_POST['user1'])) {
				array_push($errors, "Username can contain only letters and numbers!");
			}
			if (strlen($_POST['user1']) > 30) {
				array_push($errors, "Username can not be longer than 30 characters!");
			}
			if ($_POST['pass'] !== $_POST['pass_check']) {
				array_push($errors, "The 2 Passwords do not match!");
			}
			if (strlen($_POST['pass']) > 255) {
				array_push($errors, "Password can not be longer than 255 characters!");
			} 
			$sqlmail = "select * from users where user_email = '" . mysqli_escape_string($forum, $_POST['mail']) . "'";
			$result1 = mysqli_query($forum, $sqlmail);
			if (mysqli_num_rows($result1) > 0) {
				array_push($errors, "This email address is already taken!");
			}
		}	
	}
}

# new user into db if there is no error in the signup_check function
function user_into_db($forum, $errors){
	$regdate = date('Y-m-d H:i:s');
	if (count($errors) == 0 && isset($_POST['user1'])) {
		$input = "insert into users (user_name, user_pass, user_email, user_date) values ('" . mysqli_escape_string($forum, $_POST["user1"]) . "','" . sha1($_POST["pass"]) . "','" . mysqli_escape_string($forum, $_POST["mail"]) . "','$regdate')";
		if ($forum->query($input) === true) {
				echo "remek";
		}
		else{
			die(mysqli_error($forum));
		}
	}	
}

# create topic function
function create_topic($forum) {
	global $errors;
	$sql1 = "select user_id from users where user_name = '" . (mysqli_escape_string($forum, $_SESSION['signed_in']) . "'"); 
	$result = mysqli_query($forum, $sql1);
	if ($result == false) {
		die(mysqli_error($forum));
	}
	$rows = $result->fetch_assoc();
	$_SESSION['signed_id'] = $rows['user_id']; // save the user_id in a session

	$topic_date = date('Y-m-d H:i:s');
	if(isset($_POST['topic_sub'])){
		if (!empty($_POST['topic_sub'])) { // check if the box is empty or not, if empty give an error
			$sql2 = "select * from topics where topic_subject = '" . $_POST['topic_sub'] . "'";
			$result2 = mysqli_query($forum, $sql2);
			
			if ($result2 == false) {
				die(mysqli_error($forum));
			}

			if ($rows2 = mysqli_num_rows($result2) == 0) { // check is there already a topic named like this
				$sql = "insert into topics (topic_subject, topic_date, user_id) values ('" . mysqli_escape_string($forum, $_POST['topic_sub']) ."','" . $topic_date ."','" . $_SESSION['signed_id'] . "')";
				if ($forum->query($sql) === true){
					header('Location: index.php?page=topics'); //if somebody refreshes the page it is going to stop the function from giving the data to the db again
				}
				else {
					die(mysqli_error($forum));
				}
			}
			else {
				array_push($errors, "There is already a topic named like '" . $_POST['topic_sub'] . "'"); 
			}
		}
		else {
			array_push($errors, "Please give a name to the topic! :)");
		}
	}
}

# create new topic link
function display_topics_head() {
	echo '<div id="create"><form action="" method="POST"><h3>Here you can add a new topic: <input type="text" name="topic_sub" size="60">	<input type="submit" value="Create new topic"/><br></h3></form></div>';}

# the link to the previous page
function display_back() {
	if ($_GET['page'] == 'posts') {
		echo "<div><a href='index.php?page=topics'>Back to the topics!</a></div>";
	}
	elseif ($_GET['page'] == 'edit_post') {
		echo "<div><a href='index.php?page=posts&id=" . $_GET['id'] . "&sub=" . $_GET['sub'] . "&uid=" . $_GET['uid'] . "'>Back to the posts!</a></div>";
	}
	else{
		echo "<div><a href='index.php?page=posts&id=" . $_GET['id'] . "&sub=" . $_GET['sub'] . "&uid=" . $_GET['uid'] . "'>Back to the posts!</a></div>";
	}
}

# list the topics and create links to the posts
function display_topics_body($forum){
	$sql = 'select * from users, topics where users.user_id = topics.user_id order by topics.topic_date';
	$result = mysqli_query($forum, $sql);
	if ($result == false) {
		die(mysqli_error($forum));
	}
	while ($rows = $result->fetch_assoc()) {

		echo '<ul><li class="topics">' . 

		"<a href='index.php?page=posts&id=" . $rows["topic_id"] . "&sub=" . $rows["topic_subject"] . "&uid=" . $rows['user_id'] . "'>" . $rows["topic_subject"] . "</a>"	. str_repeat('&nbsp;', 25) . " topic by " . $rows["user_name"]  . "<a href='index.php?page=delete_topic&id=" . $rows['topic_id'] . "&uid=" . $rows['user_id'] . "'> Delete topic</a>" . '<br>' . '</li></ul>';
	}
}

# list the posts, give a delete and an edit link for each one
function display_posts($forum) {
	display_title($_GET['sub']);
	echo "<div class='main'><a href='index.php?page=create_post&id=". $_GET['id'] . "&sub=" . $_GET['sub'] . "&uid=" . $_GET['uid'] . "'><h3>Click here to add comment</h3></a></div>";
	$sql = "select * from posts, topics, users where posts.topic_id = topics.topic_id and posts.user_id = users.user_id and posts.topic_id = " . $_GET['id'] . " order by posts.post_date";
	$result = mysqli_query($forum, $sql);
	if ($result == false) {
		die(mysqli_error($forum));
	}
	else {
		while ($rows = $result->fetch_assoc()) {
			echo '<ul><li class="posts">' . $rows["post_content"] . str_repeat('&nbsp;', 25) . '<br>' . "<div id='posted'><a href='index.php?page=delete&id=" . $rows['topic_id'] . "&sub=" . $rows['topic_subject'] . "&uid=" . $rows['user_id'] . "&postid=" . $rows['post_id'] . "'>Delete post! </a><a href='index.php?page=edit_post&id=" . $rows['topic_id'] . "&sub=" . $rows['topic_subject'] . "&uid=" . $rows['user_id'] . "&postid=" . $rows['post_id'] . "'>Edit post</a> posted by " . $rows["user_name"] . " at " . $rows['post_date'] . '<br></div>' . '</li></ul>';
		}
	}
}

# set the page where you can create a post, give the post to the DB
function create_comment($forum) {
	echo '<form action="" method="POST">
		<div class="main"><h3>Write your comment: <br><textarea name="new_comment" rows="10" cols="120" ></textarea></h3><br><input type="submit" value="Post" /></div></form>';
	$postdate = date('Y-m-d H:i:s');
	if (isset($_POST['new_comment']) == true) {
		$sql1 = "insert into posts (post_content, post_date, topic_id, user_id) values ('" . mysqli_escape_string($forum, $_POST['new_comment']) . "','" . $postdate . "','" .  $_GET['id'] . "','" . $_SESSION['signed_id'] . "')";
		$result = mysqli_query($forum, $sql1);
		if ($result == false) {
			die(mysqli_error($forum));
		}
		else header("Location: index.php?page=posts&id=" . $_GET['id'] . "&sub=" . $_GET['sub'] . "&uid=" . $_GET['uid']);
	}
}

# edit post function, if the conditions are true
function edit_post($forum) {
	$sql = "select * from posts where post_id = " . $_GET['postid'];
	$result = mysqli_query($forum, $sql);
	if ($result == true) {
		while ($rows =  $result->fetch_assoc()){
			echo "<form action='' method='POST'>
			<div class='main'><h3>Edit your comment: <br><textarea name='edit_post' rows='10' cols='120' >" . $rows['post_content'] . "</textarea></h3><br><input type='submit' value='Edit' /></div>";

			if ($_GET['uid'] == $_SESSION['signed_id'] and isset($_POST['edit_post'])) {
				$update = "update posts set post_content = '" . mysqli_escape_string($forum, $_POST['edit_post']) . "' where post_id = " . $_GET['postid'];
				$result2 = mysqli_query($forum, $update);
				if ($result2 == false) {
					die(mysqli_error($forum));
				}
				header("Location: index.php?page=posts&id=" . $_GET['id'] . "&sub=" . $_GET['sub'] . "&uid=" . $_GET['uid']);
			}
		}
	}
}

# delete the topics if the conditions are true
function delete_topic($forum) {
	$sql1 = "select * from posts, topics where topics.topic_id = posts.topic_id and topics.topic_id = '" . $_GET['id'] . "'";
	$result = mysqli_query($forum, $sql1);
	if ($result->num_rows == 0) { // check is there any post in the topic
		$sql2 = "select * from topics where topic_id = " . $_GET['id'] . " and user_id = " . $_SESSION['signed_id']; // check 
		$result2 = mysqli_query($forum, $sql2);
		if ($result2->num_rows == 1) { // check, who can delete the topic
			$delete = "delete from topics where topic_id = " . $_GET['id'];
			if ($forum->query($delete) == false) {
				die(mysqli_error($forum));
			}
			else {
				header('Location: index.php?page=topics');
			}	
		}
		else {
			echo '<div class="main"><p>Only the creator of the topic is entitled to delete a topic!<br>To go back please click <a href="index.php?page=topics">here!</a></p></div>';
		}
	}
	else {
		echo '<div class="main"><p>The topic can ben deleted only, if there is no more post in it.<br>To go back please click <a href="index.php?page=topics">here!</a></p></div>';
		}
}

function delete_post($forum) {
	global $errors;
	$sql1 = "select * from posts, users where posts.user_id = users.user_id and posts.post_id = '" . $_GET['postid'] . "' and users.user_name = '" . $_SESSION['signed_in'] . "'";
	$result = $forum->query($sql1);
	if ($result->num_rows == 0) { // check, who can delete the post
		array_push($errors, "You can delete only your own posts!");
	}
	else{
		$sql2 = "delete from posts where post_id = '" . $_GET['postid'] . "'";
		if ($forum->query($sql2) == false) {
			die(mysqli_error($forum));
		}
	}
	header("Location: index.php?page=posts&id=" . $_GET['id'] . "&sub=" . $_GET['sub'] . "&uid=" . $_GET['uid']); // go back to the topic page
}


# last function on index.php
function footer(){
	echo '<div id="footer">Created by Atee</div>

		</body>
		</html>';
}
?>
