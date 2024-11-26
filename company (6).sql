-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 25-Nov-2024 às 20:14
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `company`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `companyevaluation`
--

CREATE TABLE `companyevaluation` (
  `EvaluationID` int(11) NOT NULL,
  `AverageScore` decimal(5,2) NOT NULL,
  `Trimester` int(11) NOT NULL,
  `Year` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `companyevaluation`
--

INSERT INTO `companyevaluation` (`EvaluationID`, `AverageScore`, `Trimester`, `Year`) VALUES
(6, 4.20, 4, 2023),
(7, 6.70, 1, 2024),
(8, 5.50, 2, 2024),
(9, 5.65, 3, 2024),
(10, 6.40, 4, 2024);

-- --------------------------------------------------------

--
-- Estrutura da tabela `criteria`
--

CREATE TABLE `criteria` (
  `CriteriaID` int(11) NOT NULL,
  `CriteriaName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `criteria`
--

INSERT INTO `criteria` (`CriteriaID`, `CriteriaName`) VALUES
(1, 'Technical Skills'),
(2, 'Communication'),
(3, 'Problem Solving'),
(4, 'Leadership'),
(5, 'Team Collaboration'),
(6, 'Attendance'),
(20, 'test3'),
(23, 'test4');

-- --------------------------------------------------------

--
-- Estrutura da tabela `department`
--

CREATE TABLE `department` (
  `DepartmentID` int(11) NOT NULL,
  `Department` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `department`
--

INSERT INTO `department` (`DepartmentID`, `Department`) VALUES
(1, 'Human Resources'),
(2, 'Finance'),
(3, 'IT'),
(4, 'Marketing'),
(5, 'Sales'),
(6, 'Operations'),
(7, 'Customer Service'),
(8, 'Research and Development'),
(9, 'Legal'),
(10, 'Procurement'),
(11, 'Quality Assurance'),
(12, 'Public Relations');

-- --------------------------------------------------------

--
-- Estrutura da tabela `departmentevaluation`
--

CREATE TABLE `departmentevaluation` (
  `DepartmentEvaluationID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `AverageScore` decimal(5,2) NOT NULL,
  `Trimester` int(11) NOT NULL,
  `Year` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `departmentevaluation`
--

INSERT INTO `departmentevaluation` (`DepartmentEvaluationID`, `DepartmentID`, `AverageScore`, `Trimester`, `Year`) VALUES
(1, 5, 7.80, 3, 2024),
(2, 1, 9.15, 3, 2024),
(3, 11, 4.20, 4, 2023),
(4, 11, 6.70, 1, 2024),
(5, 11, 5.50, 2, 2024),
(6, 11, 0.00, 3, 2024),
(7, 11, 6.40, 4, 2024);

-- --------------------------------------------------------

--
-- Estrutura da tabela `employee`
--

CREATE TABLE `employee` (
  `EmployeeID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `JobRoleID` int(11) DEFAULT NULL,
  `ManagerID` int(11) DEFAULT NULL,
  `Photo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `employee`
--

INSERT INTO `employee` (`EmployeeID`, `Name`, `DepartmentID`, `Email`, `Password`, `JobRoleID`, `ManagerID`, `Photo`) VALUES
(1, 'Alice Smith', 11, 'alice.smith@example.com', '$2y$10$RMZ1apl.Sx2ZaIY0.wMC1O30wxS9iWymW/9ktEhq16/btmKFDlKxS', 1, 2, 'images/alice.JPEG'),
(2, 'Bob Jones', 5, 'bob.jones@example.com', '$2y$10$AY2q.7dyYQtuiefIoTSJZOuU6.U2rK5/arkMoHombeHwIhMf1SFNi', 1, NULL, 'images/bob.JPG'),
(3, 'Emma Brown', 1, 'emma.brown@example.com', '$2y$10$RKKhGJIPttGYkq7nWWw86uhFQ7p4Xl5L4UhMiZ60e0aBda3gpWxue', 3, 2, 'images/emma.JPG'),
(4, 'Charlie Davis', 11, 'charlie.davis@example.com', '$2y$10$juUtCajGu5hnsK2uN6SP1OC0qddCwq/JTZAj.iTR/kr/.ljSu7Z32', 2, 3, 'images/charlie.JPG'),
(5, 'Sarah Wilson', 8, 'sarah.wilson@example.com', '$2y$10$DPE.mrcD3mIXaZizwAZSeOF7WPO0NpK5Bd98EE8Ja4HXKZnpjoR6m', 2, 3, ''),
(15, 'John Doe', 1, 'john.doe@example.com', '$2y$10$hcaJ.spHoMc/Z0ROzXHyzOimIc3eQdSIwetaRASxpTKTMbqf7OOWW', 2, 3, 'images/charlie.jpg');

-- --------------------------------------------------------

--
-- Estrutura da tabela `employeeevaluation`
--

CREATE TABLE `employeeevaluation` (
  `EvaluationID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `TotalScore` decimal(10,2) NOT NULL,
  `EvaluationDate` timestamp NULL DEFAULT current_timestamp(),
  `JobRoleID` int(11) DEFAULT NULL,
  `ManagerFeedback` varchar(255) NOT NULL,
  `Trimester` int(11) DEFAULT NULL,
  `Year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `employeeevaluation`
--

INSERT INTO `employeeevaluation` (`EvaluationID`, `EmployeeID`, `TotalScore`, `EvaluationDate`, `JobRoleID`, `ManagerFeedback`, `Trimester`, `Year`) VALUES
(1, 2, 7.80, '2024-10-04 23:00:00', 1, '', 3, 2024),
(52, 3, 9.15, '2024-10-31 00:00:00', 1, '', 3, 2024),
(53, 4, 0.00, '2024-11-04 17:41:56', 2, 'You need to improve', 3, 2024),
(54, 4, 4.20, '2024-11-04 17:43:58', 2, 'You need to improve', 4, 2023),
(55, 4, 6.40, '2024-11-15 21:17:43', 2, 'good', 4, 2024),
(56, 4, 6.70, '2024-11-15 21:25:32', 2, '', 1, 2024),
(57, 4, 5.50, '2024-11-15 21:27:43', 2, '', 2, 2024);

-- --------------------------------------------------------

--
-- Estrutura da tabela `evaluationcriteriascore`
--

CREATE TABLE `evaluationcriteriascore` (
  `EvaluationCriteriaScoreID` int(11) NOT NULL,
  `EvaluationID` int(11) DEFAULT NULL,
  `CriteriaID` int(11) DEFAULT NULL,
  `Score` decimal(4,2) DEFAULT NULL,
  `EvaluationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `StartEvaluation` date DEFAULT NULL,
  `EndEvaluation` date DEFAULT NULL,
  `Trimester` int(11) DEFAULT NULL,
  `Year` int(11) DEFAULT NULL,
  `EvaluationType` enum('mid-trimester','final') DEFAULT 'final'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `evaluationcriteriascore`
--

INSERT INTO `evaluationcriteriascore` (`EvaluationCriteriaScoreID`, `EvaluationID`, `CriteriaID`, `Score`, `EvaluationDate`, `StartEvaluation`, `EndEvaluation`, `Trimester`, `Year`, `EvaluationType`) VALUES
(1, 1, 1, 8.00, '2024-11-01 15:51:43', '2024-02-01', '2024-02-28', NULL, NULL, 'final'),
(2, 1, 2, 7.00, '2024-11-01 15:51:43', '2024-02-01', '2024-02-28', NULL, NULL, 'final'),
(3, 1, 3, 8.50, '2024-11-01 15:51:43', '2024-02-01', '2024-02-28', NULL, NULL, 'final'),
(154, 52, 1, 8.00, '2024-11-01 15:51:43', '2024-01-01', '2024-01-31', NULL, NULL, 'final'),
(155, 52, 2, 9.00, '2024-11-01 15:51:43', '2024-01-01', '2024-01-31', NULL, NULL, 'final'),
(156, 52, 3, 10.00, '2024-11-01 15:51:43', '2024-01-01', '2024-01-31', NULL, NULL, 'final'),
(157, 52, 6, 9.00, '2024-11-01 15:51:43', '2024-01-01', '2024-01-31', NULL, NULL, 'final'),
(158, 54, 2, 4.00, '2024-11-04 17:43:58', '2024-11-01', '2024-11-04', NULL, NULL, 'final'),
(159, 54, 4, 4.00, '2024-11-04 17:43:59', '2024-11-01', '2024-11-04', NULL, NULL, 'final'),
(160, 54, 5, 4.00, '2024-11-04 17:43:59', '2024-11-01', '2024-11-04', NULL, NULL, 'final'),
(161, 55, 2, 8.00, '2024-11-15 21:17:43', NULL, NULL, 4, 2024, 'final'),
(162, 55, 4, 7.00, '2024-11-15 21:17:43', NULL, NULL, 4, 2024, 'final'),
(163, 55, 5, 4.00, '2024-11-15 21:17:43', NULL, NULL, 4, 2024, 'final'),
(164, 56, 2, 8.00, '2024-11-15 21:25:32', NULL, NULL, NULL, NULL, 'final'),
(165, 56, 4, 7.00, '2024-11-15 21:25:32', NULL, NULL, NULL, NULL, 'final'),
(166, 56, 5, 5.00, '2024-11-15 21:25:32', NULL, NULL, NULL, NULL, 'final'),
(167, 57, 2, 8.00, '2024-11-15 21:27:43', NULL, NULL, NULL, NULL, 'final'),
(168, 57, 4, 7.00, '2024-11-15 21:27:43', NULL, NULL, NULL, NULL, 'final'),
(169, 57, 5, 1.00, '2024-11-15 21:27:43', NULL, NULL, NULL, NULL, 'final');

--
-- Acionadores `evaluationcriteriascore`
--
DELIMITER $$
CREATE TRIGGER `update_total_score` AFTER INSERT ON `evaluationcriteriascore` FOR EACH ROW BEGIN
    UPDATE EmployeeEvaluation
    SET TotalScore = (
        SELECT SUM(ecs.Score * rcw.Weight)
        FROM EvaluationCriteriaScore ecs
        JOIN RoleCriteriaWeight rcw
        ON ecs.CriteriaID = rcw.CriteriaID
        WHERE ecs.EvaluationID = NEW.EvaluationID
        AND rcw.JobRoleID = (SELECT JobRoleID FROM EmployeeEvaluation WHERE EvaluationID = NEW.EvaluationID)
    )
    WHERE EvaluationID = NEW.EvaluationID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `jobrole`
--

CREATE TABLE `jobrole` (
  `JobRoleID` int(11) NOT NULL,
  `JobRole` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `jobrole`
--

INSERT INTO `jobrole` (`JobRoleID`, `JobRole`) VALUES
(1, 'Software Engineer'),
(2, 'Project Manager'),
(3, 'HR Specialist');

-- --------------------------------------------------------

--
-- Estrutura da tabela `rolecriteriaweight`
--

CREATE TABLE `rolecriteriaweight` (
  `RoleCriteriaID` int(11) NOT NULL,
  `JobRoleID` int(11) DEFAULT NULL,
  `CriteriaID` int(11) DEFAULT NULL,
  `Weight` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `rolecriteriaweight`
--

INSERT INTO `rolecriteriaweight` (`RoleCriteriaID`, `JobRoleID`, `CriteriaID`, `Weight`) VALUES
(1, 1, 1, 0.35),
(2, 1, 2, 0.25),
(3, 1, 3, 0.10),
(4, 2, 2, 0.30),
(5, 2, 4, 0.40),
(6, 2, 5, 0.30),
(7, 3, 2, 0.30),
(8, 3, 5, 0.70),
(9, 1, 6, 0.05),
(23, 1, 20, 0.15),
(26, 1, 23, 0.10);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `companyevaluation`
--
ALTER TABLE `companyevaluation`
  ADD PRIMARY KEY (`EvaluationID`);

--
-- Índices para tabela `criteria`
--
ALTER TABLE `criteria`
  ADD PRIMARY KEY (`CriteriaID`);

--
-- Índices para tabela `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`DepartmentID`);

--
-- Índices para tabela `departmentevaluation`
--
ALTER TABLE `departmentevaluation`
  ADD PRIMARY KEY (`DepartmentEvaluationID`),
  ADD KEY `DepartmentID` (`DepartmentID`);

--
-- Índices para tabela `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`EmployeeID`),
  ADD KEY `ManagerID` (`ManagerID`),
  ADD KEY `fk_department` (`DepartmentID`),
  ADD KEY `fk_jobrole` (`JobRoleID`);

--
-- Índices para tabela `employeeevaluation`
--
ALTER TABLE `employeeevaluation`
  ADD PRIMARY KEY (`EvaluationID`),
  ADD UNIQUE KEY `EmployeeID` (`EmployeeID`,`Trimester`,`Year`),
  ADD KEY `JobRoleID` (`JobRoleID`);

--
-- Índices para tabela `evaluationcriteriascore`
--
ALTER TABLE `evaluationcriteriascore`
  ADD PRIMARY KEY (`EvaluationCriteriaScoreID`),
  ADD KEY `EvaluationID` (`EvaluationID`),
  ADD KEY `CriteriaID` (`CriteriaID`);

--
-- Índices para tabela `jobrole`
--
ALTER TABLE `jobrole`
  ADD PRIMARY KEY (`JobRoleID`);

--
-- Índices para tabela `rolecriteriaweight`
--
ALTER TABLE `rolecriteriaweight`
  ADD PRIMARY KEY (`RoleCriteriaID`),
  ADD KEY `JobRoleID` (`JobRoleID`),
  ADD KEY `CriteriaID` (`CriteriaID`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `companyevaluation`
--
ALTER TABLE `companyevaluation`
  MODIFY `EvaluationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `criteria`
--
ALTER TABLE `criteria`
  MODIFY `CriteriaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `departmentevaluation`
--
ALTER TABLE `departmentevaluation`
  MODIFY `DepartmentEvaluationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `employee`
--
ALTER TABLE `employee`
  MODIFY `EmployeeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `employeeevaluation`
--
ALTER TABLE `employeeevaluation`
  MODIFY `EvaluationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de tabela `evaluationcriteriascore`
--
ALTER TABLE `evaluationcriteriascore`
  MODIFY `EvaluationCriteriaScoreID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT de tabela `jobrole`
--
ALTER TABLE `jobrole`
  MODIFY `JobRoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `rolecriteriaweight`
--
ALTER TABLE `rolecriteriaweight`
  MODIFY `RoleCriteriaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `departmentevaluation`
--
ALTER TABLE `departmentevaluation`
  ADD CONSTRAINT `departmentevaluation_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`);

--
-- Limitadores para a tabela `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`),
  ADD CONSTRAINT `fk_jobrole` FOREIGN KEY (`JobRoleID`) REFERENCES `jobrole` (`JobRoleID`),
  ADD CONSTRAINT `fk_manager` FOREIGN KEY (`ManagerID`) REFERENCES `employee` (`EmployeeID`);

--
-- Limitadores para a tabela `employeeevaluation`
--
ALTER TABLE `employeeevaluation`
  ADD CONSTRAINT `employeeevaluation_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`),
  ADD CONSTRAINT `employeeevaluation_ibfk_2` FOREIGN KEY (`JobRoleID`) REFERENCES `jobrole` (`JobRoleID`);

--
-- Limitadores para a tabela `evaluationcriteriascore`
--
ALTER TABLE `evaluationcriteriascore`
  ADD CONSTRAINT `evaluationcriteriascore_ibfk_1` FOREIGN KEY (`EvaluationID`) REFERENCES `employeeevaluation` (`EvaluationID`),
  ADD CONSTRAINT `evaluationcriteriascore_ibfk_2` FOREIGN KEY (`CriteriaID`) REFERENCES `criteria` (`CriteriaID`);

--
-- Limitadores para a tabela `rolecriteriaweight`
--
ALTER TABLE `rolecriteriaweight`
  ADD CONSTRAINT `rolecriteriaweight_ibfk_1` FOREIGN KEY (`JobRoleID`) REFERENCES `jobrole` (`JobRoleID`),
  ADD CONSTRAINT `rolecriteriaweight_ibfk_2` FOREIGN KEY (`CriteriaID`) REFERENCES `criteria` (`CriteriaID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
