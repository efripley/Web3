<?php
global $user;
global $database;
global $CONFIG;
global $SETTINGS;

session_start();

require_once('config.php');
require_once('functions.php');

connect();

include('head.php');

if(isset($_GET['logout'])){
  logOut();
}
else if(isset($_SESSION['user-id'])){
  $user = getUser();

  buildMenu();

  $currentItem = NULL;
  if(isset($_GET['error'])){
    echo "<script>alert('{$_GET['error']}');</script>";
  }
  if(isset($_GET['item'])){
    $currentItem = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND id = {$_GET['item']}")->fetch_assoc();
  }

  if(isset($_GET['delete'])){
    //get the task to be removed from the database
    $removingTask = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND id = {$_GET['delete']}")->fetch_assoc();

    if($database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = {$_GET['delete']}")->num_rows > 0){
      header('location: ' . $_SERVER['HTTP_REFERER'] . '&error=Could Not Delete, item contains sub items');
      exit();
    }

    //get the item repeating command if it exists
    $repeatStart = strpos($removingTask['task'], "[every");
    $repeatEnd = "";
    //check if repeating exist and set repeat end otherwise clear repeat start
    if($repeatStart){
      $repeatEnd = strpos(substr($removingTask['task'], $repeatStart), "]");
      if(!$repeatEnd){
        $repeatStart = -1;
        $repeatEnd = -1;
      }
    }


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

    //add next task if repeating
    if($repeatStart && $repeatEnd){
      $repeatStr = substr($removingTask['task'], $repeatStart, $repeatEnd);
      $repeatStr = str_replace("[every", '', $repeatStr);
      $repeatStr = str_replace(array("on", "until"), ':', $repeatStr);
      $repeatArray = explode(':', $repeatStr);
      for($a = 0; $a < count($repeatArray); $a++){
        if($a == 0){
          $repeatArray[0] = preg_replace('/\s+/', '', $repeatArray[0]);
          $command = explode(',', $repeatArray[0])[0];
          $when = substr($command, -1);
          $value = intval($command);
          //occurs every * days
          if($when == 'd'){
            $next = date("Y-m-d", strtotime("{$removingTask['task_date']} +{$value} days"));
            if($removingTask['task_date'] != 'NULL'){
              addItem($removingTask['task'], $removingTask['parent'], $removingTask['task_time'], $next);
            }
          }
          //occurs every * weeks
          else if($when == 'w'){
            $next = date("Y-m-d", strtotime("{$removingTask['task_date']} +{$value} weeks"));
            if($removingTask['task_date'] != 'NULL'){
              addItem($removingTask['task'], $removingTask['parent'], $removingTask['task_time'], $next);
            }
          }
          //occurs every * months
          else if($when == 'm'){
            $next = date("Y-m-d", strtotime("{$removingTask['task_date']} +{$value} months"));
            if($removingTask['task_date'] != 'NULL'){
              addItem($removingTask['task'], $removingTask['parent'], $removingTask['task_time'], $next);
            }
          }
          //occurs every * years
          else if($when == 'y'){
            $next = date("Y-m-d", strtotime("{$removingTask['task_date']} +{$value} years"));
            if($removingTask['task_date'] != 'NULL'){
              addItem($removingTask['task'], $removingTask['parent'], $removingTask['task_time'], $next);
            }
          }
        }
      }
    }

    header('location: ' . $_SERVER['HTTP_REFERER']);
    exit();
  }

  if(isset($_POST['item']) && !empty($_POST['item'])){
    $time = 0;
    $date = NULL;

    //set the item time if it exists
    if($_POST['item-time'] > 0)
      $time = $_POST['item-time'];

    //set item date if it exists
    if($_POST['item-date'] != '')
      $date = $_POST['item-date'];

    //check if item is being added to root list
    $parentId = 0;
    if($currentItem != NULL)
      $parentId = $currentItem['id'];

    addItem($_POST['item'], $parentId, $time, $date);

    header('location: ' . $_SERVER['HTTP_REFERER']);
    exit();

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
    if(!isset($_GET['year']) || !isset($_GET['month']) || !isset($_GET['day'])){
      $year = date('Y');
      $month = date('m');
      $day = date('d');
      header("Location: {$CONFIG['url']}?view=month&year={$year}&month={$month}&day={$day}");
      exit();
    }
    $day = $_GET['day'];
    $month = $_GET['month'];
    $year = $_GET['year'];
    $items = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND task_date >= '{$year}-{$month}-01' AND task_date <= '{$year}-{$month}-31' ORDER BY task_date, task ASC");
  }
  else if($_GET['view'] == 'day'){
    if(!isset($_GET['year']) || !isset($_GET['month']) || !isset($_GET['day'])){
      $year = date('Y');
      $month = date('m');
      $day = date('d');
      header("Location: {$CONFIG['url']}?view=day&year={$year}&month={$month}&day={$day}");
      exit();
    }
    $date = "{$_GET['year']}-{$_GET['month']}-{$_GET['day']}";
    $items = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND task_date = '{$date}' ORDER BY task ASC");
  }
  else if($_GET['view'] == 'unscheduled'){
    $items = $database->query("SELECT ta.* FROM tasks ta LEFT JOIN tasks tb ON ta.id = tb.parent WHERE tb.id IS NULL AND ta.task_date IS NULL AND ta.user = {$user['id']} ORDER BY task ASC");
  }
  else{
    header("Location: {$CONFIG['url']}?view={$SETTINGS['default-view']}");
    exit();
  }

  echo "<div class=\"task-wdg\">";

  if($_GET['view'] == 'day' || $_GET['view'] == 'today'){
    $today = '';
    if($_GET['view'] == 'today')
      $today = date('Y-m-d');
    else
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
  else if($_GET['view'] == 'unscheduled'){
    $totalTime = $database->query("SELECT SUM(ta.task_time) AS sum FROM tasks ta LEFT JOIN tasks tb ON ta.id = tb.parent WHERE tb.id IS NULL AND ta.task_date IS NULL AND ta.user = {$user['id']}")->fetch_assoc()['sum'];
    $totalTime = $totalTime / 60;
    echo "<div class=\"title-cmp\">
            <div class=\"top\">
              <span class=\"time\">{$totalTime} hrs</span>
            </div>
            <div class=\"bottom\">
              <span class=\"text\">Unscheduled Items</span>
            </div>
      </div>";
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
    header("Location: {$CONFIG['url']}?view={$SETTINGS['default-view']}");
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
