<?php
function connect(){
  global $CONFIG;
  global $database;
  $database = new mysqli($CONFIG['host'], $CONFIG['username'], $CONFIG['password'], $CONFIG['database']);
  if($database->connect_error)
    die("Connection Failed: " . $CONFIG['connection']->connect_error);
}

function buildMonth($items, $year, $month){
  global $CONFIG;
  $monthString = DateTime::createFromFormat('!m', $month)->format('F');
  $day = 1;
  $hours = 0;
  echo "<div class=\"title-cmp\"><span class=\"text\">{$monthString}</span></div>";
  $item = $items->fetch_assoc();
  while(true){
    $dayString = sprintf("%02d", $day);
    $date = "{$year}-{$month}-{$dayString}";
    if($date == $item['task_date']){
      $time += $item['task_time'];
      $item = $items->fetch_assoc();
    }
    else{
      $timeString = '';
      if($time > 0)
        $timeString = ($time / 60) . 'hrs';
      echo "
      <a class=\"day-cmp\" href=\"{$CONFIG['url']}?view=day&year={$year}&month={$month}&day={$dayString}\">
        <span class=\"day-number\">{$day}</span>
        <span class=\"day-hrs\">{$timeString}</span>
      </a>
      ";
      $time = 0;
      $day += 1;
      if($day > 31)
        break;
    }
  }
    // if($item['task_time'] > 60){
    //   $itemHours = $item['task_time'] / 60;
    //   echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?item={$item['parent']}&delete={$item['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?view=items&item={$item['id']}\" title=\"follow item\"><span class=\"time\">{$itemHours} hrs</span><span class=\"text\">{$item['task']}</span></a></div>";
    // }
    // else if($item['task_time'] > 0){
    //   echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?item={$item['parent']}&delete={$item['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?view=items&item={$item['id']}\" title=\"follow item\"><span class=\"time\">{$item['task_time']} min</span><span class=\"text\">{$item['task']}</span></a></div>";
    // }
    // else{
    //   echo "<div class=\"task-cmp\"><a class=\"close\" href=\"{$CONFIG['url']}?item={$item['parent']}&delete={$item['id']}\" title=\"delete item\">(X)</a><a href=\"{$CONFIG['url']}?view=items&item={$item['id']}\" title=\"follow item\"><span class=\"time\">-</span><span class=\"text\">{$item['task']}</span></a></div>";      
    // }
  //}
}
?>
