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
	
	private function fetch_remote_file( $file ) {
		$path = parse_url( $file );

		if ($fs = @fsockopen($path['host'], isset($path['port'])?$path['port']:80)) {

			$header = "GET " . $path['path'] . " HTTP/1.0\r\nHost: " . $path['host'] . "\r\n\r\n";

			fwrite($fs, $header);

			$buffer = '';

			while ($tmp = fread($fs, 1024)) { $buffer .= $tmp; }

			preg_match('/HTTP\/[0-9\.]{1,3} ([0-9]{3})/', $buffer, $http);
			preg_match('/Location: (.*)/', $buffer, $redirect);

			if (isset($redirect[1]) && $file != trim($redirect[1])) { return self::fetch_remote_file(trim($redirect[1])); }

			if (isset($http[1]) && $http[1] == 200) { return substr($buffer, strpos($buffer, "\r\n\r\n") +4); } else { return false; }

		} else { return false; }

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
                    What are Service Notifications? They send out messages to different services like Twitter and Ping.fm to let your followers know of any new blog posts.
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
                            <p><input type="text" name="apikey" id="url" onfocus="if(this.value=='API/App Key')value=''" onblur="if(this.value=='')value='API/App Key';" value="API Key" /></p>
                            <p class="submit">
                                <input name="save" type="submit" value="Add Service" /> 
                                <input type="hidden" name="action" value="add" />
							</p>
						</form>
				</div>
<?php
	}
}
add_action ('admin_menu', array( 'NotifyAdmin', 'addNotifyMenu' ) );
NotifyAdmin::install();
?>