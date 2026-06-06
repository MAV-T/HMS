<?php
session_start();
require_once 'csrf_helper.php';
$con=mysqli_connect("localhost","root","","myhmsdb");
if(isset($_POST['adsub'])){
	verify_csrf_token();
	$username=$_POST['username1'];
	$password=$_POST['password2'];
	$query="select * from admintb where username=? and password=?";
	$stmt=mysqli_prepare($con,$query);
	mysqli_stmt_bind_param($stmt,'ss',$username,$password);
	mysqli_stmt_execute($stmt);
	$result=mysqli_stmt_get_result($stmt);
	if(mysqli_num_rows($result)==1)
	{
		$_SESSION['username']=$username;
		header("Location:admin-panel1.php");
	}
	else
		// header("Location:error2.php");
		echo("<script>alert('Invalid Username or Password. Try Again!');
          window.location.href = 'index.php';</script>");
}
if(isset($_POST['update_data']))
{
	verify_csrf_token();
	$contact=$_POST['contact'];
	$status=$_POST['status'];
	$query="update appointmenttb set payment=? where contact=?";
	$stmt=mysqli_prepare($con,$query);
	mysqli_stmt_bind_param($stmt,'ss',$status,$contact);
	$result=mysqli_stmt_execute($stmt);
	if($result)
		header("Location:updated.php");
}




function display_docs()
{
	global $con;
	$query="select * from doctb";
	$result=mysqli_query($con,$query);
	while($row=mysqli_fetch_array($result))
	{
		$name=$row['name'];
		# echo'<option value="" disabled selected>Select Doctor</option>';
		echo '<option value="'.$name.'">'.$name.'</option>';
	}
}

if(isset($_POST['doc_sub']))
{
	verify_csrf_token();
	$name=$_POST['name'];
	$query="insert into doctb(name)values(?)";
	$stmt=mysqli_prepare($con,$query);
	mysqli_stmt_bind_param($stmt,'s',$name);
	$result=mysqli_stmt_execute($stmt);
	if($result)
		header("Location:adddoc.php");
}