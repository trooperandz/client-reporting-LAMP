<?php
/**
 * Program: class.Documents.inc.php
 * Created: 06/04/2016 by Matt Holland
 * Purpose: Display file upload form and file retrieval table
 * Methods: getPageHeading(): Build page heading for info displays
 			getDocTable(): Build table to display user files
 			processFileUpload(): Process file uploads - insert files into db
 			getSuccessMsg(): Display user feedback after form submission
 * Updates:
 */

Class Documents extends PDO_Connect {	

	// Establish global vars for defining user access to specific components
    public $user_sos   ;
    public $user_manuf ;
    public $user_dlr   ;
    public $user_admin ;

	public function __construct($dbo) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);

		// Initialize user types
		$this->user_sos   = ($_SESSION['user']['user_type_id'] == 1) ? true : false;
		$this->user_manuf = ($_SESSION['user']['user_type_id'] == 2) ? true : false;
		$this->user_dlr   = ($_SESSION['user']['user_type_id'] == 3) ? true : false;
		$this->user_admin = ($_SESSION['user']['user_admin']   == 1) ? true : false;		
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
           					<a id="'.$array['a_id'].'" class="'.$array['a_id'].'" style="color: green; font-size: 15px;"> &nbsp; '.$msg.' </a>';
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
	public function getFileUploadForm($array) {
		// If $array['edit_doc_id'] == true, get doc details
		if($array['edit_doc_id']) {
			$doc_data     = $this->getDocData(array('edit_doc_id'=>$array['edit_doc_id']));
			$doc_title    = $doc_data[0]['doc_title'];
			$doc_cat_id   = $doc_data[0]['doc_cat_id'];
			$doc_cat_name = $doc_data[0]['doc_cat_name'];
			$doc_desc     = $doc_data[0]['doc_desc'];
			$file_name    = $doc_data[0]['file_name'];
			$form_title   = 'Edit Document Details';
			$msg1	      = 'This form is for editing document titles and descriptions.';
			$msg2	      = 'Note: If you are trying to change the actual stored file, please delete the existing file and create a new one.';
			$submit_id    = 'file_update_submit';
			$submit_value = 'Save Changes';
		} else {
			$doc_title    = null;
			$doc_cat_id   = null;
			$doc_cat_name = null;
			$doc_desc     = null;
			$form_title   = 'Upload New Document';
			$msg1	      = 'Use this form to save relevant system documents such as forms and manuals.';
			$msg2         = 'Note: Only pdf documents are allowed to be uploaded to the system.';
			$submit_id    = 'file_submit';
			$submit_value = 'Upload File';
		}
		
		// Load document category list for doc category <select> dropdown
		$doc_cat_data = $this->getDocCategories();
	
		$html = '
			<div class="row no-pad">
				<div class="large-12 columns">
					<div class="box bg-white">
						<div class="box-body pad-forty" style="display: block;">
							<div class="row">
								<div class="large-3 columns">
									<p><strong>'.$form_title.'</strong></p>
									<p>'.$msg1.'</p>
									<p>'.$msg2.'</p>
								</div>
								<div class="large-9 columns">
									<form id="file_upload_form">
										<fieldset>
											<div class="row collapse">
												<div class="small-3 large-2 columns">
													<span class="prefix">Doc Title</span>
												</div>
												<div class="small-9 large-10 columns">
													<input type="text" id="doc_title" name="doc_title" value="'.$doc_title.'" placeholder="Please enter the document title">
												</div>
											</div>
											<div class="row collapse">
												<div class="small-3 large-2 columns">
													<span class="prefix">Doc Category</span>
												</div>
												<div class="small-9 large-10 columns">
													<select id="doc_category" name="doc_category">';
														if($array['edit_doc_id']) {
															$html .='
															<option value="'.$doc_cat_id.'">'.$doc_cat_name.'</option>';
														} else {
															$html .='
															<option value="">Select...</option>';
														}
														// Fill in remaining options from $doc_cat_data
														for($i=0; $i<count($doc_cat_data); $i++) {
															$html .='
															<option value="'.$doc_cat_data[$i]['doc_cat_id'].'">'.$doc_cat_data[$i]['doc_cat_name'].'</option>';
														}
													$html .='	
													</select>
												</div>
											</div>';
										if($array['edit_doc_id']) {
											$html .='
											<div class="row collapse">
												<label>File Name</label>
												<div class="small-9 large-10 columns">
													<input type="text" id="file_name" name="file_name" value="'.substr($file_name,0,-4).'" placeholder="Please enter the file title">
												</div>
												<div class="small-3 large-2 columns">
													<span class="postfix">.pdf</span>
												</div>
											</div>';
										}
											$html .='
											<div class="row">
												<div class="small-12 columns">
													<label>Doc Description</label>
													<textarea rows="5" id="doc_desc" name="doc_desc" placeholder="Please enter a document description">'.$doc_desc.'</textarea>
												</div>
											</div>';
										if(!$array['edit_doc_id']) {
											$html .='
											<div class="row">
												<div class="small-12 columns">
													<input type="file" name="choose_file" id="choose_file" />
												</div>
											</div>';
										}
											$html .='
											</div class="row">
												<div class="small-12 columns">
													<input type="submit" class="tiny button radius" id="'.$submit_id.'" name="'.$submit_id.'" value="'.$submit_value.'" />
												</div>
											</div>
											<input type="hidden" name="action" id="action" value="file_submit" />';
										if($array['edit_doc_id']) {
											$html .='
											<input type="hidden" id="edit_doc_id" name="edit_doc_id" value="'.$array['edit_doc_id'].'" />';
										}
										$html .='
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
	
	public function getDocData($array) {
		// Generate sql statement
		$stmt = "SELECT a.file_id, a.doc_title, a.doc_desc, a.doc_cat_id, b.doc_cat_name, a.tmp_name, a.file_name, a.file_size, a.create_date, a.user_name 
				 FROM online_rep_files a
				 LEFT JOIN doc_category b ON(a.doc_cat_id = b.doc_cat_id) ";
		
		// Initialize $params
		$params = array();
		
		// If $array['edit_doc_id'], add WHERE clause and add doc id to $params
		if($array['edit_doc_id']) {
			$stmt .= " WHERE a.file_id = ? ";
			$params[] = $array['edit_doc_id'];
		}
		
		// If $array['doc_type'], add WHERE clause for document category
		if($array['doc_type']) {
			// If doc_type == 3 ('My Documents'), then add another param for user_id
			if($array['doc_type'] == 3) {
				$stmt .= " WHERE a.doc_cat_id = ? AND a.user_id = ? ";
				$params[] = $array['doc_type'];
				$params[] = $_SESSION['user']['user_id'];
			} else {
				$stmt .= " WHERE a.doc_cat_id = ? ";
				$params[] = $array['doc_type'];
			}
		}
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			$data = $stmt->fetchAll();
			return $data;
		}
	}
	
	public function getDocCategories() {
		// Note: All users have access to the 'System Docs' menu link. Provide necessary restrictions based on user type
		// Manuf users do not have access to the 
		if(!isset($_SESSION['doc_cat_list'])) {
			$stmt = "SELECT doc_cat_id, doc_cat_name FROM doc_category ";

			// If user is SOS 

			// If user type == dealer, then limit category to only 'My Documents'
			if($this->user_dealer) {
				$stmt .= " WHERE doc_cat_id = 3";
			}
			
			// Prepare and execute statement
			if(!($stmt = $this->dbo->prepare($stmt))) {
				sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
				return false;
			}
			if(!($stmt->execute())) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				return false;
			} else {
				$data = $stmt->fetchAll();
				$_SESSION['doc_cat_list'] = $data;
				return $data;
			}
		} else {
			return $_SESSION['doc_cat_list'];
		}
	}
	
	/** Generate system documents table 
	 * @param {array} $array Stored document information
	 * @return {string} $html Document table html structure
	 */
	public function getDocTable($array) {
		// Get Document information from db
		// Note: All doc data user access is determined by getDocData() function. No need to filter further in this method
		$data = $this->getDocData($array);
		
		// Save document count as SESSION var for page heading
		$_SESSION['user_doc_count'] = count($data);
		
		// Build html table
		$html ='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<div class="table-container">
						<table id="user_doc_table" class="original metric">
							<thead>
								<tr>
									<th style="width: 86px;"><a>Action</a></th>
									<!--<th colspan="3"><a> Action </a></th>-->
									<!--
									<th>Action</th>
									<th></th>
									<th></th>
									-->
									<th><a> Doc Name	</a></th>
									<th><a> Description </a></th>
									<th><a> File Name	</a></th>
									<th><a> File Size 	</a></th>
									<th><a> Create Date </a></th>
									<th><a> User	 	</a></th>
								</tr>
							</thead>
							<tbody>';
							$export = "Doc Name, Description, File Name, File Size, Create Date, User\n";
							// Build html table body and export data rows based on increments set above
							for($i=0; $i<count($data); $i++) {
								// Format create_date to show just the date, not the time also
								$date = date("m/d/Y", strtotime($data[$i]['create_date']));
								$html .='
								<tr>
									<td style="width: 86px;">
										<a class="fontello-trash tooltip-tip" title="Delete Doc" style="color: #c7254e;" id="'.$data[$i]['tmp_name'].'" name="remove_doc_icon">&nbsp;</a>
										<a class="icon-document-edit tooltip-tip" title="Edit Doc" style="color: green;" name="edit_doc_icon">&nbsp;</a>
										<form style="display: inline-block;" method="POST" action="system/utils/getFile.php">
											<input type="hidden" value="'.$data[$i]['file_id'].'" id="view_doc_id" name="view_doc_id" />
											<button type="submit" style="border: none; padding: 0; margin: 0; background: none; background-color: #FFFFFF;">
												<a class="icon-download file tooltip-tip" title="Download" id="table_doc_select" name="table_doc_select"></a>
											</button>
										</form>
									</td>
									<!--
									<td style="width: 20px;">
										<a class="fontello-trash tooltip-tip" title="Delete Doc" style="color: #c7254e;" name="remove_doc_icon"></a>
									</td>
									<td style="width: 20px;">
										<a class="icon-document-edit tooltip-tip" title="Edit Doc" style="color: green;" name="edit_doc_icon"></a>
									</td>
									<td style="width: 40px;">
										<form class="table_form" method="POST" action="system/utils/getFile.php">
											<input type="hidden" value="'.$data[$i]['file_id'].'" id="view_doc_id" name="view_doc_id" />
											<input type="submit" id="table_doc_select" name="table_doc_select" style="margin: 0px; padding: .2em .3em;" class="tiny button radius" value="View" />
										</form>
									</td>
									-->
									<td>'.$data[$i]['doc_title'].	'</td>
									<td>'.$data[$i]['doc_desc'].	'</td>
									<td>'.$data[$i]['file_name'].	'</td>
									<td>'.$data[$i]['file_size'].	'</td>
									<td>'.$date.					'</td>
									<td>'.$data[$i]['user_name'].	'</td>
								</tr>';

								// Generate export data 
								$export .= $data[$i]['doc_title'].",".$data[$i]['doc_desc'].",".$data[$i]['file_name'].",".$data[$i]['file_size'].",".$data[$i]['create_date'].",".$data[$i]['user_name']."\n";		  				  
							}

							$html .='
							</tbody>
						</table>
						</div> <!-- end div table-container -->
					</div><!-- end div large-12 columns -->
				</div><!-- end div row -->
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		
		// Save export as SESSION var
		$_SESSION['export_doc_data'] = $export;
		
		return $html;
	}	
	
	/* Process file upload - insert into db */
	public function processFileUpload() {
		if(isset($_FILES['choose_file'])) {
			// Gather all required data
			$doc_title 	  = $_POST['doc_title'];
        	$doc_desc 	  = $_POST['doc_desc'];
        	$doc_category = $_POST['doc_category'];
			$file 		  = $_FILES['choose_file'];
        	$file_name 	  = $file['name'];
        	$file_type    = $file['type'];
        	$file_size 	  = $file['size'];
        	
        	// Create the file's new name and destination if there were no errors.  Append a unique identifier to name.
			$tmp_name = sha1($file['name']) . uniqid('', true);
			$dest = USER_DOC_DIR.$tmp_name.'_tmp';
			//echo 'dest: '.$dest;
			//echo 'tmp_name: '.$tmp_name;
			
			// Create $params[] array list for db insert
			$params = array();
			$params[] = $doc_title;
			$params[] = $doc_desc;
			$params[] = $doc_category;
			$params[] = $tmp_name;
			$params[] = $file_name;
			$params[] = $file_type;
			$params[] = $file_size;
			$params[] = $_SESSION['user']['user_id'];
			$params[] = $_SESSION['user']['user_name'];
			$params[] = date("Y-m-d H:i:s");
			
			// Move the file
			if (move_uploaded_file($file['tmp_name'], $dest)) {
					
				// Make file name available in feedback message
				$feedback_file = $file['name'];
				
				// Prepare and execute file INSERT action
				$stmt = "INSERT INTO online_rep_files (doc_title, doc_desc, doc_cat_id, tmp_name, file_name, file_type, file_size, user_id, user_name, create_date)
						 VALUES (?,?,?,?,?,?,?,?,?,?)";
						 
				if(!($stmt = $this->dbo->prepare($stmt))) {
					sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
				}
				if(!($stmt->execute($params))) {
					sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
					// Delete the file so that in the case of a query error there will not be a file on the server without a corresponding database reference
					unlink ($dest);
				} else {
					// Rename the file and have the _tmp removed from the name so as to distinuish successful from unsuccessful uploads
					$original = USER_DOC_DIR.$tmp_name.'_tmp';
					$dest	  = USER_DOC_DIR.$tmp_name;
					rename ($original, $dest);
					return $feedback_file;
				}
			} else {
				// Remove from the directory if INSERT unsuccessful so as not to clutter
				unlink(USER_DOC_DIR.$file['tmp_name']);
				return false;
			}
		} else {
			return false;
		}
	}
	
	/* Display the file in the browser using the 'Content-Disposition: inline' instruction. 
	 * Much more user-friendly.
	 * Be sure to test this on mobile
	 * $array will contain 'view_doc_id' for passing to db query
	**/
	public function viewFile($array) {
		// Create the query to get the file from the db. Note that tmp_name will match the encrypted file name in user_docs directory
		$stmt = "SELECT tmp_name, file_name, file_type FROM online_rep_files WHERE file_id = ?";
		
		//echo 'view_doc_id: '.$array['view_doc_id'];
		
		// Set $params
		//$params = array($array['file_id']);
		$params = array($array['view_doc_id']);
		
		// Prepare and execute the query
		if(!($stmt = $this->dbo->prepare($stmt))) {
			//echo 'A query error has occurred!  See the administrator.';
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		if(!($stmt->execute($params))) {
			//echo 'A query error has occurred! Please see the administrator.';
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			//echo 'result: '.var_dump($result);
		}
		
		// Set file type as application/pdf - this is the only file type allowed. $file == encrypted file name. $file_name == actual file name
		$file_mime = $result['file_type'];
		$file_name = $result['file_name'];
		$file	   = USER_DOC_DIR.$result['tmp_name'];
		
		//echo 'file: '.$file.'file_name: '.$file_name.' file_mime: '.$file_mime.' view_doc_id: '.$array['view_doc_id'];
		// Print headers for inline pdf viewing
		
		header('Content-type: '.$file_mime);
		header('Content-Disposition: attachment; filename="'.$file_name.'"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		readfile($file);
		//exit;
	}
	
	// If user issues delete_doc instruction, delete document from db based on file_id
	public function deleteDoc($array) {
		$stmt = "DELETE FROM online_rep_files WHERE file_id = ?";
		
		// Add file_id to $params
		$params = array($array['view_doc_id']);
		
		// Get file_name (tmp_name of actual file stored in directory)
		$tmp_name = $array['tmp_name'];
		
		// Prepare and execute the query
		if(!($stmt = $this->dbo->prepare($stmt))) {
			//echo 'A query error has occurred!  See the administrator.';
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		if(!($stmt->execute($params))) {
			//echo 'A query error has occurred! Please see the administrator.';
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			// Db DELETE successful.  Now delete document from file folder
			unlink(USER_DOC_DIR.$tmp_name);
			return true;
		}
	}
	
	public function updateDoc() {
		$stmt = "UPDATE online_rep_files 
				 SET doc_title = ?, doc_desc = ?, doc_cat_id = ?, file_name = ? WHERE file_id = ?";
				 
		// Add vars to $params. Append '.pdf' string to $_POST['file_name'] as <input> has posfix '.pdf' connected to it
		$params = array($_POST['doc_title'],$_POST['doc_desc'],$_POST['doc_category'],$_POST['file_name'].".pdf",$_POST['file_id']);
		
		// Prepare and execute the query
		if(!($stmt = $this->dbo->prepare($stmt))) {
			//echo 'A query error has occurred!  See the administrator.';
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		if(!($stmt->execute($params))) {
			//echo 'A query error has occurred! Please see the administrator.';
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
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
}