<?php
/**
 * Program: class.DB_Connect.inc.php
 * Created: 02/26/2016 by Matt Holland
 * Purpose: Database actions (DB access, validation, etc.)
 * Methods: __construct(): tests for $dbo object and creates one if does not exist
 * Updates:
 */

Class DB_Connect {

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
				$this->dbo = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			}
			catch (Exception $e) {
				// If the DB connection fails, output the error
				die($e->getMessage());
			}
		}
	}
}
?>