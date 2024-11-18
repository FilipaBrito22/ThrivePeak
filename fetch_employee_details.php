<?php
header('Content-Type: application/json');
include 'db_connection.php';

if (isset($_GET['employeeID'])) {
    $employeeID = intval($_GET['employeeID']);

    // Fetch the department ID for the employee
    $departmentQuery = "SELECT DepartmentID FROM Employee WHERE EmployeeID = ?";
    $stmt = $conn->prepare($departmentQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $departmentResult = $stmt->get_result();
    $departmentID = null;

    if ($departmentResult && $departmentResult->num_rows > 0) {
        $row = $departmentResult->fetch_assoc();
        $departmentID = $row['DepartmentID'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Department not found for employee']);
        exit;
    }

    // Fetch employee-specific tasks
    $tasksQuery = "SELECT TaskDescription, Status, Deadline FROM DepartmentTasks WHERE AssignedTo = ?";
    $stmt = $conn->prepare($tasksQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $tasksResult = $stmt->get_result();
    $tasks = $tasksResult->fetch_all(MYSQLI_ASSOC);

    // Fetch employee-specific goals
    $goalsQuery = "SELECT GoalDescription, TargetValue, CurrentProgress, Deadline 
                   FROM DepartmentGoals
                   WHERE DepartmentID = ?";
    $stmt = $conn->prepare($goalsQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $goalsResult = $stmt->get_result();
    $goals = $goalsResult->fetch_all(MYSQLI_ASSOC);

    // Fetch performance evaluations for the employee
    $scoresQuery = "SELECT Trimester, Year, TotalScore FROM EmployeeEvaluation WHERE EmployeeID = ? ORDER BY EvaluationDate";
    $stmt = $conn->prepare($scoresQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $scoresResult = $stmt->get_result();
    $scores = $scoresResult->fetch_all(MYSQLI_ASSOC);

    // Fetch the last score (most recent evaluation)
    $lastScoreQuery = "SELECT Trimester, Year, TotalScore 
                       FROM EmployeeEvaluation 
                       WHERE EmployeeID = ? 
                       ORDER BY Year DESC, Trimester DESC 
                       LIMIT 1";
    $stmt = $conn->prepare($lastScoreQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $lastScoreResult = $stmt->get_result();
    $lastScore = $lastScoreResult->fetch_assoc();

    // Ongoing Risks
    $ongoingRisks = [];

    // Low Performance Score
    $performanceQuery = "
        SELECT TotalScore 
        FROM EmployeeEvaluation 
        WHERE EmployeeID = $employeeID 
        ORDER BY Year DESC, Trimester DESC 
        LIMIT 1";
    $performanceResult = $conn->query($performanceQuery);
    if ($performanceResult && $performanceResult->num_rows > 0) {
        $row = $performanceResult->fetch_assoc();
        if ((float) $row['TotalScore'] < 6) {
            $ongoingRisks[] = "Low performance detected: Latest score is " . round($row['TotalScore'], 2);
        }
    }

    // Overdue Tasks
    $overdueTasks = [];
    $overdueTasksQuery = "
        SELECT TaskDescription, Deadline 
        FROM DepartmentTasks 
        WHERE AssignedTo = $employeeID 
        AND (Status = 'Pending' OR Status = 'In Progress') 
        AND Deadline < NOW()";
    $overdueTasksResult = $conn->query($overdueTasksQuery);
    if ($overdueTasksResult && $overdueTasksResult->num_rows > 0) {
        while ($row = $overdueTasksResult->fetch_assoc()) {
            $ongoingRisks[] = "Overdue Task: " . htmlspecialchars($row['TaskDescription']) . " (Deadline: " . htmlspecialchars($row['Deadline']) . ")";
        }
    }

    /*Unmet Goals
    $unmetGoalsQuery = "
        SELECT GoalDescription, CurrentProgress 
        FROM DepartmentGoals 
        WHERE EmployeeID = $employeeID AND CurrentProgress < 50";
    $unmetGoalsResult = $conn->query($unmetGoalsQuery);
    if ($unmetGoalsResult && $unmetGoalsResult->num_rows > 0) {
        while ($row = $unmetGoalsResult->fetch_assoc()) {
            $ongoingRisks[] = "Unmet Goal: " . htmlspecialchars($row['GoalDescription']) . " (" . htmlspecialchars($row['CurrentProgress']) . "% progress)";
        }
    }*/

    // Potential Risks
    $potentialRisks = [];

    // Burnout Risk
    $maxTasksPerEmployee = 3;
    $burnoutQuery = "
        SELECT COUNT(TaskID) AS TaskCount 
        FROM DepartmentTasks 
        WHERE AssignedTo = $employeeID 
        HAVING TaskCount > $maxTasksPerEmployee";
    $burnoutResult = $conn->query($burnoutQuery);
    if ($burnoutResult && $burnoutResult->num_rows > 0) {
        $row = $burnoutResult->fetch_assoc();
        $potentialRisks[] = "Burnout risk: Assigned " . htmlspecialchars($row['TaskCount']) . " tasks.";
    }

    // Upcoming Deadlines
    $daysThreshold = 7;
    $upcomingTasksQuery = "
        SELECT TaskDescription, Deadline 
        FROM DepartmentTasks 
        WHERE AssignedTo = $employeeID 
        AND Deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL $daysThreshold DAY) 
        AND (Status = 'Pending' OR Status = 'In Progress')";
    $upcomingTasksResult = $conn->query($upcomingTasksQuery);
    if ($upcomingTasksResult && $upcomingTasksResult->num_rows > 0) {
        while ($row = $upcomingTasksResult->fetch_assoc()) {
            $potentialRisks[] = "Upcoming Task: " . htmlspecialchars($row['TaskDescription']) . " (Deadline: " . htmlspecialchars($row['Deadline']) . ")";
        }
    }

    // Unassigned Tasks
    $unassignedTasksQuery = "
        SELECT TaskDescription, Deadline 
        FROM DepartmentTasks 
        WHERE AssignedTo IS NULL AND DepartmentID = $departmentID";
    $unassignedTasksResult = $conn->query($unassignedTasksQuery);
    if ($unassignedTasksResult && $unassignedTasksResult->num_rows > 0) {
        while ($row = $unassignedTasksResult->fetch_assoc()) {
            $potentialRisks[] = "Unassigned Task: " . htmlspecialchars($row['TaskDescription']) . " (Deadline: " . htmlspecialchars($row['Deadline']) . ")";
        }
    }

    // Fetch trimester scores for the graph
    $scoresQuery = "SELECT Trimester, Year, AverageScore FROM departmentevaluation WHERE DepartmentID = ? ORDER BY Year, Trimester";
    $stmt = $conn->prepare($scoresQuery);
    $stmt->bind_param("i", $departmentID);
    $stmt->execute();
    $scoresResult = $stmt->get_result();
    $trimesterScores = $scoresResult->fetch_all(MYSQLI_ASSOC);
    
   
    // Combine and send response
    echo json_encode([
        'success' => true,
        'tasks' => $tasks,
        'goals' => $goals,
        'scores' => $scores,
        'lastScore' => $lastScore,
        'ongoingRisks' => $ongoingRisks,
        'potentialRisks' => $potentialRisks,
        'trimesterScores' => $trimesterScores
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
?>
