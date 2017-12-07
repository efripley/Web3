<?php
//The view class is responsible for drawing the chosen view with the given items and query string constraints

include('Item.php');
include('Query.php');

class View{
	private $items = null;
	private $constraints = null;

	function __construct($aConstraints, $aItems){
		$this->items = $aItems;
		$this->constraints = $aConstraints;
	}

	private function drawHead(){
		echo '<!DOCTYPE html>
			<html>

				<head>
					<title>calendar | newvec.com</title>
					<meta name="viewport" content="initial-scale=1 width=device-width">
					<link rel="stylesheet" type="text/css" href="style.css">

					<link rel="apple-touch-icon" sizes="57x57" href="/favicon/apple-icon-57x57.png">
					<link rel="apple-touch-icon" sizes="60x60" href="/favicon/apple-icon-60x60.png">
					<link rel="apple-touch-icon" sizes="72x72" href="/favicon/apple-icon-72x72.png">
					<link rel="apple-touch-icon" sizes="76x76" href="/favicon/apple-icon-76x76.png">
					<link rel="apple-touch-icon" sizes="114x114" href="/favicon/apple-icon-114x114.png">
					<link rel="apple-touch-icon" sizes="120x120" href="/favicon/apple-icon-120x120.png">
					<link rel="apple-touch-icon" sizes="144x144" href="/favicon/apple-icon-144x144.png">
					<link rel="apple-touch-icon" sizes="152x152" href="/favicon/apple-icon-152x152.png">
					<link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-icon-180x180.png">
					<link rel="icon" type="image/png" sizes="192x192"  href="/favicon/android-icon-192x192.png">
					<link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
					<link rel="icon" type="image/png" sizes="96x96" href="/favicon/favicon-96x96.png">
					<link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
					<link rel="manifest" href="/favicon/manifest.json">
					<meta name="msapplication-TileColor" content="#ffffff">
					<meta name="msapplication-TileImage" content="/favicon/ms-icon-144x144.png">
					<meta name="theme-color" content="#ffffff">
				</head>

				<body>';
	}

	function drawFace(){
	}

	function drawFoot(){
	}

	function drawItems(){
	}

	function drawDay(){
	}

	function drawMonth(){
	}

	function drawUnscheduled(){
	}

	function draw(){
		$this->drawHead();
		$this->drawFace();
		
		switch($this->constraints->view){
			case 'items':
				$this->drawItems();
				break;
			case 'day':
				$this->drawDay();
				break;
			case 'month':
				$this->drawMonth();
				break;
			case 'unscheduled':
				$this->drawUnscheduled();
				break;
		}

		$this->drawFoot();
	}
}
