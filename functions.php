<?php
global $SETTINGS;
$SETTINGS["default-view"] = "day";

function connect(){
  global $CONFIG;
  global $database;
  $database = new mysqli($CONFIG['host'], $CONFIG['username'], $CONFIG['password'], $CONFIG['database']);
  if($database->connect_error)
    die("Connection Failed: " . $CONFIG['connection']->connect_error);
}

function getUser(){
  global $database;
  return $database->query("SELECT * FROM users WHERE id = {$_SESSION['user-id']}")->fetch_assoc();
}

function logOut(){
  global $CONFIG;
  $_SESSION['user-id'] = NULL;
  header("Location: {$CONFIG['url']}");
}

function buildMenu(){
  global $database, $user, $CONFIG;

  $menu = "";
  $today = date('Y-m-d');
  $year = date('Y');
  $month = date('m');
  $day = date('d');

  if($_GET['view'] == 'month' || $_GET['view'] == 'day'){
    $menu = $menu . "<a class=\"item\" href=\"{$CONFIG['url']}?view=items\">Items</a>";
  }
  if($_GET['view'] == 'items' || $_GET['view'] == 'month' || ($_GET['view'] == 'day' && ($_GET['year'] != date('Y') || $_GET['month'] != date('m') || $_GET['day'] != date('d')))){
    $menu = $menu . "<a class=\"item\" href=\"{$CONFIG['url']}?view=day&year={$year}&month={$month}&day={$day}\">Today</a>";
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

  echo "<header class=\"top-bar\">
    <span class=\"name\">{$user['name']}</span>
    <div class=\"menu\" onclick=\"this.classList.toggle('open');\">
      <span>Menu</span>
      <div class=\"items\">
        {$menu}
      </div>
    </div>
  </header>";
}

function buildMonth($items, $year, $month){
  global $CONFIG;
  $monthString = DateTime::createFromFormat('!m', $month)->format('F');
  $day = 1;
  $hours = 0;
  $dayString = sprintf("%02d", $day);
  $date = "{$year}-{$month}-{$dayString}";
  $previousMonth = date('Y-m-d', strtotime($date . ' -1 month'));
  $nextMonth = date('Y-m-d', strtotime($date . ' +1 month'));
  echo "<div class=\"title-cmp\">
    <span class=\"text\">";
  $year = date('Y', strtotime($previousMonth));
  $month = date('m', strtotime($previousMonth));
  $dayString = date('d', strtotime($previousMonth));
  echo "<a class=\"back-arrow\" href=\"{$CONFIG['url']}?view=month&year={$year}&month={$month}&day={$dayString}\">&lsaquo;</a>
    <span class=\"date-text\">{$monthString}</span>";
  $year = date('Y', strtotime($nextMonth));
  $month = date('m', strtotime($nextMonth));
  $dayString = date('d', strtotime($nextMonth));
  echo "<a class=\"back-arrow\" href=\"{$CONFIG['url']}?view=month&year={$year}&month={$month}&day={$dayString}\">&rsaquo;</a>
      </span>
    </div>";
  echo "<div class=\"day-titles\"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>";
  $year = date('Y', strtotime($date));
  $month = date('m', strtotime($date));
  $firstDay = date('N', strtotime($date));
  $totalDays = date('t', strtotime($date));
  if($firstDay < 7){
    for($a = 0; $a < $firstDay; $a++){
      echo "
      <span class=\"day-cmp empty\">
        <span class=\"day-number\"></span>
        <span class=\"day-item\"></span>
        <span class=\"day-item\"></span>
        <span class=\"day-item\"></span>
        <span class=\"day-hrs\"></span>
      </span>
      ";
    }
  }
  $day = 1;
  $itemText = [];
  $item = $items->fetch_assoc();
  while(true){
    $dayString = sprintf("%02d", $day);
    $date = "{$year}-{$month}-{$dayString}";
    if($date == $item['task_date']){
      $time += $item['task_time'];
      if(count($itemText) < 3){
        array_push($itemText, $item['task']);
      }
      $item = $items->fetch_assoc();
    }
    else{
      $todayClass = '';
      if($date == date('Y-m-d')){
        $todayClass = "today";
      }
      $timeString = '';
      if($time > 0)
        $timeString = ($time / 60) . 'hrs';
        $item1 = ($itemText[0]) ?: '';
        $item2 = ($itemText[1]) ?: '';
        $item3 = ($itemText[2]) ?: '';
      echo "
      <a class=\"day-cmp {$todayClass}\" href=\"{$CONFIG['url']}?view=day&year={$year}&month={$month}&day={$dayString}\">
        <span class=\"day-number\">{$day}</span>
        <span class=\"day-item\">$item1</span>
        <span class=\"day-item\">$item2</span>
        <span class=\"day-item\">$item3</span>
        <span class=\"day-hrs\">{$timeString}</span>
      </a>
      ";
      $time = 0;
      $itemText = [];
      $day += 1;
      if($day > $totalDays)
        break;
    }
  }
}

function addItem($itemText, $itemParent, $itemTime, $itemDate){
  global $database, $user;
  //Check for sibling tasks and zero out parent task time if this is the first child task
  //Parent tasks are not allowed to have a set time. They get a total time from their children
  $currentItem = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND id = {$itemParent}")->fetch_assoc();
  $numSiblingTasks = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND parent = {$itemParent}")->num_rows;
  if($numSiblingTasks == 0){
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

  //Insert the task into the database
  $prep = $database->prepare("INSERT INTO tasks (parent, user, task_date, task_time, task) VALUES (?, ?, ?, ?, ?)");
  echo $itemDate;
  $prep->bind_param("iisss", $itemParent, $user['id'], $itemDate, $itemTime, $itemText);
  if(!$prep->execute()){
    echo "Execute failed: (" . $prep->errno . ") " . $prep->error;
  }

  //update item family 'item times'
  $updateTask = $currentItem;
  while(true){
    $updateTask['task_time'] += $itemTime;
    $database->query("UPDATE tasks SET task_time = {$updateTask['task_time']} WHERE user = {$user['id']} AND id = {$updateTask['id']}");
    if($updateTask['parent'] == 0){
      break;
    }
    $updateTask = $database->query("SELECT * FROM tasks WHERE user = {$user['id']} AND id = {$updateTask['parent']}")->fetch_assoc();
  }
}
?>
