<?php

/**
 * Provide a dashboard view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.3
 *
 * @package    Custom_Widget_Area
 * @subpackage Custom_Widget_Area/admin/partials
 */
/**
* 
*/
class CWA_view
{
	
	public function __construct()
	{
		# code...
	}
	public function cwa_settings_page(){
		add_action( 'add_meta_boxes', self::displayView());
	}
	public function displayView(){

		
		global $purl;
		?>
		
		<div class="wrap">
 
            <div id="icon-themes" class="icon32"><br /></div>
 
            <h2><?php _e( 'Custom widget area', 'wp-custom-widget-area' ); ?></h2>

			<div class="welcome-panel custom-wp">
				<div class="col col-8">
					<?php 
						self::widgetForm();
					?>
				</div>
				<div class="col col-4">
					<div class="how-to">
						<h3>How to use?</h3>
						<p>
							<ol class="list">
								<li>Create a new Widget area.</li>
								<li>Click on the "get code" link.</li>
								<li>Copy the code and Paste it in a wordpress theme where you want to display it.</li>
							</ol>
						</p>
						<br/>
						<h4 style="margin-top: 0;">How to Use it in page or post content?</h4>
						<p>
							<ol class="list">
								<li>Click on the "get shortcode" link form widget area table below.</li>
								<li>Copy the shortcode and Paste it in a post or page editor in which you want it to display it.</li>
							</ol>
						</p>	
						<br/>
						<h4 style="margin-top: 0;">How to customize widget style?</h4>
						<p>
							<ol class="list">
								<li>Click on the advance link while creating new widget area and add widget class.</li>
								<li>Add custom css targeting your widget area class. i.e. <br>
								<code>
									.mynewwidgetareaclass a{
										color: red;
									} 
								</code><br>
								at the bottom of your style.css 
								where ".mynewwidgetareaclass" is your widget area class.
								</li>
							</ol>
						</p>	
					</div>
				</div>
				
			
			</div>
		</div>
		<div class="cwa-error" style="display:none;">
			
		</div>
		<div id="cwa-table-wrap">
		<?php
			self::widgetTable();
		?>
		</div>
		<?php 
	}
	public function widgetForm(){
		?>
		<form class="cwa-form" method="post" action="" id="cwa-form">
			<input type="hidden" name="id">
			<div class="basic">
				<div class="cwa-form-row">
					<label  class="cwa-form-label">Name </label><input type="text" name="cwa_name" placeholder="Widget area name" required>	<span class="cwa-form-message"></span>
				</div>
				<div class="cwa-form-row">
					<label class="cwa-form-label">Id </label><input type="text" name="cwa_id" placeholder="Widget area id" required><span class="cwa-form-message"></span>
				</div>
				<div class="cwa-form-row">
					<label class="cwa-form-label">Description</label><input type="text" name="cwa_description" placeholder="Description"><span class="cwa-form-message"></span>
				</div> 
					
			</div>
			<div class="advanced hide">
				<div class="cwa-form-row">
					<label class="cwa-form-label">Widget class</label><input type="text" name="cwa_widget_class" placeholder="Class"><span class="cwa-form-message"></span>
				</div>
				<div class="cwa-form-row">
					<label class="cwa-form-label">Before/After widget </label>
					<select name="cwa_widget_wrapper">
						<option selected value="li">li</option>
						<option value="div">div</option>
						<option value="aside">aside</option>
						<option value="span">span</option>
					</select>
					<span class="cwa-form-message"></span>
				</div>
				<div class="cwa-form-row">
					<label class="cwa-form-label">Widget title class</label><input type="text" name="cwa_widget_header_class" placeholder="Class"><span class="cwa-form-message"></span>
				</div>
				<div class="cwa-form-row">
					<label class="cwa-form-label">Before/After widget title </label>
					<select name="cwa_widget_header_wrapper">
						<option value="h1">h1</option>
						<option selected value="h2">h2</option>
						<option value="h3">h3</option>
						<option value="h4">h4</option>
						<option value="h5">h5</option>
						<option value="h6">h6</option>
					</select>
					<span class="cwa-form-message"></span>
				</div>
				
			</div>

			<div class="cwa-form-row">
					<a href="#" id="cwa-advance-btn">Advanced</a>
				</div> 	
			<div class="cwa-form-row">
				<label class="cwa-form-label"> </label><input type="submit" name="create" value="Create" class="cwa-btn cwa-btn-primary"> <input type="reset" value="Cancel" name="cancel" class="cwa-btn cwa-btn-danger">	
			</div>
		</form>

		<?php
	}
	public function widgetTable(){
		$data = self::getWidgetData();
		//var_dump($data);
		?>
		<table class="cwa-table">
			<thead>
				<tr>
					<th>Sn</th>
					<th>Name</th>
					<th>Id</th>
					<th>Description</th>
					<td>Widget class</th>
					<td>Widget header class</th>
					<th></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php 
				$count = 1;
				foreach ($data as $table) {
					# code...
					?>
					<tr>
						<td><?php echo $count ?></td>
						<td><?php echo $table->cwa_name; ?></td>
						<td><?php echo $table->cwa_id; ?></td>
						<td><?php echo $table->cwa_description; ?></td>
						<td><?php echo $table->cwa_widget_class; ?></td>
						<td><?php echo $table->cwa_widget_header_class; ?></td>
						<td><a href="#get_shortcode" data-id="<?php echo $table->cwa_id; ?>" class="cwa-detail-link tooltip" title="[cwa id='<?php echo $table->cwa_id; ?>']">Get shortcode</a> </td>
						<td><a href="#get_code" data-id="<?php echo $table->cwa_id; ?>" class="cwa-detail-link tooltip" title="dynamic_sidebar( '<?php echo $table->cwa_id; ?>' );">Get code</a> / <a href="#delete" data-id="<?php echo $table->cwa_id; ?>" class="cwa-delete-link">Delete</a></td>
					</tr>
					<?php
					$count++;
				}
				?>
			</tbody>
		</table>
		<?php
		if(isset($_POST['action']))
			die();
	}

	public function getWidgetData(){
		global $wpdb, $table_name;

		$sql = "SELECT * FROM $table_name";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$row = $wpdb->get_results( $sql, 'OBJECT');
		return $row;
	}

}
?>
