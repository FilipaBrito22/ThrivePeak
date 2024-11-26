<?php
header('Content-Type: application/json');
include 'db_connection.php';

// Get the department ID or employee ID
if (isset($_GET['departmentID'])) {
    $departmentID = intval($_GET['departmentID']);

    // Recuperar nome do departamento
    $departmentNameQuery = "SELECT Department FROM Department WHERE DepartmentID = ?";
    $stmt = $conn->prepare($departmentNameQuery);
    $stmt->bind_param("i", $departmentID);
    $stmt->execute();
    $departmentNameResult = $stmt->get_result();
    $departmentName = $departmentNameResult->fetch_assoc()['Department'] ?? 'Unknown Department';

    // Fetch trimester scores for the graph
    $scoresQuery = "SELECT Trimester, Year, AverageScore FROM departmentevaluation WHERE DepartmentID = ? ORDER BY Year, Trimester";
    $stmt = $conn->prepare($scoresQuery);
    $stmt->bind_param("i", $departmentID);
    $stmt->execute();
    $scoresResult = $stmt->get_result();
    $trimesterScores = $scoresResult->fetch_all(MYSQLI_ASSOC);

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

 
        // Fetch trimester scores
        $scoresQuery = "
            SELECT Trimester, Year, AverageScore 
            FROM departmentevaluation 
            WHERE DepartmentID = ? 
            ORDER BY Year, Trimester";
        $stmt = $conn->prepare($scoresQuery);
        $stmt->bind_param("i", $departmentID);
        $stmt->execute();
        $scoresResult = $stmt->get_result();
        $trimesterScoresCriteria = $scoresResult->fetch_all(MYSQLI_ASSOC);
    
        // Fetch criteria scores
        $criteriaScoresQuery = "
            SELECT 
                c.CriteriaName, 
                AVG(e.Score) AS AverageScoreCriteria
            FROM evaluationcriteriascore e
            JOIN criteria c ON e.CriteriaID = c.CriteriaID
            JOIN employeeevaluation ev ON e.EvaluationID = ev.EvaluationID
            JOIN employee ee ON ev.EmployeeID = ee.EmployeeID
            WHERE ee.DepartmentID = ?
            GROUP BY c.CriteriaName
            ORDER BY c.CriteriaName";
        $stmt = $conn->prepare($criteriaScoresQuery);
        $stmt->bind_param("i", $departmentID);
        $stmt->execute();
        $criteriaScoresResult = $stmt->get_result();
        $criteriaScores = $criteriaScoresResult->fetch_all(MYSQLI_ASSOC);
    
        // Generate feedback
        $generatedFeedback = "";
        if (!empty($criteriaScores)) {
            foreach ($criteriaScores as $criteria) {
                $criteriaName = $criteria['CriteriaName'];
                $AverageScoreCriteria = round($criteria['AverageScoreCriteria'], 2); // Round to two decimal places

                if ($AverageScoreCriteria >= 8) {
                    $generatedFeedback .= "Excellent performance in $criteriaName with an average score of $AverageScoreCriteria.\n";
                } elseif ($AverageScoreCriteria >= 5) {
                    $generatedFeedback .= "Good performance in $criteriaName with an average score of $AverageScoreCriteria, but there is room for improvement.\n";
                } else {
                    $generatedFeedback .= "$criteriaName requires significant improvement, as the average score is $AverageScoreCriteria.\n";
                }
            }

    echo json_encode([
        'success' => true,
        'departmentName' => $departmentName, // Nome do departamento avaliado
        'trimesterScores' => $trimesterScores,
        'averageScore' => $averageScore, // Add averageScore to response
        'totalEmployeesDepartment' => $totalEmployeesDepartment, // Add totalEmployeesDepartment to response
        'feedback' => $generatedFeedback,

    ]);    
}exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
