<?php 

class flgalleryStats
{
	var
		$time,
		$queries,
		$startTime,
		$startQueries,
		$endTime,
		$endQueries;

	function flgalleryStats()
	{
	}

	function start()
	{
		global $wpdb;

		$this->startTime = microtime(false);
		$this->startQueries = $wpdb->num_queries;
	}

	function stop()
	{
		global $wpdb;

		$this->endQueries = $wpdb->num_queries;
		$this->endTime = microtime(false);

		$this->time = $this->endTime - $this->startTime;
		$this->queries = $this->endQueries - $this->startQueries;
	}
}




?>