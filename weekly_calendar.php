<?php 
	include("config/session.php");
	/* Session expiry */
	include('config/session_expiry.php');

	include("sql/calendarFunctions.php");
	$chosenDate = '';

	/* Cancel, call in sick */
	if(isset($_POST['cancelShift'])){
		$date = $_POST['date'];
		$employeeId = $_SESSION['employeeId'];

		cancelShift($date, $employeeId);
	}

	/* Cancel proposed shift */
	if(isset($_POST['cancelProposedShift'])){
		$date = $_POST['date'];
		$employeeId = $_SESSION['employeeId'];

		cancelProposedShift($date, $employeeId);
	}
	
	/* Confirm attendance */
	if(isset($_POST['confirm'])){
		$date = $_POST['date'];
		$employeeId = $_SESSION['employeeId'];

		confirmAttendance($date, $employeeId);
	}

	/* Accept assigned shift */
	if(isset($_POST['acceptShift'])){
		$date = $_POST['date'];
		$employeeId = $_SESSION['employeeId'];

		acceptProposedShift($date, $employeeId);
	}	

	/* Reject assigned shift */
	if(isset($_POST['rejectShift'])){
		$date = $_POST['date'];
		$employeeId = $_SESSION['employeeId'];

		rejectProposedShift($date, $employeeId);
	}


	function generateCalendar($dt, $week, $year){
		$dateComponents = getdate();
		
		$currentPage = $_SERVER['PHP_SELF'];

		// Current date
		$dateToday = date('Y-m-d');

		$nameDayToday = strtolower(date('l', strtotime($dateToday)));

		// Array containing names of all days in a week
		$daysOfWeek = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

		// Name of this month
		$monthName = $dateComponents['month'];

		// Creating the HTML table
		$calendar = "<div class='table table-bordered'>";
		$calendar .= "<h2 id='weekNr'>Week $week</h2>";

		$calendar .= "<div id='wrapper'>";
		// View type (Monthly, Weekly)
		$calendar .= "<button class='viewType active'><a href='#'' type='submit'>Weekly calendar</a></button>";
		$calendar .= "<button class='viewType'><a href='monthly_calendar.php' type='submit'>Monthly calendar</a></button>
			</div>";
		
		// Next week
		$calendar .= "<div id='wrapperSwitchWeek'><a class='switchWeek' href='".$currentPage."?week=".($week+1)."&year=".$year."'>Next Week</a>";
		// Previous week
		$calendar .= "<a class='switchWeek' href='".$currentPage."?week=".($week-1)."&year=".$year."'>Previous Week</a></div>";

		$calendar .= "<div id='header'>";

		// Calendar headers
		foreach ($daysOfWeek as $day) {
			$calendar .= "<div class='day'>$day</div>";
		}
		
		$calendar .= "</div><div class='container'>";

		// Today date
		$todayDate = new DateTime(date('Y-m-d'));

		do {
			// Get each day name, small case
			$dayname = strtolower(date('l', strtotime($dt->format('l'))));
			// Get each day date
			$daydate =strtolower(date('Y-m-d', strtotime($dt->format('Y-m-d'))));

			// Check if it's today
			$today = $daydate==$dateToday?"today":"";

			// Current date as DateTime
			$currentDate = new DateTime($daydate);

			// If weekend
			if($dayname == 'saturday' || $dayname =='sunday'){
				$calendar .= "<div class='box'><h4> ". $dt->format('d M Y')."</h4> <a class='btn holiday'>Holiday</a>";
			}
			// if old dates
			elseif($daydate < date('Y-m-d')){
				$employeeId = $_SESSION['employeeId'];
				// Check employee's agenda
				$result = DoIHaveWork($daydate, $employeeId);

				// If employee has a shift
				if($result != null){
					$calendar .= "<div class='box scheduled $today'><h4>". $dt->format('d M Y')."</h4> <p>".$result[0]."<br>". $result[1] ."</p>";		
				}
				else {
					$calendar .= "<div class='box $today'><h4>". $dt->format('d M Y')."</h4>";
				}

			}
			else {
				$employeeId = $_SESSION['employeeId'];
				// Check employee's agenda
				$result = DoIHaveWork($daydate, $employeeId);

				$interval = $todayDate->diff($currentDate);
				$interval = $interval->format('%R%a');

				// If employee has a shift
				if($result != null){
					// Confirm attendance
					// If result is auto assigned(by the system) or accepted and it's after 7 days
					if(($result[1] == 'AutoAssigned' || $result[1] == 'Accepted') && $interval <= 7){
						$calendar .= "<div class='box scheduled $today'><h4>". $dt->format('d M Y')."</h4> <p>".$result[0]."<br>". $result[1] ."</p><form action='' method='POST'><button type='submit' name='confirm' class='btn confirm'>Confirm attendance</button><input type='hidden' name='date' value=".$daydate."></form>";
					}

					// If result is assigned
					else if($result[1] == 'Assigned'){
						$calendar .= "<div class='box scheduled $today'><h4>". $dt->format('d M Y')."</h4> <p>".$result[0]."<br>". $result[1] ."</p>
							<form action='' method='POST'>
							<div id='showButtons'>Respond?</div>
							<button type='submit' name='acceptShift' class='btn accept'>Accept</button>
							<button type='submit' name='rejectShift' class='btn reject'>Reject</button>
							<input type='hidden' name='date' value=".$daydate."></form>";
					}

					// Register as sick. They can register a day before or same day. 
					else if($result[1] == 'Confirmed' && $interval <= 1){
						$calendar .= "<div class='box scheduled $today'><h4>". $dt->format('d M Y')."</h4><p>".$result[0]."<br>". $result[1] ."</p><form action='' method='POST'><button type='submit' name='cancelShift' class='btn cancel'>Call in sick</button><input type='hidden' name='date' value=".$daydate."></form>";		
					}
					// Cancel proposed shift 
					else if($result[1] == 'Proposed'){
						$calendar .= "<div class='box scheduled $today'><h4>". $dt->format('d M Y')."</h4><p>".$result[0]."<br>". $result[1] ."</p><form action='' method='POST'><button type='submit' name='cancelProposedShift' class='btn cancel'>Cancel proposal</button><input type='hidden' name='date' value=".$daydate."></form>";	
					}
					else {
					// Confirm attendance
					$calendar .= "<div class='box scheduled $today'><h4>". $dt->format('d M Y')."</h4> <p>".$result[0]."<br>". $result[1] ."</p>";
					}					
				}
				// If employee has no shift
				else {
					// Check number workers
					$morningShiftWorkers = checkShiftsMorning($daydate);
					$afternoonShiftWorkers = checkShiftsAfternoon($daydate);
					$eveningShiftWorkers = checkShiftsEvening($daydate);

					// Check if shifts are full
					if($morningShiftWorkers && $afternoonShiftWorkers && $eveningShiftWorkers){
						$calendar .= "<div class='box $today'><h4>". $dt->format('d M Y')." </h4><a href='#' class='btn full'>Full</a>";
					}
					// If shifts aren't full, allow user to propose shift
					else {
						$calendar .= "<div class='box $today'><h4>". $dt->format('d M Y')."</h4><a href='proposeShift.php?date=".$daydate."' class='btn propose'>Propose</a>";	
					}
				}
			}
			$calendar .= "</div>";

			$dt->modify('+1 day');
		} while ($week == $dt->format('W'));

		$calendar .= "</div>";

		$calendar .= "</div>";

		echo $calendar;
	}
?>


<!DOCTYPE html>
<html>
	<head>
		<title>Weekly Calendar</title>		
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" type="text/css" href="css/calendar.css">

		<!-- Include header (links to css files and navbar) -->
		<?php include('includes/header.php') ?>

		<h1><?php echo $chosenDate; ?></h1>
		<main id="main">
			<div id="calendar">
				<?php 
					$dt = new DateTime;
					if (isset($_GET['year']) && isset($_GET['week'])) {
					    $dt->setISODate($_GET['year'], $_GET['week']);
					} else {
					    $dt->setISODate($dt->format('o'), $dt->format('W'));
					}
					$year = $dt->format('o');
					$week = $dt->format('W'); 

					generateCalendar($dt, $week, $year);
				?>
			</div>		
		</main>

		<!-- Include footer -->
		<?php include('includes/footer.php') ?>

		<script
			src="https://code.jquery.com/jquery-3.4.1.js"
			integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU="
			crossorigin="anonymous"></script>
		<script type="text/javascript" src="js/calendar.js"></script>
	</body>
</html>
