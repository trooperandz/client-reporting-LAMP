<?php
/**
 * Program: class.PDO_Connect.inc.php
 * Created: 04/07/2016 by Matt Holland
 * Purpose: Database actions (DB access, validation, etc.)
 * Methods: __construct(): tests for $dbo object and creates one if does not exist
 * Updates:
 */

Class PDO_Connect {

	/**
	 * Stores a database object
	 *
	 * @var object A database object
	 */
	protected $dbo;
	
	/**
	 * Checks for a db object or creates
	 * one if one is not found.
	 * @param object $dbo A database object
	 */
	protected function __construct($dbo) {
		if (is_object($dbo)) {
			$this->dbo = $dbo;
		} else {
			try {
			    $this->dbo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
			} catch (PDOException $e) {
				echo 'Connection failed: '.$e->getMessage();
		 	}
		}
	}
}
?>