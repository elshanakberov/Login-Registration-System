<!-- HELPER FUNCTIONS -->
<?php

  function clean($string){

    return htmlentities($string);
  }

  function redirect ($location){

    return header("location: {$location}")  ;
  }

   function set_message($message){

     if(!empty($message)){
       $_SESSION['message'] = $message;
     }else {
       $message = "";
     }
   }


   function display_message (){
     if(isset($_SESSION['message'] )){
         echo $_SESSION['message'];
         unset( $_SESSION['message']);
     }
   }
   function token_generator(){

    $token = $_SESSION['token'] =  md5(uniqid(mt_rand(),true));
    return $token;
   }

   function validation_errors($error_message){
     $error_message = <<<DELIMITER
     <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <strong>Warning!</strong> $error_message
      </div>
DELIMITER;
return $error_message;

   }

   function activation_email($email,$subject,$msg,$headers){
     return mail($email,$subject,$msg,$headers);
   }


   function email_exists($email) {
     $query = "SELECT user_id FROM users WHERE email = '$email' ";
     $result = query($query);
     row_count($result);
     if(row_count($result) == 1){
       return true;
     }else{
       return false;
     }
   }


   function username_exists($user_name){
     $query = "SELECT user_id FROM users WHERE user_name = '$user_name' ";
     $result = query($query);
     if(row_count($result) == 1){
       return true;
     }else {
       return false;
     }
   }

/****************************** Validation Functions **************************/

  function validate_user_registration(){
      $errors = [];
      $min = 3;
      $max = 20;

      if($_SERVER['REQUEST_METHOD'] == "POST" ){
        $first_name       = clean($_POST['first_name']);
        $last_name        = clean($_POST['last_name']);
        $user_name        = clean($_POST['user_name']);
        $email            = clean($_POST['email']);
        $password         = clean($_POST['password']);
        $confirm_password = clean($_POST['confirm_password']);
// User Firstname
        if(strlen($first_name) < $min) {
          $errors[] = "Your Firstname should be more than {$min} characters ";
        }
        if(strlen($first_name) > $max){
          $errors[] = "Your Firstname Should not be more than {$max} characters ";
        }
// User Lastname
        if(strlen($last_name) < $min) {
          $errors[] = "Your Lastname Should Be more than {$min} characters ";
        }
        if(strlen($last_name) > $max) {
          $errors[] = "Your Lastname should not be more than {$max} characters ";
        }
 // Username
        if(strlen($user_name) < $min) {
          $errors[] = "Your Username should be more than {$min} characters ";
        }
        if(strlen($user_name) > $max) {
          $errors[] = "Your username should not be more than {$max} characters ";
        }

// Checking if username exist
        if(username_exists($user_name)){
          $errors[] = "This username is already token ";
        }
// Checking if email exist

        if(email_exists($email)){
          $errors[] = "This email is already token ";
        }
// Checking passwords are same
        if($password != $confirm_password){
          $errors[] = "Your Pasword Didn't match";
        }

 // Looping through array

          if(!empty($errors)){
            foreach ($errors as $error) {
                echo validation_errors($error);
             }
          }
          else{
              if(register_user($user_name,$first_name,$last_name,$email,$password)){
                  set_message('<div align = "center" class="alert alert-success" role="alert">
                                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                  <strong>  Success! </strong> Please Check Your Email in order to activate your profile</div>' );
                  redirect("index.php");
             }
          }
      }
  }
/****************************** Register user Functions **************************/

  function register_user($user_name,$first_name,$last_name,$email,$password) {
    $user_name       = escape($user_name);
    $first_name      = escape($first_name);
    $last_name       = escape($last_name);
    $email           = escape($email);
    $password        = escape($password);


    if(username_exists($user_name)){
      return false;
    } elseif (email_exists($email)) {
      return false;
    } else{
      $password        = md5($password);
      $validation_code = md5($user_name + microtime());
      $query = "INSERT INTO users(user_firstname,user_lastname,user_name,email,password,validation_code,active) ";
      $query .= " VALUES('{$first_name}','{$last_name}','{$user_name}','{$email}','{$password}','{$validation_code}' ,  0) ";
      $insert_query = query($query);


      $subject = "Activate Account";
      $msg = "       Please click link down below in order to activate your account </br>

              http://localhost/login/activate.php?email=$email&code=$validation_code


      ";
      $header = "From : support@tramass.com";

//activate account
      activation_email($email,$subject,$msg,$header);

      return true;

    }

}
/****************************** Activate user Functions **************************/

function activate_user (){

  if($_SERVER['REQUEST_METHOD'] == "GET"){
    if(isset($_GET['email'])){
     $email = clean($_GET['email']);
     $validation_code = clean($_GET['code']);

     $query = "SELECT user_id FROM users WHERE email = '".escape($email)."' AND validation_code = '".escape($validation_code)."' ";
     $send_query = query($query);
     confirm($send_query);
     if(row_count($send_query) == 1){
       $query = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '".escape($email)."' AND validation_code = '".escape($validation_code)."' ";
       $update_query = query($query);
       confirm($update_query);

       set_message('<div align = "center" class="alert alert-success" role="alert">
                       <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                       <strong>  Congratulation </strong> Your Account Has Been Activated please Login</div>' );
       redirect("login.php");
     } else{
       set_message('<div align = "center" class="alert alert-danger" role="alert">
                       <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                       <strong>  Sorry </strong> Your Account Could Not Activated</div>' );
      redirect("index.php");
      }
    }
  }
}


/****************************** validate user login Functions **************************/
  function validate_user_login(){

    $errors = [];
    if($_SERVER['REQUEST_METHOD'] == "POST"){
      $email    = clean($_POST['email']);
      $password = clean($_POST['password']);
      $remember = isset($_POST['remember']);

      if(!empty($errors)){

        foreach($errors as $erros){

          echo validation_errors($error);

        }
      }else{

        if(login_user($email,$password,$remember)){

          redirect("admin.php");

        }else{

          echo validation_errors("Your credentials are not correct");

        }
      }
  }
}

/****************************** User Login Functions **************************/

function login_user($email,$password,$remember){

  $query = "SELECT password,user_id FROM users WHERE email = '".escape($email)."' AND active=1 ";

  $select_query = query($query);



  if(row_count($select_query) == 1){

      $row = mysqli_fetch_assoc($select_query);

      $db_password = $row['password'];



      if(md5($password) === $db_password){

        if($remember == "on"){
          setcookie('_tram' , $email,time() + 7884000);
        }

        $_SESSION['email'] = $email;

        return true;
      }else{

        return false;
      }
  }
}
/****************************** Logged in Functions **************************/

 function logged_in(){
   if(isset($_SESSION['email']) || isset($_COOKIE['_tram'])){

     return true;

   }else {

     return false;

   }
 }


/****************************** Recover Password Functions **************************/

  function recover_password(){


    if($_SERVER['REQUEST_METHOD'] == "POST"){ // Ckeckig for POST request

      if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']){

        $email = clean($_POST['email']);

        if(email_exists($email)){ //Checking for email whether it is exists or not on DB

          $validation_code = md5($email + microtime()); //crypting validation_code with md5 function

          setcookie('temp_access_code' , $validation_code , time() + 900); // setting cookie

          $query = "UPDATE users SET validation_code = '".escape($validation_code)."' WHERE email = '".escape($email)."'"; // Updateding validation_code if mail exists on DB
          $update_query = query ($query);

          $subject = "Recover Your Password ";
          $msg = " Here is your validation code : {$validation_code} <br>
          Please Click Link : http://localhost/login/code.php?email=$email&code=$validation_code
          ";
          $headers = "From : support@tramass.com";



        if(!mail($email,$subject,$msg,$headers)){

          echo validation_errors("Email Couldn't be sent");

        }
        set_message("<p class='bg-success text-center' >Please Check Your Email</p>");
        redirect("index.php");
      } else {

          echo validation_errors("Email doesn't exists");

        }
      }else {
        echo validation_errors("Problem");
      }

      if(isset($_POST['cancel_submit'])){

        redirect("login.php");
      }
    }
  }


  /****************************** Validation Code Functions **************************/


  function validate_code() {

    if(isset($_COOKIE['temp_access_code'])) { // Check for cookie whether it aviable or not

        if(isset($_GET['email']) && isset($_GET['code'])){ //Check for email and validate_code get request on the URL

          if(isset($_POST['code'])){ //Check for code from the html form

            $validation_code = clean($_POST['code']);
            $email           = clean($_GET['email']);

            $query = "SELECT user_id FROM users WHERE validation_code = '".escape($validation_code)."' AND email = '".escape($email)."' "; // Select users in order to compare from DB and HTML form
            $select_query = query($query);

              if(row_count($select_query) == 1){ // counting DB rows in order to find any validation_code that exists

                setcookie('temp_access_code' , $validation_code, time() + 600);

                set_message("<p class='bg-success text-center'>Please reset your code</p>");

                redirect("reset.php?email=$email&code=$validation_code");
              }else{

                  set_message("<p class='bg-danger text-center'>Wrong Validation code</p>");
                  redirect("recover.php");
              }

          }


        } else {

          set_message("<p class='bg-danger text-center'>Your get request is not set</p>");
        }

    } else {

      set_message("<p class='bg-danger text-center'> Invalid Link</p>"); // message must shown after cookie expired

    }

  }


  /****************************** Reset Password Functions **************************/

  function reset_password () {

    if(isset($_COOKIE['temp_access_code'])){ // Check for cookie whether it aviable or not

        if(isset($_GET['email']) && isset($_GET['code'])) { // Check for spesific GET request on the recover password link

            if(isset($_SESSION['token']) && isset($_POST['token'])) { // Check for token session and post request is set or not

                if($_SESSION['token'] === $_POST['token']) { // Check for token session and post request are same

                    if(isset($_POST['password']) === isset($_POST['confirm_password'])) { // Check for passwords are same

                          $new_password = md5(clean($_POST['password']));
                          $email = clean($_GET['email']);

                          $query = "UPDATE users SET password = '".escape($new_password)."' , validation_code = 0 WHERE email = '".escape($email)."' ";
                          $update_query = query($query);

                          set_message("<p class='bg-success text-center'>Please Login</p>");
                          redirect("login.php");


                    }else {

                          set_message("<p class='bg-danger text-center'>Please Enter Same Password</p>");

                    }

                }

            }

        }


    }else {

        set_message("<p class='bg-danger text-center'>Please try again</p>");

        redirect("recover.php");
    }

  }
 ?>
