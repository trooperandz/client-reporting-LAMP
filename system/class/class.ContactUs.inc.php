<?php
/**
 * Program: class.ContactUs.inc.php
 * Created: 06/04/2016 by Matt Holland
 * Purpose: Display file upload form and file retrieval table
 * Methods: getPageHeading(): Build page heading for info displays
 			getDocTable(): Build table to display user files
 			processFileUpload(): Process file uploads - insert files into db
 			getSuccessMsg(): Display user feedback after form submission
 * Updates:
 */

Class ContactUs extends PDO_Connect {	
	
	public function __construct($dbo) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);
	}

	public function getPageHeading($array) {
		$msg = $array['link_msg'];
		$html ='
		<div class="title_area">
           	<div class="row">
           		<div class="small-12 medium-9 large-9 columns">
           			<p class="large-title">'.$array['page_title'];
           				if($array['title_info']) {
           					$html .='
           					<span class="blue"> '.$array['title_info'].' </span>';
           				} 
           				if($array['a_id']) {
           					$html .='
           					<a id="'.$array['a_id'].'" style="color: green; font-size: 15px;"> &nbsp; '.$msg.' </a>';
           				}
           			$html .='
           			</p>
           		</div>
           		<div class="small-12 medium-3 large-3 columns">
					<p class="right-align large-title">';
					  if($array['export-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Export Document List" href="system/utils/export_doc_list.php">
							<span class="fontello-download"></span>
						</a>';
					  }
					  if($array['print-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Print Dealer Table" href="#" onclick="window.print();">
							<span class="fontello-print"></span>
						</a>';
					  }
					  if($array['doc_count']) {
					  	$html .='
						&nbsp;Total Documents: '.number_format($_SESSION['user_doc_count']);
					  }
					$html .='
					</p>
				</div>
           	</div>
        </div>
        <!-- Container Begin -->
        <div class="row" style="margin-top:-20px">';
		return $html;
	}
	
	/* Build user documents table */
	public function getContactForm() {
		$html = '
			<div class="row no-pad">
				<div class="large-12 columns">
					<div class="box bg-white">
						<div class="box-body pad-forty" style="display: block;">
							<div class="row">
								<div class="large-3 columns">
									<p><strong>Contact Us</strong></p>
									<p>Questions? Concerns?  Please fill out this form and we will get back to you as soon as possible.</p>
									<p>If it concerns a system issue, please provide as much detail as possible so that we may diagnose the problem.</p>
								</div>
								<div class="large-9 columns">
									<form id="contact_us_form">
										<fieldset>
											<div class="row collapse">
												<div class="small-3 large-2 columns">
													<span class="prefix">First Name</span>
												</div>
												<div class="small-9 large-10 columns">
													<input type="text" id="fname" name="fname" placeholder="Please enter your first name">
												</div>
											</div>
											<div class="row collapse">
												<div class="small-3 large-2 columns">
													<span class="prefix">Last Name</span>
												</div>
												<div class="small-9 large-10 columns">
													<input type="text" id="lname" name="lname" placeholder="Please enter your last name">
												</div>
											</div>
											<div class="row collapse">
												<div class="small-3 large-2 columns">
													<span class="prefix">Contact Email</span>
												</div>
												<div class="small-9 large-10 columns">
													<input type="text" id="email" name="email" placeholder="Please enter a contact email address">
												</div>
											</div>
											<div class="row collapse">
												<div class="small-3 large-2 columns">
													<span class="prefix">Contact Phone</span>
												</div>
												<div class="small-9 large-10 columns">
													<input type="text" id="phone" name="phone" placeholder="Please enter a contact phone number">
												</div>
											</div>
											<div class="row collapse">
												<div class="small-3 large-2 columns">
													<span class="prefix">Dealer Name</span>
												</div>
												<div class="small-9 large-10 columns">
													<input type="text" id="dealer" name="dealer" placeholder="Please enter your dealer name, or N/A">
												</div>
											</div>
											<div class="row">
												<div class="small-12 columns">
													<textarea rows="5" id="comment" name="comment" placeholder="Please enter a detailed description of your inquiry"></textarea>
												</div>
											</div>
											</div class="row">
												<div class="small-12 columns">
													<input type="submit" class="tiny button radius" id="contact_us_submit" name="contact_us_submit" value="Submit" />
												</div>
											</div>
										</fieldset>
									</form>
								</div> <!-- end div large-9 columns -->
							</div> <!-- end div row -->
						</div> <!-- end div box-body -->
					</div> <!-- end div box -->
				</div> <!-- end div large-12 columns -->
			</div> <!-- end div row -->';
		return $html;
	}
	
	public function processContactForm() {
		$stmt = "INSERT INTO user_feedback_welr (fname, lname, email, phone, dealer, comment, userID, create_date)
				 VALUES (?,?,?,?,?,?,?,?)";
		// Build params array
		$params = array($_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['phone'], $_POST['dealer'], $_POST['comment'], $_SESSION['user']['user_id'], date("Y-m-d H:i:s"));
		
		// Save email SESSION var for sending email copy to original requestor
		$_SESSION['inquiry_email'] = $_POST['email'];
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			// Email user inquiry to all SOS personnal and return true
			$this->emailUserInquiry(array('fname'=>$_POST['fname'], 'lname'=>$_POST['lname'], 'email'=>$_POST['email'], 'phone'=>$_POST['phone'], 'dealer'=>$_POST['dealer'], 'comment'=>$_POST['comment']));
			return true;
		}
	}
	
	/* Display success or fail msg to user after document form submit action */
	public function getSuccessMsg($array) {
		$html .='
		<div class="row">
			<div class="large-12 columns">
				<p style="color: green; padding: 0; margin: 0;">'.$array['success_msg'].'</p>
			</div>
		</div>';
		return $html;
	}
	
	// Email user inquiry notification to admin personnel 
	public function emailUserInquiry($array) {
		$fname   = $array['fname'];
		$lname   = $array['lname'];
		$email   = $array['email'];
		$phone   = $array['phone'];
		$dealer  = $array['dealer'];
		$comment = $array['comment'];
		
		// Build each user block message
		$info = "";
		$info .= "Name: ".$fname." ".$lname."\n";
		$info .= "Email: ".$email."\n";
		$info .= "Phone: ".$phone."\n";
		$info .= "Dealer: ".$dealer."\n\n";
		$info .= "Comment: ".$comment."\n\n";
	
		// Build email message
		$subject = MANUF.' Online Reporting User Inquiry';
		$msg = "Dear Admin,\n\n";
		$msg.= "Please see below for a user inquiry submitted to the ".MANUF." Online Reporting System on ".date("m/d/Y").": \n\n";
		$msg.= $info;
		$msg.= "Thank you,\n";
		$msg.= "SOS Admin";
		
		// Mail inquiry to all admin users
		$obj = new UserInfo;
		$admin_users = $obj->getUserInfo(array('user_type_id'=>1, 'user_admin'=>1));
		for($i=0; $i<count($admin_users); $i++) {
			mail($admin_users[$i]['user_email'], $subject, $msg);
		}
		
		// Mail copy of inquiry to requestor
		$subject = MANUF.' Online Reporting Inquiry Receipt';
		$req_msg = "Dear ".$array['fname'].",\n\n";
		$req_msg.= "You submitted a request to the ".MANUF." Online Reporting system on ".date('m/d/Y').".\n";
		$req_msg.= "Please see a copy of your inquiry below: \n\n";
		$req_msg.= $comment."\n\n";
		$req_msg.= "A member of the ".MANUF." team will contact you as soon as possible.\n\n";
		$req_msg.= "Thank you,\n";
		$req_msg.= "SOS Admin";
		mail($array['email'], $subject, $req_msg);
	}	
}