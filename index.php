<?php
global $user;
global $database;
global $CONFIG;

session_start();

require_once('config.php');
require_once('functions.php');

connect();

if(isset($_GET['logout'])){
  $_SESSION['user-id'] = NULL;
  header("Location: {$CONFIG['url']}");
}
else if(isset($_SESSION['user-id'])){
  $user = $database->query("SELECT * FROM users WHERE id = {$_SESSION['user-id']}")->fetch_assoc();
  echo "Welcome {$user['username']}";
  echo "<div><a href=\"{$CONFIG['url']}?logout=true\">Logout</a></div>";

  $currentTask = NULL;
  if(isset($_GET['task'])){
    $currentTask = $database->query("SELECT * FROM tasks WHERE id = {$_GET['task']}")->fetch_assoc();
  }
  if($currentTask == NULL){
    echo '<h3>Root</h3>';
  }
  else{
    echo "<div><br><a href=\"{$CONFIG['url']}?task={$currentTask['parent']}\"><strong>BACK</strong></a></div>";
    echo "<h3>{$currentTask['task']}</h3>";
  }

  if(isset($_GET['delete'])){
    $database->query("DELETE FROM tasks WHERE id = {$_GET['delete']}");
  }

  if(isset($_POST['task'])){
    $time = 0;
    if($_POST['task-time'] > 0){
      $time = $_POST['task-time'];
    }
    if($currentTask == NULL){
      if(!$database->query("INSERT INTO tasks (parent, user, task_time, task) VALUES (0, {$user['id']}, {$time}, '{$_POST['task']}')")){
        echo 'error';
      }
    }
    else{
      if(!$database->query("INSERT INTO tasks (parent, user, task_time, task) VALUES ({$_GET['task']}, {$user['id']}, {$time}, '{$_POST['task']}')")){
        echo 'error';
      }      
    }
  }

  $tasks = NULL;
  if(isset($_GET['task'])){
    $tasks = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = {$_GET['task']}");
  }
  else{
    $tasks = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = 0");
  }

  while($task = $tasks->fetch_assoc()){
    if($task['task_time'] > 0){
      echo "<div><a href={$CONFIG['url']}?task={$task['id']}>{$task['task_time']} | {$task['task']}</a><a href={$CONFIG['url']}?task={$task['parent']}&delete={$task['id']}>(X)</a></div>";
    }
    else{
      echo "<div><a href={$CONFIG['url']}?task={$task['id']}>{$task['task']}</a><a href={$CONFIG['url']}?task={$task['parent']}&delete={$task['id']}>(X)</a></div>";      
    }
  }

echo <<<EOT2
<form action="{$_SERVER['REQUEST_URI']}" method="post">
  <input type="text" name="task" placeholder="task">
  <input type="number" name="task-time" placeholder="minutes">
  <input type="submit" value="Add Task">
</form>
EOT2;
}
else if(isset($_POST['username'])){
  $user = $database->query("SELECT * FROM users WHERE username = '{$_POST['username']}' AND password = SHA2('{$_POST['password']}', 256)")->fetch_assoc();
  if(!empty($user)){
    $_SESSION['user-id'] = $user['id'];
    header("Location: {$CONFIG['url']}");
  }
  else{
    header("Location: {$CONFIG['url']}?login=false");
  }
}
else{
  if($_GET['login'] == "false"){
    echo 'Incorrect username or password';
  }
echo <<<EOT1
<form action="{$CONFIG['url']}/index.php" method="post">
  <input type="text" name="username" placeholder="username">
  <input type="password" name="password" placeholder="password">
  <input type="submit" value="Login!">
</form>
EOT1;
}
?>
