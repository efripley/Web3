<?php
require 'src/config.php';

class Login extends view\Page{
  public $user;
  public $pass;
  
  function post(){
    if(isset($_POST['user'])){
    }
  }
  
  function body(){
    echo "<body>\n";
    $this->loginForm();
    echo "</body>\n";
  }
  
  function loginForm(){
    if(issset($_POST['user']))
      $user = $_POST['user'];
    else
      $user = '';

    $html = <<<END
      <form method="POST" action="login.php">
        <input type="text" name="user" placeholder="username" value="$user">
        <input type="password" name="pass" placeholder="password">
        <button>Login!</button>
      </form>
    END;
    echo $html;
  }
}

(new Login)->generate();
?>
