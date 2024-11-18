<?php
session_start();
include_once 'db_connection.php';

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

// Check if the function `loadJobRoles` is already defined
if (!function_exists('loadJobRoles')) {
    // Function to load job roles for dropdowns
    function loadJobRoles($conn) {
        $result = $conn->query("SELECT JobRoleID, JobRole FROM JobRole");
        $options = "<option value=''>Select Job Role</option>";
        while ($row = $result->fetch_assoc()) {
            $options .= "<option value='{$row['JobRoleID']}'>{$row['JobRole']}</option>";
        }
        return $options;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action == 'fetch_weights') {
        $jobRoleID = $_POST['job_role_id'];
        $result = $conn->query("SELECT rcw.CriteriaID, c.CriteriaName, rcw.Weight 
                                FROM RoleCriteriaWeight rcw 
                                JOIN Criteria c ON rcw.CriteriaID = c.CriteriaID 
                                WHERE rcw.JobRoleID = $jobRoleID");

        $data = "";
        while ($row = $result->fetch_assoc()) {
            $data .= "<div class='criteria-item'>";
            $data .= "<label>{$row['CriteriaName']}:</label>";
            $data .= "<input type='number' class='weight-input' data-criteria-id='{$row['CriteriaID']}' value='{$row['Weight']}' step='0.01' min='0' max='1'>";
            $data .= "<img src='images/trash.png' class='delete-icon' data-criteria-id='{$row['CriteriaID']}' alt='Delete' style='width: 20px; cursor: pointer; margin-left: 10px;'>";
            $data .= "</div><br>";
        }
        echo $data;

    } elseif ($action == 'update_weights') {
        $jobRoleID = $_POST['job_role_id'];
        $weights = $_POST['weights'];

        foreach ($weights as $criteriaID => $weight) {
            $conn->query("UPDATE RoleCriteriaWeight SET Weight = $weight WHERE JobRoleID = $jobRoleID AND CriteriaID = $criteriaID");
        }

        echo "Weights updated successfully.";

    } elseif ($action == 'add_criteria') {
        $jobRoleID = $_POST['job_role_id'];
        $criterionName = $conn->real_escape_string($_POST['criterion_name']);
        $criterionWeight = (float)$_POST['criterion_weight'];

        // Insert new criterion into the Criteria table if it doesn't exist
        $conn->query("INSERT INTO Criteria (CriteriaName) VALUES ('$criterionName')");

        // Get the new CriteriaID
        $criteriaID = $conn->insert_id;

        // Associate the new criterion with the job role and weight
        $conn->query("INSERT INTO RoleCriteriaWeight (JobRoleID, CriteriaID, Weight) VALUES ($jobRoleID, $criteriaID, $criterionWeight)");

        echo "New criterion added successfully.";

    } elseif ($action == 'delete_criteria') {
        $criteriaID = $_POST['criteria_id'];
        $jobRoleID = $_POST['job_role_id'];

        // First, delete the criterion for the selected job role in RoleCriteriaWeight table
        $deleteRoleCriteriaWeight = $conn->query("DELETE FROM RoleCriteriaWeight WHERE JobRoleID = $jobRoleID AND CriteriaID = $criteriaID");

        // Check if this criterion is associated with any other job role
        $checkIfUsedElsewhere = $conn->query("SELECT COUNT(*) AS count FROM RoleCriteriaWeight WHERE CriteriaID = $criteriaID");
        $row = $checkIfUsedElsewhere->fetch_assoc();

        if ($row['count'] == 0) {
            // If the criterion is not used by any other job role, delete it from the Criteria table
            $deleteCriteria = $conn->query("DELETE FROM Criteria WHERE CriteriaID = $criteriaID");

            if ($deleteCriteria) {
                echo "Criterion deleted successfully from both tables.";
            } else {
                echo "Error deleting criterion from Criteria table: " . $conn->error;
            }
        } else {
            echo "Criterion deleted successfully from RoleCriteriaWeight table only.";
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThrivePeak Dashboard</title>
    <link rel="stylesheet" href="css/formula.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                        <li class="menu-item">
                            <img src="images/evaluation.png" alt="team">
                            <a href="#" class="evaluation-link">Evaluation</a>
                        </li>
                        <ul class="submenu">
                            <li><a href="individual_evaluation.php">Individual</a></li>
                            <li><a href="department_evaluation.php">Department</a></li>
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
                    <?php include ("inc/check_user.php")?>
                </div>
            </header>

            <!-- Adjust Criteria Weights Section -->
            <section class="formula-section">
                <h2>Adjust Criteria Weights</h2>
                <label for="job_role">Select Job Role:</label>
                <select id="job_role">
                    <?php echo loadJobRoles($conn); ?>
                </select>
                
                <div id="criteria-weights">
                    <!-- Criteria and weights will load here based on the selected job role -->
                </div>
                
                <button id="update_weights" class="primary-button">Update Weights</button>

                <h3>Add New Criterion</h3>
                <label for="new_criteria_name">Criterion Name:</label>
                <input type="text" id="new_criteria_name" placeholder="Enter Criterion Name">

                <label for="new_criteria_weight">Weight:</label>
                <input type="number" id="new_criteria_weight" step="0.01" min="0" max="1" placeholder="Enter Weight (0-1)">
                
                <button id="add_criteria" class="primary-button">Add New Criterion</button>
            </section>
        </main>
    </div>

    <script>
    document.querySelector('.evaluation-link').addEventListener('click', function(event) {
        event.preventDefault();
        const submenu = document.querySelector('.submenu');
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    });

    $(document).ready(function () {
        // Cambia de criterios al seleccionar un puesto de trabajo
        $('#job_role').change(function () {
            const jobRoleID = $(this).val();
            if (jobRoleID) {
                $.post('formula.php', { action: 'fetch_weights', job_role_id: jobRoleID }, function (data) {
                    $('#criteria-weights').html(data);
                });
            } else {
                $('#criteria-weights').empty();
            }
        });

        // Actualizar pesos
        $('#update_weights').click(function () {
            const jobRoleID = $('#job_role').val();
            const weights = {};

            $('.weight-input').each(function () {
                const criteriaID = $(this).data('criteria-id');
                const weight = $(this).val();
                weights[criteriaID] = weight;
            });

            $.post('formula.php', { action: 'update_weights', job_role_id: jobRoleID, weights: weights }, function (data) {
                alert(data);
            });
        });

        // Agregar nuevo criterio
        $('#add_criteria').click(function () {
            const jobRoleID = $('#job_role').val();
            const criterionName = $('#new_criteria_name').val();
            const criterionWeight = $('#new_criteria_weight').val();

            $.post('formula.php', { action: 'add_criteria', job_role_id: jobRoleID, criterion_name: criterionName, criterion_weight: criterionWeight }, function (data) {
                alert(data);
                $('#job_role').trigger('change'); // Refresca la lista de criterios
                
                // Limpiar los campos de entrada después de añadir el criterio
                $('#new_criteria_name').val('');
                $('#new_criteria_weight').val('');
            });
        });

        // Eliminar criterio
        $('#criteria-weights').on('click', '.delete-icon', function () {
            const criteriaID = $(this).data('criteria-id');
            const jobRoleID = $('#job_role').val();

            $.post('formula.php', { action: 'delete_criteria', criteria_id: criteriaID, job_role_id: jobRoleID }, function (data) {
                alert(data);
                $('#job_role').trigger('change'); // Refresca la lista de criterios
            });
        });
    });
    </script>

</body>
</html>

