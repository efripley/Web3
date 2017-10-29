<?php
global $SETTINGS;
$SETTINGS["default-view"] = "today";

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
?>
