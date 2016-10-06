<?php
class ErrorHandling {
	// For important errors, email to your personal address in case admin debugging is needed
	public function emailError($email_address, $error) {
		$subject = 'Online reporting error';
		mail(CONTACT_EMAIL, $subject, $error);
	}
}
?>