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
  $today = date('Y-m-d');
  $year = date('Y');
  $month = date('m');
  $day = date('d');

  if($_GET['view'] == 'month' || $_GET['view'] == 'day'){
    $menu = $menu . "<a class=\"item\" href=\"{$CONFIG['url']}?view=items\">Items</a>";
  }
  if($_GET['view'] == 'day' || $_GET['view'] == 'items'){
    $menu = $menu . "<a class=\"item\" href=\"{$CONFIG['url']}?view=month&year={$year}&month={$month}&day={$day}\">Calendar</a>";
  }
  if(isset($_GET['submit'])){
    if($_GET['submit'] == 'Update Date'){
      $editDate = 'NULL';
      if($_GET['data'] != ''){
        $editDate = "'" . $_GET['data'] . "'";
      }
      $database->query("UPDATE tasks SET task_date = {$editDate} WHERE user = {$user['id']} AND id = {$_GET['item']}");
    }
    else if($_GET['submit'] == 'Update Time'){
      if($database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = {$_GET['item']}")->num_rows <= 0){
        $database->query("UPDATE tasks SET task_time = {$_GET['data']} WHERE user = {$user['id']} AND id = {$_GET['item']}");
      }
    }
    else if($_GET['submit'] == 'Update Item'){
      if($_GET['data'] != ''){
        $database->query("UPDATE tasks SET task = '{$_GET['data']}' WHERE user = {$user['id']} AND id = {$_GET['item']}");
      }
    }
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

  $currentItem = NULL;
  if(isset($_GET['item'])){
    $currentItem = $database->query("SELECT * FROM tasks WHERE id = {$_GET['item']}")->fetch_assoc();
  }

  if(isset($_GET['delete'])){
    $removingTask = $database->query("SELECT task_time FROM tasks WHERE id = {$_GET['delete']}")->fetch_assoc();
    if($currentItem != NULL){
      $updateTask = $currentItem;
      $currentItem['task_time'] -= $removingTask['task_time'];
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
    header('location: ' . $_SERVER['HTTP_REFERER']);
    exit();
  }

  if(isset($_POST['item']) && !empty($_POST['item'])){
    $time = 0;
    $date = NULL;
    if($_POST['item-time'] > 0){
      $time = $_POST['item-time'];
    }
    if($_POST['item-date'] != ''){
      $date = $_POST['item-date'];
    }
    if($currentItem == NULL){
      $parentId = 0;
      if (!($prep = $database->prepare("INSERT INTO tasks (parent, user, task_date, task_time, task) VALUES (?, ?, ?, ?, ?)"))) {
        echo "Prepare failed: (" . $prep->errno . ") " . $prep->error;
      }
      if(!$prep->bind_param("iisss", $parentId, $user['id'], $date, $time, $_POST['item'])){
        echo "Binding parameters failed: (" . $prep->errno . ") " . $prep->error;
      }
      if(!$prep->execute()){
        echo "Execute failed: (" . $prep->errno . ") " . $prep->error;
      }
    }
    else{
      $numSubTasks = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = {$currentItem['id']}")->num_rows;
      if($numSubTasks == 0){
        $updateTask = $currentItem;
        while(true){
          $updateTask['task_time'] -= $currentItem['task_time'];
          $database->query("UPDATE tasks SET task_time = {$updateTask['task_time']} WHERE user = {$user['id']} AND id = {$updateTask['id']}");
          if($updateTask['parent'] == 0){
            break;
          }
          $updateTask = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND id = {$updateTask['parent']}")->fetch_assoc();
        }
        $currentItem['task_time'] = 0;
      }
      if (!($prep = $database->prepare("INSERT INTO tasks (parent, user, task_date, task_time, task) VALUES (?, ?, ?, ?, ?)"))){
        echo "Prepare failed: (" . $prep->errno . ") " . $prep->error;
      }
      if(!$prep->bind_param("iisss", $_GET['item'], $user['id'], $date, $time, $_POST['item'])){
         echo "Binding parameters failed: (" . $prep->errno . ") " . $prep->error;
      }
      if(!$prep->execute()){
        echo "Execute failed: (" . $prep->errno . ") " . $prep->error;
      }
      $updateTask = $currentItem;
      while(true){
        $updateTask['task_time'] += $_POST['item-time'];
        $database->query("UPDATE tasks SET task_time = {$updateTask['task_time']} WHERE user = {$user['id']} AND id = {$updateTask['id']}");
        if($updateTask['parent'] == 0){
          break;
        }
        $updateTask = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND id = {$updateTask['parent']}")->fetch_assoc();
      }
    }
  }

  $items = NULL;
  if($_GET['view'] == 'items'){
    if(isset($_GET['item'])){
      $items = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = {$_GET['item']} ORDER BY task ASC");
    }
    else{
      $items = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = 0 ORDER BY task ASC");
    }
  }
  else if($_GET['view'] == 'month'){
    $day = $_GET['day'];
    $month = $_GET['month'];
    $year = $_GET['year'];
    $items = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND task_date >= '{$year}-{$month}-01' AND task_date <= '{$year}-{$month}-31' ORDER BY task_date, task ASC");
  }
  else if($_GET['view'] == 'day'){
    $date = "{$_GET['year']}-{$_GET['month']}-{$_GET['day']}";
    $items = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND task_date = '{$date}' ORDER BY task ASC");
  }
  else{
    header("Location: {$CONFIG['url']}?view=items");
    exit();
  }

  echo "<div class=\"task-wdg\">";

  if($_GET['view'] == 'day'){
    $today = "{$_GET['year']}-{$_GET['month']}-{$_GET['day']}";
    $totalTime = $database->query("SELECT SUM(task_time) AS sum FROM tasks WHERE user = {$user['id']} AND task_date = '{$today}'")->fetch_assoc()['sum'];
    $totalTime = $totalTime / 60;
    $text = $today;
    if($today == date('Y-m-d'))
      $text = 'Today';
    $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
    $tomorrow = date('Y-m-d', strtotime($today . ' +1 day')); 
    echo "<div class=\"title-cmp\">
            <span class=\"time\">{$totalTime} hrs</span>
            <span class=\"text\">";
    $year = date('Y', strtotime($yesterday));
    $month = date('m', strtotime($yesterday));
    $day = date('d', strtotime($yesterday));
    echo "<a class=\"back-arrow\" href=\"{$CONFIG['url']}?view=day&year={$year}&month={$month}&day={$day}\">&lsaquo;</a>
          <span class=\"date-text\">{$text}</span>";
    $year = date('Y', strtotime($tomorrow));
    $month = date('m', strtotime($tomorrow));
    $day = date('d', strtotime($tomorrow));
    echo "<a class=\"back-arrow\" href=\"{$CONFIG['url']}?view=day&year={$year}&month={$month}&day={$day}\">&rsaquo;</a>
        </span>
      </div>";
  }
  else if($_GET['view'] == 'month'){
    
  }
  else if($currentItem == NULL){
    echo "<div class=\"title-cmp\"><span class=\"text\">Items</span></div>";
  }
  else{
    $itemHours = $currentItem['task_time'] / 60;
    $numSubTasks = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = {$currentItem['id']}")->num_rows;
    echo "<div class=\"title-cmp\">";
    echo "<div class=\"top\">";
    echo "<a class=\"back-arrow\" href=\"{$CONFIG['url']}?view=items&item={$currentItem['parent']}\">&lsaquo;</a>";
    echo "<div class=\"task-info\">";
    if($numSubTasks > 0){
      echo "<span class=\"time\">{$itemHours} hrs</span>";
    }
    else{
      echo "<span id=\"task-time\" class=\"time\">{$itemHours} hrs</span>";
    }
    if($currentItem['task_date'] != ''){
      echo "<span id=\"task-date\" class=\"date\">{$currentItem['task_date']}</span>";
    }
    else{
      echo "<span id=\"task-date\" class=\"date\">Schedule Item</span>";
    }
    echo "</div>";
    echo "</div>";
    echo "<div class=\"bottom\">";
    echo "<span id=\"item-text\" class=\"text\">{$currentItem['task']}</span>";
    echo "</div>";
    echo "</div>";
  }

  if($_GET['view'] == 'month'){
    buildMonth($items, $year, $month);
  }
  else{
    while($item = $items->fetch_assoc()){
      if($item['task_time'] > 60){
        $itemHours = $item['task_time'] / 60;
        echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?item={$item['parent']}&delete={$item['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?view=items&item={$item['id']}\" title=\"follow item\"><span class=\"time\">{$itemHours} hrs</span><span class=\"text\">{$item['task']}</span></a></div>";
      }
      else if($item['task_time'] > 0){
        echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?item={$item['parent']}&delete={$item['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?view=items&item={$item['id']}\" title=\"follow item\"><span class=\"time\">{$item['task_time']} min</span><span class=\"text\">{$item['task']}</span></a></div>";
      }
      else{
        echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?item={$item['parent']}&delete={$item['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?view=items&item={$item['id']}\" title=\"follow item\"><span class=\"time\">-</span><span class=\"text\">{$item['task']}</span></a></div>";      
      }
    }
  }

  if($_GET['view'] == 'items'){
echo <<<EOT2
<form action="{$_SERVER['REQUEST_URI']}" method="post">
  <input type="text" name="item" placeholder="item">
  <input type="number" name="item-time" placeholder="minutes">
  <input type="date" name="item-date" placeholder="date">
  <input type="submit" name="submit" value="Add Item">
</form>
EOT2;
  }

  echo "</div>";
}
else if(isset($_POST['username'])){
  $user = $database->query("SELECT * FROM users WHERE username = '{$_POST['username']}' AND password = SHA2('{$_POST['password']}', 256)")->fetch_assoc();
  if(!empty($user)){
    $_SESSION['user-id'] = $user['id'];
    if(isset($_GET['login'])){
      header("Location: {$CONFIG['url']}?view=items");
    }
    else{
      header("location: {$_SERVER['HTTP_REFERER']}");
    }
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
