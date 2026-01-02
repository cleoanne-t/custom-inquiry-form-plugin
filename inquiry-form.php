<?php
/*
Plugin Name: Inquiry Form
Plugin URI: http://localhost/inquiry-form
Description: A simple inquiry form plugin that saves data to a database with CSS example. Allows admin to mark inputs as complete. Grid layout.
Version: 1.0
Author: Cleo Anne Tabacolde
Author URI: http://localhost
*/

// Create table on plugin activation
function inquiry_form_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'inquiry_form';

    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        firstName VARCHAR(30) NOT NULL,
        lastName VARCHAR(30) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(25) NOT NULL,
        workshop VARCHAR(25) NOT NULL,
        campus VARCHAR(25) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        PRIMARY KEY (id)
    )";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}

// register the activation hook
register_activation_hook(__FILE__, 'inquiry_form_install');



// Form creation function (`inquiry_form`): Generates the HTML for the form.
function inquiry_form()
{
    $content = '';
    // Check for the 'submitted' parameter to display a thank-you message
    if (isset($_GET['submitted']) && $_GET['submitted'] == 'yes') {
        $content = '<div>Thank you for your submission!</div>';
    } else {
        // $action_url = esc_url($_SERVER['REQUEST_URI']); // Get the current URL
        $action_url = admin_url('admin-post.php');

        // Generate the form HTML
        $content = <<<FORM_CONTENT
        <form action="{$action_url}" method="post" class="grid-form">
            <input type="hidden" name="action" value="handle_inquiry_form">
            <label for="firstName">First Name:</label>
            <input type="text" id="firstName" name="firstName" required>
            <label for="lastName">Last Name:</label>
            <input type="text" id="lastName" name="lastName" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" required>
            <label for="campus">Which campus are you interested in? :</label>
            <select id="campus" name="campus" required>
                <option value="">Please select...</option>
                <option value="baltimore">Baltimore</option>
                <option value="towson">Towson</option>
                <option value="rockville">Rockville</option>
            </select>
            <label for="workshop">Which workshop would you like to learn more about?:</label><br>   
            <textarea id="workshop" name="workshop" rows="4" cols="100">Type here...</textarea>           

            <input type="submit" value="Submit">
        </form>
        FORM_CONTENT;
    }
    return $content;
}

// Add/register the shortcode [custom-inquiry-form] to display the form
add_shortcode('custom-inquiry-form', 'inquiry_form');



// Form submission function (`inquiry_form_handler`)
// This function handles form submission and saves data to the database.
function inquiry_form_handler()
{
    // Only proceed if the specific fields for our form are present
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inquiry_form';
        // $table_name = $wpdb->prefix . 'simple_form';

        $firstName = sanitize_text_field($_POST['firstName']);
        $lastName = sanitize_text_field($_POST['lastName']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $campus = sanitize_text_field($_POST['campus']);
        $workshop =  sanitize_text_field($_POST['workshop']);

        // Ensure the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

        if ($table_exists) {
            $wpdb->insert(
                $table_name,
                [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'campus' => $campus, 
                    'workshop' => $workshop
                ]
            );

            // Redirect to avoid form resubmission issues
            $redirect_url = add_query_arg(
                'submitted',     // Add the'submitted' parameter to the URL
                'yes',           // Set the value of the'submitted' parameter to 'yes'
                wp_get_referer() // Get the URL the user came from
            );

            //send email
            $message = "Thank you for your interest in our Workshops!";
            $subject = "Confirmation Message";
            wp_mail($email, $subject, $message);

            wp_redirect($redirect_url); // Redirect to the URL the user came from
            exit;
        }
    }
}

// Register the admin_post_handle_inquiry_form action hook to handle form submissions
add_action('admin_post_handle_inquiry_form', 'inquiry_form_handler');



// Function to enqueue/add CSS styles
function inquiry_form_styles()
{
    wp_enqueue_style('inquiry-form-css', plugins_url('inquiry-form.css', __FILE__));
}

// Add the form CSS to the wp_enqueue_scripts hook
add_action('wp_enqueue_scripts', 'inquiry_form_styles');


function inquiry_form_menu(){
    add_menu_page(
        'Inquiry Form Settings', //Page title
        'Inquiry Form', //Menu Title
        'manage_options', 
        'inquiry-form', //URL
        'inquiry_form_settings_page',//Callback function
        '', //Default Icon
        20 //Position on Menu Bar
    );
}

add_action('admin_menu', 'inquiry_form_menu');

//callback function
function inquiry_form_settings_page(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'inquiry_form';
    // $table_name = $wpdb->prefix . 'simple_form';

    if(isset($_GET['status'])){
        $id = intval($_GET['status']);
        //$wpdb->update($table_name,['status'=> 1])
        $complete = 'complete';
        $wpdb->update($table_name, ['status' => $complete], ['id' => $id]);
    }

    $result = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    echo '<div class="wrap"><h1>Inquiry Form Submissions</h1>';

    if($result){
        echo '<table class="widefat fixed">';
        echo '<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th>
        <th>Campus</th><th>Workshop</th><th>Created At</th><th>Status</th><th>Action</th></tr>';
        foreach ($result as $row){
            echo "<tr>";
            echo "<td>$row->id</td>";
            echo "<td>$row->firstName</td>";
            echo "<td>$row->lastName</td>";
            echo "<td>$row->email</td>";
            echo "<td>$row->phone</td>";
            echo "<td>$row->campus</td>";
            echo "<td>$row->workshop</td>";
            echo "<td>$row->created_at</td>";
            echo "<td> $row->status</td>";
            echo "<td>";
           if($row->status == 'pending'){
            echo '<a href="'.admin_url('admin.php?page=inquiry-form&status='.$row->id).'">Mark Complete</a>';
           }else{
            echo 'Complete';
           }
            echo "</td>";
            echo "</tr>";
        }
        echo '</table>';
    }else{
        echo '<p>No submissions found.</p>';
    }
    echo '</div>';
}