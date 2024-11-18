<?php
header('Content-Type: application/json');
include 'db_connection.php';

// Get the department ID or employee ID
if (isset($_GET['departmentID'])) {
    $departmentID = intval($_GET['departmentID']);

    // Fetch goals
    $goalsQuery = "SELECT GoalDescription, TargetValue, CurrentProgress, Deadline FROM DepartmentGoals WHERE DepartmentID = ?";
    $stmt = $conn->prepare($goalsQuery);
    $stmt->bind_param("i", $departmentID);
    $stmt->execute();
    $goalsResult = $stmt->get_result();
    $goals = $goalsResult->fetch_all(MYSQLI_ASSOC);

    // Fetch tasks
    $tasksQuery = "SELECT TaskDescription, AssignedTo, Status, Deadline FROM DepartmentTasks WHERE DepartmentID = ?";
    $stmt = $conn->prepare($tasksQuery);
    $stmt->bind_param("i", $departmentID);
    $stmt->execute();
    $tasksResult = $stmt->get_result();
    $tasks = $tasksResult->fetch_all(MYSQLI_ASSOC);

    // Fetch trimester scores for the graph
    $scoresQuery = "SELECT Trimester, Year, AverageScore FROM departmentevaluation WHERE DepartmentID = ? ORDER BY Year, Trimester";
    $stmt = $conn->prepare($scoresQuery);
    $stmt->bind_param("i", $departmentID);
    $stmt->execute();
    $scoresResult = $stmt->get_result();
    $trimesterScores = $scoresResult->fetch_all(MYSQLI_ASSOC);

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
        $averageScore = (float) $row['AverageScore']; 
        if ($averageScore < 6) {
            $ongoingRisks[] = "Latest Score: " . round($averageScore, 2);
        }
    } else {
        echo "No score found for DepartmentID: $departmentID";
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
    

    echo json_encode([
        'success' => true,
        'goals' => $goals,
        'tasks' => $tasks,
        'trimesterScores' => $trimesterScores,
        'averageScore' => $averageScore, // Add averageScore to response
        'totalEmployeesDepartment' => $totalEmployeesDepartment, // Add totalEmployeesDepartment to response
        'ongoingRisks' => $ongoingRisks,
        'potentialRisks' => $potentialRisks
    ]);    
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
