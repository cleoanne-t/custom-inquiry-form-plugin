# Custom Inquiry Form Plugin

## Description
A simple inquiry form plugin that saves data to a database with CSS example. Allows admin to mark inputs as complete. Grid layout.
```
/*
Plugin Name: Inquiry Form
Plugin URI: http://localhost/inquiry-form
Description: A simple inquiry form plugin that saves data to a database with CSS example. Allows admin to mark
inputs as complete. Grid layout.
Version: 1.0
Author: Cleo Anne Tabacolde
Author URI: http://localhost
*/
```

## Code Explanation

**Form Handling**: The `inquiry_form_handler` function checks if the form has been submitted by verifying the request method and the presence of a specific field in the POST data. This helps prevent unintended execution during other POST requests. It then sanitizes the input data to prevent security vulnerabilities and inserts the data into the database. Finally, it redirects the user to avoid duplicate submissions upon refreshing the page.

**Data Validation and Insertion**: After sanitizing the input data to prevent security
vulnerabilities, the function inserts the data into the specified table. It then redirects the user to avoid duplicate submissions upon refreshing the page.

**Redirection**: After successfully processing the form, the function redirects the user to the same page with a query parameter (submitted=yes) that can be used to display a confirmation message or similar feedback.

## The Code/functions

### 1. Database Table Creation function (`inquiry_form_install`)

- Sets up a custom database table upon plugin activation to store form submissions essential for data management and retrieval.

```
function inquiry_form_install(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'inquiry_form';

    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firstName VARCHAR(30) NOT NULL,
        lastName VARCHAR(30) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(25) NOT NULL,
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

```

### 2. Form creation function (`inquiry_form`)

- This function first checks for the `submitted` parameter to display a thank-you message. Otherwise, it generates the form.

- In this example, to generate the form, we are using a special feature in PHP called Heredocs. Heredocs allow you to create a multi-line string without using quotes or escaping characters.

- Example heredoc syntax: <<<FORM_CONTENT some literal content FORM_CONTENT;

```
// generates the HTML for the form.
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
        <h1>Assignment 4</h1>
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
            select id="campus" name="campus" required>
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

```

### 3. Shortcode Registration (`add_shortcode`)

- This function registers the shortcode [*my-simple-form*] to display the form.
  We can use the shortcode to embed this form within posts or pages, making it flexible and easy to use across the site.

```
// Add/register the shortcode [custom-inquiry-form] to display the form
add_shortcode('custom-inquiry-form', 'inquiry_form');
```

### 4. Form Handling function (`inquiry_form_handler`)

- This function handles the form submission and saves data to the database.

- It handles the server-side processing of the form including sanitizing and inserting data into the database.

```
// Form submission function (`inquiry_form_handler`)
// This function handles form submission and saves data to the database.
function inquiry_form_handler()
{
    // Only proceed if the specific fields for our form are present
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'itec_simple_form';
        // $table_name = $wpdb->prefix . 'simple_form';

        $firstName = sanitize_text_field($_POST['firstName']);
        $lastName = sanitize_text_field($_POST['lastName']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        //add campus

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
                    'campus' => $campus
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
// Register the admin_post_handle_itec_simple_form action hook to handle form submissions
add_action('admin_post_handle_inquiry_form', 'inquiry_form_handler');

```

### 5. Adding CSS to the plugin: Enqueue Styles Function (`inquiry_form_styles`)

- Add CSS styles for the form layout, improving the form's visual appearance and usability on the front-end.

- This function adds the CSS styles to the `wp_enqueue_scripts` hook.

```
// Function to enqueue/add CSS styles
function inquiry_form_styles()
{
    wp_enqueue_style('inquiry-form-css', plugins_url('inquiry-form.css', __FILE__));
}

// Add the form CSS to the wp_enqueue_scripts hook
add_action('wp_enqueue_scripts', 'inquiry_form_styles');

```

### 6. Adding a menu option for admin: inquiry_form_menu Function
- Add a menu option on admin page to view all submitted inquiries with information. 

function inquiry_form_menu(){
    add_menu_page(
        'Inquiry Form Settings', //Page title
        'Inquiry Form', //Menu Title
        'manage_options', 
        'inquiry-form', //URL
        'inquiry_form_settings_page',//Callback function
        '', //Default Icon
        20 // Position on Menu bar
    );
}

add_action('admin_menu', 'inquiry_form_menu');

### 7. Formatting Settings page for Inquiry Form: inquiry_form_settings_page Function

- Creates table that display information from submissions. Admins will also be able to mark submissions as complete.

function inquiry_form_settings_page(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'inquiry_form';
    // $table_name = $wpdb->prefix . 'simple_form';

    $result=$wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

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
            echo "<td>$row->is_complete ?'Complete':'Pending'.</td>";
            echo "<td>";
           if(!$row->is_complete){
            echo '<a href="'.admin_url('admin.php?page=sfc-submissions&mark_complete='.$row->id).'">Mark Complete</a>';
           }else{
            echo '-';
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

### CSS for the Form (`inquiry-form-style.css`)

- The form-style.css contains the styles necessary to utilize CSS Grid for the layout, ensuring the form is responsive and well-structured on various devices.

- Create a CSS file and name it: inquiry-form-style.css and put the CSS code below in it.

```
/*
This CSS provides the styles necessary to utilize
CSS Grid for the layout, ensuring the form is responsive
and well-structured on various devices.
*/

.grid-form {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 10px;
    align-items: center;
}

.grid-form label {
    grid-column: 1 / 2;
}

.grid-form input[type="text"],
.grid-form input[type="email"],
.grid-form input[type="submit"] {
    grid-column: 2 / 3;
}
```



