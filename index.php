<!--<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Huntington Robotics is FIRST Team 5016, and has developed a job system to organize and streamline the process of the delegation of tasks on the team. It also provides a way to easily log team member activities.">
    <meta name="author" content="Huntington Robotics">

    <title>Login | Huntington Robotics Job Manager</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/freelancer.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="http://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
    <link href="http://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic" rel="stylesheet" type="text/css">
	<link rel="shortcut icon" href="http://team5016.com/favicon.ico" type="image/x-icon">
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
	
	<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		  ga('create', 'UA-68349714-1', 'auto');
		  ga('send', 'pageview');
	</script>

<!--</head>

<body id="page-top" class="index">
    <section id="signup">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
					<!--<a href="http://team5016.com/"><img class="img-responsive" src="http://team5016.com/img/profile.png" width="400" alt=""></a><br />
                    <hr>
                    <h2 class="align-left">Jobs</h2><br /><br />
                </div>
            </div>
            <div class="row text-left">
				<div class="col-lg-12">-->
					<?php
					
					error_reporting(0);
					
					class OneFileLoginApplication
					{
						/**
						 * @var string Type of used database (currently only SQLite, but feel free to expand this with mysql etc)
						 */
						private $db_type = "sqlite"; //

						/**
						 * @var string Path of the database file (create this with _install.php)
						 */
						private $db_sqlite_path = "./data.db";

						/**
						 * @var object Database connection
						 */
						private $db_connection = null;

						/**
						 * @var bool Login status of user
						 */
						private $user_is_logged_in = false;

						/**
						 * @var string System messages, likes errors, notices, etc.
						 */
						public $feedback = "";


						/**
						 * Does necessary checks for PHP version and PHP password compatibility library and runs the application
						 */
						public function __construct()
						{
							if ($this->performMinimumRequirementsCheck()) {
								$this->runApplication();
							}
						}

						/**
						 * Performs a check for minimum requirements to run this application.
						 * Does not run the further application when PHP version is lower than 5.3.7
						 * Does include the PHP password compatibility library when PHP version lower than 5.5.0
						 * (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
						 * @return bool Success status of minimum requirements check, default is false
						 */
						private function performMinimumRequirementsCheck()
						{
							if (version_compare(PHP_VERSION, '5.3.7', '<')) {
								echo "Sorry, Simple PHP Login does not run on a PHP version older than 5.3.7 !";
							} elseif (version_compare(PHP_VERSION, '5.5.0', '<')) {
								require_once("libraries/password_compatibility_library.php");
								return true;
							} elseif (version_compare(PHP_VERSION, '5.5.0', '>=')) {
								return true;
							}
							// default return
							return false;
						}

						/**
						 * This is basically the controller that handles the entire flow of the application.
						 */
						public function runApplication()
						{
							// start the session, always needed!
							$this->doStartSession();
							// check for possible user interactions (login with session/post data or logout)
							$this->performUserLoginAction();
							// show "page", according to user's login status
							if ($this->getUserLoginStatus()) {
								$this->showPageLoggedIn();
							} else {
								$this->showPageLoginForm();
							}
						}

						/**
						 * Creates a PDO database connection (in this case to a SQLite flat-file database)
						 * @return bool Database creation success status, false by default
						 */
						private function createDatabaseConnection()
						{
							try {
								$this->db_connection = new PDO($this->db_type . ':' . $this->db_sqlite_path);
								return true;
							} catch (PDOException $e) {
								$this->feedback = "PDO database connection problem: " . $e->getMessage();
							} catch (Exception $e) {
								$this->feedback = "General problem: " . $e->getMessage();
							}
							return false;
						}

						/**
						 * Handles the flow of the login/logout process. According to the circumstances, a logout, a login with session
						 * data or a login with post data will be performed
						 */
						private function performUserLoginAction()
						{
							if (isset($_GET["action"]) && $_GET["action"] == "logout") {
								$this->doLogout();
							} elseif (!empty($_SESSION['student_id']) && ($_SESSION['user_is_logged_in'])) {
								$this->doLoginWithSessionData();
							} elseif (isset($_POST["login"])) {
								$this->doLoginWithPostData();
							}
						}

						/**
						 * Simply starts the session.
						 * It's cleaner to put this into a method than writing it directly into runApplication()
						 */
						private function doStartSession()
						{
							session_start();
						}

						/**
						 * Set a marker (NOTE: is this method necessary ?)
						 */
						private function doLoginWithSessionData()
						{
							$this->user_is_logged_in = true; // ?
						}

						/**
						 * Process flow of login with POST data
						 */
						private function doLoginWithPostData()
						{
							if ($this->checkLoginFormDataNotEmpty()) {
								if ($this->createDatabaseConnection()) {
									$this->checkPasswordCorrectnessAndLogin();
								}
							}
						}

						/**
						 * Logs the user out
						 */
						private function doLogout()
						{
							$_SESSION = array();
							session_destroy();
							$this->user_is_logged_in = false;
							$this->feedback = "You were just logged out.";
						}

						/**
						 * The registration flow
						 * @return bool
						 */
						private function doRegistration()
						{
							if ($this->checkRegistrationData()) {
								if ($this->createDatabaseConnection()) {
									$this->createNewUser();
								}
							}
							// default return
							return false;
						}

						/**
						 * Validates the login form data, checks if username and password are provided
						 * @return bool Login form data check success state
						 */
						private function checkLoginFormDataNotEmpty()
						{
							if (!empty($_POST['student_id'])) {
								return true;
							} else {
								$this->feedback = "You must input a student ID to log in";
							}
							// default return
							return false;
						}

						/**
						 * Checks if user exits, if so: check if provided password matches the one in the database
						 * @return bool User login success status
						 */
						private function checkPasswordCorrectnessAndLogin()
						{
							// remember: the user can log in with username or email address
							$sql = 'SELECT student_id, firstname, lastname
									FROM students
									WHERE student_id = :student_id
									LIMIT 1';
							$query = $this->db_connection->prepare($sql);
							$student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_NUMBER_INT);
							$query->bindValue(':student_id', $student_id);
							$query->execute();

							// If you meet the inventor of PDO, punch him.
							$result_row = $query->fetchObject();
							if ($result_row) {
								// write user data into PHP SESSION [a file on your server]
								$_SESSION['firstname'] = $result_row->firstname;
								$_SESSION['lastname'] = $result_row->lastname;
								$_SESSION['student_id'] = $result_row->student_id;
								$_SESSION['user_is_logged_in'] = true;
								$this->user_is_logged_in = true;
								return true;
							} else {
								$this->feedback = "Your student ID was not found in the database. Please contact a team leader.<br />If you are a team leader, please login <a href='/leader/'>here</a>.";
							}
							// default return
							return false;
						}
						
						private function acceptJob()
						{
							$sql = 'UPDATE jobs SET job_accepted = 1, time_accepted = :date WHERE job_id = :job_id AND student_id = :student_id';
							$query = $this->db_connection->prepare($sql);
							$query->bindValue(':date', date('Y-m-d H:i:s'));
							$job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
							$query->bindValue(':job_id', $job_id);
							$query->bindValue(':student_id', $_SESSION['student_id']);
							$query->execute();
						}
						
						private function markJobComplete()
						{
							$sql = 'UPDATE jobs SET job_complete = 1, time_completed = :date WHERE job_id = :job_id AND student_id = :student_id';
							$query = $this->db_connection->prepare($sql);
							$query->bindValue(':date', date('Y-m-d H:i:s'));
							$job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
							$query->bindValue(':job_id', $job_id);
							$query->bindValue(':student_id', $_SESSION['student_id']);
							$query->execute();
						}
						
						private function rateJob()
						{
							$sql = 'UPDATE jobs SET job_rating_user=:job_rating_user WHERE job_id=:job_id AND student_id=:student_id';
							$query = $this->db_connection->prepare($sql);
							$job_rating_user = filter_input(INPUT_POST, 'job_user_rating', FILTER_SANITIZE_NUMBER_INT);
							$query->bindValue(':job_rating_user', $job_rating_user);
							$job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
							$query->bindValue(':job_id', $job_id);
							$query->bindValue(':student_id', $_SESSION['student_id']);
							$query->execute();
						}
						
						/**
						 * Simply returns the current status of the user's login
						 * @return bool User's login status
						 */
						public function getUserLoginStatus()
						{
							return $this->user_is_logged_in;
						}

						/**
						 * Simple demo-"page" that will be shown when the user is logged in.
						 * In a real application you would probably include an html-template here, but for this extremely simple
						 * demo the "echo" statements are okay.
						 */
						private function showPageLoggedIn()
						{		
							if ($this->feedback) {
								echo $this->feedback . "<br/><br/>";
							}
							echo '<title>View Jobs</title>';
							echo 'Hello ' . $_SESSION['firstname'] . " " . $_SESSION['lastname'] . '.<br>Here are the job(s) you have:<br/><br/>';
							if ($this->createDatabaseConnection()) {
								// check is user wants to accept/complete/rate a job
								if (isset($_POST["job_id"])) {
									if (isset($_POST['accept'])) {
										$this->acceptJob();
									} elseif (isset($_POST['complete'])) {
										$this->markJobComplete();
									} elseif (isset($_POST['rate'])) {
										$this->rateJob();
									}
								}
								$sql = "SELECT * FROM jobs WHERE student_id=:student_id ORDER BY job_complete,time_sent";
								$query = $this->db_connection->prepare($sql);
								$query->bindValue(':student_id', $_SESSION['student_id']);
								$query->execute();
								$result = $query->fetchAll();
								if ($result) {
									echo "<script type='text/css'>
										th, td {
											border: 1px solid white; padding: 5px; text-align: center;
										}
									</script>
									<table style='text-align: center; padding: 5px; border: 1px solid white; width: 100%;'>
									<tr><th>Description</th><th>Time Sent</th><th>Time Accepted</th><th>Time Complete</th><th>Sent By</th><th>Rating</th><th></th></tr>";
									foreach ($result as $entry) {
										if ($entry['job_accepted'] == 0) {
											echo "<tr><td>" . $entry["job_desc"] . "</td><td>" . $entry["time_sent"] . "</td><td> - </td><td> - </td><td>" . $entry["sent_by"] . "</td><td> - </td><td><form method='post' action='' name='acceptform'><input type='hidden' name='job_id' value='" . $entry['job_id'] . "' /><input type='submit' name='accept' class='btn btn-outline' value='Accept Job' /></form></td></tr>";
										} elseif ($entry['job_complete'] == 0) {
											echo "<tr><td>" . $entry["job_desc"] . "</td><td>" . $entry["time_sent"] . "</td><td> " . $entry['time_accepted'] . " </td><td> - </td><td>" . $entry["sent_by"] . "</td><td> - </td><td><form method='post' action='' name='completeform'><input type='hidden' name='job_id' value='" . $entry['job_id'] . "' /><input type='submit' name='complete' class='btn btn-outline' value='Mark Job Complete' /></form></td></tr>";
										} else {
											if ($entry['job_rating_user'] > 0 && $entry['job_rating_user'] <= 5) {
												echo "<tr><td>" . $entry["job_desc"] . "</td><td>" . $entry["time_sent"] . "</td><td> " . $entry['time_accepted'] . " </td><td> " . $entry['time_completed'] . " </td><td>" . $entry["sent_by"] . "</td><td> " . $entry['job_rating_user'] . "/5 </td><td></td></tr>";
											} else {
												echo "<tr><td>" . $entry["job_desc"] . "</td><td>" . $entry["time_sent"] . "</td><td> " . $entry['time_accepted'] . " </td><td> " . $entry['time_completed'] . " </td><td>" . $entry["sent_by"] . "</td><td> - </td><td><form method='post' action='' name='rateform'><input type='hidden' name='job_id' value='" . $entry['job_id'] . "' /><input type='text' name='job_user_rating' /><input type='submit' name='rate' class='btn btn-outline' value='Rate Job' /></form></td></tr>";}
										}
									}
									echo "</table><br>";
								} else {
									echo "No jobs yet<br><br>";
								}
							} else {
								echo "Database not working";
							}
							echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout" class="btn btn-outline" >Log out</a>';
						}

						/**
						 * Simple login form.
						 */
						private function showPageLoginForm()
						{
							if ($this->feedback) {
								echo $this->feedback . "<br/><br/>";
							}
							//echo '<center>';
							echo '<title>Login | Huntington Robotics</title>';
							echo '<h4>Login with your student ID</h4>';
							echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '" name="loginform">';
							echo '<label for="login_input_student_id" style="padding: 5px;">Student ID  </label>';
							echo '<input id="login_input_student_id" type="text" style="color: initial; padding: 5px;" class="input-group-small" name="student_id" required /> <br>';
							echo '<input type="submit"  name="login" class="btn btn-outline" value="Log in" />';
							echo '</form>';
							//echo '</center>';
						}

					}

					// run the application
					$application = new OneFileLoginApplication();
					?>

<!--				</div>
            </div>
        </div>
    </section>

    
    <script src="js/jquery.js"></script>
	<script src="js/bootstrap.min.js"></script>

    <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
    <script src="../js/classie.js"></script>
    <script src="../js/cbpAnimatedHeader.js"></script>

    <script src="js/jqBootstrapValidation.js"></script>
    
    <script src="../js/freelancer.js"></script>

</body>

</html>

-->