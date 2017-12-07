<?php
class Item{
	var $text = null;
	var $duration = null;
	var $date = null;

	function __construct($aText, $aDuration = null, $aDate = null){
		$this->text = $aText;
		$this->duration = $aDuration;
		$this->date = $aDate;
	}

	function draw(){
	}
}
?>
