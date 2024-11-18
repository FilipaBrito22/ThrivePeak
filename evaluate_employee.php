<?php
session_start();
include 'db_connection.php';

if (!isset($_GET['employeeID']) || !isset($_GET['trimester']) || !isset($_GET['year'])) {
    die("Invalid access. Please provide employeeID, trimester, and year in the URL.");
}

$employeeID = $_GET['employeeID'];
$trimester = $_GET['trimester'];
$year = $_GET['year'];

// Query to get the job role of the employee
$jobRoleQuery = $conn->query("SELECT JobRoleID FROM Employee WHERE EmployeeID = $employeeID");
$jobRoleData = $jobRoleQuery->fetch_assoc();

if (!$jobRoleData) {
    die("Employee not found.");
}

$jobRoleID = $jobRoleData['JobRoleID'];

// Fetch criteria based on the employee's job role
$criteriaQuery = $conn->query("
    SELECT c.CriteriaID, c.CriteriaName 
    FROM RoleCriteriaWeight rcw 
    JOIN Criteria c ON rcw.CriteriaID = c.CriteriaID 
    WHERE rcw.JobRoleID = $jobRoleID
");

$criteriaList = [];
while ($criteriaRow = $criteriaQuery->fetch_assoc()) {
    $criteriaList[] = $criteriaRow;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Evaluation System</title>
    <link rel="stylesheet" href="css/evaluate_employee.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<aside class="sidebar">
    <div class="logo">
        <img src="images/thrivepeak_text_logo.png" alt="ThrivePeak Logo">
    </div>
    <nav class="menu">
        <ul>
            <li class="menu-item">
                <img src="images/dashboard_company.png" alt="dashboard">
                <a href="dashboard.php">Dashboard</a>
            </li>
            <li class="menu-item active">
                <img src="images/group.png" alt="team">
                <a href="team.php">Team</a>
            </li>
            <li class="menu-item">
                <img src="images/evaluation.png" alt="evaluation">
                <a href="evaluation_form.php">Evaluate</a>
            </li>
        </ul>
    </nav>
    <hr>
    <div class="logout">
        <a href="autentica.php?logout=true">Logout</a>
    </div>
</aside>

<div class="container">
    <header class="header">
        <div class="user-info">
            <?php include("inc/check_user.php"); ?>
        </div>
    </header>
    <h1>Employee Evaluation System</h1>

    <!-- Section to Evaluate Employee -->
    <div class="section">
        <h2>Evaluate Subordinate (Employee ID: <?php echo htmlspecialchars($employeeID); ?>, Trimester: <?php echo htmlspecialchars($trimester); ?>, Year: <?php echo htmlspecialchars($year); ?>)</h2>

        <div id="evaluation-criteria">
            <!-- Display criteria inputs for evaluation -->
            <?php foreach ($criteriaList as $criteria): ?>
                <label><?php echo htmlspecialchars($criteria['CriteriaName']); ?>:</label>
                <input type="number" class="score-input" data-criteria-id="<?php echo $criteria['CriteriaID']; ?>" min="0" max="10"><br>
            <?php endforeach; ?>
        </div>
        
        <button id="submit_evaluation">Submit Evaluation</button>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Submit evaluation
        $('#submit_evaluation').click(function() {
            const scores = {};

            // Collect scores for each criterion
            $('.score-input').each(function() {
                const criteriaID = $(this).data('criteria-id');
                const score = $(this).val();
                scores[criteriaID] = score;
            });

            $.post('', {
                action: 'submit_evaluation',
                employee_id: <?php echo $employeeID; ?>,
                job_role_id: <?php echo $jobRoleID; ?>,
                trimester: <?php echo $trimester; ?>,
                year: <?php echo $year; ?>,
                scores: scores
            }, function(response) {
                alert(response);
            });
        });
    });
</script>
</body>
</html>

<?php
// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_evaluation') {
    $employeeID = $_POST['employee_id'];
    $jobRoleID = $_POST['job_role_id'];
    $trimester = $_POST['trimester'];
    $year = $_POST['year'];
    $scores = $_POST['scores'];
    
    // Insert into EmployeeEvaluation with the year parameter
    $conn->query("INSERT INTO EmployeeEvaluation (EmployeeID, JobRoleID, Trimester, Year, EvaluationDate) VALUES ($employeeID, $jobRoleID, $trimester, $year, NOW())");
    $evaluationID = $conn->insert_id; // Get the last inserted ID for EvaluationID
    
    // Insert each score into EvaluationCriteriaScore
    foreach ($scores as $criteriaID => $score) {
        $conn->query("INSERT INTO EvaluationCriteriaScore (EvaluationID, CriteriaID, Score, EvaluationDate) VALUES ($evaluationID, $criteriaID, $score, NOW())");
    }
    
    echo "Evaluation submitted successfully!";
    exit;
}
?>
