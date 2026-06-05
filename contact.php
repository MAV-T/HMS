<?php 
$con=mysqli_connect("localhost","root","","myhmsdb");
if(isset($_POST['btnSubmit']))
{
	$name = $_POST['txtName'];
	$email = $_POST['txtEmail'];
	$contact = $_POST['txtPhone'];
	$message = $_POST['txtMsg'];

	$query="insert into contact(name,email,contact,message) values(?,?,?,?)";
	$stmt = mysqli_prepare($con,$query);
	mysqli_stmt_bind_param($stmt,'ssss',$name,$email,$contact,$message);
	$result = mysqli_stmt_execute($stmt);
	
	if($result)
    {
    	echo '<script type="text/javascript">'; 
		echo 'alert("Message sent successfully!");'; 
		echo 'window.location.href = "contact.html";';
		echo '</script>';
    }
}