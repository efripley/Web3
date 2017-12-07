<?php
//class to hold application constraints for use in drawing html, querying database and application logic
include('config.php');
include('defaults.php');

class Query{
	public $view = null;
	public $year = null;
	public $month = null;
	public $day = null;
	public $action = null;

	//sets view constraints based on query string values
	//sets defaults or redirects if query string is broken
	function __construct(){
		if(isset($_GET['view']))
			$this->view = $_GET['view'];
		else
			$this->view = $default['view'];

		if($this->view == 'day' || $this->view == 'month'){
			var $correctDate = true;

			if(isset($_GET['day']))
				$this->day = $_GET['day'];
			else
				$correctDate = false;

			if(isset($_GET['month']))
				$this->month = $_GET['month'];
			else
				$correctDate = false;

			if(isset($_GET['year']))
				$this->year = $_GET['year'];
			else
				$correctDate = false;

			//redirect to current day if correct date values are not set
			if(!$correctDate){
				$year = date('Y');
				$month = date('m');
				$day = date('d');
				header("Location: {$CONFIG['url']}?view=month&year={$year}&month={$month}&day={$day}");
				exit();
			}
		}
	}
}
?>
