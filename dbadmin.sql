-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2025 at 02:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbadmin`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `employeeID` varchar(20) NOT NULL,
  `username` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `position` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`employeeID`, `username`, `firstname`, `middlename`, `lastname`, `password`, `position`, `email`, `type`, `status`) VALUES
('E001', 'Gabriel James V. Valdez', 'Gabriel James', 'V.', 'Valdez', '482c811da5d5b4bc6d497ffa98491e38', 'Sci. Res. Assist.', 'gabrieljames.valdez@irc.pshs.edu.ph', 'staff', 'active'),
('E002', 'Christian Benedict S. Soy', 'Christian Benedict', 'S.', 'Soy', 'b433aa67dbac4b04f50b3202169aea7e', 'Teacher', 'christianbenedict.soy@irc.pshs.edu.ph', 'staff', 'active'),
('E004', 'Zyx Leiabe B. Barangan', 'Zyx Leiabe', 'B.', 'Barangan', '482c811da5d5b4bc6d497ffa98491e38', 'Teacher', 'zyxleiabe.barangan@irc.pshs.edu.ph', 'staff', 'active'),
('E005', 'Liana Gabrielle R. Roque', 'Liana Gabrielle', 'R.', 'Roque', '2a1b95ecc2825a27ceef9c28e0b985f5', 'Teacher', 'lianagabrielle.roque@irc.pshs.edu.ph', 'staff', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `current`
--

CREATE TABLE `current` (
  `id` int(11) NOT NULL,
  `description` varchar(11) NOT NULL DEFAULT 'School Year',
  `value` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `current`
--

INSERT INTO `current` (`id`, `description`, `value`) VALUES
(1, 'School Year', '2020-2021'),
(2, 'School Year', '2021-2022'),
(3, 'School Year', '2022-2023'),
(4, 'School Year', '2023-2024'),
(5, 'School Year', '2024-2025');

-- --------------------------------------------------------

--
-- Table structure for table `scilab_availability`
--

CREATE TABLE `scilab_availability` (
  `id` int(11) NOT NULL,
  `scilabName` varchar(30) NOT NULL,
  `mainImagePath` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `availability` varchar(13) NOT NULL DEFAULT 'Available',
  `status` varchar(8) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `scilab_availability`
--

INSERT INTO `scilab_availability` (`id`, `scilabName`, `mainImagePath`, `location`, `availability`, `status`) VALUES
(1, 'Science Laboratory 1', 'img/labimages/lab1.jpg', 'Third Floor, Advance Science and Technology Building, PSHS-IRC', 'Available', 'active'),
(2, 'Science Laboratory 2', 'img/labimages/lab2.jpg', 'Third Floor, Advance Science and Technology Building, PSHS-IRC', 'Available', 'active'),
(3, 'Science Laboratory 3', 'img/labimages/lab3.jpg', 'Third Floor, Advance Science and Technology Building, PSHS-IRC', 'Available', 'active'),
(4, 'Science Laboratory 4', 'img/labimages/lab4.jpg', 'Third Floor, Advance Science and Technology Building, PSHS-IRC', 'Available', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `scilab_form_requests`
--

CREATE TABLE `scilab_form_requests` (
  `statusScilabPersonnel` varchar(8) NOT NULL DEFAULT 'pending',
  `id` int(11) NOT NULL,
  `requesterEmployeeID` varchar(50) NOT NULL,
  `subjectAcademicUnit` varchar(50) NOT NULL,
  `controlNumber` int(100) NOT NULL,
  `scilabName` varchar(255) NOT NULL,
  `teacherInCharge` varchar(100) NOT NULL,
  `sy` varchar(10) NOT NULL,
  `gradeLevel` int(2) NOT NULL,
  `section/s` varchar(255) NOT NULL,
  `subject` varchar(25) NOT NULL,
  `subjectTopic` varchar(100) NOT NULL,
  `inclusiveDate` date NOT NULL,
  `inclusiveTime` varchar(255) NOT NULL,
  `dateRequested` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `feedback` varchar(999) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `scilab_form_requests`
--

INSERT INTO `scilab_form_requests` (`statusScilabPersonnel`, `id`, `requesterEmployeeID`, `subjectAcademicUnit`, `controlNumber`, `scilabName`, `teacherInCharge`, `sy`, `gradeLevel`, `section/s`, `subject`, `subjectTopic`, `inclusiveDate`, `inclusiveTime`, `dateRequested`, `feedback`) VALUES
('pending', 19, 'E004', '', 0, 'Science Laboratory 1', 'Barangan, Zyx Leiabe B.', '2024-2025', 7, 'Diamond, Emerald, Ruby, Sapphire', 'Integrated Science', 'Cellular Respiration', '2025-10-27', '9:47 AM to 11:47 AM', '2025-10-23 19:47:24', '');

-- --------------------------------------------------------

--
-- Table structure for table `scilab_inventory`
--

CREATE TABLE `scilab_inventory` (
  `id` int(11) NOT NULL,
  `productID` varchar(12) NOT NULL,
  `item` varchar(255) NOT NULL,
  `classification` varchar(20) NOT NULL,
  `quantity` int(6) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `status` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `scilab_inventory`
--

INSERT INTO `scilab_inventory` (`id`, `productID`, `item`, `classification`, `quantity`, `unit`, `description`, `status`) VALUES
(1, 'E-001', 'Beaker', 'Equipment', 12, 'pieces', '250mL glass beaker', 'Available'),
(2, 'E-002', 'Beaker', 'Equipment', 10, 'pieces', '500mL glass beaker', 'Available'),
(3, 'E-003', 'Beaker', 'Equipment', 8, 'pieces', '500mL Pyrex beaker', 'Available'),
(4, 'E-004', 'Beaker', 'Equipment', 6, 'pieces', '1000mL borosilicate beaker', 'Available'),
(5, 'E-005', 'Flask', 'Equipment', 15, 'pieces', '250mL Erlenmeyer flask, glass', 'Available'),
(6, 'E-006', 'Flask', 'Equipment', 10, 'pieces', '500mL round-bottom flask, borosilicate', 'Available'),
(7, 'E-007', 'Microscope', 'Equipment', 3, 'units', 'Compound light microscope with 1000x magnification', 'Available'),
(8, 'E-008', 'Scanning Electron Microscope', 'Equipment', 1, 'unit', 'High-resolution SEM, 500,000x magnification', 'Available'),
(9, 'E-009', 'Centrifuge Machine', 'Equipment', 2, 'units', '12-slot rotor, max 15,000 RPM, temperature-controlled', 'Available'),
(10, 'E-010', 'pH Meter', 'Equipment', 4, 'units', 'Digital pH meter with automatic calibration', 'Available'),
(11, 'E-011', 'Digital Weighing Scale', 'Equipment', 3, 'units', 'Precision ±0.001g, 200g capacity', 'Available'),
(12, 'E-012', 'Hot Plate Stirrer', 'Equipment', 5, 'units', 'Magnetic stirrer with temperature control up to 300°C', 'Available'),
(13, 'C-001', 'Sodium Bicarbonate', 'Consumable', 6, 'boxes', 'Analytical grade, 99.7% purity', 'Available'),
(14, 'C-002', 'Hydrochloric Acid (HCl)', 'Consumable', 5000, 'mL', 'Concentration: 37% w/w', 'Available'),
(15, 'C-003', 'Hydrochloric Acid (HCl)', 'Consumable', 2500, 'mL', 'Concentration: 10% w/w (diluted)', 'Available'),
(16, 'C-004', 'Ethanol', 'Consumable', 4, 'L', 'Concentration: 95% v/v', 'Available'),
(17, 'C-005', 'Ethanol', 'Consumable', 3, 'L', 'Concentration: 70% v/v', 'Available'),
(18, 'C-006', 'Sodium Chloride', 'Consumable', 10, 'kg', 'Purity: 99.5% NaCl, laboratory grade', 'Available'),
(19, 'C-007', 'Sulfuric Acid (H?SO?)', 'Consumable', 2000, 'mL', 'Concentration: 98%', 'Out of Stock'),
(20, 'C-008', 'Acetic Acid (CH?COOH)', 'Consumable', 2500, 'mL', 'Concentration: 99.8% glacial', 'Available'),
(21, 'C-009', 'Ammonium Hydroxide (NH?OH)', 'Consumable', 1500, 'mL', 'Concentration: 28%', 'Available'),
(22, 'C-010', 'Phenolphthalein Indicator', 'Consumable', 500, 'mL', 'Concentration: 1% in ethanol', 'Available'),
(23, 'C-011', 'Distilled Water', 'Consumable', 20, 'L', 'Conductivity < 1 µS/cm, pH 6.8–7.2', 'Available'),
(24, 'C-012', 'Sodium Hydroxide Pellets', 'Consumable', 5, 'kg', 'Purity: 98% NaOH, corrosive pellets', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `scilab_material_requests`
--

CREATE TABLE `scilab_material_requests` (
  `formID` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `item` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `description` varchar(50) NOT NULL,
  `issuedCondition` varchar(25) NOT NULL,
  `returnedCondition` varchar(25) NOT NULL,
  `returnedItemInspector` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `scilab_material_requests`
--

INSERT INTO `scilab_material_requests` (`formID`, `id`, `item`, `quantity`, `unit`, `description`, `issuedCondition`, `returnedCondition`, `returnedItemInspector`) VALUES
(19, 15, 'Sodium Chloride', 1, 'kg', 'Purity: 99.5% NaCl, laboratory grade', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `scilab_students_involved`
--

CREATE TABLE `scilab_students_involved` (
  `formID` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `id` int(11) NOT NULL,
  `grade` varchar(5) NOT NULL,
  `section` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`id`, `grade`, `section`) VALUES
(1, '7', 'Diamond'),
(2, '7', 'Emerald'),
(3, '7', 'Ruby'),
(4, '7', 'Sapphire'),
(5, '8', 'Adelfa'),
(6, '8', 'Camia'),
(7, '8', 'Dahlia'),
(8, '8', 'Sampaguita'),
(9, '9', 'Beryllium'),
(10, '9', 'Barium'),
(11, '9', 'Cesium'),
(12, '9', 'Lithium'),
(13, '10', 'Electron'),
(14, '10', 'Graviton'),
(15, '10', 'Photon'),
(16, '10', 'Boson'),
(17, '11', 'Newton'),
(18, '11', 'Curie'),
(19, '11', 'Mendel'),
(20, '11', 'Dalton'),
(21, '12', 'Alpha'),
(22, '12', 'Delta'),
(23, '12', 'Omega');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `LRN` varchar(50) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `birthdate` varchar(20) NOT NULL,
  `entryData` varchar(25) NOT NULL,
  `scholarhipCategory` varchar(25) NOT NULL,
  `batch` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`LRN`, `lastname`, `firstname`, `middlename`, `sex`, `birthdate`, `entryData`, `scholarhipCategory`, `batch`) VALUES
('1111111111', 'Dumlao', 'Rojan Joefel', 'T', 'Male', '2009-01-01', '2020', 'Partial 1', '2027'),
('1234567890', 'Dela Cruz', 'Juan', 'Santos', 'Male', '2007-05-10', '2020', 'Full Scholar', '2028'),
('2345678901', 'Reyes', 'Maria', 'Clara', 'Female', '2006-08-15', '2019', 'Partial 1', '2029'),
('3456789012', 'Santos', 'Pedro', 'Lopez', 'Male', '2008-03-20', '2021', 'Partial 2', '2028'),
('4567890123', 'Garcia', 'Ana', 'Luz', 'Female', '2007-11-30', '2020', 'Partial 3', '2028'),
('5678901234', 'Torres', 'Luis', 'Enrique', 'Male', '2006-01-05', '2018', 'Full Scholar', '2027');

-- --------------------------------------------------------

--
-- Table structure for table `student_directory`
--

CREATE TABLE `student_directory` (
  `LRN` varchar(50) NOT NULL,
  `id` int(11) NOT NULL,
  `studentEmail` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_directory`
--

INSERT INTO `student_directory` (`LRN`, `id`, `studentEmail`) VALUES
('1111111111', 5, 'rojanjoefel.dumlao@irc.pshs.edu.ph');

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `id` int(11) NOT NULL,
  `subjectCode` varchar(50) NOT NULL,
  `subjectDescription` varchar(255) NOT NULL,
  `subjectUnit` varchar(10) NOT NULL,
  `subjectAcademicUnit` varchar(100) NOT NULL,
  `subjectGradeLevel` varchar(2) NOT NULL,
  `status` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`id`, `subjectCode`, `subjectDescription`, `subjectUnit`, `subjectAcademicUnit`, `subjectGradeLevel`, `status`) VALUES
(1, 'SCI101', 'Integrated Science', '3', 'Science', '7', 'active'),
(2, 'MATH101', 'Mathematics', '3', 'Mathematics', '7', 'active'),
(3, 'CS101', 'Computer Science', '2', 'Computer Studies', '7', 'active'),
(4, 'ENG101', 'English', '3', 'Languages', '7', 'active'),
(5, 'FIL101', 'Filipino', '2', 'Languages', '7', 'active'),
(6, 'SOCSCI101', 'Social Science', '2', 'Social Studies', '7', 'active'),
(7, 'PEHM101', 'PEHM', '2', 'MAPEH', '7', 'active'),
(8, 'VE101', 'Values Education', '1', 'Values Education', '7', 'active'),
(9, 'ADTECH101', 'Adtech', '2', 'Technology', '7', 'active'),
(10, 'EARTH101', 'Earth Science', '3', 'Science', '8', 'active'),
(11, 'STAT101', 'Statistics', '3', 'Mathematics', '9', 'active'),
(12, 'BIO101', 'Biology', '3', 'Science', '9', 'active'),
(13, 'CHEM101', 'Chemistry', '3', 'Science', '10', 'active'),
(14, 'PHYS101', 'Physics', '3', 'Science', '10', 'active'),
(15, 'ELEC101', 'Elective', '2', 'Electives', '10', 'active'),
(16, 'RES101', 'Research', '2', 'Research', '10', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`employeeID`);

--
-- Indexes for table `current`
--
ALTER TABLE `current`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scilab_availability`
--
ALTER TABLE `scilab_availability`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scilab_form_requests`
--
ALTER TABLE `scilab_form_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scilab_inventory`
--
ALTER TABLE `scilab_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scilab_material_requests`
--
ALTER TABLE `scilab_material_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `formID` (`formID`);

--
-- Indexes for table `scilab_students_involved`
--
ALTER TABLE `scilab_students_involved`
  ADD PRIMARY KEY (`id`),
  ADD KEY `formID` (`formID`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`LRN`);

--
-- Indexes for table `student_directory`
--
ALTER TABLE `student_directory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `current`
--
ALTER TABLE `current`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scilab_availability`
--
ALTER TABLE `scilab_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `scilab_form_requests`
--
ALTER TABLE `scilab_form_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `scilab_inventory`
--
ALTER TABLE `scilab_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `scilab_material_requests`
--
ALTER TABLE `scilab_material_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `scilab_students_involved`
--
ALTER TABLE `scilab_students_involved`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `student_directory`
--
ALTER TABLE `student_directory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `scilab_material_requests`
--
ALTER TABLE `scilab_material_requests`
  ADD CONSTRAINT `scilab_material_requests_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `scilab_students_involved`
--
ALTER TABLE `scilab_students_involved`
  ADD CONSTRAINT `scilab_students_involved_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
