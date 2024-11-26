<?php
session_start();
include 'db_connection.php';
include 'inc/top.php';

$hasDepartmentOne = false;
$userPhoto = '';

if (isset($_SESSION['user_id'])) {
    $userID = $_SESSION['user_id'];

    // Query to get the DepartmentID, EmployeeID, JobRole, ManagerID, and Photo for the logged-in user
    $query = "SELECT Employee.EmployeeID, Employee.Name, JobRole.JobRole AS JobRole, Employee.DepartmentID, Employee.ManagerID, Employee.Photo 
              FROM Employee
              JOIN JobRole ON Employee.JobRoleID = JobRole.JobRoleID
              WHERE Employee.EmployeeID = $userID";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $loggedInEmployee = $result->fetch_assoc();

        // Set user photo path
        $userPhoto = !empty($loggedInEmployee['Photo']) ? $loggedInEmployee['Photo'] : 'images/default.png';

        // Check if DepartmentID is 1
        if ($loggedInEmployee['DepartmentID'] == 1) {
            $hasDepartmentOne = true;
        }

        // Fetch subordinates for the logged-in employee
        $subordinateQuery = "SELECT Employee.EmployeeID, Employee.Name, JobRole.JobRole AS JobRoleName, Employee.Photo 
                        FROM Employee
                        JOIN JobRole ON Employee.JobRoleID = JobRole.JobRoleID
                        WHERE Employee.ManagerID = $userID";

        $subordinateResult = $conn->query($subordinateQuery);

        $subordinates = [];
        while ($subordinateRow = $subordinateResult->fetch_assoc()) {
            $subordinates[] = $subordinateRow;
        }

        // Add subordinates to $loggedInEmployee array
        $loggedInEmployee['subordinates'] = $subordinates;
    }

    // Function to determine if a subordinate can be evaluated in the current trimester
// Function to determine the current trimester
function getCurrentTrimester() {
    $month = date('m');
    if ($month >= 1 && $month <= 3) return 1;
    if ($month >= 4 && $month <= 6) return 2;
    if ($month >= 7 && $month <= 9) return 3;
    return 4;
}
$currentTrimester = getCurrentTrimester();

function canEvaluate($employeeID, $conn) {
    $currentYear = date('Y');
    $currentTrimester = getCurrentTrimester();
    $isLastWeek = (date('d') >= 24); // Last week of the month (24th onwards)

    // Loop through each trimester up to the current one
    for ($trimester = 1; $trimester <= $currentTrimester; $trimester++) {
        // Print debug information for the query parameters

        $checkQuery = "
            SELECT COUNT(*) as evaluationCount
            FROM EmployeeEvaluation 
            WHERE EmployeeID = $employeeID 
              AND Year = $currentYear
              AND Trimester = $trimester
        ";


        $checkResult = $conn->query($checkQuery);

        if (!$checkResult) {

            return false;
        }

        $evaluationData = $checkResult->fetch_assoc();
        

        // Allow evaluation if there's no record for the trimester
        if ($evaluationData['evaluationCount'] == 0) {
            // If it's the last week of the current trimester or the trimester is missed
            if ($trimester == $currentTrimester && $isLastWeek) {
                return $trimester; // Current trimester
            } elseif ($trimester < $currentTrimester) {
                return $trimester; // Missed trimester
            }
        }
    }

    // No missed trimesters and not last week of current trimester
    return false;
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThrivePeak Dashboard</title>
    <link rel="stylesheet" href="css/employee_list1.css">
</head>
<body>

<div class="container">
<button class="toggle-sidebar">☰</button>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="images/thrivepeak_text_logo.png" alt="ThrivePeak Logo">
        </div>
        <nav class="menu">
            <ul>
                <li class="menu-item active">
                    <img src="images/home.png" alt="dashboard">
                    <a href="dashboard_company.php">Dashboard</a>
                </li>
                <li class="menu-item">
                    <img src="images/group.png" alt="team">
                    <a href="team.php">Team</a>
                </li>
                <li class="evaluation">
                    <li class="menu-item">
                        <img src="images/evaluation.png" alt="evaluation">
                        <a href="#" class="evaluation-link">Evaluation</a>
                    </li>
                    <ul class="submenu">
                        <li><a href="individual_evaluation.php">Individual</a></li>
                        <li><a href="department_evaluation.php">Department</a></li>
                    </ul>
                </li>
                <?php if (!empty($loggedInEmployee['subordinates'])): ?>
                    <li class="menu-item">
                        <img src="images/evaluate.png" alt="team">
                        <a href="evaluation_form.php">Evaluate</a>
                    </li>
                <?php endif; ?>
                <?php if ($hasDepartmentOne): ?>
                    <li class="menu-item">
                        <img src="images/formula.png" alt="team">
                        <a href="formula.php">Formula</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="logout">
            <a href="autentica.php?logout=true">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
<main class="main-content">
    <header class="header">
        <div class="user-info">
            <!-- Display User's Photo -->
            <img src="<?php echo htmlspecialchars($userPhoto); ?>" alt="User Photo" class="user-photo">
            <span><?php echo htmlspecialchars($loggedInEmployee['Name']); ?></span>
        </div>
    </header>

    <section class="dashboard">
        <h2>Subordinate Evaluations</h2>

        <!-- Loop through the subordinates and display their data -->
        <?php foreach ($subordinates as $subordinate): ?>
    <?php 
    $subordinatePhoto = htmlspecialchars($subordinate['Photo'] ?? 'images/default.png');
    $subordinateName = htmlspecialchars($subordinate['Name'] ?? 'Unknown');
    $subordinateRole = htmlspecialchars($subordinate['JobRoleName'] ?? 'Not Specified');
    $trimesterToEvaluate = canEvaluate($subordinate['EmployeeID'], $conn); 
    ?>
    
    <div class="employee-card">
        <div class="employee-info">
            <img src="<?php echo htmlspecialchars($subordinate['Photo'] ?? 'images/default.png'); ?>" alt="Profile Picture" class="profile-image">
            <div class="profile-details">
                <p><strong><?php echo $subordinateName; ?></strong></p>
                <p><?php echo $subordinateRole; ?></p>
            </div>
        </div>
        <?php if ($trimesterToEvaluate): ?>
            <a href="evaluate_employee.php?employeeID=<?php echo $subordinate['EmployeeID']; ?>&trimester=<?php echo $trimesterToEvaluate; ?>&year=<?php echo date('Y'); ?>" class="save-changes-btn">Evaluate</a>
        <?php else: ?>
            <span class="evaluated-check">✔️ Evaluated</span>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

    </section>
</main>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.querySelector('.toggle-sidebar');
    const sidebar = document.querySelector('.sidebar');

    if (toggleButton) {
        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }
});

    // Toggle submenu visibility
    document.querySelector('.evaluation-link').addEventListener('click', function(event) {
        event.preventDefault();
        const submenu = document.querySelector('.submenu');
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });

    // Chart configuration
    const ctx = document.getElementById('companyChart').getContext('2d');
    const companyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['5k', '10k', '15k', '20k', '25k', '30k', '35k', '40k', '45k', '50k', '55k', '60k'],
            datasets: [{
                label: 'Employee Performance',
                data: [2, 4, 6, 8.7, 5, 4, 5, 6, 7, 8, 6, 7],
                borderColor: 'blue',
                fill: false,
                pointRadius: 4,
                pointBackgroundColor: 'blue'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>