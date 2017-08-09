<?php
/*
Plugin Name: G_Buzz_Button
Plugin URI: http://www.muzhiks.com/google_buzz_button_wordpress
Description: It adds Google buzz button to your post, and counts number of clicks.
Version: 1.1.1	
Author: Andrew Aronsky
Author URI: http://www.muzhiks.com/
*/
//ini_set('display_errors',1);
//error_reporting(E_ALL);

global $wpdb;
$wpdb->g_buzz = $wpdb->prefix.'g_buzz';

define("G_BUZZ_BUTTON","1.1.1",false);
register_activation_hook( __FILE__, 'activate_g_buzz' );
register_deactivation_hook(__FILE__, 'deactivate_g_buzz' );

global $google_buzz_options;	
$google_buzz_options = get_option('google_buzz_options');

if ( is_admin() )
	{ 
	add_action('admin_menu', 'google_buzz_settings');
	add_action( 'admin_init', 'register_google_buzz_settings' ); 
	}

function g_buzz_url( $path = '' ) 
	{
	global $wp_version;
	if ( version_compare( $wp_version, '2.8', '<' ) ) 
		{ 
		$folder = dirname( plugin_basename( __FILE__ ) );
		if ( '.' != $folder )
			$folder.=$path;
		return plugins_url( $folder );
		}
	return plugins_url( $path, __FILE__ );
	}

function activate_g_buzz() 
	{
	global $wpdb;
	global $google_buzz_options;
	$google_buzz_options = array(	'show_counter'=>'on',
									'location'=>'before',
									'allign'=>'right',
									'size'=>'big',
									'icon'=>'g_buzz_icon_1',
									'show_shadow'=>'on',
									'custom_acss'=>'color: #fff; padding: 11px 0px; font-size: 14px;' ,
									'custom_css'=>NULL);
							   
	add_option('google_buzz_options',$google_buzz_options);

	$sql = "CREATE TABLE $wpdb->g_buzz (id bigint(20) NOT NULL AUTO_INCREMENT, 
										post_id bigint(20) NOT NULL, 
										post_counter int(20) NOT NULL,
										post_id_log longtext NOT NULL,
										UNIQUE KEY id (id));";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	}

function deactivate_g_buzz () 
	{
	global $wpdb;
	delete_option('google_buzz_options');
	}

function add_new_field ($post_id)
	{
	global $wpdb;
	$sql = "INSERT INTO $wpdb->g_buzz (`id`, `post_id`, `post_counter`) VALUES (NULL, " . $post_id . ", '0');";
	$wpdb->query($sql);
	return 0;
	}

function get_count ($post_id)
	{
	global $wpdb;
	$get_count = $wpdb->get_var("SELECT post_counter FROM $wpdb->g_buzz WHERE post_id = " . $post_id . " ");
	return $get_count;
	}
	

function generate_button ($post_id) 
	{
	global $google_buzz_options, $post;
		$p_title = get_the_title($post_id);
		$count = get_count ($post_id);
		if ($google_buzz_options['show_shadow'] == 'on')
			{
			$image = g_buzz_url("/images/" . $google_buzz_options['icon'] . "_sc.png");
			}
		else
			{
			$image = g_buzz_url("/images/" . $google_buzz_options['icon'] . ".png");
			$atext = NULL;
			}
		if ($google_buzz_options['show_counter'] == 'on')
			{
			$atext = get_count ($post_id);
			}
		else
			{
			$atext = NULL;
			}
		$g_buzz_url = 'http://www.google.com/reader/link?url='.get_permalink($post_id).'&title='.str_replace(' ','+',$p_title).'&srcURL='.get_bloginfo( 'url' ).'';
		$css = 'float: '.$google_buzz_options['allign'].'; '.$google_buzz_options['custom_css'].'';
		$a_css = 'width: 50px; height: 58px; display: block; background: url('.$image.') no-repeat; text-align: center; text-decoration: none; '.$google_buzz_options['custom_acss'].'';
		$google_buzz_button = '<div style="' . $css . '">
			<a	style="' . $a_css. '" href="" onclick="openWindow()" />' .$atext. '</a>
				<script language="JavaScript">
				function openWindow() {
					var leftvar = (screen.width-500)/2;
					var topvar = (screen.height-400)/2;
					myWin = window.open("' .g_buzz_url('/google_buzz_counter.php'). '?link=' .$g_buzz_url. '&pid=' .$post_id. '", "displayWindow", "width=500,height=400,left="+leftvar+",top="+topvar+",status=no,toolbar=no,menubar=no");
				}
				</script>
			</div>';
		return $google_buzz_button;
	}
	
function add_google_buzz_button_automatic($content)
	{ 
	global $google_buzz_options, $post, $g_buzz_url, $wpdb;
	if (($number = get_count($post->ID)) == NULL)
		{
		add_new_field ($post->ID);
		$number = 0;
		}
	$gbb = generate_button ($post->ID);
	if (is_single())
		{ 
		if($google_buzz_options['location'] == 'before' )
			{
			$content = $gbb.$content;
			}
		else
			{
			$content = $content.$gbb;
			}
		}
	return $content;
	}





function add_google_buzz_button()
	{
	global $google_buzz_options, $post, $g_buzz_url, $wpdb;
	if (($number = get_count($post->ID)) == NULL)
		{
		add_new_field ($post->ID);
		$number = 0;
		}
	$gbb = generate_button ($post->ID);
	echo $gbb;
	}
	
	
function register_google_buzz_settings() 
	{
	if (function_exists('register_setting'))
		{
		register_setting( 'google_buzz_options_group', 'google_buzz_options' );
		return TRUE;
		}
	else
		{
		if (function_exists('add_option_update_handler'))
			{
			add_option_update_handler ( 'google_buzz_options_group', 'google_buzz_options' );
			return TRUE;
			}
		}
	return FALSE;
	}

function google_buzz_settings() 
	{
	add_options_page('Google Buzz', 'Google Buzz', 9, basename(__FILE__), 'google_buzz_options_form');
	}

function google_buzz_options_form()
	{ 
	global $google_buzz_options;

?>
	<div class="wrap">
		<form method="post" action="options.php">
			<?php wp_nonce_field('update-options'); ?>
			<h2>G_Buzz Button Options</h2> 
				<table class="form-table">
				
					<tr valign="top">
						<th scope="row">Show counter</th>
						<td>
							<select name="google_buzz_options[show_counter]" id="location" >
								<option value="on" <?php if ($google_buzz_options['show_counter'] == "on"){ echo "selected";}?> >On</option>	
								<option value="off" <?php if ($google_buzz_options['show_counter'] == "off"){ echo "selected";}?> >Off</option>
							</select>
							(You can disable mapping of quantity of additions, but statistics will be carried on.)
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row">Align</th>
						<td>
							<select name="google_buzz_options[allign]" id="location" >
								<option value="left" <?php if ($google_buzz_options['allign'] == "left"){ echo "selected";}?> >Left</option>	
								<option value="right" <?php if ($google_buzz_options['allign'] == "right"){ echo "selected";}?> >Right</option>
								<option value="none" <?php if ($google_buzz_options['allign'] == "none"){ echo "selected";}?> >None</option>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Location of the button</th>
						<td>
							<select name="google_buzz_options[location]" id="location" >
								<option value="before" <?php if ($google_buzz_options['location'] == "before"){ echo "selected";}?> >Before Content</option>
								<option value="after" <?php if ($google_buzz_options['location'] == "after"){ echo "selected";}?> >After Content</option>
								<option value="manual" <?php if ($google_buzz_options['location'] == "manual"){ echo "selected";}?> >Manual Insertion</option>
							</select>
							(Use template tag <code>add_google_buzz_button();</code> for Manual Insertion)
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row">Custom CSS</th>
						<td>
							<input type="text" name="google_buzz_options[custom_css]" id="item_name" class="regular-text code" value="<?php echo $google_buzz_options['custom_css']; ?>" />
							(Input custom CSS-styles for DIV element. Example: <code>margin-left: 100px; border: 1px solid #CCCCCC; </code>)
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Custom text CSS</th>
						<td>
							<input type="text" name="google_buzz_options[custom_acss]" id="item_name" class="regular-text code" value="<?php echo $google_buzz_options['custom_acss']; ?>" />
							(Input custom CSS-styles for text. Example: <code>color: #fff; padding: 6px 0px; </code>)
							Warning: If the counter text is located not in the image centre - change value <code>padding: </code>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row">Select icon</th>
						<td>
							<input name="google_buzz_options[icon]" type="radio" value="g_buzz_icon_1" <?php checked('g_buzz_icon_1', $google_buzz_options[icon]); ?> class="tog"/>
							&nbsp;<img src="<?php echo g_buzz_url(); ?>/images/g_buzz_icon_1.png" alt="g_buzz_icon_1" /> &nbsp;<img src="<?php echo g_buzz_url(); ?>/images/g_buzz_icon_1_sc.png" alt="g_buzz_icon_1_sc" />
						</td>
					</tr>					
					<tr valign="top">
						<th scope="row"></th>						
						<td>
							<input name="google_buzz_options[icon]" type="radio" value="g_buzz_icon_2" <?php checked('g_buzz_icon_2', $google_buzz_options[icon]); ?> class="tog"/>
							&nbsp;<img src="<?php echo g_buzz_url(); ?>/images/g_buzz_icon_2.png" alt="g_buzz_icon_2" /> &nbsp;<img src="<?php echo g_buzz_url(); ?>/images/g_buzz_icon_2_sc.png" alt="g_buzz_icon_2_sc" />
							( Do not forget to change a text colour to dark if this icon is selected.)
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"></th>			
						<td>
							<input name="google_buzz_options[icon]" type="radio" value="g_buzz_icon_3" <?php checked('g_buzz_icon_3', $google_buzz_options[icon]); ?> class="tog"/>
							&nbsp;<img src="<?php echo g_buzz_url(); ?>/images/g_buzz_icon_3.png" alt="g_buzz_icon_3" /> &nbsp;<img src="<?php echo g_buzz_url(); ?>/images/g_buzz_icon_3_sc.png" alt="g_buzz_icon_3_sc" />
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row">Show shadow</th>
						<td>
							<select name="google_buzz_options[show_shadow]" id="location" >
								<option value="on" <?php if ($google_buzz_options['show_shadow'] == "on"){ echo "selected";}?> >On</option>	
								<option value="off" <?php if ($google_buzz_options['show_shadow'] == "off"){ echo "selected";}?> >Off</option>
							</select>
						</td>
					</tr>

					
				</table>
				<p class="submit">
					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="page_options" value="google_buzz_options" />
					<input type="submit" name="update" value="Submit">
				</p>
		</form>

	</div>
<?php }

		
if ($google_buzz_options['location'] != 'manual')
	{
	add_filter('the_content','add_google_buzz_button_automatic'); 
	}
	
?>