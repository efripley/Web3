<?php
include('View.php');

$constraints = new Query();
$items = [];
$view = new View($constraints, $items);

$view->draw();
?>
