<?php
class NotifyAdmin
{
        
	/**
	* Here we install the tables and initial data needed to
	* power our special functions
	*/
	public function install() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$table= $wpdb->prefix . 'wik_notify';
				
		$q= '';
		if( $wpdb->get_var( "show tables like '$table'" ) != $table ) {
			$q= "CREATE TABLE " . $table . "( 
				id int NOT NULL AUTO_INCREMENT,
				service varchar(255) NOT NULL,
				username varchar(255) NULL,
				password varchar(255) NULL,
				apikey varchar(255) NULL,
                sortorder smallint(9) NOT NULL,
				UNIQUE KEY id (id)
			);";
		}
		if( $q != '' ) {
			dbDelta( $q );
		}			
	}
	
	public function check() {
		global $wpdb;
		$table= $wpdb->prefix . 'wik_notify';
		if( $wpdb->get_var( "show tables like '$table'" ) != $table ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function count() {
		global $wpdb;
		$table= $wpdb->prefix . 'wik_notify';
		$total= $wpdb->get_results( "SELECT ID as count FROM $table" );
		return $total[0]->count;
	}
	
	public function add( $_REQUEST ) {
		global $wpdb;
		
		$args= $_REQUEST;		
		$table= $wpdb->prefix . 'wik_notify';
		if( $args['service'] != 'Service Name' ) {
        if($args['apikey'] == "API Key") $args['apikey'] = "";
        if($args['username'] == "Username") $args['username'] = "";
        if($args['password'] == "Password") $args['password'] = "";
		if( !$wpdb->get_var( "SELECT id FROM $table WHERE service = '" . $args['service'] . "'" ) ) {
			$id= $wpdb->get_var( "SELECT sortorder FROM $table ORDER BY sortorder DESC LIMIT 1" );
			$new_id= ( $id + 1 );
			
			$i= "INSERT INTO " . $table . " (id,service,username,password,apikey,sortorder) VALUES('', '" 
				. $args['service'] . "','" 
				. $args['username'] . "','"
                . $args['password'] . "','"
                . $args['apikey'] . "',"
				. $new_id . ")";
			$query= $wpdb->query( $i );
		}
		}
	}
	
	public function collect() {
		global $wpdb;
		$table= $wpdb->prefix . 'wik_notify';
		$sources= $wpdb->get_results( "SELECT * FROM $table" );
		if( is_array( $sources ) ) {
			return $sources;
		} else {
			return array();
		}
	}
	
	public function gather( $id ) {
		global $wpdb;
		$table= $wpdb->prefix . 'wik_notify';
		$gather= $wpdb->get_results( "SELECT * FROM $table WHERE id= $id" );
		return $gather;
	}
	
	public function burninate( $id ) {
		global $wpdb;
		$table= $wpdb->prefix . 'wik_notify';
		$d= $wpdb->query( "DELETE FROM $table WHERE id = $id" );
		$trogdor= $wpdb->query( $d );
	}
	
	/**
	* Method to grab all of our lifestream data from the DB.
	* <code>
	* foreach( $sources->show_streams() as $stream ) {
	*	// do something clever
	* }
	* </code>
	*/
	public function show_notifications() {
		global $wpdb;
		$table= $wpdb->prefix . 'wik_notify';
		$show= $wpdb->get_results( "SELECT * FROM $table ORDER BY sortorder ASC" );
		return $show;
	}
	
	public function positions() {
		global $wpdb;
		$table= $wpdb->prefix . 'wik_notify';
		$numbers= $wpdb->get_results( "SELECT sortorder FROM $table ORDER BY sortorder ASC" );
		return $numbers;
	}
	
	public function sort( $_REQUEST ) {
		global $wpdb;
		$args= $_REQUEST;
		$table= $wpdb->prefix . 'wik_notify';
		$orig_sort= $wpdb->get_results( "SELECT sortorder FROM $table WHERE id= " . $args['id'] );
		$old_value= $orig_sort[0]->sortorder;
		if( $orig_sort ) {
			$bump_up= $wpdb->query( "UPDATE $table SET sortorder= sortorder + 1 WHERE sortorder > " . $args['newsort'] );
			$update= $wpdb->query( "UPDATE $table SET sortorder= ". ( $args['newsort'] + 1 ) . " WHERE id= " . $args['id'] );
			$bump_down= $wpdb->query( "UPDATE $table SET sortorder= sortorder -1 WHERE sortorder > " . $old_value );
		}
	}
	
	public function addNotifyMenu() {
		add_management_page( __('WicketPixie Notifications'), __('WicketPixie Notifications'), 9, basename(__FILE__), array( 'NotifyAdmin', 'notifyMenu' ) );
	}
	
	/**
	* The admin menu for our faves system
	*/
	public function notifyMenu() {
		$notify= new NotifyAdmin;
        $wp_notify = get_option('wp_notify');
		if ( $_GET['page'] == basename(__FILE__) ) {
	        if ( 'add' == $_REQUEST['action'] ) {
				$notify->add( $_REQUEST );
			}			
			elseif ( 'delete' == $_REQUEST['action'] ) {
				$notify->burninate( $_REQUEST['id'] );
			}
		}
		?>
		<?php if ( isset( $_REQUEST['add'] ) ) { ?>
		<div id="message" class="updated fade"><p><strong><?php echo __('Service added.'); ?></strong></p></div>
		<?php } ?>
			<div class="wrap">
			
				<div id="admin-options">
					<h2><?php _e('Service Notification Settings'); ?></h2>
                    <?php if($wp_notify != 1) { ?>
                    <b>WicketPixie Service Notifications are currently disabled, please go to the WicketPixie Options page to enable them.</b><br />
                    <?php } ?>
                    What are Service Notifications? They send out messages to different services like Twitter and Ping.fm to let your followers know of any new blog posts.<br />
                    Please note, when entering service details, you may only enter in a username and password, you may only enter an API/App key, or you may enter both.<br />
                    For Ping.fm, you'll only need to enter your App key. For Twitter, you need to enter a username and password.
					<?php if( $notify->check() != 'false' && $notify->count() != '' ) { ?>
					<table class="form-table" style="margin-bottom:30px;">
						<tr>
							<th>Service</th>
							<th style="text-align:center;">Username</th>
							<th style="text-align:center;" colspan="1">Actions</th>
						</tr>
					<?php 
						foreach( $notify->collect() as $service ) {
					?>		
						<tr>
							<td><?php echo $service->service; ?></td>
						   	<td style="text-align:center;"><?php echo $service->username; ?></td>
							<td style="text-align:center;">
							<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=notify.php&amp;delete=true&amp;id=<?php echo $service->id; ?>">
								<input type="submit" name="action" value="Delete" />
								<input type="hidden" name="action" value="delete" />
							</form>
							</td>
						</tr>
					<?php } ?>
					</table>
					<?php } else { ?>
						<p>You haven't added any services, add them here.</p>
					<?php } ?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=notify.php&amp;add=true" class="form-table">
							<h2>Add a Service</h2>
							<p><select name="service" id="title">
                            <option value="ping.fm">Ping.fm</option>
                            <option value="twitter">Twitter</option>
                            </select></p>
                            <p><input type="text" name="username" id="url" onfocus="if(this.value=='Username')value=''" onblur="if(this.value=='')value='Username';" value="Username" /></p>
                            <p><input type="text" name="password" id="url" onfocus="if(this.value=='Password')value=''" onblur="if(this.value=='')value='Password';" value="Password" /></p>
                            <p><input type="text" name="apikey" id="url" onfocus="if(this.value=='API/App Key')value=''" onblur="if(this.value=='')value='API/App Key';" value="API/App Key" /></p>
                            <p class="submit">
                                <input name="save" type="submit" value="Add Service" /> 
                                <input type="hidden" name="action" value="add" />
							</p>
						</form>
				</div>
<?php
	}
}
$wp_notify = get_option('wp_notify');
/**
* This gets called when a post gets published and
* prepares to notify all services listed in the database
*/
function prep_notify($id) {
    global $wpdb;
    $table = $wpdb->posts;
    $post['title'] = $wpdb->get_var("SELECT post_title FROM $table WHERE ID=$id");
    $post['link'] = get_permalink($id);
    $post['id'] = $id;
    
    // Developer API Keys DO NOT MODIFY FOR ANY REASON!
    $devkeys = array(
    "ping.fm" => "7cf76eb04856576acaec0b2abd2da88b"
    );
    
    notify($post,$devkeys);
    return $id;
}

/**
* This calls each services' notification function
*/
function notify($post,$devkeys) {
$notify = new NotifyAdmin();
    foreach($notify->collect() as $services) {
        if($services->service == 'ping.fm') {
            $errnum = notify_pingfm($post,$services->apikey,$devkeys['ping.fm']);
        }
        elseif($services->service == 'twitter') {
            $errnum = notify_twitter($post,$services);
        }
    }
}

/**
* Executes a cURL request and returns the output
*/
function notify_go($service,$type,$postdata,$ident) {
    if($service == 'ping.fm')
    {
        // Set the url based on type
        $url = "http://api.ping.fm/v1/".$type;
        
        // Setup cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        
        // Send the data and close up shop
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    }
    elseif($service == 'twitter')
    {
        // Tidy $postdata before sending it
        $postdata = urlencode(stripslashes(urldecode($postdata)));
        
        // Set the url based on type and add the POST data
        $url = "http://twitter.com/".$type."?status=".$postdata;
        
        // Setup cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERPWD, $ident['user'].":".$ident['pass']);
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // Send the data and fetch the HTTP code
        $output = curl_exec($ch);
        $outArray = curl_getinfo($ch);
        
        if($outArray['http_code'] == 200)
        {
            return 1;
        } else {
            return 0;
        }
    }
}

/**
* Ping.fm notification function
*/
function notify_pingfm($post,$appkey,$apikey) {
    // Message to be sent
    $message = $post['title'] . " ~ " . $post['link'];
    
    // First, we validate the user's app key
    $postdata = array('api_key' => $apikey, 'user_app_key' => $appkey);
    $apicall = "user.validate";
    $output = notify_go('ping.fm',$apicall,$postdata,NULL);
    
    if(preg_match("/(<rsp status=\"OK\">)/",$output))
    {
        // Okay, app key validated, now we can continue
        $postdata = array('api_key' => $apikey, 'user_app_key' => $appkey, 'post_method' => 'status', 'body' => $message);
        $apicall = "user.post";
        $output = notify_go('ping.fm',$apicall,$postdata,NULL);
        $success = preg_match("/(<rsp status=\"OK\">)/",$output);
        return $success;
    }
}

/**
* Twitter notification function
*/
function notify_twitter($post,$dbdata) {
    // Message to be sent
    $message = $post['title'] . " ~ " . $post['link'];
    
    // Put username and password into an array for easier passing
    $ident = array("user" => $dbdata->username,"pass" => $dbdata->password);
    
    // Choose update format (update.xml or update.json)
    $type = "statuses/update.xml";
    
    $success = notify_go('twitter',$type,$message,$ident);
    return $success;
}

add_action ('admin_menu', array( 'NotifyAdmin', 'addNotifyMenu' ) );
if($wp_notify == 1)
{
    add_action ('publish_post', 'prep_notify');
}
NotifyAdmin::install();
?>