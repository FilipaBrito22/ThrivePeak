<?php
include 'db_connection.php';
include ("inc/top.php");

// Initialize variables
$hasDepartmentOne = false;
$loggedInEmployee = null;
$managerHierarchy = [];

// Function to get all employees for building hierarchy
function getEmployees($conn) {
    $query = "SELECT Employee.EmployeeID, Employee.Name, JobRole.JobRole AS JobRole, Employee.ManagerID, Employee.DepartmentID 
            FROM Employee
            JOIN JobRole ON Employee.JobRoleID = JobRole.JobRoleID";
    $result = $conn->query($query);
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    return $employees;
}

// Recursive function to build the employee hierarchy
function buildHierarchy($employees, $startID) {
    $hierarchy = [];
    foreach ($employees as $employee) {
        if ($employee['ManagerID'] == $startID) {
            $employee['subordinates'] = buildHierarchy($employees, $employee['EmployeeID']);
            $hierarchy[] = $employee;
        }
    }
    return $hierarchy;
}

// Define the displayHierarchy function
function displayHierarchy($hierarchy) {
    echo '<ul class="hierarchy">';
    foreach ($hierarchy as $employee) {
        echo '<li>';
        echo '<div class="team-member">';
        echo '<div class="role-box">';
        echo '<p>' . $employee['JobRole'] . '</p>';
        echo '<span>' . $employee['Name'] . '</span>';
        echo '</div>';
        echo '</div>';
        
        // Display subordinates if they exist
        if (!empty($employee['subordinates'])) {
            displayHierarchy($employee['subordinates']);
        }
        echo '</li>';
    }
    echo '</ul>';
}

// Check if the user is logged in
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

        // Check if the logged-in employee has a manager
        if ($loggedInEmployee['ManagerID']) {
            $managerID = $loggedInEmployee['ManagerID'];
            
            // Fetch the manager's details
            $managerQuery = "SELECT Employee.EmployeeID, Employee.Name, JobRole.JobRole AS JobRole, Employee.ManagerID
                            FROM Employee
                            JOIN JobRole ON Employee.JobRoleID = JobRole.JobRoleID
                            WHERE Employee.EmployeeID = $managerID";
            $managerResult = $conn->query($managerQuery);
            
            if ($managerResult && $managerResult->num_rows > 0) {
                $manager = $managerResult->fetch_assoc();
                
                // Set the logged-in employee as a subordinate of their manager
                $loggedInEmployee['subordinates'] = buildHierarchy(getEmployees($conn), $loggedInEmployee['EmployeeID']);
                $manager['subordinates'] = [$loggedInEmployee];
                
                // Set the hierarchy starting with the manager
                $managerHierarchy = [$manager];
            }
        } else {
            // If there is no manager, just display the logged-in employee's hierarchy
            $loggedInEmployee['subordinates'] = buildHierarchy(getEmployees($conn), $loggedInEmployee['EmployeeID']);
            $managerHierarchy = [$loggedInEmployee];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThrivePeak Team Hierarchy</title>
    <link rel="stylesheet" href="css/team.css">
    <style>
        /* Modal styling */
        .modal {
        display: none; /* Hidden by default */
        position: fixed;
        z-index: 1000; /* Stay on top */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5); /* Black with opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;  /* Reduced margin to give more space */
            padding: 40px; /* Increased padding */
            border: 1px solid #888;
            width: 70%; /* Increased width to make the form larger */
            max-width: 700px; /* Adjust the max width if needed */
            position: relative; /* This makes it a positioning context for the close button */
            font-size: 1.2rem; /* Make text a bit larger */
        }

        /* Close button in the top-right corner */
        .close {
            position: absolute; /* Absolute positioning */
            top: 10px; /* 10px from the top */
            right: 10px; /* 10px from the right */
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px; /* Space between form elements */
        }

        label {
            font-size: 1.1rem; /* Larger label font size */
        }

        input, select, button {
            padding: 12px 20px; /* Larger padding for input fields */
            font-size: 1.1rem; /* Larger text in input fields */
            border-radius: 5px; /* Rounded corners */
            border: 1px solid #ccc;
            width: 100%; /* Make inputs and selects take full width */
        }

        button {
            background-color: #4CAF50; /* Green color for button */
            color: white;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #45a049; /* Darker green when hovering */
        }

        button:active {
            background-color: #4CAF50; /* Normal color when clicked */
        }

        
    </style>
    <script>
        // Function to open the modal
        function openModal() {
            document.getElementById("addEmployeeModal").style.display = "block";
        }

        // Function to close the modal
        function closeModal() {
            document.getElementById("addEmployeeModal").style.display = "none";
        }

        // Submit the form via AJAX
        function submitAddEmployeeForm(event) {
            event.preventDefault(); // Prevent the form from submitting traditionally
            const formData = new FormData(document.getElementById("addEmployeeForm"));

            fetch("add_employee.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message); // Show success message
                    closeModal(); // Close the modal on success
                    location.reload(); // Reload the page to display the updated hierarchy
                } else {
                    alert("Error: " + data.message); // Show error message
                }
            })
            .catch(error => {
                console.error("Error:", error);
            });
        }

        // Function to render the team hierarchy
        function renderHierarchy(hierarchy) {
            const teamContainer = document.querySelector('.team-hierarchy');
            teamContainer.innerHTML = ''; // Clear existing hierarchy
            displayHierarchy(hierarchy, teamContainer); // Re-render the hierarchy
        }

        // Recursive function to display hierarchy
        function displayHierarchy(hierarchy, container) {
            const ul = document.createElement('ul');
            ul.classList.add('hierarchy');
            hierarchy.forEach(employee => {
                const li = document.createElement('li');
                const div = document.createElement('div');
                div.classList.add('team-member');

                const roleBox = document.createElement('div');
                roleBox.classList.add('role-box');
                const roleP = document.createElement('p');
                roleP.textContent = employee.JobRole;
                const nameSpan = document.createElement('span');
                nameSpan.textContent = employee.Name;

                roleBox.appendChild(roleP);
                roleBox.appendChild(nameSpan);
                div.appendChild(roleBox);
                li.appendChild(div);

                if (employee.subordinates && employee.subordinates.length > 0) {
                    displayHierarchy(employee.subordinates, li); // Recursively display subordinates
                }
                ul.appendChild(li);
            });
            container.appendChild(ul);
        }
    </script>
</head>
<body>

<div class="container">
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
                <li class="menu-item active">
                    <img src="images/group.png" alt="team">
                    <a href="#">Team</a>
                </li>
                <li class="menu-item">
                    <img src="images/evaluation.png" alt="team">
                    <a href="#">Evaluation</a>
                </li>
            </ul>
        </nav>
        <hr>
        <div class="logout">
            <a href="autentica.php?logout=true">Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="user-info">
                <?php include ("inc/check_user.php")?>
            </div>
        </header>

        <section class="team-hierarchy">
            <h2>Team</h2>
            <?php if ($hasDepartmentOne): ?>
                <div class="dashboard-header">
                    <img src="images/user_plus.png" alt="Add User" onclick="openModal()">
                    <img src="images/user_minus.png" alt="Remove User">
                </div>
            <?php endif; ?>
            <?php displayHierarchy($managerHierarchy); ?> <!-- Display the current hierarchy -->
        </section>
    </main>
</div>

<!-- Modal for Add Employee -->
<div id="addEmployeeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Add New Employee</h2>
        <form id="addEmployeeForm" onsubmit="submitAddEmployeeForm(event)">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="department">Department:</label>
            <select id="department" name="department">
                <?php
                $departments = $conn->query("SELECT DepartmentID, Department FROM Department");
                while ($row = $departments->fetch_assoc()) {
                    echo '<option value="'.$row['DepartmentID'].'">'.$row['Department'].'</option>';
                }
                ?>
            </select>

            <label for="jobRole">Job Role:</label>
            <select id="jobRole" name="jobRole">
                <?php
                $jobRoles = $conn->query("SELECT JobRoleID, JobRole FROM JobRole");
                while ($row = $jobRoles->fetch_assoc()) {
                    echo '<option value="'.$row['JobRoleID'].'">'.$row['JobRole'].'</option>';
                }
                ?>
            </select>

            <label for="managerID">Manager:</label>
            <select id="managerID" name="managerID">
                <option value="">None</option>
                <?php
                $employees = $conn->query("SELECT EmployeeID, Name FROM Employee");
                while ($row = $employees->fetch_assoc()) {
                    echo '<option value="'.$row['EmployeeID'].'">'.$row['Name'].'</option>';
                }
                ?>
            </select>

            <button type="submit">Add Employee</button>
        </form>
    </div>
</div>

</body>
</html>



