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
      $todayClass = '';
      if($date == date('Y-m-d')){
        $todayClass = "today";
      }
      $timeString = '';
      if($time > 0)
        $timeString = ($time / 60) . 'hrs';
      echo "
      <a class=\"day-cmp {$todayClass}\" href=\"{$CONFIG['url']}?view=day&year={$year}&month={$month}&day={$dayString}\">
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
}
?>
