<?php

/**
 * Class OneFileLoginApplication
 *
 * An entire php application with user registration, login and logout in one file.
 * Uses very modern password hashing via the PHP 5.5 password hashing functions.
 * This project includes a compatibility file to make these functions available in PHP 5.3.7+ and PHP 5.4+.
 *
 * @author Panique
 * @link https://github.com/panique/php-login-one-file/
 * @license http://opensource.org/licenses/MIT MIT License
 */
class OneFileLoginApplication
{
    /**
     * @var string Type of used database (currently only SQLite, but feel free to expand this with mysql etc)
     */
    private $db_type = "sqlite"; //

    /**
     * @var string Path of the database file (create this with _install.php)
     */
    private $db_sqlite_path = "../data.db";

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
        $sql = 'SELECT student_id, firstname, lastname, position, user_password_hash
                FROM leaders
                WHERE student_id = :student_id
                LIMIT 1';
        $query = $this->db_connection->prepare($sql);
		$student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_NUMBER_INT);
        $query->bindValue(':student_id', $student_id);
		//$query->bindValue(':user_password_hash', password_hash($_POST['user_password'], PASSWORD_DEFAULT));
        $query->execute();

        // If you meet the inventor of PDO, punch him.
        $result_row = $query->fetchObject();
        if ($result_row && password_verify($_POST['user_password'], $result_row->user_password_hash)) {
			// write user data into PHP SESSION [a file on your server]
			$_SESSION['firstname'] = $result_row->firstname;
			$_SESSION['lastname'] = $result_row->lastname;
			$_SESSION['student_id'] = $result_row->student_id;
			$_SESSION['user_is_logged_in'] = true;
			$this->user_is_logged_in = true;
			return true;
        } else {
            $this->feedback = "There was an error logging in. Either your ID is not in the database, or your password was incorrect.";
        }
        // default return
        return false;
    }
	
	private function acceptJob()
	{
		//if ($this->createDatabaseConnection()) {
			$sql = 'UPDATE jobs SET job_accepted = 1, time_accepted = :date WHERE job_id = :job_id AND student_id = :student_id';
			$query = $this->db_connection->prepare($sql);
			$query->bindValue(':date', date('Y-m-d H:i:s'));
			$job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
			$query->bindValue(':job_id', $job_id);
			//$student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_NUMBER_INT);
			$query->bindValue(':student_id', $_SESSION['student_id']);
			$query->execute();
		//}
	}
	
	private function markJobComplete()
	{
		//if ($this->createDatabaseConnection()) {
			$sql = 'UPDATE jobs SET job_complete = 1, time_completed = :date WHERE job_id = :job_id AND student_id = :student_id';
			$query = $this->db_connection->prepare($sql);
			$query->bindValue(':date', date('Y-m-d H:i:s'));
			$job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
			$query->bindValue(':job_id', $job_id);
			//$student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_NUMBER_INT);
			$query->bindValue(':student_id', $_SESSION['student_id']);
			$query->execute();
		//}
	}
	
	private function rateJob()
	{
		//if ($this->createDatabaseConnection()) {
			$sql = 'UPDATE jobs SET job_rating_user=:job_rating_user WHERE job_id=:job_id AND student_id=:student_id';
			$query = $this->db_connection->prepare($sql);
			$job_rating_user = filter_input(INPUT_POST, 'job_user_rating', FILTER_SANITIZE_NUMBER_INT);
			$query->bindValue(':job_rating_user', $job_rating_user);
			$job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
			$query->bindValue(':job_id', $job_id);
			//$student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_NUMBER_INT);
			$query->bindValue(':student_id', $_SESSION['student_id']);
			$query->execute();
		//}
	}
	
	private function delegateJob()
	{
		$sql = 'SELECT * FROM students WHERE student_id = :delegate_id';
		$query = $this->db_connection->prepare($sql);
		$delegate_id = filter_input(INPUT_POST, 'delegate_id', FILTER_SANITIZE_NUMBER_INT);
		$query->bindValue(':delegate_id', $delegate_id);
		$query->execute();
		$result = $query->fetchAll();
		if ($result) {
			$sql = 'UPDATE jobs SET student_id = :delegate_id, time_sent = :date WHERE job_id = :job_id';
			$query = $this->db_connection->prepare($sql);
			$query->bindValue(':delegate_id', $delegate_id);
			$query->bindValue(':date', date('Y-m-d H:i:s'));
			$job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
			$query->bindValue(':job_id', $job_id);
			$query->execute();
		}
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
			// check is user wants to accept/complete/rate/delegate a job
			if (isset($_POST["job_id"])) {
				if (isset($_POST['accept'])) {
					$this->acceptJob();
				} elseif (isset($_POST['complete'])) {
					$this->markJobComplete();
				} elseif (isset($_POST['rate'])) {
					$this->rateJob();
				} elseif (isset($_POST['delegate'])) {
					$this->delegateJob();
				}
			}
			$sql = "SELECT * FROM jobs WHERE student_id=:student_id ORDER BY job_complete,time_sent";
			$query = $this->db_connection->prepare($sql);
			$query->bindValue(':student_id', $_SESSION['student_id']);
			$query->execute();
			$result = $query->fetchAll();
			if ($result) {
				echo "<script type='text/css'>
					table, th, td {
						border: 1px solid black;
						padding: 5px;
						text-align: center;
					}
				</script>
				<table style='text-align: center; border: solid 1px black; padding: 5px; width: 100%;'>
				<tr><th>Description</th><th>Time Sent</th><th>Time Accepted</th><th>Time Complete</th><th>Sent By</th><th>Rating</th><th></th></tr>";
				foreach ($result as $entry) {
					if ($entry['job_accepted'] == 0) {
						echo "<tr><td>" . $entry["job_desc"] . "</td><td>" . $entry["time_sent"] . "</td><td> - </td><td> - </td><td>" . $entry["sent_by"] . "</td><td> - </td><form method='post' action='' name='acceptform'><td><input type='hidden' name='job_id' value='" . $entry['job_id'] . "' /><input type='submit' name='accept' value='Accept Job' /></td><td><input type='text' name='delegate_id' /><input type='submit' name='delegate' value='Delegate Job' /></td></form></tr>";
					} elseif ($entry['job_complete'] == 0) {
						echo "<tr><td>" . $entry["job_desc"] . "</td><td>" . $entry["time_sent"] . "</td><td> " . $entry['time_accepted'] . " </td><td> - </td><td>" . $entry["sent_by"] . "</td><td> - </td><td><form method='post' action='' name='completeform'><input type='hidden' name='job_id' value='" . $entry['job_id'] . "' /><input type='submit' name='complete' value='Mark Job Complete' /></form></td></tr>";
					} else {
						if ($entry['job_rating_user'] > 0 && $entry['job_rating_user'] <= 5) {
							echo "<tr><td>" . $entry["job_desc"] . "</td><td>" . $entry["time_sent"] . "</td><td> " . $entry['time_accepted'] . " </td><td> " . $entry['time_completed'] . " </td><td>" . $entry["sent_by"] . "</td><td> " . $entry['job_rating_user'] . "/5 </td><td></td></tr>";
						} else {
							echo "<tr><td>" . $entry["job_desc"] . "</td><td>" . $entry["time_sent"] . "</td><td> " . $entry['time_accepted'] . " </td><td> " . $entry['time_completed'] . " </td><td>" . $entry["sent_by"] . "</td><td> - </td><td><form method='post' action='' name='rateform'><input type='hidden' name='job_id' value='" . $entry['job_id'] . "' /><input type='text' name='job_user_rating' /><input type='submit' name='rate' value='Rate Job' /></form></td></tr>";}
					}
				}
				echo "</table><br>";
			} else {
				echo "No jobs yet<br><br>";
			}
		} else {
			echo "Database not working";
		}
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout">Log out</a>';
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
        echo '<h2>Login with your student ID</h2>';
        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '" name="loginform">';
        echo '<label for="login_input_student_id">Student ID </label>';
        echo '<input id="login_input_student_id" type="text" name="student_id" required /> <br>';
		echo '<label for="user_password">Password </label>';
		echo '<input id="user_password" type="password" name="user_password" required /> <br>';
        echo '<input type="submit"  name="login" value="Log in" />';
        echo '</form>';
		//echo '</center>';
    }

}

// run the application
$application = new OneFileLoginApplication();
