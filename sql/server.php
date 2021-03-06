<?php 
	include("config/dbConfig.php");
	// include("php/connection.inc.php");
	$errors = array();

	// Load configuration as an array. Use the actual location of your configuration file
    $config = parse_ini_file('config/config.ini');

	function login($giveEmail, $givenPassword_login){
		global $conn;
		
		
		try{

			$email = $giveEmail;
			$password_login = $givenPassword_login;

				$sql = "SELECT * FROM person WHERE email=:email AND password= :password_login AND role= :givenRole";
				$stmt = $conn->prepare($sql);
				$stmt->execute(
					array(
						'givenRole' => "Employee",
						'email' => $email,
						'password_login' =>  $password_login	
					)
					
				);
				// $sql2 ="SELECT * FROM person WHERE email = '$email'";
    //             $result = $connec->query($sql2);
    //             while($row = $result->fetch_assoc()){
				// 	$id = $row['id'];
				// }
				
				$result = $stmt->fetch();
				$count = $stmt->rowCount();
				if($count > 0 ){
					session_start();

					// Save email
					$_SESSION["email"] = $email;

					// Save id
					$_SESSION["employeeId"] = $result[0];

					// Used in remember me
					$_SESSION['loggedin_time'] = time();

					//Check remember me
					if(isset($_POST['remember_me']))
					{
						$remember_me = $_POST['remember_me'];
						$_SESSION['remember_me'] = $remember_me;
					}
				

					// If user has a session
					if(isset($_SESSION['email']))
					{
						setcookie('uid', $result[0], time() + (86400 * 30));
						header('Location: index.php');
						// echo '<script>
						// alert("Youre logged in")
						// </script>';
					}
				}
				else
				{
					echo '<script>
					alert("Password/Username is wrong")
					</script>';
				}
				$conn = null;

		}catch(PDOEXCEPTION $e) {
			print_r("Something went wrong: " . $e->getMessage());
		}
	}

	function SendEmailForForgotPassword($givenEmail)
	{
		global $conn;
		try{
		require 'mail\PHPMailer-master\PHPMailerAutoload.php';
		$emailTo=$givenEmail;
		// $url = $_SERVER["PHP_SELF"];
		// $url = getcwd(); 

		// $pageName = $_SERVER["PHP_SELF"];
		// $filePath = realpath(dirname(__FILE__))."$pageName";
		// $rootPath = realpath($_SERVER['DOCUMENT_ROOT']);

		// $htmlPath = str_replace($rootPath, '', $filePath);

		//echo $filePath;  



			// another way adding the root folder to link, for example to add the relative path
		// $path = (@$filePath == "C:\xampp\htdocs") ? "https://xampp/htdocs" : "https://xampp/htdocs";
		// $path .="r2da-project-website/changePassword.php"; 

		// to add 
		// $path = (@$filePath == "C:\xampp\htdocs") ? "https://xampp/htdocs" : "https://xampp/htdocs";
		// $path .=$_SERVER["SERVER_NAME"]. dirname($_SERVER["PHP_SELF"]);  
		// $link = $path."/changePassword.php" ;  

		// to add by server
		$path = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		$path .=$_SERVER["SERVER_NAME"]. dirname($_SERVER["PHP_SELF"]);        
		
		$link = $path."/changePassword.php" ;     
		//echo $link;
		$body = "<!DOCTYPE html>
		<html lang='en'>
		<head>
			<meta charset='UTF-8'>
			<meta name='viewport'content='width=device-width, initial-scale=1.0'>
			<title>Document</title>
		</head>
		<body>
			<div class = 'Wrapper'>
			
			<p>	
				Hello there,
				
				Please click on the link to reset your password.
				<a href='http://i439426.hera.fhict.nl/changePassword.php'> </a>
				
			Reset your password
			
			</p>	
			</div>
		</body>
		</html>";
		// <a href='".$link."?email=".$emailTo."'</a>
		$sql = "SELECT * FROM person WHERE email=:emailTo AND role= 'Employee'";
		
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':emailTo', $emailTo);
		// $stmt->bindParam(':givenRole', "Employee");
		$mail = new PHPMailer();
		  
		  //Enable SMTP debugging.
		  $mail->SMTPDebug = 3;
		  //Set PHPMailer to use SMTP.
		  $mail->isSMTP();
		  //Set SMTP host name
		  $mail->Host = "smtp.gmail.com";
		  $mail->SMTPOptions = array(
							'ssl' => array(
								'verify_peer' => false,
								'verify_peer_name' => false,
								'allow_self_signed' => true
							)
						);


		  global $config;

		  $mediaBazaarEmail = $config['mediaBazaarEmail'];
		  $mediaBazaarPassword = $config['mediaBazaarPassword'];

		  //Set this to true if SMTP host requires authentication to send email
		  $mail->SMTPAuth = TRUE;
		  //Provide username and password
		  $mail->Username = $mediaBazaarEmail;
		  $mail->Password = $mediaBazaarPassword;
		  //If SMTP requires TLS encryption then set it
		  $mail->SMTPSecure = "false";
		  $mail->Port = 587;
		  //Set TCP port to connect to
		  
		  $mail->From = $mediaBazaarEmail;
		  $mail->FromName = "Media Bazaar";
		  
		  //$mail->addAddress("Anaswarraich72@gmail.com");
		  $mail->addAddress("rawan.ad7@gmail.com");

		  
		 $mail->isHTML(true);
		 
		  $mail->Subject = "Password Reset";
		  $mail->Body = $body;
		$mail->AltBody = "This is the plain text version of the email content";
		  if($mail->send())
		  {
			echo '<script>
			alert("email sent")
			</script>';
			
			//header("Location:login.php");
		  }
		 // header("Location:login.php");
		//   else
		//   {
		// 	echo "Mailer Error: " . $mail->ErrorInfo;
		//   }
	
		}
		catch(PDOEXCEPTION $e) {
         print_r("Something went wrong: " . $e->getMessage());
		}
	}

	function updateInfo($connec) {
		if(isset($_POST['infoSubmit'])){
			$fName = $_POST['firstName'];
			$lName = $_POST['lastName'];
			$email = $_POST['email'];
			$dob = $_POST['dob'];
			$streetName = $_POST['streetName'];
			$houseN = $_POST['houseN'];
			$city = $_POST['city'];
			$zipcode = $_POST['zipcode'];
			$uid = $_POST['uid'];
			$pass = $_POST['pass'];
			
			$sql = "UPDATE person
			SET firstName = '$fName', lastName = '$lName', email  = '$email',  dateOfBirth = '$dob' ,streetName = '$streetName', houseNr = '$houseN', city = '$city', zipcode = '$zipcode', password = '$pass'
			WHERE id = '$uid'";
			
			$result = $connec->query($sql);
		}
	}
	function PasswordToChange($givenEmail, $givenNewPassword, $givenNewRepeatPassword){

		global $conn;
		try{
		    $NewPassword = $givenNewPassword;
			$R_New_Password = $givenNewRepeatPassword;

			$password = md5($R_New_Password);//encrypt the password before saving in the database
			$sql =  "UPDATE person SET password = :R_New_Password WHERE email = :emailToChange AND role = 'Employee'" ;

			//UPDATE person SET password = "hoho" WHERE email = "MelvinRodgers@mediabazaar.com" AND role = 'Employee'
			$stmt = $conn->prepare($sql); 
			// $stmt->bindParam(':givenRole', "Employee");
			$stmt->bindParam(':emailToChange', $givenEmail);
			$stmt->bindParam(':R_New_Password', $R_New_Password);
			
			$result = $stmt->execute();

			if($result){
				echo '<script>
				alert("Password was successfully changed");
				</script>';

				header("refresh:5; url=login.php"); 
			}
			else {
				echo '<script>
				alert("Something went wrong");
				</script>';
			}

		}
		catch(PDOException $e)
		{
		echo $sql . "<br>" . $e->getMessage();
		}
		$conn = null;
	}
	function updatePass($connec){
		$id = $_COOKIE['uid'];
		$sql ="SELECT * FROM person WHERE id = '$id'";
        $result = $connec->query($sql);
        while($row = $result->fetch_assoc()){
			$pass = $row['password'];
		}

		if(isset($_POST['infoSubmit'])){
			
		$oldPass = $_POST['oldPass'];
		$newPass = $_POST['newPass'];
		$conNewPass = $_POST['conNewPass'];

		if($pass == $oldPass){

		
			if($conNewPass == $newPass){
				$sql = "UPDATE person
				SET password = '$newPass'
				WHERE id = '$id'";
			
				$result = $connec->query($sql);

			} else {
				echo '<script language="javascript">';
				echo 'alert("New password and confirm pasword are not the same")';
				echo '</script>';
			}
		} else {
			echo '<script language="javascript">';
			echo 'alert("Old pasword is not correct")';
			echo '</script>';
		}
		
		}
	}
?>