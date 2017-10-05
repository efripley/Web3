<?php
global $user;
global $database;
global $CONFIG;

session_start();

require_once('config.php');
require_once('functions.php');

connect();

include('head.php');

if(isset($_GET['logout'])){
  $_SESSION['user-id'] = NULL;
  header("Location: {$CONFIG['url']}");
}
else if(isset($_SESSION['user-id'])){
  $user = $database->query("SELECT * FROM users WHERE id = {$_SESSION['user-id']}")->fetch_assoc();

$menu = "";
if(isset($_GET['calendar'])){
  $menu = $menu . "<a class=\"item\" href=\"{$CONFIG['url']}\">Tasks</a>";
}
else{
  $menu = $menu . "<a class=\"item\" href=\"{$CONFIG['url']}?calendar=today\">Today</a>";
}
$menu = $menu . "<a class=\"item\" href=\"{$CONFIG['url']}?logout=true\">Logout</a>";
echo <<<HEAD
<header class="top-bar">
  <span class="name">{$user['name']}</span>
  <div class="menu" onclick="this.classList.toggle('open');">
    <span>Menu</span>
    <div class="items">
      {$menu}
    </div>
  </div>
</header>
HEAD;

  $currentTask = NULL;
  if(isset($_GET['task'])){
    $currentTask = $database->query("SELECT * FROM tasks WHERE id = {$_GET['task']}")->fetch_assoc();
  }

  if(isset($_GET['delete'])){
    $removingTask = $database->query("SELECT task_time FROM tasks WHERE id = {$_GET['delete']}")->fetch_assoc();
    if($currentTask != NULL){
      $updateTask = $currentTask;
      while(true){
        $updateTask['task_time'] -= $removingTask['task_time'];
        $database->query("UPDATE tasks SET task_time = {$updateTask['task_time']} WHERE id = {$updateTask['id']}");
        if($updateTask['parent'] == 0){
          break;
        }
        $updateTask = $database->query("SELECT * FROM tasks WHERE id = {$updateTask['parent']}")->fetch_assoc();
      }
    }
    $database->query("DELETE FROM tasks WHERE id = {$_GET['delete']}");
  }

  if(isset($_POST['task'])){
    $time = 0;
    $date = 'NULL';
    if($_POST['task-time'] > 0){
      $time = $_POST['task-time'];
    }
    if($_POST['task-date'] != ''){
      $date = "'" . $_POST['task-date'] . "'";
    }
    if($currentTask == NULL){
      if(!$database->query("INSERT INTO tasks (parent, user, task_date, task_time, task) VALUES (0, {$user['id']}, {$date}, {$time}, '{$_POST['task']}')")){
        echo 'error';
      }
    }
    else{
      $numSubTasks = $database->query("SELECT * FROM tasks WHERE parent = {$currentTask['id']}")->num_rows;
      if($numSubTasks == 0){
        $updateTask = $currentTask;        
        while(true){
          $updateTask['task_time'] -= $currentTask['task_time'];
          $database->query("UPDATE tasks SET task_time = {$updateTask['task_time']} WHERE id = {$updateTask['id']}");
          if($updateTask['parent'] == 0){
            break;
          }
          $updateTask = $database->query("SELECT * FROM tasks WHERE id = {$updateTask['parent']}")->fetch_assoc();
        }
        $currentTask['task_time'] = 0;
      }
      if(!$database->query("INSERT INTO tasks (parent, user, task_date, task_time, task) VALUES ({$_GET['task']}, {$user['id']}, {$date}, {$time}, '{$_POST['task']}')")){
        echo 'error';
      }
      else{
        $updateTask = $currentTask;
        while(true){
          $updateTask['task_time'] += $_POST['task-time'];
          $database->query("UPDATE tasks SET task_time = {$updateTask['task_time']} WHERE id = {$updateTask['id']}");
          if($updateTask['parent'] == 0){
            break;
          }
          $updateTask = $database->query("SELECT * FROM tasks WHERE id = {$updateTask['parent']}")->fetch_assoc();
        }
      }    
    }
  }

  $tasks = NULL;
  if(isset($_GET['task'])){
    $tasks = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = {$_GET['task']}");
  }
  else if($_GET['calendar'] == 'today'){
    $today = date("Y-m-d");
    $tasks = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND task_date = '{$today}'");
  }
  else{
    $tasks = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = 0");
  }

  echo "<div class=\"task-wdg\">";

  if($_GET['calendar'] == 'today'){
    $today = date("Y-m-d");
    $totalTime = $database->query("SELECT SUM(task_time) AS sum FROM tasks WHERE user = {$user['id']} AND task_date = '{$today}'")->fetch_assoc()['sum'];
    $totalTime = $totalTime / 60;
    echo "<div class=\"title-cmp\"><span class=\"time\">{$totalTime} hrs</span><span class=\"text\">Today</span></div>";
  }
  else if($currentTask == NULL){
    echo "<div class=\"title-cmp\"><span class=\"text\">The List</span></div>";
  }
  else{
    $taskHours = $currentTask['task_time'] / 60;
    echo "<div class=\"title-cmp\">";
    echo "<div class=\"top\">";
    echo "<span class=\"time\">{$taskHours} hrs</span>";
    echo "<span class=\"date\">Due: {$currentTask['task_date']}</span>";
    echo "</div>";
    echo "<div class=\"bottom\">";
    echo "<a class=\"back-arrow\" href=\"{$CONFIG['url']}?task={$currentTask['parent']}\">&lsaquo;</a><span class=\"text\">{$currentTask['task']}</span>";
    echo "</div>";
  }

  while($task = $tasks->fetch_assoc()){
    if($task['task_time'] > 60){
      $taskHours = $task['task_time'] / 60;
      echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?task={$task['parent']}&delete={$task['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?task={$task['id']}\" title=\"follow item\"><span class=\"time\">{$taskHours} hrs</span><span class=\"text\">{$task['task']}</span></a></div>";
    }
    else if($task['task_time'] > 0){
      echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?task={$task['parent']}&delete={$task['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?task={$task['id']}\" title=\"follow item\"><span class=\"time\">{$task['task_time']} min</span><span class=\"text\">{$task['task']}</span></a></div>";
    }
    else{
      echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?task={$task['parent']}&delete={$task['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?task={$task['id']}\" title=\"follow item\"><span class=\"time\">-</span><span class=\"text\">{$task['task']}</span></a></div>";      
    }
  }

  if(!isset($_GET['calendar'])){
echo <<<EOT2
<form action="{$_SERVER['REQUEST_URI']}" method="post">
  <input type="text" name="task" placeholder="task">
  <input type="number" name="task-time" placeholder="minutes">
  <input type="date" name="task-date" placeholder="date">
  <input type="submit" value="Add Task">
</form>
EOT2;
  }

  echo "</div>";
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
<header class="top-bar"><span class="name">Calendar by newvec</span></header>
<div class="login-wdg">
  <form action="{$CONFIG['url']}/index.php" method="post">
    <input type="text" name="username" placeholder="username">
    <input type="password" name="password" placeholder="password">
    <input type="submit" value="Login!">
  </form>
</div>
EOT1;
}

include('foot.php');
?>
