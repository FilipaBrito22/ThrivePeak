<?php
session_start();
include 'db_connection.php';
include 'inc/check_user.php';
include 'inc/top.php';

$hasDepartmentOne = false;

if (isset($_SESSION['user_id'])) {
    $userID = $_SESSION['user_id'];

    // Query to get the DepartmentID, EmployeeID, JobRole, ManagerID, and Subordinates for the logged-in user
    $query = "SELECT Employee.EmployeeID, Employee.Name, JobRole.JobRole AS JobRole, Employee.DepartmentID, Employee.ManagerID 
              FROM Employee
              JOIN JobRole ON Employee.JobRoleID = JobRole.JobRoleID
              WHERE Employee.EmployeeID = $userID";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        // Assign logged-in employee details to $loggedInEmployee
        $loggedInEmployee = $result->fetch_assoc();
        
        // Assign department ID only after $loggedInEmployee is fetched
        $departmentID = $loggedInEmployee['DepartmentID'];

        // Debugging: Ensure $departmentID is set
        if (empty($departmentID)) {
            echo "Error: departmentID is not set for user ID: $userID";
            exit;
        }

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
    } else {
        echo "Error: No employee data found for user ID: $userID";
        exit;
    }

    // Fetch scores per trimester for the evolution chart
    $trimesterScores = [];
    if (!empty($departmentID)) {
        $trimesterQuery = "
            SELECT 
                Trimester,
                Year,
                AverageScore
            FROM departmentevaluation
            WHERE DepartmentID = $departmentID
            ORDER BY Year, Trimester";


        $trimesterResult = $conn->query($trimesterQuery);

        if ($trimesterResult && $trimesterResult->num_rows > 0) {
            while ($row = $trimesterResult->fetch_assoc()) {
                $trimesterScores[] = [
                    'trimester' => $row['Trimester'],
                    'year' => $row['Year'],
                    'average' => round($row['AverageScore'], 2)
                ];
            }
        } else {
            echo "No data found for DepartmentID: $departmentID";
        }
    }

    $departments = [];
    if ($hasDepartmentOne) {
        $departmentQuery = "SELECT DepartmentID, Department FROM Department";
        $departmentResult = $conn->query($departmentQuery);

        while ($departmentRow = $departmentResult->fetch_assoc()) {
            $departments[] = $departmentRow;
        }
    }


// Fetch department goals
$goals = [];
if (!empty($loggedInEmployee['DepartmentID'])) {
    $departmentID = $loggedInEmployee['DepartmentID'];
    $goalQuery = "SELECT GoalDescription, TargetValue, CurrentProgress, Deadline 
                  FROM DepartmentGoals 
                  WHERE DepartmentID = $departmentID";
    $goalResult = $conn->query($goalQuery);

    if ($goalResult) {
        while ($row = $goalResult->fetch_assoc()) {
            $goals[] = $row;
        }
    }
}
// Fetch department tasks
$tasks = [];
if (!empty($loggedInEmployee['DepartmentID'])) {
    $taskQuery = "SELECT TaskDescription, AssignedTo, Status, Deadline 
                  FROM DepartmentTasks 
                  WHERE DepartmentID = $departmentID";
    $taskResult = $conn->query($taskQuery);

    if ($taskResult) {
        while ($row = $taskResult->fetch_assoc()) {
            $tasks[] = $row;
        }
    }
}

$ongoingRisks = [];

// Fetch department's latest average performance score
$averageScore = null;
$scoreQuery = "
    SELECT AverageScore 
    FROM departmentevaluation
    WHERE DepartmentID = $departmentID
    ORDER BY Year DESC, Trimester DESC
    LIMIT 1"; // Get the latest score based on Year and Trimester

$scoreResult = $conn->query($scoreQuery);

if ($scoreResult && $scoreResult->num_rows > 0) {
    $row = $scoreResult->fetch_assoc();
    $averageScore = $row['AverageScore'];
    if ($averageScore < 6) {
        $ongoingRisks[] = "Latest Score: " . round($averageScore, 2);
    }
} else {
    echo "No score found for DepartmentID: $departmentID";
}

// Fetch total number of employees in the company
$totalEmployees = 0;
$totalEmployeesQuery = "SELECT COUNT(*) as Total FROM Employee";
$totalEmployeesResult = $conn->query($totalEmployeesQuery);

if ($totalEmployeesResult && $totalEmployeesResult->num_rows > 0) {
    $row = $totalEmployeesResult->fetch_assoc();
    $totalEmployees = $row['Total'];
}

// Fetch total number of employees in the department
$totalEmployeesDepartment = 0;
$totalEmployeesQueryDepartment = "SELECT COUNT(*) as Total FROM Employee WHERE DepartmentID = $departmentID";
$totalEmployeesResultDepartment = $conn->query($totalEmployeesQueryDepartment); // Corrected this line

if ($totalEmployeesResultDepartment && $totalEmployeesResultDepartment->num_rows > 0) {
    $row = $totalEmployeesResultDepartment->fetch_assoc();
    $totalEmployeesDepartment = $row['Total'];
}

// Fetch total workload for the department
$tasksPerEmployee = 10; // Define tasks each employee can handle
$totalEstimatedTasks = 0;

// Step 1: Calculate the total number of estimated tasks for the department
$workloadQuery = "SELECT SUM(EstimatedTasks) AS TotalEstimatedTasks
                  FROM Projects
                  WHERE DepartmentID = $departmentID";
$workloadResult = $conn->query($workloadQuery);

if ($workloadResult && $workloadResult->num_rows > 0) {
    $row = $workloadResult->fetch_assoc();
    $totalEstimatedTasks = $row['TotalEstimatedTasks'];
}

// Step 2: Calculate required staff based on the workload
$requiredStaff = ceil($totalEstimatedTasks / $tasksPerEmployee);

// Step 3: Fetch current number of employees in the department
$currentStaff = 0;

$currentStaffQuery = "SELECT COUNT(*) AS CurrentStaff
                      FROM Employee
                      WHERE DepartmentID = $departmentID";
$currentStaffResult = $conn->query($currentStaffQuery);

if ($currentStaffResult && $currentStaffResult->num_rows > 0) {
    $row = $currentStaffResult->fetch_assoc();
    $currentStaff = $row['CurrentStaff'];
}

// Step 4: Compare and identify risks
$ongoingRisks = [];

if ($currentStaff < $requiredStaff) {
    $ongoingRisks[] = "Understaffing detected: Only $currentStaff employees available, but $requiredStaff required.";
}

$unmetGoals = [];
$unmetGoalsQuery = "
    SELECT GoalDescription, CurrentProgress 
    FROM DepartmentGoals 
    WHERE DepartmentID = $departmentID AND CurrentProgress < 50";
$unmetGoalsResult = $conn->query($unmetGoalsQuery);

if ($unmetGoalsResult && $unmetGoalsResult->num_rows > 0) {
    while ($row = $unmetGoalsResult->fetch_assoc()) {
        $unmetGoals[] = "Goal: " . htmlspecialchars($row['GoalDescription']) . " (" . htmlspecialchars($row['CurrentProgress']) . "% progress)";
    }
}

$overdueTasks = [];
$overdueTasksQuery = "
    SELECT TaskDescription, Deadline 
    FROM DepartmentTasks 
    WHERE DepartmentID = $departmentID 
      AND (Status = 'Pending' OR Status = 'In Progress') 
      AND Deadline < NOW()";
$overdueTasksResult = $conn->query($overdueTasksQuery);

if ($overdueTasksResult && $overdueTasksResult->num_rows > 0) {
    while ($row = $overdueTasksResult->fetch_assoc()) {
        $overdueTasks[] = "Task: " . htmlspecialchars($row['TaskDescription']) . " (Deadline: " . htmlspecialchars($row['Deadline']) . ")";
    }
}

$ongoingRisks = array_merge($ongoingRisks, $unmetGoals, $overdueTasks);

$potentialRisks = [];

// Upcoming Deadlines
$daysThreshold = 7;
$upcomingDeadlines = [];
$upcomingTasksQuery = "SELECT TaskDescription, Deadline FROM DepartmentTasks WHERE DepartmentID = $departmentID AND Deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL $daysThreshold DAY) AND (Status = 'Pending' OR Status = 'In Progress')";
$upcomingTasksResult = $conn->query($upcomingTasksQuery);
if ($upcomingTasksResult && $upcomingTasksResult->num_rows > 0) {
    while ($row = $upcomingTasksResult->fetch_assoc()) {
        $upcomingDeadlines[] = "Task: " . htmlspecialchars($row['TaskDescription']) . " (Deadline: " . htmlspecialchars($row['Deadline']) . ")";
    }
}

// Unassigned Tasks
$unassignedTasks = [];
$unassignedTasksQuery = "SELECT TaskDescription, Deadline FROM DepartmentTasks WHERE DepartmentID = $departmentID AND AssignedTo IS NULL";
$unassignedTasksResult = $conn->query($unassignedTasksQuery);
if ($unassignedTasksResult && $unassignedTasksResult->num_rows > 0) {
    while ($row = $unassignedTasksResult->fetch_assoc()) {
        $unassignedTasks[] = "Task: " . htmlspecialchars($row['TaskDescription']) . " (Deadline: " . htmlspecialchars($row['Deadline']) . ")";
    }
}

// Burnout Risks
$burnoutRisks = [];
$maxTasksPerEmployee = 3;
$burnoutQuery = "SELECT AssignedTo, COUNT(TaskID) AS TaskCount FROM DepartmentTasks WHERE DepartmentID = $departmentID AND AssignedTo IS NOT NULL GROUP BY AssignedTo HAVING TaskCount > $maxTasksPerEmployee";
$burnoutResult = $conn->query($burnoutQuery);
if ($burnoutResult && $burnoutResult->num_rows > 0) {
    while ($row = $burnoutResult->fetch_assoc()) {
        $burnoutRisks[] = "Employee ID: " . htmlspecialchars($row['AssignedTo']) . " (Task Count: " . htmlspecialchars($row['TaskCount']) . ")";
    }
}

// Combine Potential Risks
$potentialRisks = array_merge($upcomingDeadlines, $unassignedTasks, $burnoutRisks);


}

// Fetch department-specific data for the logged-in user
$defaultDepartmentData = [
    'goals' => $goals,
    'tasks' => $tasks,
    'trimesterScores' => $trimesterScores,
    'ongoingRisks' => $ongoingRisks,
    'potentialRisks' => $potentialRisks,
    'averageScore' => $averageScore,
    'totalEmployeesDepartment' => $totalEmployeesDepartment
];
?>

<script>
    // Pass the default department data to JavaScript
    const defaultDepartmentData = <?php echo json_encode($defaultDepartmentData); ?>;
</script>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThrivePeak Dashboard</title>
    <link rel="stylesheet" href="css/department_evaluation.css">
</head>
<body>

    <div class="container">
        <!-- Sidebar -->
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
                        <li class="menu-item active">
                            <img src="images/evaluation.png" alt="team">
                            <a href="#" class="evaluation-link">Evaluation</a>
                        </li>
                        <ul class="submenu">
                            <li><a href="individual_evaluation.php">Individual</a></li>
                            <li><a href="individual_evaluation.php" class="active">Department</a></li>
                        </ul>
                    </li>
                    <?php if (!empty($loggedInEmployee['subordinates'])): ?>
                        <li class="menu-item">
                            <img src="images/group.png" alt="team">
                            <a href="evaluation_form.php">Evaluate</a>
                        </li>
                    <?php endif; ?>
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="user-info">
                    <img src="<?php echo htmlspecialchars($_SESSION['userPhoto']); ?>" alt="User Photo" class="user-photo" style="width:40px; height:40px; border-radius:50%;">
                    <span><?php echo htmlspecialchars($_SESSION['userName']); ?></span>
                </div>
            </header> 

            <section class="filters">
                <div class="filter">
                <select id="department" onchange="fetchDepartmentData(this.value)">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['DepartmentID']; ?>">
                            <?php echo htmlspecialchars($department['Department']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </div>

                <div class="filter">
                <select id="employee" onchange="fetchEmployeeData(this.value)">
                    <option value="">Select Employee</option>
                    <?php foreach ($subordinates as $subordinate): ?>
                        <option value="<?php echo $subordinate['EmployeeID']; ?>">
                            <?php echo htmlspecialchars($subordinate['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                </div>
            </section>


            <section class="department-goals">
                <h3>Department Goals</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Goal</th>
                            <th>Target</th>
                            <th>Progress</th>
                            <th>Deadline</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </section>

            <section class="department-tasks">
                <h3>Department Tasks</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Assigned To</th>
                            <th>Deadline</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </section>
            
            <section class="dashboard">
                <div class="dashboard-header">
                    <div class="dashboard-stat">
                        <span>Total Employees</span>
                        <h3 id="totalEmployees"><?php echo htmlspecialchars($totalEmployees); ?></h3>
                    </div>
                    <div class="dashboard-stat">
                        <span>Total Employees</span>
                        <h3 id="departmentEmployees"><?php echo htmlspecialchars($totalEmployeesDepartment); ?></h3>
                    </div>
                    <div class="dashboard-stat">
                        <h3>Department Score</h3>
                        <ul id="departmentScore">
                        <?php 
                        if (isset($averageScore)) {
                            echo htmlspecialchars(round($averageScore, 2));
                        } else {
                            echo "N/A";
                        }
                        ?>
                        </ul>
                    </div>
                </div>

                <div class="company-details">
                    <h3>Company Details</h3>
                    <div class="chart">
                        <canvas id="companyChart"></canvas>
                    </div>
                </div>

                <div class="dashboard-bottom">
                    <div class="dashboard-stat">
                        <span>On going Risks</span>
                        <ul class="ongoing-risks"></ul>
                    </div>
                    <div class="dashboard-stat">
                        <span>Potential Risks</span>
                        <ul class="potential-risks"></ul>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Toggle visibility of the submenu "Evaluation"
    document.querySelector('.evaluation-link').addEventListener('click', function(event) {
        event.preventDefault();
        const submenu = document.querySelector('.submenu');
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });

    

    document.addEventListener("DOMContentLoaded", function () {
    if (defaultDepartmentData) {
        // Populate the UI with the default department data
        updateUI(defaultDepartmentData);
        updateDepartmentGraph(defaultDepartmentData.trimesterScores); // Render the default department graph
    } else {
        console.error("No default department data available.");
    }
});

    // Global chart instance
    let myChart = null;

    // Pass PHP data to JavaScript
    const trimesterScores = <?php echo json_encode($trimesterScores); ?>;

    // Debugging: Check the data in the browser console
    console.log(trimesterScores);

    // Function to update the graph department
    function updateDepartmentGraph(trimesterScores) {
    const labels = trimesterScores.map(score => `${score.Trimester} - ${score.Year}`);
    const data = trimesterScores.map(score => parseFloat(score.AverageScore));

    const ctx = document.getElementById('companyChart').getContext('2d');

    // Destroy the previous chart instance if it exists
    if (myChart) {
        myChart.destroy();
        myChart = null; // Clear the chart instance
    }

    // Create a new chart instance
    myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Average Department Score by Trimester',
                data: data,
                borderColor: 'blue',
                backgroundColor: 'rgba(0, 0, 255, 0.2)',
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: 'blue'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Trimester'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Average Score'
                    },
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });
}

function updateEmployeeGraph(Scores, departmentScores) {
    // Handle missing employee scores
    if (!Scores || Scores.length === 0) {
        console.warn("No data available for employee graph.");
        Scores = [{ Trimester: "N/A", Year: "N/A", TotalScore: 0 }];
    }

    // Handle missing department scores
    if (!departmentScores || departmentScores.length === 0) {
        console.warn("No data available for department graph.");
        departmentScores = [{ Trimester: "N/A", Year: "N/A", AverageScore: 0 }];
    }

    // Extract labels and data for employee scores
    const employeeLabels = Scores.map(score => `${score.Trimester} - ${score.Year}`);
    const employeeData = Scores.map(score => parseFloat(score.TotalScore || 0));

    // Extract data for department scores (assumes same labels as employee scores for simplicity)
    const departmentData = departmentScores.map(score => parseFloat(score.AverageScore || 0));

    const ctx = document.getElementById('companyChart').getContext('2d');

    // Destroy the previous chart instance if it exists
    if (myChart) {
        myChart.destroy();
        myChart = null; // Clear the chart instance
    }

    // Create a new chart instance with two datasets
    myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: employeeLabels, // Use the labels from employee scores
            datasets: [
                {
                    label: 'Employee Performance Scores by Trimester',
                    data: employeeData,
                    borderColor: 'green',
                    backgroundColor: 'rgba(0, 255, 0, 0.2)',
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: 'green'
                },
                {
                    label: 'Average Department Score by Trimester',
                    data: departmentData,
                    borderColor: 'blue',
                    backgroundColor: 'rgba(0, 0, 255, 0.2)',
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: 'blue'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Trimester'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Score'
                    },
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });
}




    // Initial chart rendering (if data is available)
    if (trimesterScores.length === 0) {
        console.error("No data available to render the chart.");
    } else {
        updateDepartmentGraph(trimesterScores); // Use the updateGraph function
    }

    // Department filter change event
    document.getElementById('department').addEventListener('change', function () {
    console.log('Department filter changed: ', this.value);
    fetchEmployeesByDepartment(this.value);
});

// Event listener for employee selection
document.getElementById('employee').addEventListener('change', function () {
    console.log('Employee filter changed: ', this.value);
    fetchEmployeeData(this.value);
});

function fetchEmployeesByDepartment(departmentID) {
    console.log('Fetching employees for department:', departmentID);
    fetch(`fetch_employees.php?departmentID=${departmentID}`)
        .then(response => {
            if (!response.ok) {
                console.error('Error fetching employees:', response.statusText);
                return [];
            }
            return response.json();
        })
        .then(data => {
            console.log('Employees fetched:', data);
            const employeeDropdown = document.getElementById('employee');
            employeeDropdown.innerHTML = '<option value="">Select Employee</option>';
            data.forEach(employee => {
                const option = document.createElement('option');
                option.value = employee.EmployeeID;
                option.textContent = employee.Name;
                employeeDropdown.appendChild(option);
            });
        })
        .catch(error => console.error('Error in fetchEmployeesByDepartment:', error));
}

    // Fetch department-specific data and update the UI
    function fetchDepartmentData(departmentID) {
        console.log('Fetching data for department:', departmentID);

        if (!departmentID) {
            console.warn('No department selected.');
            clearDepartmentData();
            return;
        }

        fetch(`fetch_department_data.php?departmentID=${departmentID}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Fetched Data:', data);
                // Debug fetched data
                if (data.success) {
                    updateUI(data);
                    updateDepartmentGraph(data.trimesterScores); // Call the department graph function
                } else {
                    console.error('Error message from server:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching department data:', error);
            });
    }

    // Clear department data from the UI
    function clearDepartmentData() {
        document.querySelector('.department-goals tbody').innerHTML = '<tr><td colspan="4">No goals available</td></tr>';
        document.querySelector('.department-tasks tbody').innerHTML = '<tr><td colspan="4">No tasks available</td></tr>';
        document.querySelector('.ongoing-risks').innerHTML = '<li>No ongoing risks</li>';
        document.querySelector('.potential-risks').innerHTML = '<li>No potential risks</li>';
        document.querySelector('#departmentEmployees').textContent = "0";

        const scoreElement = document.querySelector('#departmentScore');
        if (scoreElement) {
            scoreElement.innerHTML = "<li>Department Score: N/A</li>";
        }

        // Clear the graph
        if (myChart) {
            myChart.destroy();
            myChart = null; // Reset the chart instance
        }
    }

    // Update the UI with fetched data
    function updateUI(data) {
        const goalsTable = document.querySelector('.department-goals tbody');
        if (data.goals && data.goals.length > 0) {
            goalsTable.innerHTML = data.goals.map(goal => `
                <tr>
                    <td>${goal.GoalDescription}</td>
                    <td>${goal.TargetValue}</td>
                    <td>${goal.CurrentProgress}</td>
                    <td>${goal.Deadline}</td>
                </tr>
            `).join('');
        } else {
            goalsTable.innerHTML = '<tr><td colspan="4">No goals available</td></tr>';
        }

        const tasksTable = document.querySelector('.department-tasks tbody');
        if (data.tasks && data.tasks.length > 0) {
            tasksTable.innerHTML = data.tasks.map(task => `
                <tr>
                    <td>${task.TaskDescription}</td>
                    <td>${task.AssignedTo || 'Unassigned'}</td>
                    <td>${task.Status || 'Pending'}</td>
                    <td>${task.Deadline}</td>
                </tr>
            `).join('');
        } else {
            tasksTable.innerHTML = '<tr><td colspan="4">No tasks available</td></tr>';
        }

        const ongoingRisksList = document.querySelector('.ongoing-risks');
        if (data.ongoingRisks && data.ongoingRisks.length > 0) {
            ongoingRisksList.innerHTML = data.ongoingRisks.map(risk => `<li>${risk}</li>`).join('');
        } else {
            ongoingRisksList.innerHTML = '<li>No ongoing risks</li>';
        }

        const potentialRisksList = document.querySelector('.potential-risks');
        if (data.potentialRisks && data.potentialRisks.length > 0) {
            potentialRisksList.innerHTML = data.potentialRisks.map(risk => `<li>${risk}</li>`).join('');
        } else {
            potentialRisksList.innerHTML = '<li>No potential risks</li>';
        }

        const departmentEmployeesElement = document.querySelector('#departmentEmployees');
        if (data.totalEmployeesDepartment) {
            departmentEmployeesElement.textContent = `${data.totalEmployeesDepartment}`;
        }

        const scoreElement = document.querySelector('#departmentScore');
        if (data.averageScore !== null && scoreElement) {
            console.log('Updating department score:', data.averageScore); // Debug: Log the score
            scoreElement.innerHTML = `<p>${data.averageScore.toFixed(2)}</p>`;
        }

        if (data.trimesterScores && data.trimesterScores.length > 0) {
            console.log('Updating graph with new scores:', data.trimesterScores); // Debug log
            updateDepartmentGraph(data.trimesterScores);
        } else {
            console.warn('No data available for the graph.');
            updateDepartmentGraph([]); // Clear the graph if no data
        }
    }

// Employee change
    function fetchEmployeeData(employeeID) {
    console.log('Fetching data for employee:', employeeID);

    if (!employeeID) {
        console.warn('No employee selected.');
        clearEmployeeData();
        return;
    }

    fetch(`fetch_employee_details.php?employeeID=${employeeID}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Fetched employee data:', data);
            if (data.success) {
                updateEmployeeUI(data); // Call update function with the fetched data
                updateEmployeeGraph(data.scores, data.trimesterScores); // Call the employee graph function
            } else {
                console.error('Error message from server:', data.message);
                clearEmployeeData(); // Clear UI in case of error
            }
        })
        .catch(error => {
            console.error('Error fetching employee data:', error);
        });
}


function clearEmployeeData() {
    // Clear employee-specific tasks
    const employeeTasksTable = document.querySelector('.department-tasks tbody');
    if (employeeTasksTable) {
        employeeTasksTable.innerHTML = '<tr><td colspan="4">No tasks available</td></tr>';
    }

    // Clear employee-specific goals
    const employeeGoalsTable = document.querySelector('.department-goals tbody');
    if (employeeGoalsTable) {
        employeeGoalsTable.innerHTML = '<tr><td colspan="4">No goals available</td></tr>';
    }

    // Clear employee-specific ongoing risks
    const ongoingRisksList = document.querySelector('.ongoing-risks');
    if (ongoingRisksList) {
        ongoingRisksList.innerHTML = '<li>No ongoing risks identified</li>';
    }

    // Clear employee-specific potential risks
    const potentialRisksList = document.querySelector('.potential-risks');
    if (potentialRisksList) {
        potentialRisksList.innerHTML = '<li>No potential risks identified</li>';
    }

    // Clear employee-specific socre
    const scoreElement = document.querySelector('#departmentScore');
        if (scoreElement) {
            scoreElement.innerHTML = "<li>Department Score: N/A</li>";
        }

    // Clear employee-specific chart
    if (myChart) {
        myChart.destroy();
        myChart = null; // Reset chart instance
    }


}


// Function to update the UI with employee-specific data
function updateEmployeeUI(data) {
    // Update employee tasks
    const employeeTasksTable = document.querySelector('.department-tasks tbody');
    if (data.tasks && data.tasks.length > 0) {
        employeeTasksTable.innerHTML = data.tasks.map(task => `
            <tr>
                <td>${task.TaskDescription}</td>
                <td>${task.Status || 'Pending'}</td>
                <td>${task.Deadline}</td>
            </tr>
        `).join('');
    } else {
        employeeTasksTable.innerHTML = '<tr><td colspan="4">No tasks available</td></tr>';
    }

    // Update employee goals
    const employeeGoalsTable = document.querySelector('.department-goals tbody');
    if (data.goals && data.goals.length > 0) {
        employeeGoalsTable.innerHTML = data.goals.map(goal => `
            <tr>
                <td>${goal.GoalDescription}</td>
                <td>${goal.TargetValue}</td>
                <td>${goal.CurrentProgress}</td>
                <td>${goal.Deadline}</td>
            </tr>
        `).join('');
    } else {
        employeeGoalsTable.innerHTML = '<tr><td colspan="4">No goals available</td></tr>';
    }

    // Display the last score
    const lastScoreElement = document.querySelector('#departmentScore');
    if (data.lastScore && lastScoreElement) {
        console.log('Updating last score:', data.lastScore); // Debug: Log the last score
        lastScoreElement.innerHTML = `
            <p>Last Score:</p>
            <p>Trimester: ${data.lastScore.Trimester}, Year: ${data.lastScore.Year}, Total Score: ${data.lastScore.TotalScore}</p>
        `;
    } else if (lastScoreElement) {
        lastScoreElement.innerHTML = '<p>Last Score: N/A</p>';
    }

    // Update employee ongoing risks
    const ongoingRisksList = document.querySelector('.ongoing-risks');
    if (data.ongoingRisks && data.ongoingRisks.length > 0) {
        ongoingRisksList.innerHTML = data.ongoingRisks.map(risk => `<li>${risk}</li>`).join('');
    } else {
        ongoingRisksList.innerHTML = '<li>No ongoing risks identified</li>';
    }

    // Update employee potential risks
    const potentialRisksList = document.querySelector('.potential-risks');
    if (data.potentialRisks && data.potentialRisks.length > 0) {
        potentialRisksList.innerHTML = data.potentialRisks.map(risk => `<li>${risk}</li>`).join('');
    } else {
        potentialRisksList.innerHTML = '<li>No potential risks identified</li>';
    }


    // Update the graph with new scores
    if (data.scores && data.scores.length > 0 && data.departmentScores && data.departmentScores.length > 0) {
        console.log('Updating graph with employee scores:', data.scores);
        console.log('Updating graph with department scores:', data.departmentScores);
        updateEmployeeGraph(data.scores, data.departmentScores); // Pass both employee and department scores
    } else {
        console.warn('No data available for the graph.');
        updateEmployeeGraph([], []); // Clear the graph if no data
    }


    
}

    
</script>
  

</body>
</html>