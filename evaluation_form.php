<?php
session_start();
include 'db_connection.php';
include 'inc/top.php';

// Initialize $hasDepartmentOne to avoid undefined variable warnings
$hasDepartmentOne = false;

// Check if the user is logged in and retrieve their ID
if (isset($_SESSION['user_id'])) {
    $userID = $_SESSION['user_id'];

    // Query to get the DepartmentID, EmployeeID, JobRole, and ManagerID for the logged-in user
    $query = "SELECT Employee.EmployeeID, Employee.Name, JobRole.JobRole AS JobRole, Employee.DepartmentID, Employee.ManagerID 
            FROM Employee
            JOIN JobRole ON Employee.JobRoleID = JobRole.JobRoleID
            WHERE Employee.EmployeeID = $userID";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $loggedInEmployee = $result->fetch_assoc();

        // Check if DepartmentID is 1
        if ($loggedInEmployee['DepartmentID'] == 1) {
            $hasDepartmentOne = true;
        }

        // Fetch subordinates for the logged-in employee
        $subordinateQuery = "SELECT EmployeeID, Name FROM Employee WHERE ManagerID = $userID";
        $subordinateResult = $conn->query($subordinateQuery);

        $subordinates = [];
        while ($subordinateRow = $subordinateResult->fetch_assoc()) {
            $subordinates[] = $subordinateRow;
        }

        // Add subordinates to $loggedInEmployee array
        $loggedInEmployee['subordinates'] = $subordinates;
    }
}

// Function to load job roles for dropdowns
function loadJobRoles($conn) {
    $result = $conn->query("SELECT JobRoleID, JobRole FROM JobRole");
    $options = "<option value=''>Select Job Role</option>";
    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='{$row['JobRoleID']}'>{$row['JobRole']}</option>";
    }
    return $options;
}

function loadSubordinatesWithScores($conn, $loggedInEmployeeID) {
    if (!$loggedInEmployeeID) {
        return "<p>Error: Invalid employee ID.</p>";
    }

    // Fetch all unique criteria to dynamically create table headers
    $criteriaQuery = $conn->query("SELECT CriteriaID, CriteriaName FROM Criteria");
    $criteriaHeaders = [];
    while ($criteriaRow = $criteriaQuery->fetch_assoc()) {
        $criteriaHeaders[$criteriaRow['CriteriaID']] = $criteriaRow['CriteriaName'];
    }

    // Fetch subordinates' scores for each criteria
    $query = "
        SELECT e.EmployeeID, e.Name, c.CriteriaID, c.CriteriaName, COALESCE(ecs.Score, 'N/A') AS Score
        FROM Employee e
        LEFT JOIN EmployeeEvaluation ee ON e.EmployeeID = ee.EmployeeID
        LEFT JOIN EvaluationCriteriaScore ecs ON ee.EvaluationID = ecs.EvaluationID AND ecs.CriteriaID IS NOT NULL
        LEFT JOIN Criteria c ON ecs.CriteriaID = c.CriteriaID
        WHERE e.ManagerID = $loggedInEmployeeID
    ";

    $result = $conn->query($query);
    if (!$result) {
        return "<p>Error fetching data: {$conn->error}</p>";
    }

    // Prepare an associative array to organize data by EmployeeID
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $employeeID = $row['EmployeeID'];
        $employeeName = $row['Name'];
        $criteriaID = $row['CriteriaID'];
        $criteriaName = $row['CriteriaName'];
        $score = $row['Score'];

        // Initialize employee data if not already done
        if (!isset($data[$employeeID])) {
            $data[$employeeID] = [
                'EmployeeID' => $employeeID,
                'Name' => $employeeName,
                'Scores' => array_fill_keys(array_keys($criteriaHeaders), 'N/A')
            ];
        }

        // Assign score for each criteria
        if ($criteriaID) {
            $data[$employeeID]['Scores'][$criteriaID] = $score;
        }
    }

    // Build the HTML table with criteria headers
    $table = "<table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>";
    foreach ($criteriaHeaders as $criteriaName) {
        $table .= "<th>$criteriaName</th>";
    }
    $table .= "    </tr>
                </thead>
                <tbody>";

    // Populate rows with employee data
    foreach ($data as $employee) {
        $table .= "<tr>
                    <td>{$employee['EmployeeID']}</td>
                    <td>{$employee['Name']}</td>";
        foreach ($employee['Scores'] as $score) {
            $table .= "<td>$score</td>";
        }
        $table .= "</tr>";
    }

    $table .= "</tbody></table>";
    return $table;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Evaluation System</title>
    <link rel="stylesheet" href="css/evaluation_form.css">
</head>
<body>
<aside class="sidebar">
    <div class="logo">
        <img src="images/thrivepeak_text_logo.png" alt="ThrivePeak Logo">
    </div>
    <nav class="menu">
        <ul>
            <li class="menu-item">
                <img src="images/dashboard.png" alt="dashboard">
                <a href="dashboard_company.php">Dashboard</a>
            </li>
            <li class="menu-item">
                <img src="images/group.png" alt="team">
                <a href="team.php">Team</a>
            </li>
            <li class="evaluation">
                <li class="menu-item">
                    <img src="images/evaluation.png" alt="team">
                    <a href="#" class="evaluation-link">Evaluation</a>
                </li>
                <ul class="submenu">
                    <li><a href="individual_evaluation.php">Individual</a></li>
                    <li><a href="department_evaluation.php">Department</a></li>
                </ul>
            </li>
            
            <!-- Display "Evaluate" only if the user has subordinates -->
            <?php if (!empty($loggedInEmployee['subordinates'])): ?>
                <li class="menu-item">
                    <img src="images/group.png" alt="team">
                    <a href="evaluation_form.php">Evaluate</a>
                </li>
            <?php endif; ?>
            
            <!-- Display "Formula" only if the user is in Department 1 -->
            <?php if ($hasDepartmentOne): ?>
                <li class="menu-item">
                    <img src="images/group.png" alt="team">
                    <a href="formula.php">Formula</a>
                </li>
            <?php endif; ?>
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
            <?php include ("inc/check_user.php")?>
        </div>
    </header>
    <section class="evaluation-system">
        <h1>Employee Evaluation System</h1>
        <!-- Section to Display Subordinates and Scores -->
        <div class="section">
            <h2>Subordinates' Evaluation Scores</h2>
            <div id="subordinates-scores">
                <?php
                // Display subordinates' scores for the logged-in employee
                echo loadSubordinatesWithScores($conn, $userID);
                ?>
            </div>
        </div>
        <button onclick="location.href='employee_list1.php'">Evaluate Employee</button>
    </section>
</div>

</body>
</html>
