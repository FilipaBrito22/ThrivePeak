<?php
header('Content-Type: application/json');
include 'db_connection.php';

if (isset($_GET['employeeID'])) {
    $employeeID = intval($_GET['employeeID']);

    // Fetch employee details including DepartmentID and Name
    $employeeQuery = "
        SELECT Employee.Name, Employee.DepartmentID
        FROM Employee
        WHERE Employee.EmployeeID = ?";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $employeeResult = $stmt->get_result();

    if ($employeeResult && $employeeResult->num_rows > 0) {
        $employeeData = $employeeResult->fetch_assoc();
        $employeeName = $employeeData['Name'];
        $departmentID = $employeeData['DepartmentID'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    // Fetch performance evaluations for the employee
    $scoresQuery = "SELECT Trimester, Year, TotalScore FROM EmployeeEvaluation WHERE EmployeeID = ? ORDER BY EvaluationDate";
    $stmt = $conn->prepare($scoresQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $scoresResult = $stmt->get_result();
    $scores = $scoresResult->fetch_all(MYSQLI_ASSOC);

    // Fetch the last score (most recent evaluation)
    $lastScoreQuery = "
        SELECT Trimester, Year, TotalScore 
        FROM EmployeeEvaluation 
        WHERE EmployeeID = ? 
        ORDER BY Year DESC, Trimester DESC 
        LIMIT 1";
    $stmt = $conn->prepare($lastScoreQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $lastScoreResult = $stmt->get_result();
    $lastScore = $lastScoreResult->fetch_assoc();

    // Fetch trimester scores for the department
    $departmentScoresQuery = "SELECT Trimester, Year, AverageScore FROM departmentevaluation WHERE DepartmentID = ? ORDER BY Year, Trimester";
    $stmt = $conn->prepare($departmentScoresQuery);
    $stmt->bind_param("i", $departmentID);
    $stmt->execute();
    $departmentScoresResult = $stmt->get_result();
    $trimesterScores = $departmentScoresResult->fetch_all(MYSQLI_ASSOC);

    // Fetch criteria scores
    $criteriaScoresQuery = "
        SELECT 
            c.CriteriaName, e.Score AS AverageScoreCriteria
        FROM evaluationcriteriascore e
        JOIN criteria c ON e.CriteriaID = c.CriteriaID
        JOIN employeeevaluation ev ON e.EvaluationID = ev.EvaluationID
        WHERE ev.EmployeeID = ?
        GROUP BY c.CriteriaName
        ORDER BY c.CriteriaName";
    $stmt = $conn->prepare($criteriaScoresQuery);
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $criteriaScoresResult = $stmt->get_result();
    $criteriaScores = $criteriaScoresResult->fetch_all(MYSQLI_ASSOC);

    // Generate feedback
    $generatedFeedback = "";
    if (!empty($criteriaScores)) {
        foreach ($criteriaScores as $criteria) {
            $criteriaName = $criteria['CriteriaName'];
            $AverageScoreCriteria = round($criteria['AverageScoreCriteria'], 2);

            if ($AverageScoreCriteria >= 8) {
                $generatedFeedback .= "Excellent performance in $criteriaName with an average score of $AverageScoreCriteria.\n";
            } elseif ($AverageScoreCriteria >= 5) {
                $generatedFeedback .= "Good performance in $criteriaName with an average score of $AverageScoreCriteria, but there is room for improvement.\n";
            } else {
                $generatedFeedback .= "$criteriaName requires significant improvement, as the average score is $AverageScoreCriteria.\n";
            }
        }
    }

    // Combine and send response
    echo json_encode([
        'success' => true,
        'employeeName' => $employeeName,
        'scores' => $scores,
        'lastScore' => $lastScore,
        'trimesterScores' => $trimesterScores,
        'feedback' => $generatedFeedback,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
?>
