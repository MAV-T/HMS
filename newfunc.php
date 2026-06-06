<?php
$con=mysqli_connect("localhost","root","","myhmsdb");
if(isset($_POST['update_data']))
{
 $contact=$_POST['contact'];
 $status=$_POST['status'];
 $query="update appointmenttb set payment=? where contact=?";
 $stmt=mysqli_prepare($con,$query);
 mysqli_stmt_bind_param($stmt,'ss',$status,$contact);
 $result=mysqli_stmt_execute($stmt);
 if($result)
  header("Location:updated.php");
}

function display_specs() {
  global $con;
  $query="select distinct(spec) from doctb";
  $result=mysqli_query($con,$query);
  while($row=mysqli_fetch_array($result))
  {
    $spec=$row['spec'];
    echo '<option data-value="'.htmlspecialchars($spec, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($spec, ENT_QUOTES, 'UTF-8').'</option>';
  }
}

function display_docs()
{
 global $con;
 $query = "select * from doctb";
 $result = mysqli_query($con,$query);
 while( $row = mysqli_fetch_array($result) )
 {
  $username = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
  $price = htmlspecialchars($row['docFees'], ENT_QUOTES, 'UTF-8');
  $spec = htmlspecialchars($row['spec'], ENT_QUOTES, 'UTF-8');
  echo '<option value="' .$username. '" data-value="'.$price.'" data-spec="'.$spec.'">'.$username.'</option>';
 }
}

if(isset($_POST['doc_sub']))
{
 $username=$_POST['username'];
 $query="insert into doctb(username)values(?)";
 $stmt=mysqli_prepare($con,$query);
 mysqli_stmt_bind_param($stmt,'s',$username);
 $result=mysqli_stmt_execute($stmt);
 if($result)
  header("Location:adddoc.php");
}

?>