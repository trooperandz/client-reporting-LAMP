<?php
/**
* Program: class.UserInfo.inc.php
* Created: 03/02/2016 by Matt Holland
* Purpose: Get all user data
* Methods: getUserTypes(): get user type data from user_type db table
		   getAdvisors(): create dropdown menu for advisors
*		   updateUser(): execute UPDATE statement when user is editing a system user
* Updates:
*/

Class UserInfo extends PDO_Connect  {

	public function __construct($dbo=NULL) {
    	// Call the parent construct to check for a database object
    	parent::__construct($dbo);
  	}
	
	// Get array of user type data from user_type db table. Set as SESSION var
	public function getUserTypes() {
		if(!isset($_SESSION['user_type_data'])) {
			$stmt = "SELECT * FROM user_type";
			
			if(!($stmt = $this->dbo->prepare($stmt))) {
			  	sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
				return false;
			}
				
			if(!($stmt->execute($params))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				return false;
			} else {
				$result = $stmt->fetchAll();
			}
			$_SESSION['user_type_data'] = $result;
    		return $result;
    	} else {
    		return $_SESSION['user_type_data'];
    	}
	}
	
	// Get array of user team data from user_type db table. Set as SESSION var
	public function getUserTeams() {
		if(!isset($_SESSION['user_team_data'])) {
			$stmt = "SELECT * FROM user_team";
			
			if(!($stmt = $this->dbo->prepare($stmt))) {
			  	sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
				return false;
			}
				
			if(!($stmt->execute($params))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				return false;
			} else {
				$result = $stmt->fetchAll();
			}
			$_SESSION['user_team_data'] = $result;
    		return $result;
    	} else {
    		return $_SESSION['user_team_data'];
    	}
	}
	
	// This function is being used by the modal to get advisor dropdown info
  	public function getAdvisors($dealer_id) {
  		$stmt = "SELECT user_id, user_name FROM user
			     WHERE user_dealer_id = ?
			     ORDER BY user_name ASC";
			     
		$params = array($dealer_id);
			       
		if(!($stmt = $this->dbo->prepare($stmt))) {
		  	sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return 'error_query';
		}
			
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return 'error_query';
		} else {
			$index = 0;
			$user_id = array();
			$user_name = array();
			while($lookup = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$user_id[$index]['user_id'] = $lookup['user_id'];
    	        $user_name[$index]['user_name'] = $lookup['user_name'];
    	        $index += 1;
			}
		}
    	return array('user_id'=>$user_id, 'user_name'=>$user_name);
    }
    
    /* Get user info from system.
     * $array may contain the following params: 
     * 'user_name', 'user_email', 'dealer_id', 'user_type_id', 'user_admin', 'user_active'
    **/
    public function getUserInfo($array) {
    	// This will be used for concatenating $stmt's together (with sql AND operator etc)
    	$stmt = array();
    	$params = array();
    
    	$stmt[] = "SELECT a.user_id, a.user_name, b.type_id, b.type_name, c.team_id, c.team_name, a.user_dealer_id, 
    					  d.dealercode, d.dealername, a.user_pass, a.user_fname, a.user_lname, a.user_email, 
    					  a.user_active, a.user_admin, a.create_date
				   FROM user a 
				   LEFT JOIN user_type b ON(a.user_type_id = b.type_id)
				   LEFT JOIN user_team c ON(a.user_team_id = c.team_id)
				   LEFT JOIN dealer    d ON(a.user_dealer_id = d.dealerID) ";
				   
		// Add $stmt[] and $params[] to array for passing to getUserQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		// Build remaining query statement based on params.
		$data = Query::getUserQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
		
		//echo 'query: '.$query.'<br>';
		//echo 'params: '.var_dump($params).'<br>';
		// Prepare and execute query statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			return $result = $stmt->fetchAll();
		}
    }
    
    // Insert users into user table
    public function insertUser($array) {
    	$stmt = "INSERT INTO user (user_name, user_type_id, user_team_id, user_dealer_id, user_pass, user_fname,
    							   user_lname, user_email, user_active, user_admin, registered_by, create_date)
    						VALUES(?,?,?,?,?,?,?,?,?,?,?,?)";
    	
    	// Build values from user array for $params
    	$users = $array['users'];
    	
    	if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		for($i=0; $i<count($users); $i++) {
			// Build params array for easy reference.  Clear out each time for new INSERT
			$params = array();
			$params[]= $users[$i]['uname'];
			$params[]= $users[$i]['user_type_id'];
			$params[]= $users[$i]['user_team_id'];
			$params[]= $users[$i]['dealerID'];
			$params[]= $users[$i]['pass_hash'];
			$params[]= $users[$i]['fname'];
			$params[]= $users[$i]['lname'];
			$params[]= $users[$i]['email'];
			$params[]= $users[$i]['active'];
			$params[]= $users[$i]['admin'];
			$params[]= $users[$i]['user_id'];
			$params[]= date("Y-m-d H:i:s");
			
			if(!($stmt->execute($params))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				$error = true;
			} else {
				$error = false;
			}	
    	}
    	
    	// If there were no errors, return true
    	return (!$error) ? true : false;
    }
    
    /* Update user when system user has submitted the update user form successfully.  
     * user_pass field treatment dictated by $array['pass_entered']
     * All possible params: 'pass_entered', 'user_id', 'params'
     * Only accepts one row at a time; possible future functionality could incorporate multiple updates at a time,
     * but you would also have to update the main user table interface to allow user to select multiple rows.
    **/ 
    public function updateUser($array) {
    	//return 'entered update user';
    	// Get pass_entered value (true or false)
    	$pass_entered = $array['pass_entered'];
    	
    	// Get easy access to params
    	$param_list = $array['params'];
    	
    	// Remove after debugging complete
    	//return 'param_list: '.var_dump($param_list);
    	
    	// Get easy access to $edit_user_id val
    	$edit_user_id = $array['edit_user_id'];
    	
    	// Provide user_pass field if $pass_entered == true
    	$pass_stmt = ($pass_entered) ? " user_pass = ?, " :  null;
    	
    	// Reconfigure $params array so that params order matches UPDATE statement
    	$params = array();
    	foreach ($param_list as $param) {
    		$params[] = $param['fname'];
    		$params[] = $param['lname'];
    		$params[] = $param['uname'];
    		$params[] = $param['email'];
    		// Only include password if it was entered
    		if ($pass_entered) {
    			$params[] = $param['pass_hash'];
    		}
    		$params[] = $param['dealerID'];
    		$params[] = $param['admin'];
    		$params[] = $param['active'];
    		$params[] = $param['user_type_id'];
    		$params[] = $param['user_team_id'];
    	}
    	
    	// Add last user_id param to $params[] for WHERE clause
    	$params[] = $edit_user_id;
    	
    	// Include user_pass field if password was entered
    	$stmt = "UPDATE user Set user_fname = ?, user_lname = ?, user_name = ?, user_email = ?, ".$pass_stmt."
    			 user_dealer_id = ?, user_admin = ?, user_active = ?, user_type_id = ?, user_team_id = ? 
    			 WHERE user_id = ?";
    	
    	// Prepare the query statement
    	if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		// Execute the UPDATE instruction
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			return true;
		}
	}	
    
    public function deleteUserSetupRequest($array) {
    	// $array contains: 'row_ids' (array of all table rows id's that need to be deleted from user_request_setup table)
    	
    	// Establish easy access to table row ids
    	$row_ids = $array['row_ids'];
    	
    	// Prepare and execute stmt
    	$stmt = "DELETE FROM user_setup_request WHERE id = ?";
    	
    	if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		// Run the delete instruction for each table row id
		foreach($row_ids as $id) {
			$params = array($id);
			if(!($stmt->execute($params))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				$error = true;
			} else {
				$error = false;
			}	
		}
		return (!$error) ? true : false;
    }
}
?>
