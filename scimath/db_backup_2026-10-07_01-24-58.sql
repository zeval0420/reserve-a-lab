-- Database Backup
-- Generated: 2026-10-07 01:24:58

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `accounts`;

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
  `status` varchar(10) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`employeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



DROP TABLE IF EXISTS `current`;

CREATE TABLE `current` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(100) NOT NULL,
  `value` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `current` VALUES("1","School Year","2026-2027");



DROP TABLE IF EXISTS `designation`;

CREATE TABLE `designation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employeeID` varchar(20) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `sy` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=370 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `designation` VALUES("3","14-048","MIS Head","2021-2022");
INSERT INTO `designation` VALUES("10","04-018","CID Chief","2021-2022");
INSERT INTO `designation` VALUES("11","06-013","SSD Chief","2021-2022");
INSERT INTO `designation` VALUES("12","03-008","FAD Chief","2021-2022");
INSERT INTO `designation` VALUES("15","05-026","AUH-Biology","2021-2022");
INSERT INTO `designation` VALUES("16","16-012","AUH-Mathematics","2021-2022");
INSERT INTO `designation` VALUES("17","14-051","AUH-Research/Integrated Science","2021-2022");
INSERT INTO `designation` VALUES("19","05-025","AUH-English","2021-2022");
INSERT INTO `designation` VALUES("22","15-027","AUH-Social Science/Values Education","2021-2022");
INSERT INTO `designation` VALUES("23","09-041","Homeroom Coordinator","2021-2022");
INSERT INTO `designation` VALUES("24","11-015","Life Mentor Coordinator","2021-2022");
INSERT INTO `designation` VALUES("25","04-022","Discipline Officer","2021-2022");
INSERT INTO `designation` VALUES("26","09-017","Discipline Officer","2021-2022");
INSERT INTO `designation` VALUES("27","04-021","AUH-Filipino","2021-2022");
INSERT INTO `designation` VALUES("28","14-032","AUH-Computer Science","2021-2022");
INSERT INTO `designation` VALUES("29","17-066","AUH-Chemistry","2021-2022");
INSERT INTO `designation` VALUES("30","15-053","AUH-PEHM","2021-2022");
INSERT INTO `designation` VALUES("31","09-039","AUH-Physics","2021-2022");
INSERT INTO `designation` VALUES("32","04-019","Campus Director","2021-2022");
INSERT INTO `designation` VALUES("33","16-033","AUH-Technology","2021-2022");
INSERT INTO `designation` VALUES("34","05-025","Batch Adviser-G7","2021-2022");
INSERT INTO `designation` VALUES("35","03-003","Batch Adviser-G8","2021-2022");
INSERT INTO `designation` VALUES("36","14-045","Batch Adviser-G9","2021-2022");
INSERT INTO `designation` VALUES("37","04-022","Batch Adviser-G10","2021-2022");
INSERT INTO `designation` VALUES("39","15-027","Batch Adviser-G12","2021-2022");
INSERT INTO `designation` VALUES("40","11-040","Homeroom Adviser G7-Emerald","2021-2022");
INSERT INTO `designation` VALUES("41","08-038","Homeroom Adviser G7-Sapphire","2021-2022");
INSERT INTO `designation` VALUES("42","17-034","Homeroom Adviser G8-Adelfa","2021-2022");
INSERT INTO `designation` VALUES("43","11-031","Homeroom Adviser G8-Dahlia","2021-2022");
INSERT INTO `designation` VALUES("44","16-055","Homeroom Adviser G8-Sampaguita","2021-2022");
INSERT INTO `designation` VALUES("45","21-065","Homeroom Adviser G9-Lithium","2021-2022");
INSERT INTO `designation` VALUES("46","20-073","Homeroom Adviser G9-Beryllium","2021-2022");
INSERT INTO `designation` VALUES("47","18-074","Homeroom Adviser G10-Electron","2021-2022");
INSERT INTO `designation` VALUES("49","21-076","Homeroom Adviser G10-Photon","2021-2022");
INSERT INTO `designation` VALUES("50","21-073","Homeroom Adviser G11-Dalton","2021-2022");
INSERT INTO `designation` VALUES("51","16-061","Homeroom Adviser G11-Mendel","2021-2022");
INSERT INTO `designation` VALUES("52","16-033","Homeroom Adviser G11-Newton","2021-2022");
INSERT INTO `designation` VALUES("54","20-030","Homeroom Adviser G12-Omega","2021-2022");
INSERT INTO `designation` VALUES("55","12-006","Homeroom Adviser G10-Graviton","2021-2022");
INSERT INTO `designation` VALUES("57","21-062","Homeroom Adviser G12-Delta","2021-2022");
INSERT INTO `designation` VALUES("58","14-043","Discipline Committee Chairperson","2021-2022");
INSERT INTO `designation` VALUES("59","15-054","Budget Officer","2021-2022");
INSERT INTO `designation` VALUES("60","03-014","Cashier","2021-2022");
INSERT INTO `designation` VALUES("61","03-011","Procurement Officer","2021-2022");
INSERT INTO `designation` VALUES("62","17-067","Supply and Property Mgt. Officer","2021-2022");
INSERT INTO `designation` VALUES("63","03-010","HRMO","2021-2022");
INSERT INTO `designation` VALUES("64","11-050","Records Officer","2021-2022");
INSERT INTO `designation` VALUES("65","06-028","Life Mentor G7-Diamond","2021-2022");
INSERT INTO `designation` VALUES("66","11-040","Life Mentor G7-Emerald","2021-2022");
INSERT INTO `designation` VALUES("67","11-020","Life Mentor G7-Ruby","2021-2022");
INSERT INTO `designation` VALUES("68","03-003","Life Mentor G8-Adelfa","2021-2022");
INSERT INTO `designation` VALUES("70","11-031","Life Mentor G8-Dahlia","2021-2022");
INSERT INTO `designation` VALUES("71","16-060","Life Mentor G8-Sampaguita","2021-2022");
INSERT INTO `designation` VALUES("72","17-066","Life Mentor G9-Lithium","2021-2022");
INSERT INTO `designation` VALUES("73","21-078","Life Mentor G9-Beryllium","2021-2022");
INSERT INTO `designation` VALUES("74","15-053","Life Mentor G10-Electron","2021-2022");
INSERT INTO `designation` VALUES("75","16-033","Life Mentor G10-Graviton","2021-2022");
INSERT INTO `designation` VALUES("76","18-077","Life Mentor G10-Photon","2021-2022");
INSERT INTO `designation` VALUES("77","04-022","Life Mentor G11-Mendel","2021-2022");
INSERT INTO `designation` VALUES("80","14-051","Life Mentor G12-Delta","2021-2022");
INSERT INTO `designation` VALUES("81","20-030","Life Mentor G12-Omega","2021-2022");
INSERT INTO `designation` VALUES("82","14-045","Life Mentor G12-Alpha","2021-2022");
INSERT INTO `designation` VALUES("83","12-002","Batch Adviser-G11","2021-2022");
INSERT INTO `designation` VALUES("84","08-038","Life Mentor G7-Sapphire","2021-2022");
INSERT INTO `designation` VALUES("85","21-079","Homeroom Adviser G7-Diamond","2021-2022");
INSERT INTO `designation` VALUES("86","21-080","Homeroom Adviser G9-Cesium","2021-2022");
INSERT INTO `designation` VALUES("87","21-080","Life Mentor G9-Cesium","2021-2022");
INSERT INTO `designation` VALUES("88","21-014","Homeroom Adviser G8-Camia","2021-2022");
INSERT INTO `designation` VALUES("89","08-038","Life Mentor G8-Camia","2021-2022");
INSERT INTO `designation` VALUES("90","16-004","Life Mentor G11-Dalton","2021-2022");
INSERT INTO `designation` VALUES("91","11-015","Homeroom Adviser G7-Ruby","2021-2022");
INSERT INTO `designation` VALUES("92","21-062","Homeroom Adviser G12-Alpha","2021-2022");
INSERT INTO `designation` VALUES("93","20-030","Homeroom Adviser G12-Alpha","2021-2022");
INSERT INTO `designation` VALUES("94","21-073","Homeroom Adviser G11-Mendel","2021-2022");
INSERT INTO `designation` VALUES("95","21-019","Life Mentor G11-Newton","2021-2022");
INSERT INTO `designation` VALUES("96","04-019","Campus Director","2022-2023");
INSERT INTO `designation` VALUES("97","04-018","CID Chief","2022-2023");
INSERT INTO `designation` VALUES("98","09-017","SSD Chief","2022-2023");
INSERT INTO `designation` VALUES("99","03-008","FAD Chief","2022-2023");
INSERT INTO `designation` VALUES("100","17-066","AUH-Chemistry","2022-2023");
INSERT INTO `designation` VALUES("101","09-039","AUH-Physics","2022-2023");
INSERT INTO `designation` VALUES("102","16-061","AUH-Biology","2022-2023");
INSERT INTO `designation` VALUES("103","12-006","AUH-Computer Science","2022-2023");
INSERT INTO `designation` VALUES("104","12-002","AUH-Mathematics","2022-2023");
INSERT INTO `designation` VALUES("105","14-051","AUH-Research/Integrated Science","2022-2023");
INSERT INTO `designation` VALUES("106","16-033","AUH-Technology","2022-2023");
INSERT INTO `designation` VALUES("107","05-025","AUH-English","2022-2023");
INSERT INTO `designation` VALUES("108","04-021","AUH-Filipino","2022-2023");
INSERT INTO `designation` VALUES("109","15-053","AUH-PEHM","2022-2023");
INSERT INTO `designation` VALUES("110","15-027","AUH-Social Science/Values Education","2022-2023");
INSERT INTO `designation` VALUES("111","05-025","Batch Adviser-G7","2022-2023");
INSERT INTO `designation` VALUES("112","03-007","Batch Adviser-G8","2022-2023");
INSERT INTO `designation` VALUES("113","03-003","Batch Adviser-G9","2022-2023");
INSERT INTO `designation` VALUES("114","06-013","Batch Adviser-G10","2022-2023");
INSERT INTO `designation` VALUES("115","04-022","Batch Adviser-G11","2022-2023");
INSERT INTO `designation` VALUES("116","11-015","Batch Adviser-G12","2022-2023");
INSERT INTO `designation` VALUES("117","16-055","Homeroom Adviser G7-Diamond","2022-2023");
INSERT INTO `designation` VALUES("118","21-079","Homeroom Adviser G7-Emerald","2022-2023");
INSERT INTO `designation` VALUES("119","08-038","Homeroom Adviser G7-Sapphire","2022-2023");
INSERT INTO `designation` VALUES("120","11-040","Homeroom Adviser G7-Ruby","2022-2023");
INSERT INTO `designation` VALUES("121","16-060","Homeroom Adviser G8-Adelfa","2022-2023");
INSERT INTO `designation` VALUES("123","17-034","Homeroom Adviser G8-Dahlia","2022-2023");
INSERT INTO `designation` VALUES("124","11-031","Homeroom Adviser G8-Sampaguita","2022-2023");
INSERT INTO `designation` VALUES("125","21-065","Homeroom Adviser G9-Cesium","2022-2023");
INSERT INTO `designation` VALUES("126","21-080","Homeroom Adviser G9-Lithium","2022-2023");
INSERT INTO `designation` VALUES("127","21-019","Homeroom Adviser G9-Beryllium","2022-2023");
INSERT INTO `designation` VALUES("128","17-064","Homeroom Adviser G9-Barium","2022-2023");
INSERT INTO `designation` VALUES("129","18-077","Homeroom Adviser G10-Electron","2022-2023");
INSERT INTO `designation` VALUES("130","11-049","Homeroom Adviser G10-Graviton","2022-2023");
INSERT INTO `designation` VALUES("132","16-033","Homeroom Adviser G11-Dalton","2022-2023");
INSERT INTO `designation` VALUES("135","20-073","Homeroom Adviser G12-Alpha","2022-2023");
INSERT INTO `designation` VALUES("136","21-014","Homeroom Adviser G12-Omega","2022-2023");
INSERT INTO `designation` VALUES("137","16-004","Homeroom Adviser G12-Delta","2022-2023");
INSERT INTO `designation` VALUES("138","09-041","Homeroom Coordinator","2022-2023");
INSERT INTO `designation` VALUES("139","04-022","Discipline Officer","2022-2023");
INSERT INTO `designation` VALUES("140","03-007","Discipline Officer","2022-2023");
INSERT INTO `designation` VALUES("141","14-043","Discipline Committee Chairperson","2022-2023");
INSERT INTO `designation` VALUES("142","11-050","Records Officer","2022-2023");
INSERT INTO `designation` VALUES("143","21-073","Homeroom Adviser G11-Mendel","2022-2023");
INSERT INTO `designation` VALUES("145","16-033","Homeroom Adviser G11-Mendel","2022-2023");
INSERT INTO `designation` VALUES("147","05-024","Homeroom Adviser G11-Newton","2022-2023");
INSERT INTO `designation` VALUES("148","16-004","Homeroom Adviser G12-Alpha","2022-2023");
INSERT INTO `designation` VALUES("149","12-006","Homeroom Adviser G8-Camia","2022-2023");
INSERT INTO `designation` VALUES("150","18-074","Homeroom Adviser G10-Photon","2022-2023");
INSERT INTO `designation` VALUES("151","20-030","Homeroom Adviser G12-Alpha","2022-2023");
INSERT INTO `designation` VALUES("152","04-019","Campus Director","2023-2024");
INSERT INTO `designation` VALUES("153","04-018","CID Chief","2023-2024");
INSERT INTO `designation` VALUES("154","03-005","SSD Chief","2023-2024");
INSERT INTO `designation` VALUES("155","03-008","FAD Chief","2023-2024");
INSERT INTO `designation` VALUES("156","20-073","MIS Head","2023-2024");
INSERT INTO `designation` VALUES("157","17-066","AUH-Chemistry","2023-2024");
INSERT INTO `designation` VALUES("158","09-039","AUH-Physics","2023-2024");
INSERT INTO `designation` VALUES("159","16-061","AUH-Biology","2023-2024");
INSERT INTO `designation` VALUES("160","20-073","AUH-Computer Science","2023-2024");
INSERT INTO `designation` VALUES("161","16-055","AUH-Mathematics","2023-2024");
INSERT INTO `designation` VALUES("162","11-040","AUH-Research/Integrated Science","2023-2024");
INSERT INTO `designation` VALUES("163","16-004","AUH-Technology","2023-2024");
INSERT INTO `designation` VALUES("164","16-058","AUH-English","2023-2024");
INSERT INTO `designation` VALUES("165","06-028","AUH-Filipino","2023-2024");
INSERT INTO `designation` VALUES("166","15-053","AUH-PEHM","2023-2024");
INSERT INTO `designation` VALUES("167","15-027","AUH-Social Science/Values Education","2023-2024");
INSERT INTO `designation` VALUES("168","05-025","Batch Adviser-G7","2023-2024");
INSERT INTO `designation` VALUES("169","03-003","Batch Adviser-G8","2023-2024");
INSERT INTO `designation` VALUES("170","12-002","Batch Adviser-G9","2023-2024");
INSERT INTO `designation` VALUES("171","04-022","Batch Adviser-G10","2023-2024");
INSERT INTO `designation` VALUES("172","09-041","Batch Adviser-G11","2023-2024");
INSERT INTO `designation` VALUES("173","14-045","Batch Adviser-G12","2023-2024");
INSERT INTO `designation` VALUES("174","11-031","Homeroom Adviser G7-Diamond","2023-2024");
INSERT INTO `designation` VALUES("175","21-079","Homeroom Adviser G7-Emerald","2023-2024");
INSERT INTO `designation` VALUES("176","20-030","Homeroom Adviser G7-Sapphire","2023-2024");
INSERT INTO `designation` VALUES("177","16-012","Homeroom Adviser G7-Ruby","2023-2024");
INSERT INTO `designation` VALUES("178","11-040","Homeroom Adviser G8-Adelfa","2023-2024");
INSERT INTO `designation` VALUES("179","17-034","Homeroom Adviser G8-Camia","2023-2024");
INSERT INTO `designation` VALUES("180","18-074","Homeroom Adviser G8-Dahlia","2023-2024");
INSERT INTO `designation` VALUES("181","17-064","Homeroom Adviser G8-Sampaguita","2023-2024");
INSERT INTO `designation` VALUES("182","16-060","Homeroom Adviser G9-Barium","2023-2024");
INSERT INTO `designation` VALUES("183","22-022","Homeroom Adviser G9-Beryllium","2023-2024");
INSERT INTO `designation` VALUES("184","21-065","Homeroom Adviser G9-Cesium","2023-2024");
INSERT INTO `designation` VALUES("185","21-080","Homeroom Adviser G9-Lithium","2023-2024");
INSERT INTO `designation` VALUES("186","09-039","Homeroom Adviser G10-Boson","2023-2024");
INSERT INTO `designation` VALUES("187","23-038","Homeroom Adviser G10-Electron","2023-2024");
INSERT INTO `designation` VALUES("188","21-076","Homeroom Adviser G10-Graviton","2023-2024");
INSERT INTO `designation` VALUES("189","23-037","Homeroom Adviser G10-Photon","2023-2024");
INSERT INTO `designation` VALUES("191","05-026","Homeroom Adviser G11-Mendel","2023-2024");
INSERT INTO `designation` VALUES("192","05-024","Homeroom Adviser G11-Newton","2023-2024");
INSERT INTO `designation` VALUES("193","23-036","Homeroom Adviser G12-Alpha","2023-2024");
INSERT INTO `designation` VALUES("194","21-062","Homeroom Adviser G12-Delta","2023-2024");
INSERT INTO `designation` VALUES("195","21-014","Homeroom Adviser G12-Omega","2023-2024");
INSERT INTO `designation` VALUES("196","23-031","Homeroom Adviser G11-Dalton","2023-2024");
INSERT INTO `designation` VALUES("197","04-022","Discipline Officer","2023-2024");
INSERT INTO `designation` VALUES("198","03-007","Discipline Officer","2023-2024");
INSERT INTO `designation` VALUES("199","14-043","Discipline Committee Chairperson","2023-2024");
INSERT INTO `designation` VALUES("201","04-018","CID Chief","2024-2025");
INSERT INTO `designation` VALUES("203","03-008","FAD Chief","2024-2025");
INSERT INTO `designation` VALUES("204","20-073","MIS Head","2024-2025");
INSERT INTO `designation` VALUES("205","17-066","AUH-Chemistry","2024-2025");
INSERT INTO `designation` VALUES("206","05-024","AUH-Physics","2024-2025");
INSERT INTO `designation` VALUES("207","16-061","AUH-Biology","2024-2025");
INSERT INTO `designation` VALUES("208","20-073","AUH-Computer Science","2024-2025");
INSERT INTO `designation` VALUES("209","16-055","AUH-Mathematics","2024-2025");
INSERT INTO `designation` VALUES("210","14-051","AUH-Integrated Science","2024-2025");
INSERT INTO `designation` VALUES("211","16-004","AUH-Technology","2024-2025");
INSERT INTO `designation` VALUES("212","16-058","AUH-English","2024-2025");
INSERT INTO `designation` VALUES("213","06-028","AUH-Filipino","2024-2025");
INSERT INTO `designation` VALUES("214","15-053","AUH-PEHM","2024-2025");
INSERT INTO `designation` VALUES("215","15-027","AUH-Social Science/Values Education","2024-2025");
INSERT INTO `designation` VALUES("216","05-025","Batch Adviser-G7","2024-2025");
INSERT INTO `designation` VALUES("217","03-003","Batch Adviser-G8","2024-2025");
INSERT INTO `designation` VALUES("218","12-002","Batch Adviser-G9","2024-2025");
INSERT INTO `designation` VALUES("219","04-022","Batch Adviser-G10","2024-2025");
INSERT INTO `designation` VALUES("220","14-051","Batch Adviser-G11","2024-2025");
INSERT INTO `designation` VALUES("221","14-045","Batch Adviser-G12","2024-2025");
INSERT INTO `designation` VALUES("222","21-079","Homeroom Adviser G7-Diamond","2024-2025");
INSERT INTO `designation` VALUES("223","11-031","Homeroom Adviser G7-Emerald","2024-2025");
INSERT INTO `designation` VALUES("224","20-030","Homeroom Adviser G7-Sapphire","2024-2025");
INSERT INTO `designation` VALUES("225","08-038","Homeroom Adviser G7-Ruby","2024-2025");
INSERT INTO `designation` VALUES("226","11-015","Homeroom Adviser G8-Adelfa","2024-2025");
INSERT INTO `designation` VALUES("227","16-033","Homeroom Adviser G8-Camia","2024-2025");
INSERT INTO `designation` VALUES("228","18-074","Homeroom Adviser G8-Dahlia","2024-2025");
INSERT INTO `designation` VALUES("229","17-064","Homeroom Adviser G8-Sampaguita","2024-2025");
INSERT INTO `designation` VALUES("230","24-040","Homeroom Adviser G9-Lithium","2024-2025");
INSERT INTO `designation` VALUES("231","16-060","Homeroom Adviser G9-Barium","2024-2025");
INSERT INTO `designation` VALUES("232","21-080","Homeroom Adviser G9-Beryllium","2024-2025");
INSERT INTO `designation` VALUES("233","23-037","Homeroom Adviser G10-Electron","2024-2025");
INSERT INTO `designation` VALUES("234","21-076","Homeroom Adviser G10-Photon","2024-2025");
INSERT INTO `designation` VALUES("235","21-073","Homeroom Adviser G10-Boson","2024-2025");
INSERT INTO `designation` VALUES("236","21-065","Homeroom Adviser G10-Graviton","2024-2025");
INSERT INTO `designation` VALUES("237","05-026","Homeroom Adviser G11-Mendel","2024-2025");
INSERT INTO `designation` VALUES("239","05-024","Homeroom Adviser G11-Newton","2024-2025");
INSERT INTO `designation` VALUES("240","23-036","Homeroom Adviser G12-Alpha","2024-2025");
INSERT INTO `designation` VALUES("241","21-014","Homeroom Adviser G12-Omega","2024-2025");
INSERT INTO `designation` VALUES("243","04-022","Discipline Officer","2024-2025");
INSERT INTO `designation` VALUES("244","03-007","Discipline Officer","2024-2025");
INSERT INTO `designation` VALUES("245","14-043","Discipline Committee Chairperson","2024-2025");
INSERT INTO `designation` VALUES("247","24-043","Homeroom Adviser G11-Curie","2024-2025");
INSERT INTO `designation` VALUES("248","21-019","Homeroom Adviser G9-Cesium","2024-2025");
INSERT INTO `designation` VALUES("250","23-031","Homeroom Adviser G12-Delta","2024-2025");
INSERT INTO `designation` VALUES("251","04-018","AUH-Research","2024-2025");
INSERT INTO `designation` VALUES("252","05-025","AUH-SCALE","2024-2025");
INSERT INTO `designation` VALUES("253","03-005","Campus Director","2024-2025");
INSERT INTO `designation` VALUES("254","14-045","SSD Chief","2024-2025");
INSERT INTO `designation` VALUES("255","08-001","Dorm Manager","2024-2025");
INSERT INTO `designation` VALUES("256","20-081","Dorm Manager","2024-2025");
INSERT INTO `designation` VALUES("257","24-053","Homeroom Adviser G11-Dalton","2024-2025");
INSERT INTO `designation` VALUES("258","03-005","Campus Director","2025-2026");
INSERT INTO `designation` VALUES("259","09-017","CID Chief","2025-2026");
INSERT INTO `designation` VALUES("260","14-045","SSD Chief","2025-2026");
INSERT INTO `designation` VALUES("261","03-008","FAD Chief","2025-2026");
INSERT INTO `designation` VALUES("262","20-073","MIS Head","2025-2026");
INSERT INTO `designation` VALUES("263","17-066","AUH-Chemistry","2025-2026");
INSERT INTO `designation` VALUES("264","11-049","AUH-Physics","2025-2026");
INSERT INTO `designation` VALUES("265","04-018","AUH-Biology","2025-2026");
INSERT INTO `designation` VALUES("266","20-073","AUH-Computer Science","2025-2026");
INSERT INTO `designation` VALUES("267","12-002","AUH-Mathematics","2025-2026");
INSERT INTO `designation` VALUES("268","16-061","AUH-Integrated Science","2025-2026");
INSERT INTO `designation` VALUES("269","16-004","AUH-Technology","2025-2026");
INSERT INTO `designation` VALUES("270","16-058","AUH-English","2025-2026");
INSERT INTO `designation` VALUES("271","06-028","AUH-Filipino","2025-2026");
INSERT INTO `designation` VALUES("272","15-053","AUH-PEHM","2025-2026");
INSERT INTO `designation` VALUES("273","09-041","AUH-Social Science/Values Education","2025-2026");
INSERT INTO `designation` VALUES("274","16-061","AUH-Research","2025-2026");
INSERT INTO `designation` VALUES("276","04-022","Discipline Officer","2025-2026");
INSERT INTO `designation` VALUES("277","03-007","Discipline Officer","2025-2026");
INSERT INTO `designation` VALUES("278","08-038","Discipline Officer","2025-2026");
INSERT INTO `designation` VALUES("279","06-013","Discipline Committee Chairperson","2025-2026");
INSERT INTO `designation` VALUES("280","11-031","Homeroom Adviser G7-Diamond","2025-2026");
INSERT INTO `designation` VALUES("281","08-038","Homeroom Adviser G7-Emerald","2025-2026");
INSERT INTO `designation` VALUES("282","14-051","Homeroom Adviser G7-Sapphire","2025-2026");
INSERT INTO `designation` VALUES("283","20-030","Homeroom Adviser G7-Ruby","2025-2026");
INSERT INTO `designation` VALUES("284","05-025","Batch Adviser-G7","2025-2026");
INSERT INTO `designation` VALUES("285","05-024","Batch Adviser-G8","2025-2026");
INSERT INTO `designation` VALUES("286","17-064","Homeroom Adviser G8-Adelfa","2025-2026");
INSERT INTO `designation` VALUES("287","16-055","Homeroom Adviser G8-Camia","2025-2026");
INSERT INTO `designation` VALUES("288","17-034","Homeroom Adviser G8-Dahlia","2025-2026");
INSERT INTO `designation` VALUES("289","04-021","Homeroom Adviser G8-Sampaguita","2025-2026");
INSERT INTO `designation` VALUES("290","23-037","Homeroom Adviser G9-Cesium","2025-2026");
INSERT INTO `designation` VALUES("291","24-040","Homeroom Adviser G9-Lithium","2025-2026");
INSERT INTO `designation` VALUES("292","21-079","Homeroom Adviser G9-Beryllium","2025-2026");
INSERT INTO `designation` VALUES("293","16-060","Homeroom Adviser G9-Barium","2025-2026");
INSERT INTO `designation` VALUES("294","12-006","Homeroom Adviser G10-Electron","2025-2026");
INSERT INTO `designation` VALUES("295","21-019","Homeroom Adviser G10-Graviton","2025-2026");
INSERT INTO `designation` VALUES("296","03-003","Homeroom Adviser G10-Photon","2025-2026");
INSERT INTO `designation` VALUES("297","21-065","Homeroom Adviser G10-Boson","2025-2026");
INSERT INTO `designation` VALUES("298","24-053","Homeroom Adviser G11-Dalton","2025-2026");
INSERT INTO `designation` VALUES("299","21-014","Homeroom Adviser G11-Newton","2025-2026");
INSERT INTO `designation` VALUES("301","24-044","Homeroom Adviser G11-Curie","2025-2026");
INSERT INTO `designation` VALUES("302","23-036","Homeroom Adviser G12-Alpha","2025-2026");
INSERT INTO `designation` VALUES("303","23-031","Homeroom Adviser G12-Delta","2025-2026");
INSERT INTO `designation` VALUES("304","23-038","Homeroom Adviser G12-Omega","2025-2026");
INSERT INTO `designation` VALUES("305","24-043","Homeroom Adviser G12-Sigma","2025-2026");
INSERT INTO `designation` VALUES("306","08-037","Batch Adviser-G9","2025-2026");
INSERT INTO `designation` VALUES("307","11-015","Batch Adviser-G10","2025-2026");
INSERT INTO `designation` VALUES("308","21-080","Batch Adviser-G11","2025-2026");
INSERT INTO `designation` VALUES("309","21-073","Batch Adviser-G12","2025-2026");
INSERT INTO `designation` VALUES("310","05-026","Homeroom Adviser G11-Mendel","2025-2026");
INSERT INTO `designation` VALUES("311","04-021","AUH-SCALE","2025-2026");
INSERT INTO `designation` VALUES("312","21-080","Discipline Officer","2025-2026");
INSERT INTO `designation` VALUES("313","23-037","Discipline Officer","2025-2026");
INSERT INTO `designation` VALUES("314","24-043","Discipline Officer","2025-2026");
INSERT INTO `designation` VALUES("315","03-005","Campus Director","2026-2027");
INSERT INTO `designation` VALUES("316","09-017","CID Chief","2026-2027");
INSERT INTO `designation` VALUES("317","14-045","SSD Chief","2026-2027");
INSERT INTO `designation` VALUES("318","03-008","FAD Chief","2026-2027");
INSERT INTO `designation` VALUES("319","20-073","MIS Head","2026-2027");
INSERT INTO `designation` VALUES("320","17-066","AUH-Chemistry","2026-2027");
INSERT INTO `designation` VALUES("321","11-049","AUH-Physics","2026-2027");
INSERT INTO `designation` VALUES("322","04-018","AUH-Biology","2026-2027");
INSERT INTO `designation` VALUES("323","14-032","AUH-Computer Science","2026-2027");
INSERT INTO `designation` VALUES("324","12-002","AUH-Mathematics","2026-2027");
INSERT INTO `designation` VALUES("325","14-051","AUH-Integrated Science","2026-2027");
INSERT INTO `designation` VALUES("326","16-004","AUH-Technology","2026-2027");
INSERT INTO `designation` VALUES("327","11-015","AUH-English","2026-2027");
INSERT INTO `designation` VALUES("328","06-028","AUH-Filipino","2026-2027");
INSERT INTO `designation` VALUES("329","15-053","AUH-PEHM","2026-2027");
INSERT INTO `designation` VALUES("330","09-041","AUH-Social Science/Values Education","2026-2027");
INSERT INTO `designation` VALUES("331","06-028","Batch Adviser-G7","2026-2027");
INSERT INTO `designation` VALUES("332","03-003","Batch Adviser-G8","2026-2027");
INSERT INTO `designation` VALUES("333","20-073","Batch Adviser-G9","2026-2027");
INSERT INTO `designation` VALUES("334","16-033","Batch Adviser-G10","2026-2027");
INSERT INTO `designation` VALUES("335","03-007","Batch Adviser-G11","2026-2027");
INSERT INTO `designation` VALUES("336","21-080","Batch Adviser-G12","2026-2027");
INSERT INTO `designation` VALUES("337","04-022","Discipline Officer","2026-2027");
INSERT INTO `designation` VALUES("338","14-032","Discipline Officer","2026-2027");
INSERT INTO `designation` VALUES("339","08-038","Discipline Officer","2026-2027");
INSERT INTO `designation` VALUES("340","03-007","Discipline Committee Chairperson","2026-2027");
INSERT INTO `designation` VALUES("341","14-051","AUH-Research","2026-2027");
INSERT INTO `designation` VALUES("342","04-021","AUH-SCALE","2026-2027");
INSERT INTO `designation` VALUES("343","08-038","Homeroom Adviser G7-Diamond","2026-2027");
INSERT INTO `designation` VALUES("344","25-063","Homeroom Adviser G7-Emerald","2026-2027");
INSERT INTO `designation` VALUES("345","20-030","Homeroom Adviser G7-Sapphire","2026-2027");
INSERT INTO `designation` VALUES("346","11-031","Homeroom Adviser G7-Ruby","2026-2027");
INSERT INTO `designation` VALUES("347","16-060","Homeroom Adviser G8-Adelfa","2026-2027");
INSERT INTO `designation` VALUES("348","17-064","Homeroom Adviser G8-Camia","2026-2027");
INSERT INTO `designation` VALUES("349","24-043","Homeroom Adviser G8-Dahlia","2026-2027");
INSERT INTO `designation` VALUES("350","04-021","Homeroom Adviser G8-Sampaguita","2026-2027");
INSERT INTO `designation` VALUES("351","21-079","Homeroom Adviser G9-Cesium","2026-2027");
INSERT INTO `designation` VALUES("352","24-040","Homeroom Adviser G9-Lithium","2026-2027");
INSERT INTO `designation` VALUES("353","26-009","Homeroom Adviser G9-Beryllium","2026-2027");
INSERT INTO `designation` VALUES("354","23-036","Homeroom Adviser G9-Barium","2026-2027");
INSERT INTO `designation` VALUES("355","18-074","Homeroom Adviser G10-Electron","2026-2027");
INSERT INTO `designation` VALUES("356","21-019","Homeroom Adviser G10-Graviton","2026-2027");
INSERT INTO `designation` VALUES("357","21-065","Homeroom Adviser G10-Photon","2026-2027");
INSERT INTO `designation` VALUES("358","21-073","Homeroom Adviser G10-Boson","2026-2027");
INSERT INTO `designation` VALUES("359","24-053","Homeroom Adviser G11-Dalton","2026-2027");
INSERT INTO `designation` VALUES("360","05-026","Homeroom Adviser G11-Mendel","2026-2027");
INSERT INTO `designation` VALUES("361","21-078","Homeroom Adviser G11-Newton","2026-2027");
INSERT INTO `designation` VALUES("362","23-038","Homeroom Adviser G11-Curie","2026-2027");
INSERT INTO `designation` VALUES("363","23-037","Homeroom Adviser G12-Alpha","2026-2027");
INSERT INTO `designation` VALUES("364","16-055","Homeroom Adviser G12-Omega","2026-2027");
INSERT INTO `designation` VALUES("365","23-031","Homeroom Adviser G12-Delta","2026-2027");
INSERT INTO `designation` VALUES("366","11-015","Homeroom Adviser G12-Sigma","2026-2027");
INSERT INTO `designation` VALUES("367","10-046","ACIDAA","2026-2027");
INSERT INTO `designation` VALUES("368","15-027","ACIDSA","2026-2027");
INSERT INTO `designation` VALUES("369","11-015","Food Committee","2026-2027");



DROP TABLE IF EXISTS `guidance_referral_form`;

CREATE TABLE `guidance_referral_form` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `campus` varchar(255) NOT NULL,
  `student` varchar(255) NOT NULL,
  `grade_section` varchar(100) NOT NULL,
  `date_referred` date NOT NULL,
  `concern_academic` tinyint(1) NOT NULL DEFAULT 0,
  `concern_behavior` tinyint(1) NOT NULL DEFAULT 0,
  `concern_personal` tinyint(1) NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  `intervention` text NOT NULL,
  `requires_followup` enum('Yes','No') NOT NULL,
  `behavior_depressed` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_hopelessness` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_crying` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_suicide` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_mood` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_emotional` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_withdrawal` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_excessive_activity` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_interaction` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_disruptive` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_appearance` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_academic_decline` tinyint(1) NOT NULL DEFAULT 0,
  `other_behavior` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `referrer_email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;




DROP TABLE IF EXISTS `office_designation`;

CREATE TABLE `office_designation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `officedesignation` varchar(200) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `office_designation` VALUES("1","Campus Director","active");
INSERT INTO `office_designation` VALUES("2","CID Chief","active");
INSERT INTO `office_designation` VALUES("3","SSD Chief","active");
INSERT INTO `office_designation` VALUES("4","FAD Chief","active");
INSERT INTO `office_designation` VALUES("5","MIS Head","active");
INSERT INTO `office_designation` VALUES("6","AUH-Chemistry","active");
INSERT INTO `office_designation` VALUES("7","AUH-Physics","active");
INSERT INTO `office_designation` VALUES("8","AUH-Biology","active");
INSERT INTO `office_designation` VALUES("9","AUH-Computer Science","active");
INSERT INTO `office_designation` VALUES("10","AUH-Mathematics","active");
INSERT INTO `office_designation` VALUES("11","AUH-Integrated Science","active");
INSERT INTO `office_designation` VALUES("12","AUH-Technology","active");
INSERT INTO `office_designation` VALUES("13","AUH-English","active");
INSERT INTO `office_designation` VALUES("14","AUH-Filipino","active");
INSERT INTO `office_designation` VALUES("15","AUH-PEHM","active");
INSERT INTO `office_designation` VALUES("16","AUH-Social Science/Values Education","active");
INSERT INTO `office_designation` VALUES("17","Batch Adviser-G7","active");
INSERT INTO `office_designation` VALUES("18","Batch Adviser-G8","active");
INSERT INTO `office_designation` VALUES("19","Batch Adviser-G9","active");
INSERT INTO `office_designation` VALUES("20","Batch Adviser-G10","active");
INSERT INTO `office_designation` VALUES("21","Batch Adviser-G11","active");
INSERT INTO `office_designation` VALUES("22","Batch Adviser-G12","active");
INSERT INTO `office_designation` VALUES("23","Homeroom Adviser G7-Diamond","active");
INSERT INTO `office_designation` VALUES("24","Homeroom Adviser G7-Emerald","active");
INSERT INTO `office_designation` VALUES("25","Homeroom Adviser G7-Sapphire","active");
INSERT INTO `office_designation` VALUES("26","Homeroom Adviser G7-Ruby","active");
INSERT INTO `office_designation` VALUES("27","Homeroom Adviser G8-Adelfa","active");
INSERT INTO `office_designation` VALUES("28","Homeroom Adviser G8-Camia","active");
INSERT INTO `office_designation` VALUES("29","Homeroom Adviser G8-Dahlia","active");
INSERT INTO `office_designation` VALUES("30","Homeroom Adviser G8-Sampaguita","active");
INSERT INTO `office_designation` VALUES("31","Homeroom Adviser G9-Cesium","active");
INSERT INTO `office_designation` VALUES("32","Homeroom Adviser G9-Lithium","active");
INSERT INTO `office_designation` VALUES("33","Homeroom Adviser G9-Beryllium","active");
INSERT INTO `office_designation` VALUES("34","Homeroom Adviser G10-Electron","active");
INSERT INTO `office_designation` VALUES("35","Homeroom Adviser G10-Graviton","active");
INSERT INTO `office_designation` VALUES("36","Homeroom Adviser G10-Photon","active");
INSERT INTO `office_designation` VALUES("37","Homeroom Adviser G11-Dalton","active");
INSERT INTO `office_designation` VALUES("38","Homeroom Adviser G11-Mendel","active");
INSERT INTO `office_designation` VALUES("39","Homeroom Adviser G11-Newton","active");
INSERT INTO `office_designation` VALUES("40","Homeroom Adviser G12-Alpha","active");
INSERT INTO `office_designation` VALUES("41","Homeroom Adviser G12-Omega","active");
INSERT INTO `office_designation` VALUES("42","Life Mentor G7-Diamond","active");
INSERT INTO `office_designation` VALUES("43","Life Mentor G7-Emerald","active");
INSERT INTO `office_designation` VALUES("44","Life Mentor G7-Sapphire","active");
INSERT INTO `office_designation` VALUES("45","Life Mentor G7-Ruby","active");
INSERT INTO `office_designation` VALUES("46","Life Mentor G8-Adelfa","active");
INSERT INTO `office_designation` VALUES("47","Life Mentor G8-Camia","active");
INSERT INTO `office_designation` VALUES("48","Life Mentor G8-Dahlia","active");
INSERT INTO `office_designation` VALUES("49","Life Mentor G8-Sampaguita","active");
INSERT INTO `office_designation` VALUES("50","Life Mentor G9-Cesium","active");
INSERT INTO `office_designation` VALUES("51","Life Mentor G9-Lithium","active");
INSERT INTO `office_designation` VALUES("52","Life Mentor G9-Beryllium","active");
INSERT INTO `office_designation` VALUES("53","Life Mentor G10-Electron","inactive");
INSERT INTO `office_designation` VALUES("54","Life Mentor G10-Graviton","inactive");
INSERT INTO `office_designation` VALUES("55","Life Mentor G10-Photon","inactive");
INSERT INTO `office_designation` VALUES("56","Life Mentor G11-Dalton","active");
INSERT INTO `office_designation` VALUES("57","Life Mentor G11-Mendel","active");
INSERT INTO `office_designation` VALUES("58","Life Mentor G11-Newton","active");
INSERT INTO `office_designation` VALUES("59","Life Mentor G12-Alpha","active");
INSERT INTO `office_designation` VALUES("60","Life Mentor G12-Delta","active");
INSERT INTO `office_designation` VALUES("61","Life Mentor G12-Omega","active");
INSERT INTO `office_designation` VALUES("62","Homeroom Coordinator","active");
INSERT INTO `office_designation` VALUES("63","Life Mentor Coordinator","active");
INSERT INTO `office_designation` VALUES("64","Discipline Officer","active");
INSERT INTO `office_designation` VALUES("65","Homeroom Adviser G12-Delta","active");
INSERT INTO `office_designation` VALUES("66","Discipline Committee Chairperson","active");
INSERT INTO `office_designation` VALUES("67","Budget Officer","active");
INSERT INTO `office_designation` VALUES("68","Cashier","active");
INSERT INTO `office_designation` VALUES("69","Procurement Officer","active");
INSERT INTO `office_designation` VALUES("70","Supply and Property Mgt. Officer","active");
INSERT INTO `office_designation` VALUES("71","HRMO","active");
INSERT INTO `office_designation` VALUES("72","Records Officer","active");
INSERT INTO `office_designation` VALUES("73","Document Controller","active");
INSERT INTO `office_designation` VALUES("74","QMSO","active");
INSERT INTO `office_designation` VALUES("75","Homeroom Adviser G9-Barium","active");
INSERT INTO `office_designation` VALUES("76","Homeroom Adviser G10-Boson","active");
INSERT INTO `office_designation` VALUES("77","Homeroom Adviser G11-Curie","active");
INSERT INTO `office_designation` VALUES("78","AUH-SCALE","active");
INSERT INTO `office_designation` VALUES("79","AUH-Research","active");
INSERT INTO `office_designation` VALUES("80","Dorm Manager","active");
INSERT INTO `office_designation` VALUES("81","Dorm Assistant","active");
INSERT INTO `office_designation` VALUES("82","Guard","active");
INSERT INTO `office_designation` VALUES("83","Homeroom Adviser G12-Sigma","active");
INSERT INTO `office_designation` VALUES("84","ACIDAA","active");
INSERT INTO `office_designation` VALUES("85","ACIDSA","active");
INSERT INTO `office_designation` VALUES("86","Food Committee","active");



DROP TABLE IF EXISTS `scilab_availability`;

CREATE TABLE `scilab_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scilabName` varchar(30) NOT NULL,
  `mainImagePath` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `availability` varchar(13) NOT NULL DEFAULT 'Available',
  `color` varchar(50) NOT NULL,
  `status` varchar(8) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `scilab_availability` VALUES("1","Science Laboratory 1","img/labimages/Science Laboratory 1.jpg","Third Floor, Advance Science and Technology Building, PSHS-IRC","Available","#ff000a","active");
INSERT INTO `scilab_availability` VALUES("2","Science Laboratory 2","img/labimages/Science Laboratory 2.jpg","Third Floor, Advance Science and Technology Building, PSHS-IRC","Available","#24a32e","active");
INSERT INTO `scilab_availability` VALUES("3","Science Laboratory 3","img/labimages/Science Laboratory 3.jpg","Third Floor, Advance Science and Technology Building, PSHS-IRC","Available","#ffdf12","active");
INSERT INTO `scilab_availability` VALUES("4","Science Laboratory 4","img/labimages/Science Laboratory 4.jpg","Third Floor, Advance Science and Technology Building, PSHS-IRC","Available","#009aff","active");
INSERT INTO `scilab_availability` VALUES("5","Specialized Equipment Room","img/labimages/Specialized Equipment Room.jpg","Second Floor, Advance Science and Technology Building, PSHS-IRC","Available","#940dd3","active");
INSERT INTO `scilab_availability` VALUES("6","Physics Laboratory 1","img/labimages/Physics Laboratory 1.jpg","Fourth Floor, Advance Science and Technology Building, PSHS-IRC","Available","#ff9a57","active");
INSERT INTO `scilab_availability` VALUES("7","Physics Laboratory 2","img/labimages/Physics Laboratory 2.jpg","Fourth Floor, Advance Science and Technology Building, PSHS-IRC","Available","#f97934","active");
INSERT INTO `scilab_availability` VALUES("8","Physics Laboratory 3","img/labimages/Physics Laboratory 3.jpg","Fourth Floor, Advance Science and Technology Building, PSHS-IRC","Not Available","#f7531d","active");



DROP TABLE IF EXISTS `scilab_form_requests`;

CREATE TABLE `scilab_form_requests` (
  `statusScilabPersonnel` varchar(8) NOT NULL DEFAULT 'pending',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requesterEmployeeID` varchar(50) NOT NULL,
  `subjectAcademicUnit` varchar(50) NOT NULL,
  `controlNumber` int(100) NOT NULL,
  `control_equipment` varchar(100) DEFAULT NULL,
  `control_reagent` varchar(100) DEFAULT NULL,
  `control_permit` varchar(100) DEFAULT NULL,
  `control_reservation` varchar(100) DEFAULT NULL,
  `scilabName` varchar(255) NOT NULL,
  `teacherInCharge` varchar(100) NOT NULL,
  `sy` varchar(10) NOT NULL,
  `gradeLevel` int(2) NOT NULL,
  `sections` varchar(255) NOT NULL,
  `subject` varchar(25) NOT NULL,
  `subjectTopic` varchar(100) NOT NULL,
  `inclusiveDate` date NOT NULL,
  `inclusiveTime` varchar(255) NOT NULL,
  `dateRequested` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `feedback` varchar(999) NOT NULL,
  `supervisor_status` varchar(20) DEFAULT 'pending',
  `subject_teacher_status` varchar(20) DEFAULT 'pending',
  `lab_personnel_status` varchar(20) DEFAULT 'pending',
  `cid_chief_status` varchar(20) DEFAULT 'pending',
  `supervisor_approved_at` datetime DEFAULT NULL,
  `supervisor_approved_by` varchar(255) DEFAULT NULL,
  `subject_teacher_approved_at` datetime DEFAULT NULL,
  `subject_teacher_approved_by` varchar(255) DEFAULT NULL,
  `lab_personnel_approved_at` datetime DEFAULT NULL,
  `lab_personnel_approved_by` varchar(255) DEFAULT NULL,
  `cid_chief_approved_at` datetime DEFAULT NULL,
  `cid_chief_approved_by` varchar(255) DEFAULT NULL,
  `dateApproved` timestamp NULL DEFAULT NULL,
  `dateEndorsed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `scilab_form_requests` VALUES("Approved","11","E004","","63142",NULL,NULL,NULL,NULL,"Science Laboratory 1","Tarlit, Faith Paeste","2025-2026","11","Curie","Economics","Macroeconomics","2025-10-25","12:50 AM to 10:54 AM","2025-10-24 01:55:30","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","12","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Fronda, Joseph Victor Tadena","2025-2026","11","Curie, Dalton, Mendel, Newton","Science Core 1 (Physics)","Fluids","2025-10-29","9:55 PM to 10:55 PM","2025-10-27 13:56:42","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","13","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Fronda, Joseph Victor Tadena","2025-2026","11","Curie, Dalton, Mendel, Newton","Science Core 1 (Physics)","Fluids","2025-10-29","9:55 PM to 10:55 PM","2025-10-27 13:56:50","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","14","E004","","654321",NULL,NULL,NULL,NULL,"Science Laboratory 1","Fronda, Joseph Victor Tadena","2025-2026","11","Curie, Dalton, Mendel, Newton","Science Core 1 (Physics)","Fluids","2025-10-29","9:55 PM to 10:55 PM","2025-10-27 14:08:31","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","15","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Paz, Jan Czarina Bautista","2025-2026","11","Dalton","Science Core 1 (Chemistry","Sample Topic","2025-11-06","2:50 PM to 4:50 PM","2025-11-05 05:51:37","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","16","23-037","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Espanto-Maas, Cherry Ann Doctolero","2025-2026","9","Barium, Beryllium, Cesium, Lithium","Fundamentals of Biology 1","Pig Heart Dissection","2025-11-17","8:04 AM to 10:05 AM","2025-11-06 09:07:50","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","17","23-037","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Espanto-Maas, Cherry Ann Doctolero","2025-2026","9","Barium, Beryllium, Cesium, Lithium","Fundamentals of Biology 1","Pig Heart Dissection","2025-11-17","8:04 AM to 10:05 AM","2025-11-06 09:07:58","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","18","23-037","","2718",NULL,NULL,NULL,NULL,"Science Laboratory 1","Espanto-Maas, Cherry Ann Doctolero","2025-2026","9","Barium, Beryllium, Cesium, Lithium","Fundamentals of Biology 1","Pig Heart Dissection","2025-11-17","8:04 AM to 10:05 AM","2025-11-07 06:08:58","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","19","23-037","","123456789",NULL,NULL,NULL,NULL,"Science Laboratory 1","Espanto-Maas, Cherry Ann Doctolero","2025-2026","9","Barium, Beryllium, Cesium, Lithium","Fundamentals of Biology 1","Pig Heart Dissection","2026-01-13","8:00 AM to 5:00 PM","2026-01-13 07:28:56","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","20","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Paz, Jan Czarina Bautista","2025-2026","11","Dalton","Science Core 1 (Chemistry","Trial 1","2026-01-31","8:00 AM to 9:40 AM","2026-01-15 09:09:02","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","21","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Paz, Jan Czarina Bautista","2025-2026","11","Dalton","Science Core 1 (Chemistry","Trial 1","2026-01-31","8:00 AM to 9:40 AM","2026-01-15 09:09:08","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","22","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Bisenio, Richard Abad","2025-2026","11","Newton","Research 2","AgriCool","2026-01-29","1:00 PM to 5:00 PM","2026-01-29 04:30:19","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Rejected","23","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Bisenio, Richard Abad","2025-2026","11","Newton","Research 2","AgriCool","2026-01-29","2:00 PM to 5:00 PM","2026-02-10 08:02:12","Reagent X Unavailable","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","24","E004","","1765",NULL,NULL,NULL,NULL,"Science Laboratory 2","Albano, Jonellyn Stohner","2025-2026","11","Newton","Research 2","Sample Topic","2026-01-29","8:00 AM to 5:00 PM","2026-02-10 08:01:15","Nice","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","25","E004","","12345",NULL,NULL,NULL,NULL,"Science Laboratory 3","Valdez, Gabriel James V.","2025-2026","11","Newton","Science Core 1 (Physics)","Sample test","2026-02-06","8:00 AM to 5:00 PM","2026-02-05 12:41:35","approve email test","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","26","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 3","Fronda, Joseph Victor Tadena","2025-2026","11","Newton","Science Core 1 (Physics)","sample test","2026-02-05","3:42 AM to 5:21 AM","2026-02-05 14:23:39","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","27","E004","","0",NULL,NULL,NULL,NULL,"Chemistry Laboratory 1","Altar, Mary Grace Barnachea","2025-2026","10","Boson, Electron, Graviton, Photon","Chem Elective (Envi. and ","Food Chem","2026-02-05","10:36 PM to 11:36 PM","2026-02-05 14:36:15","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","28","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Fronda, Joseph Victor Tadena","2025-2026","8","Adelfa, Camia, Dahlia, Sampaguita","Physics 1","Kinetic Energy","2026-02-06","8:34 AM to 12:34 PM","2026-02-06 00:35:37","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","29","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Albano, Jonellyn Stohner, Altar, Mary Grace Barnachea","2025-2026","10","Boson, Electron, Graviton, Photon","CS Elective (Robotics)","Test 2","2026-02-12","9:01 PM to 11:02 PM","2026-02-09 13:02:30","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","30","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Albano, Jonellyn Stohner, Ambion, Arianne Noreen Bactin","2025-2026","8","Adelfa, Camia, Dahlia, Sampaguita","Chemistry 1","Test 3","2026-02-09","9:04 PM to 11:04 PM","2026-02-09 13:04:22","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","31","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Albano, Jonellyn Stohner","2025-2026","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Test","2026-02-10","3:30 PM to 6:31 PM","2026-02-10 07:31:11","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","32","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Albano, Jonellyn Stohner","2025-2026","9","Barium, Beryllium, Cesium, Lithium","Biology 1","Test","2026-02-10","3:35 PM to 6:35 PM","2026-02-10 07:35:51","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","33","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Albano, Jonellyn Stohner","2025-2026","8","Adelfa","Social Science 2","Test","2026-02-10","3:48 PM to 6:48 PM","2026-02-10 07:58:21","","approved","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","34","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 3","Ducusin, Michelle Bengzon","2025-2026","10","Boson, Electron, Graviton, Photon","Physics Elective (Earth S","Ma\'am Mich","2026-02-10","4:05 PM to 6:05 PM","2026-02-10 08:05:16","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","35","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Ngayaan, Niro Maog","2025-2026","7","Diamond, Emerald, Ruby, Sapphire","Computer Science 1","Test","2026-02-11","9:02 PM to 11:02 PM","2026-02-11 13:02:38","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","36","E004","","7652351",NULL,NULL,NULL,NULL,"Science Laboratory 2","Albano, Jonellyn Stohner","2025-2026","7","Diamond, Emerald, Ruby, Sapphire","Filipino 1","Test","2026-02-13","4:48 PM to 9:48 PM","2026-02-13 08:51:40","?","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","37","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Albano, Jonellyn Stohner","2025-2026","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Test","2026-02-16","2:11 PM to 6:11 PM","2026-02-16 06:11:35","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","38","E004","","4124",NULL,NULL,NULL,NULL,"Science Laboratory 2","Valdez, Gabriel James V.","2025-2026","8","Adelfa, Camia, Dahlia, Sampaguita","Physics 1","Test","2026-02-16","2:13 PM to 4:13 PM","2026-02-16 06:14:47","Gabdez","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","39","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 3","Valdez, Gabriel James V.","2025-2026","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Test","2026-02-16","8:11 PM to 11:11 PM","2026-02-16 12:13:24","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","40","","","0",NULL,NULL,NULL,NULL,"Science Laboratory 3","Barangan, Zyx Leiabe B.","2025-2026","8","Adelfa, Camia, Dahlia, Sampaguita","Social Science 2","test","2026-02-18","10:14 AM to 11:14 AM","2026-02-18 01:14:30","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","41","100075150040","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Barangan, Zyx Leiabe B.","2025-2026","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","test","2026-03-11","8:00 AM to 9:00 AM","2026-03-11 00:16:33","","approved","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","42","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Barangan, Zyx Leiabe B.","2025-2026","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","tes","2026-03-19","3:38 PM to 4:38 PM","2026-03-19 07:38:13","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","43","08-037","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Callueng, Monaliza Mandac","2025-2026","9","Barium, Beryllium, Cesium, Lithium","Chemistry 1","Solution","2026-03-23","8:00 AM to 5:00 PM","2026-03-23 06:53:59","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","44","24-053","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Paz, Jan Czarina Bautista","2025-2026","11","Dalton","Science Core 1 (Chemistry","Trial 1","2026-03-23","7:00 AM to 10:00 AM","2026-03-23 07:02:47","","approved","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","45","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 3","Barangan, Zyx Leiabe B.","2025-2026","8","Adelfa, Camia, Dahlia, Sampaguita","Biology 1","Frog Dissection","2026-03-25","4:04 PM to 6:04 PM","2026-03-23 08:04:34","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","46","24-044","","0",NULL,NULL,NULL,NULL,"Science Laboratory 3","Palabrica, Angel Castro","2025-2026","9","Barium, Beryllium","Biology 1","Nervous System","2026-04-06","8:15 AM to 5:00 PM","2026-04-06 07:35:29","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("pending","47","17-034","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Bucsit, Jumari Visitacion","2025-2026","8","Adelfa, Camia, Dahlia, Sampaguita","Chemistry 1","IMFAs","2026-04-13","8:00 AM to 5:00 PM","2026-04-13 07:08:55","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","48","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Valdez, Gabriel James V.","2025-2026","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","beta test","2026-05-12","10:00 AM to 11:11 AM","2026-05-12 13:56:45","","approved","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","49","E004","","123",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Science Core 2 (Physics)","test","2026-07-27","8:00 AM to 10:00 AM","2026-08-12 03:31:39","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","50","E004","","123",NULL,NULL,NULL,NULL,"Physics Laboratory 3","Ngayaan, June Leonel Maog","2026-2027","9","Barium, Beryllium, Cesium, Lithium","Biology 1","Frog Dissection","2026-08-05","11:50 AM to 3:20 PM","2026-08-12 06:32:06","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","51","400280150068","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Sigma","SCALEg12","SCALE","2026-08-12","3:00 PM to 4:00 PM","2026-08-12 07:06:37","","approved","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","52","E004","","87987",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Frog Dissection","2026-08-12","4:25 PM to 5:25 PM","2026-08-12 06:54:16","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","53","E004","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Sample Test","2026-08-12","3:00 PM to 4:00 PM","2026-08-12 06:59:30","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","54","400280150068","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Alpha, Delta, Omega, Sigma","DMT","test","2026-08-14","8:00 AM to 9:00 AM","2026-08-13 15:58:08","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","55","400280150068","","0",NULL,NULL,NULL,NULL,"Science Laboratory 4","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","asd","2026-08-17","6:00 AM to 8:00 AM","2026-08-13 16:16:19","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","56","E004","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Integ Sci","2026-08-13","2:49 AM to 4:49 AM","2026-08-13 18:49:34","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Rejected","57","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Sample Test","2026-08-14","8:37 AM to 9:37 AM","2026-08-14 03:34:39","Test","rejected","rejected","rejected","rejected",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","58","102283140501","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Frog Dissection","2026-08-14","8:51 AM to 9:51 AM","2026-08-14 00:52:39","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","59","400280150068","","412",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","asd","2026-08-14","8:00 AM to 10:00 AM","2026-08-14 05:12:41","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","60","102283140501","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","8","Adelfa, Camia, Dahlia, Sampaguita","Biology 1","Pig Heart Dissection","2026-08-14","8:54 AM to 9:54 AM","2026-08-14 00:54:55","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","61","400280150068","","1234",NULL,NULL,NULL,NULL,"Science Laboratory 1","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","PEHM 1","material test","2026-08-14","8:00 AM to 10:00 AM","2026-08-14 01:14:32","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","62","102283140501","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B., Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Integ Sci","2026-08-14","9:28 AM to 11:28 AM","2026-08-14 03:36:58","","approved","approved","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","63","102283140501","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Alpha, Delta, Omega, Sigma","Elective","Livestock ","2026-08-14","8:00 AM to 12:00 PM","2026-08-14 03:46:12","","approved","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","64","08-2021-093","","1236",NULL,NULL,NULL,NULL,"Science Laboratory 2","Valdez, Gabriel James V.","2026-2027","12","Sigma","Research 3","res","2026-08-17","8:00 AM to 10:00 AM","2026-08-14 03:45:13","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","65","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Frog Dissection","2026-08-14","11:53 AM to 1:53 PM","2026-08-14 04:04:31","","approved","approved","approved","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","66","E004","","242151",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Frog Dissection","2026-08-14","12:13 PM to 3:13 PM","2026-08-14 05:13:01","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","67","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Frog Dissection","2026-08-14","1:05 PM to 4:05 PM","2026-08-14 05:06:56","","approved","approved","approved","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","68","102283140501","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Ngayaan, June Leonel Maog","2026-2027","12","Omega","Engineering","test","2026-08-14","8:00 AM to 10:00 AM","2026-08-14 05:20:50","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","69","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Ngayaan, June Leonel Maog","2026-2027","12","Sigma","DMT","test 2","2026-08-14","11:02 AM to 12:00 PM","2026-08-14 05:38:21","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","70","102283140501","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Valdez, Gabriel James V.","2026-2027","10","Boson, Electron, Graviton, Photon","Technology Elective (DNF)","asd","2026-08-17","1:01 AM to 3:00 AM","2026-08-14 05:39:56","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","71","00-000","","413",NULL,NULL,NULL,NULL,"Science Laboratory 1","Ngayaan, June Leonel Maog","2026-2027","12","Omega","Computer Science 5","dadsdsa","2026-08-18","10:00 AM to 11:00 AM","2026-08-14 05:53:45","","approved","approved","approved","approved",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","72","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Values Education 1","asd","2026-08-15","11:00 AM to 11:30 AM","2026-08-15 19:51:31","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","73","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Values Education 1","asd","2026-08-15","11:00 AM to 11:30 AM","2026-08-15 19:56:20","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","74","102283140501","","0",NULL,NULL,NULL,NULL,"Science Laboratory 2","Valdez, Gabriel James V.","2026-2027","8","Adelfa, Camia, Dahlia, Sampaguita","Earth Science 1","asdg","2026-08-24","10:00 AM to 11:00 AM","2026-08-15 20:16:18","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","75","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Barangan, Zyx Leiabe B.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Integrated Science","Integ Sci","2026-08-16","9:15 PM to 10:16 PM","2026-08-16 13:18:32","","approved","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","76","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Barangan, Zyx Leiabe B.","2026-2027","10","Boson, Electron, Graviton, Photon","Physics 2","Test","2026-08-17","3:23 PM to 5:23 PM","2026-08-17 07:26:40","","approved","approved","approved","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,"2026-08-17 07:26:40","2026-08-17 07:24:43");
INSERT INTO `scilab_form_requests` VALUES("Pending","77","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","10","Boson, Electron, Graviton, Photon","Physics 2","Sample Test","2026-08-17","3:59 PM to 5:59 PM","2026-08-17 15:59:55","","approved","approved","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","78","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","8","Adelfa, Camia, Dahlia, Sampaguita","Physics 1","sample test","2026-08-18","10:00 AM to 11:00 AM","2026-08-17 17:57:38","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","79","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","8","Adelfa, Camia, Dahlia, Sampaguita","Physics 1","test","2026-08-18","10:00 AM to 12:00 PM","2026-08-17 10:40:28","","approved","approved","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","80","E004","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Barangan, Zyx Leiabe B.","2026-2027","10","Boson, Electron, Graviton, Photon","Physics 2","Sample Test","2026-08-17","7:28 PM to 8:28 PM","2026-08-17 11:37:19","","approved","approved","approved","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","81","102283140501","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Alpha, Delta, Omega, Sigma","Engineering","test","2026-08-19","8:00 AM to 10:00 AM","2026-08-19 03:26:28","","approved","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","82","E004","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Barangan, Zyx Leiabe B.","2026-2027","11","Curie, Dalton, Mendel, Newton","Computer Science 5","test","2026-08-18","10:00 AM to 12:00 PM","2026-08-19 15:02:00","","approved","approved","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","83","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","8","Adelfa, Camia, Dahlia, Sampaguita","English 2","test","2026-08-25","8:00 AM to 10:00 AM","2026-08-26 07:02:14","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","84","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega, Sigma","Science Core 2 (Physics)","Uncertainty","2026-09-01","8:00 AM to 10:00 AM","2026-09-01 19:18:25","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Rejected","85","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Ngayaan, June Leonel Maog","2026-2027","12","Omega, Sigma","Science Core 2 (Physics)","test","2026-09-04","8:00 AM to 10:00 AM","2026-09-04 03:00:28","Class suspension","rejected","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Approved","86","GUEST-1788490977","","0","","","","","Physics Laboratory 1","Ngayaan, June Leonel Maog","2026-2027","12","Omega, Sigma","Computer Science 5","test","2026-09-04","8:00 AM to 10:00 AM","2026-09-18 02:42:54","","approved","approved","approved","approved","2026-09-18 02:42:54","Gabriel James V. Valdez","2026-09-18 02:42:54","Gabriel James V. Valdez","2026-09-18 02:42:54","Gabriel James V. Valdez","2026-09-18 02:42:54","Gabriel James V. Valdez",NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","87","E004","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega, Sigma","Science Core 2 (Physics)","test","2026-09-04","10:00 AM to 11:00 AM","2026-09-04 04:13:52","","approved","approved","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Rejected","88","E004","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Valdez, Gabriel James V.","2026-2027","12","Omega, Sigma","Science Core 2 (Physics)","test","2026-09-04","8:00 AM to 10:00 AM","2026-09-04 06:29:15","test reject","rejected","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","89","102283140501","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Ngayaan, June Leonel Maog","2026-2027","8","Sampaguita","Physics 1","test of info irc email","2026-09-04","2:50 PM to 3:50 PM","2026-09-04 14:50:29","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","90","","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","9","Barium, Beryllium, Cesium, Lithium","Statistics 1","test","2026-09-17","8:00 AM to 10:00 AM","2026-09-17 08:06:47","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","91","08-2021-300","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","12","Omega","Research 3","Test","2026-09-21","8:00 AM to 10:00 AM","2026-09-18 02:06:37","","approved","pending","pending","pending","2026-09-18 02:06:37","MARIA ELLYNE ANGELIQUE GAOR RAMOS",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","92","08-2021-300","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","12","Omega","Math 6","Test","2026-09-18","10:23 AM to 2:23 PM","2026-09-18 02:24:47","","approved","pending","pending","pending","2026-09-18 02:24:47","Approval Link User",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","93","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","Test","2026-09-18","10:27 AM to 11:27 AM","2026-09-18 10:27:17","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","94","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","Test","2026-09-18","10:33 AM to 11:33 AM","2026-09-18 10:33:58","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","95","E004","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","Test","2026-09-18","10:39 AM to 11:39 AM","2026-09-18 02:41:21","","approved","approved","pending","pending","2026-09-18 02:39:58","Approval Link User","2026-09-18 02:41:21","Approval Link User",NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","96","100050140026","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","12","Omega","Math 6","Test","2026-09-18","10:47 AM to 11:47 AM","2026-09-18 02:49:16","","approved","pending","pending","pending","2026-09-18 02:49:16","MARIA ELLYNE ANGELIQUE GAOR RAMOS",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","97","100050140026","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","12","Omega","Math 6","Test","2026-09-18","11:03 AM to 11:14 AM","2026-09-18 11:03:40","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","98","100050140026","","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","12","Omega","Math 6","Test","2026-09-18","11:10 AM to 11:15 AM","2026-09-18 11:10:32","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","99","100050140026","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Barangan, Zyx Leiabe B.","2026-2027","12","Alpha, Delta, Omega, Sigma","Math 6","test","2026-09-18","11:42 AM to 11:48 AM","2026-09-18 11:42:37","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","100","100050140026","","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Barangan, Zyx Leiabe B.","2026-2027","12","Omega","Math 6","test","2026-09-18","11:48 AM to 11:53 AM","2026-09-18 03:55:19","","approved","pending","pending","pending","2026-09-18 03:55:19","MARIA ELLYNE ANGELIQUE GAOR RAMOS",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","101","102283140501","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","test","2026-09-19","8:00 AM to 10:00 AM","2026-09-20 13:02:11","","approved","pending","pending","pending","2026-09-20 13:02:11","Approval Link User",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","102","Guest","Mathematics","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Barangan, Zyx Leiabe B.","2026-2027","12","Delta","Chem 4 Elective","Test","2026-09-21","10:00 AM to 11:00 AM","2026-09-21 05:43:08","","approved","pending","pending","pending","2026-09-21 05:43:08","Approval Link User",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","103","E004","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Valdez, Gabriel James V.","2026-2027","7","Diamond, Emerald, Ruby, Sapphire","Mathematics 1","Test","2026-09-21","2:48 PM to 3:48 PM","2026-09-21 07:52:01","","approved","approved","pending","pending","2026-09-21 06:50:53","Approval Link User","2026-09-21 07:52:01","Zyx Leiabe B. Barangan",NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","104","102283140501","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","Test","2026-09-21","8:00 AM to 10:00 AM","2026-09-21 15:10:17","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","105","102283140501","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","test","2026-09-21","8:00 AM to 10:00 AM","2026-09-21 15:11:29","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","106","102283140501","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","test 3","2026-09-21","8:01 AM to 10:00 AM","2026-09-21 15:12:38","","pending","pending","pending","pending",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","107","102283140501","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","final test","2026-09-22","8:00 AM to 10:00 AM","2026-09-22 13:24:08","","approved","pending","pending","pending","2026-09-22 13:24:08","NERSON DAVE CALMA TABION",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","108","102283140501","Mathematics","0",NULL,NULL,NULL,NULL,"Science Laboratory 4","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","test tes","2026-09-22","8:00 AM to 10:00 AM","2026-09-22 13:51:38","","approved","pending","pending","pending","2026-09-22 13:51:38","NERSON DAVE CALMA TABION",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","109","102283140501","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","morning test","2026-09-22","8:00 AM to 9:00 AM","2026-09-22 23:08:29","","approved","pending","pending","pending","2026-09-22 23:08:29","NERSON DAVE CALMA TABION",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","110","102283140501","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","tester","2026-10-04","8:22 PM to 9:22 PM","2026-10-04 12:55:32","","approved","pending","pending","pending","2026-10-04 12:55:32","Approval Link User",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","111","E004","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 2","Valdez, Gabriel James V.","2026-2027","9","Barium, Beryllium, Cesium, Lithium","Mathematics 3","Test","2026-10-05","3:31 PM to 4:31 PM","2026-10-05 15:32:46","","approved","pending","pending","approved","2026-10-05 15:32:46","Zyx Leiabe B. Barangan",NULL,NULL,NULL,NULL,"2026-10-05 15:32:46","Auto-approved (Teacher request)",NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","112","E004","Mathematics","0",NULL,NULL,NULL,NULL,"Physics Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","test","2026-10-05","3:47 PM to 4:47 PM","2026-10-05 15:47:45","","approved","pending","pending","approved","2026-10-05 15:47:45","Zyx Leiabe B. Barangan",NULL,NULL,NULL,NULL,"2026-10-05 15:47:45","Auto-approved (Teacher request)",NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","113","E004","Mathematics","0",NULL,NULL,NULL,NULL,"Science Laboratory 1","Valdez, Gabriel James V.","2026-2027","12","Omega","Math 6","testertest","2026-10-05","3:49 PM to 4:49 PM","2026-10-05 15:49:30","","approved","pending","pending","approved","2026-10-05 15:49:30","Zyx Leiabe B. Barangan",NULL,NULL,NULL,NULL,"2026-10-05 15:49:30","Auto-approved (Teacher request)",NULL,NULL);
INSERT INTO `scilab_form_requests` VALUES("Pending","114","102283140501","Chemistry","0",NULL,NULL,NULL,NULL,"Science Laboratory 3","Muska, April Jieren Ramirez","2026-2027","12","Delta","Science Core 2","Chemistry","2026-10-06","3:59 PM to 5:00 PM","2026-10-06 08:10:04","","approved","approved","pending","pending","2026-10-06 08:02:34","Supervisor (via approval link)","2026-10-06 08:10:04","Subject Teacher (via approval link)",NULL,NULL,NULL,NULL,NULL,NULL);



DROP TABLE IF EXISTS `scilab_inventory`;

CREATE TABLE `scilab_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productID` varchar(12) NOT NULL,
  `item` varchar(255) NOT NULL,
  `classification` varchar(20) NOT NULL,
  `location` varchar(100) NOT NULL,
  `laboratory` varchar(100) NOT NULL,
  `quantity` int(6) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `status` varchar(12) NOT NULL,
  `threshold_qty` int(6) DEFAULT NULL,
  `threshold_notified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1235 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `scilab_inventory` VALUES("714","7446-70-0","Aluminum Chloride","Reagent","","","300","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("715","10326-27-9","Barium Chloride","Reagent","","","400","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("716","temp-0001","Calcium Chloride","Reagent","","","150","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("717","10125-13-0","Copper (II) Chloride","Reagent","","","1200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("718","temp-0002","Iron (III) Chloride","Reagent","","","300","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("719","temp-0003","Potassium Chloride","Reagent","","","1150","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("720","temp-0004","Sodium Chloride","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("721","temp-0005","Zinc Chloride","Reagent","","","10","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("722","1002-31-8","Barium Nitrate","Reagent","","","300","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("723","temp-0006","Bromine Water","Reagent","","","600","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("724","temp-0007","2-propanol","Reagent","","","10","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("725","temp-0008","Acetone","Reagent","","","4","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("726","temp-0009","Denatured Alcohol","Reagent","","","60","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("727","64-17-5","Ethanol","Reagent","","","110","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("728","temp-0010","Phenolphthalein Powder","Reagent","","","250","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("729","106-42-3","Xylene","Reagent","","","1","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("730","temp-0011","Nutrient Agar","Reagent","","","700","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("731","temp-0012","Nutrient Broth","Reagent","","","900","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("732","temp-0013","Potato Dextrose Agar","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("733","7784-24-9","Aluminum Potassium Sulfate","Reagent","","","194","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("734","6009-70-7","Ammonium Oxalate Monohydrate","Reagent","","","400","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("735","7727-43-7","Barium Sulphate","Reagent","","","200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("736","1303-96-4","Borax","Reagent","","","100","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("737","573-58-0","Congo Red","Reagent","","","10","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("738","1317-38-0","Copper (II) Oxide","Reagent","","","480","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("739","7440-50-8","Copper Metal Powder","Reagent","","","235","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("740","7758-98-7","Cupric Sulfate","Reagent","","","1800","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("741","75-09-02","Dichloromethane / Methylene Chloride","Reagent","","","9","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("742","178761-7","Eriochrome Black T","Reagent","","","25","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("743","7782-42-5","Graphite","Reagent","","","50","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("744","7553-56-2","Iodine Crystals","Reagent","","","200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("745","547-58-0","Methyl Orange Solution Salt","Reagent","","","5","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("746","temp-0014","Mossy Zinc","Reagent","","","0","","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("747","779-23-3","Potassium Fluoride Anhydrous","Reagent","","","250","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("748","333-20-0","Potassium Thiocyanate","Reagent","","","200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("749","temp-0015","Sodium Acetate Trihydrate","Reagent","","","200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("750","497-19-8","Sodium Carbonate Anhydrous","Reagent","","","250","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("751","7681-49-4","Sodium Fluoride","Reagent","","","125","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("752","7681-57-4","Sodium Metabisulfite","Reagent","","","400","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("753","7558-79-4","Sodium Phosphate","Reagent","","","300","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("754","7757-83-7","Sodium Sulphite","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("755","540-72-7","Sodium Thiocyanate","Reagent","","","270","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("756","1314-13-2","Zinc Oxide","Reagent","","","447","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("757","temp-0016","Benedict’s Solution","Reagent","","","700","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("758","6153-39-5","Bial Orcinol Reagent","Reagent","","","300","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("759","76-60-8","Bromocresol Green","Reagent","","","30","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("760","temp-0017","Bromthymol Blue","Reagent","","","100","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("761","temp-0018","Carbol Fuchsin Stain","Reagent","","","500","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("762","57-48-7","D-Fructose","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("763","50-99-7","D-Glucose Monohydrate","Reagent","","","100","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("764","95-45-4","Dimethylglyoxime","Reagent","","","480","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("765","temp-0019","Dimethyl Sulfoxide","Reagent","","","1","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("766","temp-0020","Fehling’s Reagent A","Reagent","","","1","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("767","temp-0021","Fehling’s Reagent B","Reagent","","","500","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("768","temp-0022","Gentian Violet","Reagent","","","100","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("769","56-40-6","Glycine","Reagent","","","400","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("770","temp-0023","Iodine Solution","Reagent","","","200","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("771","56-41-7","L-Alanine","Reagent","","","50","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("772","1312-81-8","Lanthanum Oxide","Reagent","","","80","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("773","74-79-3","L-Arganine","Reagent","","","50","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("774","temp-0024","Lead Acetate","Reagent","","","250","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("775","7439-92-1","Lead Grain","Reagent","","","20","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("776","temp-0025","Lucas Reagent","Reagent","","","1000","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("777","temp-0026","Lugol’s Solution","Reagent","","","1500","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("778","1309-48-4","Magnesium Oxide Lite","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("779","temp-0027","Manganese Dioxide","Reagent","","","100","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("780","temp-0028","Mayer’s Reagent","Reagent","","","1700","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("781","temp-0029","Methylene Blue (Powder)","Reagent","","","100","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("782","64-17-5","Molisch Reagent","Reagent","","","600","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("783","7440-66-6","Mossy Zinc","Reagent","","","400","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("784","temp-0030","Phenolphthalein","Reagent","","","200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("785","RNBM3697","Phosphate Buffer Saline","Reagent","","","500","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("786","27260","Poly Ethylene Glycol","Reagent","","","300","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("787","9002-89-5","Poly Vinyl Alcohol","Reagent","","","700","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("788","temp-0031","Potassium Iodide","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("789","temp-0032","Potassium Iodide","Reagent","","","200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("790","6381-59-5","Potassium Sodium Tartrate","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("791","77-09-8","Profame Stain","Reagent","","","800","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("792","3822-0090","Seliwanoff’s Reagent","Reagent","","","300","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("793","temp-0033","Sodium Hydrogen Carbonate","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("794","6255-5-5","Thioacetamide","Reagent","","","1","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("795","temp-0034","Tollens Reagent","Reagent","","","1","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("796","temp-0035","Yeast, Active Dry","Reagent","","","100","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("797","90-15-3","1-Naphthol","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("798","7981-26-1","Aluminum Sulphate Hydrate","Reagent","","","200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("799","temp-0036","Ammonia Solution","Reagent","","","2","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("800","temp-0037","Boric Acid","Reagent","","","200","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("801","temp-0038","Benzoic Acid","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("802","548-62-9","Crystal Violet","Reagent","","","100","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("803","temp-0039","Glacial Acetic Acid","Reagent","","","1","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("804","temp-0040","Hydrochloric Acid","Reagent","","","8","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("805","7697-37-2","Nitric Acid","Reagent","","","1","L","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("806","25155-30-0","Sodium Dodecylbenzene Sulfonate","Reagent","","","25","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("807","7631-40","Sodium Hydrogen Sulphite","Reagent","","","500","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("808","temp-0041","Sodium Hydroxide","Reagent","","","2","kg","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("809","7664-93-9","Sulfuric Acid","Reagent","","","100","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("810","9002-93-1","Triton X-100","Reagent","","","100","ml","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("811","7446-20-0","Zinc Sulfate","Reagent","","","400","g","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("812","TS-03-12-028","Alcohol Lamp","Semi Expendable","","","1","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("813","temp-0042","Amber Bottle","Semi Expendable","","","1","piece","500 ml with cover","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("814","temp-0043","Amber Bottle","Semi Expendable","","","1","piece","1 L with cover","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("815","temp-0044","Amber Bottle","Semi Expendable","","","1","piece","2 L with screw cap cover","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("816","temp-0045","Beaker","Glassware","","","1","piece","pyrex, 30 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("817","TS-03-07-004","Beaker","Glassware","","","1","piece","pyrex, 50 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("818","TS-04-08-106","Beaker","Glassware","","","1","piece","pyrex, 100 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("819","TS-03-07-003","Beaker","Glassware","","","1","piece","250 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("820","10404070-01","Beaker","Glassware","","","1","piece","pyrex, 250 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("821","temp-0046","Beaker","Glassware","","","1","piece","pyrex, #1060, 500 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("822","temp-0047","Beaker","Glassware","","","1","piece","pyrex, 1 L","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("823","temp-0048","Buchner Funnel","Semi Expendable","","","1","piece","200 ml, China","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("824","temp-0049","Buchner Funnel","Semi Expendable","","","1","piece","4 ceramic, 110 mm","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("825","temp-0050","Burette Acid","Semi Expendable","","","1","piece","50 ml teflon stopcock, 0.1 ml resolution","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("826","TS-07-12-156","Burette Acid","Semi Expendable","","","1","piece","glass stopcock, china","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("827","temp-0051","Burette Acid","Semi Expendable","","","1","piece","25 ml PTFE stopcock","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("828","temp-0052","Burette, Base","Semi Expendable","","","1","piece","50 ml teflon stopcock, 0.1 ml resolution","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("829","temp-0053","Burette, Base","Semi Expendable","","","1","piece","50 ml, pyrex 2116","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("830","TS-03-12-036","Crucible with cover","Semi Expendable","","","1","piece","heat resistant ceramics","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("831","temp-0054","Dessicator with Cover","Semi Expendable","","","1","set","glass","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("832","temp-0055","Distillation Flask","Semi Expendable","","","1","piece","250 ml with side arm","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("833","temp-0056","Distillation Flask","Semi Expendable","","","1","piece","25 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("834","temp-0057","Distillation Flask","Semi Expendable","","","1","piece","50 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("835","temp-0058","Erlenmeyer Flask","Semi Expendable","","","1","piece","125 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("836","temp-0059","Erlenmeyer Flask","Semi Expendable","","","1","piece","150 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("837","TS-08-07-166","Erlenmeyer Flask","Semi Expendable","","","1","piece","pyrex, 250 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("838","TS-08-07-167","Erlenmeyer Flask","Semi Expendable","","","1","piece","screw cap","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("839","temp-0060","Erlenmeyer Flask","Semi Expendable","","","1","piece","500ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("840","temp-0061","Evaporating Dish","Semi Expendable","","","1","piece","100 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("841","TS-07-12-146","Florence Flask","Semi Expendable","","","1","piece","250 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("842","TS-07-12-145","Funnel (Glass)","Semi Expendable","","","1","piece","small","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("843","10404070-01","Funnel (Glass)","Semi Expendable","","","1","piece","medium, 90 mm","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("844","10404070-01","Funnel (Glass)","Semi Expendable","","","1","piece","large, 120 mm","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("845","temp-0062","Glass Spreader","Semi Expendable","","","1","piece","L Shape Glass","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("846","temp-0063","Graduated Beaker","Glassware","","","1","piece","Low form PMP (TPX®) Material, Crystal Clear, Autoclavable, Acid Resistant, Germany PLASTIC 1000 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("847","temp-0064","Graduated Beaker","Glassware","","","1","piece","Low form PMP (TPX®) Material, Crystal Clear, Autoclavable, Acid Resistant, Germany PLASTIC 250 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("848","temp-0065","Graduated Beaker","Glassware","","","1","piece","Low form PMP (TPX®) Material, Crystal Clear, Autoclavable, Acid Resistant, Germany PLASTIC 500 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("849","TS-04-08-100","Graduated Cylinder","Glassware","","","1","piece","10 ml, glass based","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("850","TS-03-07-011","Graduated Cylinder","Glassware","","","1","piece","50 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("851","TS-04-08-101","Graduated Cylinder","Glassware","","","1","piece","100 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("852","TS-04-08-101","Graduated Cylinder","Glassware","","","1","piece","250 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("853","temp-0066","Graduated Cylinder, Pure heavy plastic","Glassware","","","1","piece","25 ml, glass based","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("854","temp-0067","Graduated Cylinder, Pure heavy plastic","Semi Expendable","","","1","piece","100 ml, glass based","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("855","temp-0068","Graduated Cylinder, Pure heavy plastic","Semi Expendable","","","1","piece","250 ml, glass based","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("856","temp-0069","Media Bottle","Semi Expendable","","","1","piece","250 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("857","temp-0070","Media Bottle","Semi Expendable","","","1","piece","500 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("858","temp-0071","Media Bottle","Semi Expendable","","","1","piece","1L, Kimax 14395","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("859","temp-0072","Media Bottle","Semi Expendable","","","1","piece","2 L, Kimax 14395","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("860","TS-06-09-119","Petri Dish","Semi Expendable","","","1","piece","pyrex, 15 x 100 mm","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("861","TS-03-12-052","Pipette","Semi Expendable","","","1","piece","glass","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("862","TS-07-12-150","Reagent Bottle","Glassware","","","1","piece","100 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("863","TS-07-12.151","Reagent Bottle","Glassware","","","1","piece","250 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("864","temp-0073","Reagent Bottle","Glassware","","","1","bottle","500 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("865","temp-0074","Reagent Bottle","Glassware","","","1","bottle","1000 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("866","temp-0075","Separatory Funnel","Semi Expendable","","","1","piece","500 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("867","temp-0076","Female Internal Reproductive Organ Model","Semi Expendable","","","1","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("868","temp-0077","Stirring Rod","Semi Expendable","","","1","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("869","temp-0078","Spotplate","Semi Expendable","","","1","piece","ceramics, 8 holes","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("870","TS-03-12-063","Test Tube","Glassware","","","1","piece","small 9 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("871","temp-0079","Test Tube","Glassware","","","1","piece","medium screw cap","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("872","temp-0080","Test Tube","Glassware","","","1","piece","small, Pyrex with screw cap","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("873","temp-0081","Test Tube","Glassware","","","1","piece","medium , pyrex","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("874","temp-0082","Test Tube","Glassware","","","1","piece","large, 25 x 200 mm, pyrex #9820","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("875","temp-0083","Thermometer","Semi Expendable","","","1","piece","Glass","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("876","temp-0084","Vacuum Filtration Set up/System","Semi Expendable","","","1","unit","with complete vacuum system, buchner funnel traps, hose, vacuum flasks","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("877","temp-0085","Graduated Beaker","Glassware","","","1","piece","30 ml Low form PMP (TPX®) Material, Crystal Clear, Autoclavable, Acid Resistant, Germany GLASS 30 ml","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("878","temp-0086","Volumetric Flask","Glassware","","","1","piece","100 ml Kimax w/ plastic snap cap","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("879","temp-0087","Volumetric Flask","Glassware","","","1","piece","1000 ml Kimax w/ plastic snap cap","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("880","temp-0088","Volumetric Flask","Glassware","","","1","piece","250 ml, Kimax w/ plastic snap cap","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("881","temp-0089","Volumetric Flask","Glassware","","","1","piece","500 ml, Kimax w/ plastic snap cap","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("882","temp-0090","Volumetric Flask","Glassware","","","1","piece","50 ml, pyrex w/ plastic stopcock","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("883","TS-04-08-116","Watch Glass","Semi Expendable","","","1","piece","big","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("884","TS-04-08-107","Watch Glass","Semi Expendable","","","1","piece","small","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("885","00-001-078 /","550 Universal Interface","Equipment","","","1","pieces","(for PASCO Sensor)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("886","00-001-99-20","Absorbance Microplate Reader","Equipment","","","1","pieces","Model: EL-10A, Elisa Reader","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("887","temp-0091","Adjustable-Volume Pippette","Semi Expendable","","","1","unit","Biopette Autoclavable 8-channel, 20-200 ul (BPE-200)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("888","00-001-138-2","Adjustable-Volume Pippette","Semi Expendable","","","1","set","Adjustable-Volume Pippette-Labnet P4608-200A Biopette Autoclavable 8-channel, 20-200uL (BPE-200)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("889","00-001-61-20","Airlink 2 (PASCO)","Semi Expendable","","","1","set","Airlink 2 with free passport sensors/ non contact temperature","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("890","temp-0092","Analytical Balance","Equipment","","","1","pieces","Shimadzu AUX-220, 220g x 0.1mg","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("891","00-001-130 /","Analytical Balance","Equipment","","","1","pieces","Shimadzu Unibloc, Model AP324Y","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("892","00-001-194/1","Analytical Balance","Equipment","","","1","pieces","Shimadzu Unibloc, Model AP324Y","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("893","00-001-62-20","Autoclave Sterilizer Machine, portable","Semi Expendable","","","1","unit","Model: HY230","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("894","00-001-120-2","Autoclave (Vertical)","Equipment","","","1","pieces","Model: BKQ-Z50I (BIOBASE)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("895","00-001-90-20","Benchtop Circulating Chiller","Equipment","","","1","pieces","DLSB 5/10","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("896","00-001-93-20","Biosafety Cabinet","Equipment","","","1","pieces","Class 2, Type B1, BYKG-V","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("897","00-001-126-2","Biosafety Cabinet","Equipment","","","1","pieces","Streamline Class II Biological Safety Cabinet, stainless steel side walls (SC2-series)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("898","00-001-151-2","Blender","Equipment","","","1","pieces","Waring Single speed, USA","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("899","00-001-178/1","Blender","Equipment","","","1","unit","Waring Blender 700G, 120V AC","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("900","00-001-73 / ","Boyle\'s Law Apparatus","Semi Expendable","","","1","set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("901","00-001-106 /","Calorimetry Set (Basic)","Semi Expendable","","","1","unit","PASCO TD-8557B","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("902","00-001-92-20","Centrifuge, Benchtop","Equipment","","","1","unit","DM0412 with adapter plugs","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("903","00-001-147-2","Centrifuge, Benchtop","Equipment","","","1","unit","w/ A12-10 P Rotor, Dlab (USA)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("904","temp-0093","Chemical Storage cabinet","Equipment","","","1","unit","general storage (1), acids(1),corrosive(1) & flammable(1)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("905","00-001-197-2","Chemistry Analyzer","Equipment","","","1","unit","Aggape Mispa Plus, Chemistry Machine","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("906","00-001-090-2","Colony Counter, Automatic","Equipment","","","1","unit","power: 100-240 V, Countless II FL SN:2185A17031142","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("907","00-001-024-2","Color Ming Appratus","Equipment","","","1","unit","Cenco, 16V9953, uses LED Technology","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("908","00-001-134 /","Compact Incubator","Equipment","","","1","unit","Model: BJPX-H30II (BIOBASE)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("909","00-001-83-20","Computerized Astronomical Telescope","Equipment","","","1","set","Celestron, advance VX6 Schmidt-Cassegrain, USA","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("910","temp-0094","Dehumidifier","Semi Expendable","","","1","piece","Model: RHD26E, 2G L/day","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("911","TS-07-10-133","Digital DO Meter, Water Testing Kit","Semi Expendable","","","1","unit","Milwaukee","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("912","00-001-44-20","Digital Spectronic 20D +Spectrophotometer","Equipment","","","1","unit","GB # 9000795","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("913","00-001-54-20","Distilling Apparatus","Semi Expendable","","","1","piece","Tower type,5L","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("914","00-001-018-2","DPS Desktop Video Camera","Equipment","","","1","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("915","00-001-98-20","Drying Oven","Equipment","","","1","unit","Model: JSOF-150 JSR","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("916","00-001-119-2","Drying Oven","Equipment","","","1","unit","Model: BOV-V70F (BIOBASE)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("917","temp-0095","Egg Incubator","Semi Expendable","","","1","unit","BIOBASE BK-E1264","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("918","00-001-191-2","Electron Dispersive X-ray (EDX)","Equipment","","","1","unit","EDX System","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("919","00-001-165-2","Field Mapper Kit","Semi Expendable","","","1","unit","PK-9023","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("920","00-001-163-2","Fourier Transform Infrared Spectrometer (FTIR)","Equipment","","","1","unit","with software, Brand: PERKINELMER","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("921","00-001-136 /","Fume Hood","Equipment","","","1","unit","Model: FH1000 (E) BIOBASE","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("922","00-001-127-2","Furnace","Equipment","","","1","set","Model: MC2.5-12","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("923","00-001-140-2","Gas Chromatography","Equipment","","","1","unit","Shimadzu, Model: GC20101 Plus","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("924","00-001-152-2","Gel Electrophoresis","Equipment","","","1","unit","Cleaver Scientific","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("925","00-001-148-2","Genes in a bottle","Semi Expendable","","","1","unit","Genes in a bottle kit","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("926","00-001-95-20","Glassware Washer","Equipment","","","1","unit","Model: BK-LW120","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("927","00-001-190-2","Homogenizer","Equipment","","","1","unit","Benchtop, Brand/Model: Benckmark, D1030-E, Beadbug 3 Microtube Homogenizer","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("928","00-001-192-2","Homogenizer","Equipment","","","1","unit","Brand/Model JOANLAB OS-30PRO overhead stirrer, brushless motor, 60W","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("929","00-001-157 /","Hooke\'s Law","Semi Expendable","","","1","unit","ME-9827, #1, #2, #3, #4 & #5","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("930","00-001-162-2","HPLC Flexar System","Equipment","","","1","set","Brand: Perkinelmer, origin USA, 3KVA AVR 220 VAC, 50/60 Hz, HP Printer, intel core i5 Destop PC CPU, !9 LED Monitor,","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("931","00-001-53-20","Human Skeleton","Semi Expendable","","","1","unit","(Articulated), Life Size, US Made","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("932","00-001-108 /","Human Torso","Semi Expendable","","","1","set","Male","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("933","00-001-012-2","Human Torso","Semi Expendable","","","1","piece","unisex","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("934","00-001-008-2","Incubator Binder","Semi Expendable","","","1","unit","Binder, Model B-28, Germany","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("935","00-001-167-2","Introductory Michelson Interferometer","Equipment","","","1","unit","Includes: Michelson Interferometer, gas cell, collimating lens (18.4mm focal length), lens holder, storage case","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("936","00-001-125 /","Laboratory Refrigerator","Equipment","","","1","unit","36 cuft, 1 Glass Door Display","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("937","00-001-176-2","Laboratory Refrigerator","Equipment","","","1","unit","Double Door","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("938","00-001-142 /","Laboratory Refrigerator","Semi Expendable","","","1","unit","large freezer compartment","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("939","temp-0096","Laboratory Side Tables","Equipment","","","1","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("940","00-001-081 /","LRC Circuit Experiment Kit","Semi Expendable","","","1","set","voltage sensor, resistor capacitor conductor network, banana plug and sets.","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("941","00-001-42-20","Magnetic stirrer multi-point","Semi Expendable","","","1","unit","SBS A13 (N 1131-0000279)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("942","00-001-84 / ","Magnetic Stirrer with Hot Plate","Semi Expendable","","","1","unit","Brand: IKA, model C-Mag HS 7 Origin USA","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("943","00-001-009-2","Micropipettor10-100 UL-ASC?","Semi Expendable","","","1","piece","ASC","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("944","00-001-123-2","Micropipettor","Semi Expendable","","","1","set","fixed volume pipette, 4 micropipettor (5, 10, 25, 100 ul)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("945","00-001-124-2","Micropipettor (Pipette Set)","Semi Expendable","","","1","set","adjustable volume pipettes(0.1-2.5 ul, 2-20 ul, 5-50 ul, 10-100 ul, 20-200 ul and 100-1000 ul)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("946","00-001-146-2","Microscope","Semi Expendable","","","1","unit","Monocular Biological Microscope LB 110-LB (USA)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("947","00-001-67 / ","Microscope","Semi Expendable","","","1","unit","Binocular microscope, Brand: Optech K7161-10x eyepiece (18mm field); 6V-20W adjustable lighting system, #4 & #5","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("948","00-001-026-2","Microscope","Equipment","","","1","unit","Model DMB1-223 ASC(Motic)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("949","00-001-86 / ","Microscope","Equipment","","","1","unit","KERN SOHN","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("950","TS-07-12-154","Microscope","Semi Expendable","","","1","unit","Novex 60.200(P-20)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("951","10605140-1/3","Microscope","Semi Expendable","","","1","unit","Supertek","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("952","00-001-57-20","Microtome","Equipment","","","1","unit","rotary,China #20243 similar to AO Spencer Model 820","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("953","00-001-189-2","Micro volume Spectrophotometer","Equipment","","","1","unit","Nabi UV/Vis Nano Spectrophotometer, Serial No: NB1-E-210810","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("954","00-001-96-20","Moisture Analyzer","Equipment","","","1","unit","Model: FD 660-Kett w/ accessories","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("955","00-001-41-20","Multi-Parameter Instrument","Equipment","","","1","unit","GB # 1072449 W/SenTIx, Cellox 325 (BN) and accesories","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("956","temp-0097","Multi-Vortex Mixer","Semi Expendable","","","1","unit","MSV 3500, BIOSAN","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("957","00-001-40-20","OS 8515C Basic Optics System","Equipment","","","1","set","includes 1.2 m optical Bench; Basic Optics Light Source, 50mm diameter glass lenses in Holder, (+100mm, -150mm,A29 +200mm, +250mm), Adjustable Lens Holder; Ray Optics Kit, Concave/Convex mirror with Screen; Storage Box, Ray Table with D-shaped lens; Viewi","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("958","00-001-94 / ","Optic System","Equipment","","","1","lot","PASCO, OS-8515C","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("959","00-001-169/1","Oven","Equipment","","","1","unit","Electric single oven range; Model/Brand: Electrolux EKM9689X; easy cleaning feature, large capacity;true convention; intuitoch controls","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("960","temp-0098","Physics Table","Semi Expendable","","","1","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("961","00-001-114 /","PH Meter","Semi Expendable","","","1","unit","BIOBASE: Bench PH Meter","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("962","00-001-100.1","Portable Eyewash and Shower","Equipment","","","1","unit","Head: 10SS shower and 10 SS bowl, ABS eyewash","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("963","00-001-43-20","Portable microscope/ Digital Camera for plants","Semi Expendable","","","1","unit","GB # 9000507 with 1 Video Software (GB # 9000508) @ 4,464.00","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("964","00-001-89-20","PASCO Green Diode Laser","Semi Expendable","","","1","unit","OS 8458","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("965","00-001-72-20","PASCO-Introductory Dynamics 1.2 m with carts","Equipment","","","1","set","with carts","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("966","00-001-149/1","PGLO Bacterial Transformation","Semi Expendable","","","1","unit","kit, gene #1 & #2","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("967","00-001-166-2","Power Supply","Semi Expendable","","","1","unit","SE 8828","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("968","00-001-51-20","PS 2400, SPARK Vue Site License","Semi Expendable","","","1","pack","software","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("969","00-001-33 / ","PS-2008 SPARK Science Learning System","Equipment","","","1","set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("970","00-001-48 / ","PS-2008 SPARK Science Learning System","Equipment","","","1","set","PS 2008 Spark Science Learning System, #1, #2 & #3","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("971","00-001-69 / ","PASCO Super Pulley Force Table","Semi Expendable","","","1","unit","PASCO ME-9447","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("972","00-001-196-2","Projectile Launcher","Equipment","","","1","unit","Wireless Smart Gate System-PASCO ME-6796","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("973","00-001-38-20","PS-2174 Pasport Weather/Anenometer Sensor","Semi Expendable","","","1","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("974","00-001-39-20","PS-2175 Pasport GPS Position Sensor","Semi Expendable","","","1","unit","PASCO Scientific","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("975","00-001-111-2","PS 2196 Pasport Optical Dissolved Oxygen Sensor","Semi Expendable","","","1","unit","PASCO Scientific","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("976","00-001-35-20","PS-2920 SPARK Biology Starter Kit","Equipment","","","1","set","includes PS-2126 Oxygen Gas Sensor, PS-2110 Pasport Carbon Dioxide Gas Sensor, PS-2102 Pasport pH Sensor, PS-2113A Pasport Barometer/Low Pressure Sensor","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("977","00-001-36-20","PS-2921 SPARK Chemistry Sensor","Equipment","","","1","set","includes PS-2170 Pasport Chemistry Sensor, PS-2121 Pasport Colorimeter, PS-2117 Pasport Drop Counter","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("978","00-001-37-20","PS-2922 SPARK Earth Science Starter Kit","Equipment","","","1","set","includes PS-2169 Pasport Water Quality Sensor","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("979","temp-0099","Quick Fit Distillation Set-up","Semi Expendable","","","1","unit","contains a selection of basic small scale glassware for extraction","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("980","00-001-185-2","Recirculating Chiller","Equipment","","","1","unit","Brand/Model: DLAB CCP5-20","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("981","00-001-141-2","Recirculating Cooler/Chiller","Equipment","","","1","unit","Flow: 20L/min, Lift: 4-6 mtrs, Power Supply: 220V, Dimention (LxWxH): 500 x 370 x 540mm, Origin: China, Serial No.: 1705203","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("982","00-001-022 /","Riffle Tank Apparatus","Equipment","","","1","unit","WA-9899","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("983","00-001-030-2","Rock Detective Kit","Semi Expendable","","","1","set","(Bucket Kit) #GE060","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("984","00-001-025-2","Rotary Evaporator","Equipment","","","1","unit","Rotary evaporator, rotation speed 0-15 rpm,adjustable height & angle of motor, evaporating capacity up to 2 liters","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("985","00-001-77-20","Rotary Evaporator with vacuum pump","Equipment","","","1","set","RE100-PRO rotary evaporator, vertical coiled condenser, Brand: Scilogex, U.S.A.","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("986","00-001-139-2","Rotary Evaporator","Equipment","","","1","set","Model: RE100-Pro with set of glassware","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("987","00-001-171-2","Scanning Electronic Microscope (SEM)","Equipment","","","1","set","Model: Hitachi TM 4000 PLUS II, Serial No. 207090-10","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("988","00-001-78 / ","Shaker with Universal Platform","Equipment","","","1","set","Brand: Thermo Scientific, Coleparmer, U.S.A.","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("989","00-001-80-20","Sohxlet Extraction System","Equipment","","","1","unit","KM series, multi position heating mantle w/ magnetic stirrer, Israel","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("990","00-001-81-20","Sonicator","Equipment","","","1","set","Model: DC-150H, Israel","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("991","00-001-110-2","Syringe Pump","Equipment","","","1","set","Biobase SP Lab01","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("992","00-001-113-2","Thermal Cycler","Equipment","","","1","unit","PCR Machine, Model: K960","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("993","00-001-91 / ","Two Basket and Four Basket Glassware Carts","Equipment","","","1","unit","stainless steel, 5 layered cart","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("994","00-001-76-20","Ultra Series Pure Water System","Equipment","","","1","unit","Smart S15","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("995","00-001-133-2","UV-Vis Spectrophotometer","Equipment","","","1","unit","Brand: SHIMADZU UV-Vis spectropothometer","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("996","00-001-187-2","Vacuum Dessicator Cabinet Style","Equipment","","","1","unit","Brand/Model: Nanostar AP 48EX","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("997","00-001-100-2","Vacuum Filtration Set up/System","Semi Expendable","","","1","unit","support: sintered glass, funnel base: borosilicate glass, silicone rubber stopper, clamp: anodized aluminum","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("998","00-001-121 /","Van de Graff Generator","Semi Expendable","","","1","unit","American Educational 7-511","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("999","00-001-186-2","Van de Graff Generator","Semi Expendable","","","1","unit","110-220 V","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1000","10405130","Van de Graff Generator","Semi Expendable","","","1","unit","220 V, 330L x 200 W x 600H, Storage Ball, 200 mm, discharge ball: 600 mm","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1001","00-001-032-2","Van de Graff Generator","Semi Expendable","","","1","unit","Electrical","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1002","00-001-028-2","Vertical Laminar Flow","Equipment","","","1","unit","48 standard size","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1003","00-001-019-2","Video Lab","Semi Expendable","","","1","set","Mineral","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1004","00-001-021-2","Video Lab","Semi Expendable","","","1","set","Our Stars & Outer Space","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1005","00-001-020-2","Video Lab","Semi Expendable","","","1","set","Plate Tectonic","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1006","00-001-100 /","Viscometer","Equipment","","","1","unit","Model: BDV-4N Biobase","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1007","00-001-82-20","Visible Spectrophotometer","Equipment","","","1","set","Brand: Coleparmer, U.S.A.","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1008","00-001-118-2","Vortex Mixer","Semi Expendable","","","1","unit","CLEAVER, with PH, Mv temperature with electrode and swing with calibration solution of 4, 7 and 10.","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1009","00-001-103 /","Water Bath Rectangular","Semi Expendable","","","1","unit","double walled construction upper body, glass wool insulation, volume 15 L max BRAND: Digisystem WB1000","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1010","00-001-193-2","Water Bath with Shaker","Equipment","","","1","unit","Biobase Thermostatic Shaking Water Bath","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1011","00-001-031-2","Weather Investigation Kit","Semi Expendable","","","1","box","(Classroom, #7 49103)","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1012","00-001-172/1","Wireless Conductivity Sensor","Semi Expendable","","","1","unit","PASO-PS 3210","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1013","00-001-105-2","Wireless CO2 Sensor","Semi Expendable","","","1","unit","PASCO PS-3208","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1014","00-001-154 /","Wireless Motion Sensor","Semi Expendable","","","1","unit","PS-3219, wireless motion sensor, #1, #2 & #3","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1015","00-001-168-2","Wireless Dissolved Oxygen Sensor","Equipment","","","1","unit","Brand: PASCO PS-3224","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1016","00-001-164-2","Wireless Oxygen Gas Sensor","Semi Expendable","","","1","unit","Brand: PASCO PS-3217","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1017","temp-0100","Wireless PH Sensor","Semi Expendable","","","1","unit","Brand: PASCO PS-3204","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1018","00-001-112-2","Wireless Weather Anenometer","Semi Expendable","","","1","unit","PASCO with GPS","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1019","","Adjustable End stop","Semi Expendable","","","1","piece","part of dynamic cart assembly","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1020","TS-08-07-149","Ammeter","Semi Expendable","","","13","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1021","","Analog Triple Output DC Power Supply","Semi Expendable","","","3","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1022","TS-03-07-002","Angle Indicator","Semi Expendable","","","1","piece","part of dynamic cart assembly","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1023","10405130","Automatic Voltage Regulator","Semi Expendable","","","3","unit","with time delay 3000 W","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1024","","","Removed","","","0","unit","","",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1025","TS-07-12-142","Ballistic pendulum","Semi Expendable","","","5","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1026","","battery holder","Semi Expendable","","","9","piece","for size D","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1027","","calorimetry kit/calorimeter","Semi Expendable","","","2","set","2 units, basic, PASCO","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1028","","Calorimeter Electric","Semi Expendable","","","8","unit","350 ml, supplied with resistors, thermometer and stirrer","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1029","TS-03-07-007","Cent-O Gram Balance/Quadruple Beam Balance?","Semi Expendable","","","5","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1030","TS-03-12-066","collission cart w/mass magnet","Semi Expendable","","","1","piece","part of Dynamic Cart Assembly","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1031","TS-07-10-132","DC Motor","Semi Expendable","","","2","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1032","TS-08-07-154","DC Power Source","Semi Expendable","","","3","unit","Nikko PS-03s","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1033","","DC Power Supply with Knob Adjustment","Semi Expendable","","","2","piece","Kolin KL-03D","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1034","","Dynamic Carts","Semi Expendable","","","2","set","set of 2","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1035","","Electric Plasma Ball","Semi Expendable","","","2","unit","5 globe diameter, lamp height 12, 110V-220V plastic base","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1036","","Electroscope *","Semi Expendable","","","5","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1037","TS-07-10-130","Electroscope *","Semi Expendable","","","1","unit","flask type, local","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1038","TS-03-12-042","Fixed End Stop","Semi Expendable","","","1","piece","part of Dynamic Cart Assembly","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1039","TS-07-12-136","Force Table","Semi Expendable","","","4","unit","local","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1040","TS-03-12-043","Friction Block- IDS","Semi Expendable","","","1","piece","part of Dynamic Cart Assembly","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1041","TS-08-07-152","Galvanometer","Semi Expendable","","","14","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1042","","Hammer","Semi Expendable","","","1","piece","rubber, Stanley brand","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1043","","Hand Saw (for wood)","Semi Expendable","","","1","piece","for wood","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1044","","Hand Saw (for metal)","Semi Expendable","","","2","piece","for metal","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1045","TS-03-12-044","Harmonic Springs IDS","Semi Expendable","","","3","piece","part of Dynamic Cart Assembly","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1046","TS-07-12-139","Heat Transfer Apparatus","Semi Expendable","","","4","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1047","","Hooke\'s Law Apparatus","Semi Expendable","","","5","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1048","TS-08-07-155","Knife Switch","Semi Expendable","","","10","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1049","TS-08-07-170","Longitudinal Wave Model","Semi Expendable","","","1","unit","CAT. CO. 16V0412","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1050","TS-04-08-115","Magnet (bar)?","Semi Expendable","","","5","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1051","TS-07-10-134","magnet (U-shaped)","Semi Expendable","","","2","piece","china","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1052","TS-04-01-076","Magnetic Compass? *","Semi Expendable","","","40","piece","china","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1053","","Magnetic field apparatus","Semi Expendable","","","25","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1054","TS-08-07-161","Magnetic levitation kit","Semi Expendable","","","1","piece","SE 7339","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1055","","ME 8968 Spherical Mass set","Semi Expendable","","","1","set","PASCO Scientific","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1056","","ME 9864 Steel Balls (4 packs)","Semi Expendable","","","1","set","PASCO Scientific","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1057","","ME 9872 small steel balls (10 packs)","Semi Expendable","","","1","set","PASCO Scientific","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1058","TS-03-12-053","Pivot clamps IDS","Semi Expendable","","","1","piece","part of Dynamic Cart Assembly","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1059","TS-03-12-067","Plunger Cart w/ mass magnet","Semi Expendable","","","1","piece","part of Dynamic Cart Assembly","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1060","TS-07-12-140","Projectile Launcher Apparatus","Semi Expendable","","","2","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1061","10405130","PASCO Demonstration Wave Spring","Semi Expendable","","","2","unit","WA 7334","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1062","","PASCO- Discover Centripetal Force","Semi Expendable","","","2","set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1063","10405130","PASCO Double Length Slinky","Semi Expendable","","","6","unit","SE 8760","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1064","10405130","PASCO Longitudinal Wave Spring","Semi Expendable","","","6","unit","WA 9401","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1065","","PASCO Mass and Hanger Set","Semi Expendable","","","3","set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1066","","Pendulum Waves","Semi Expendable","","","1","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1067","TS-03-12-023","PS-2100 Pasport USB Link","Semi Expendable","","","2","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1068","TS-03-12-024","PS-2102 Pasport PH Sensor","Semi Expendable","","","2","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1069","","PS-2104 Pasport Force Sensor","Semi Expendable","","","2","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1070","TS-03-12-025","PS-2103 Pasport Motion Sensor","Semi Expendable","","","2","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1071","TS-03-12-031","PS-2113 Pasport Barometer Sensor","Semi Expendable","","","2","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1072","TS-03-12-064","PS-2115 Pasport Voltage/Current Sensor","Semi Expendable","","","1","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1073","TS-03-12-026","PS-2125 Pasport Temperature Sensor","Semi Expendable","","","2","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1074","","Pulley force table","Semi Expendable","","","3","set","accessory","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1075","","Resonance Apparatus","Semi Expendable","","","2","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1076","","SE-8759 Hooked Mass Set","Semi Expendable","","","1","set","PASCO Scientific","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1077","","Solderless Breadboard","Semi Expendable","","","8","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1078","piece","Stainless Steel Weight","Semi Expendable","","","1","","Model TWC - 0.2S/F2, 200 g w/ calibration cert","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1079","TS-08-07-157","Table Top Acceleration Timer Kit","Semi Expendable","","","5","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1080","","Three Hole Can Experiment","Semi Expendable","","","5","","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1081","","Voltmeter","Semi Expendable","","","9","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1082","","Aspirator","Semi Expendable","","","23","unit","Blue","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1083","TS-04-04-092","Binocular Telescope ?","Semi Expendable","","","3","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1084","TS-07-06-125","Centrifuge","Semi Expendable","","","2","unit","model 800, China","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1085","TS-03-12-033","Clamps double burette","Semi Expendable","","","4","piece","double burette","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1086","TS-03-12-034","Clamps utility","Semi Expendable","","","15","piece","utility","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1087","TS-07-12-137","Clamps assorted,local, China","Semi Expendable","","","4","set","assorted,local, China","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1088","","Deflagrating Spoon","Semi Expendable","","","10","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1089","","Dissectible Eye Model","Semi Expendable","","","1","unit","ITN 11-40 w/ sticker,Magnified 5x, 7 parts","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1090","","Dissecting Kit - Gold Cross,16 pcs","Semi Expendable","","","35","set","Gold Cross,16 pcs","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1091","","Dissecting Kit - Dolphin, junior set, 7 piece","Semi Expendable","","","14","set","Dolphin, junior set, 7 piece","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1092","","Dissecting Kit - Secheron","Semi Expendable","","","15","set","Secheron","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1093","","Dissecting pan - with feld pad & wax","Semi Expendable","","","10","piece","with feld pad & wax","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1094","","Dissecting pan - with feld pad & wax","Semi Expendable","","","6","unit","with feld pad & wax","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1095","TS-07-12-138","Flask/Beaker Tong","Semi Expendable","","","2","piece","belart","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1096","","Flask Tong","Semi Expendable","","","10","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1097","PAG-IYA 09-0","Gallileoscope with tripod","Semi Expendable","","","4","set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1098","2022-09-148","Hard Plastic Tray","Semi Expendable","","","30","piece","clear/transparent, Size: L-16, W-12","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1099","10405130","Hot Plate","Semi Expendable","","","2","set","Electric Stove w/ Pilot Light indicator, 8 plate heavy duty","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1100","10405130","Hot Plate with magnetic Stirrer","Semi Expendable","","","5","unit","Biobase BS-2H, with stirring bars package,speed: 100-2000 rpm","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1101","TS-04-03-081","Human Embryo?","Semi Expendable","","","8","piece","set of 8 parts","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1102","","Human Torso, male","Semi Expendable","","","1","set","Dissectible parts,  18 parts","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1103","TS-03-12-071","Hydrometer","Semi Expendable","","","6","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1104","TS-08-07-179","Introduction to Rock Study Kit","Semi Expendable","","","4","set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1105","TS-03-12-074","Iron Ring","Semi Expendable","","","10","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1106","","Iron stand with ring","Semi Expendable","","","10","set","Stand- 24, Ring- 4","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1107","","Laboratory Gown","Semi Expendable","","","180","piece","Cotton, Medium","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1108","","Laboratory Shoes size 6","Semi Expendable","","","30","pair","size 6","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1109","","Laboratory Shoes size 7","Semi Expendable","","","60","pair","size 7","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1110","","Laboratory Shoes size 8","Semi Expendable","","","60","pair","size 8","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1111","","Laboratory Shoes size 9","Semi Expendable","","","60","pair","size 9","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1112","","Laboratory Shoes size 10","Semi Expendable","","","60","pair","size 10","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1113","TS-1-04-04-0","Laboratory Thermometer","Semi Expendable","","","31","piece","10 to 200 ?C","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1114","TS-07-12-135","Lenses","Semi Expendable","","","2","set","set of 6","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1115","","Lifesize Human Skeleta Model","Semi Expendable","","","1","piece","China","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1116","TS-03-07-013","magnifying lens, glass?","Semi Expendable","","","5","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1117","","Magnifying Glass","Semi Expendable","","","20","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1118","TS-03-12-047","Male pelvis model","Semi Expendable","","","1","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1119","TS-08-12-193","Manometer (u-shaped) w/ stand","Semi Expendable","","","4","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1120","2022-10-186.","Manual Cell Tally Counter","Semi Expendable","","","2","piece","Local","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1121","","Medicine and instrument cabinet","Semi Expendable","","","1","piece","Single Door glass side, 3 glass shelves one center drawer and lower metal shelf cabinet, mounted on four swivel caster rubber wheels","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1122","TS-03-12-048","Meter stick","Semi Expendable","","","7","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1123","TS-04-04-090","Micrometer eyepiece?","Semi Expendable","","","5","piece","CAT No. MA-285","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1124","","Micrometer Caliper","Semi Expendable","","","4","piece","digital, Lutron Model: DC-516","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1125","TS-1-04-04-0","Microspatula","Semi Expendable","","","5","piece","stainless blade, wooden handle","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1126","","Microtiter plates","Semi Expendable","","","1","pack","96 wells, polystyrene, round well shape plates","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1127","TS-08-07-177","Mineral identification kit w/sort guide","Semi Expendable","","","4","set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1128","TS-07-10-126","Molecular model kit - china","Semi Expendable","","","5","unit","china","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1129","10405130","Molecular model kit - Darling Models","Semi Expendable","","","12","set","Darling Models","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1130","TS-07-12-128","mortar and pestle - marble","Semi Expendable","","","2","piece","marble","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1131","","mortar and pestle - porcelain","Semi Expendable","","","20","piece","porcelain","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1132","","mortar and pestle - glass","Semi Expendable","","","6","piece","glass","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1133","","Oersted Apparatus","Semi Expendable","","","5","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1134","10405130","Pipettor","Semi Expendable","","","1","piece","Biopette BP1000","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1135","","Precision scale - 1141413","Semi Expendable","","","3","unit","Lutron Model: GM 600G, SN 1141413/1141412","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1136","","Precision scale - 1141412","Semi Expendable","","","0","set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1137","2022-10-0179","Prepared Bacteria and Fungi Microscope Slides","Semi Expendable","","","1","set","1 set","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1138","","","Removed","","","0","set","","",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1139","TS-06-09-122","Prepared Microscope Slides, 25 pcs - Botany","","","","0","","","",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1140","","","","","","0","","","",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1141","TS-06-09-120","Prepared Microscope Slides, 25 pcs - Histology","Semi Expendable","","","2","set","Histology","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1142","","Prepared Microscope Slides, 25 pcs - Zoology","Semi Expendable","","","1","set","Zoology","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1143","TS-06-09-121","Prepared Microscope Slides, 25 pcs - Bacteriology","Semi Expendable","","","1","set","Bacteriology","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1144","2022-10-0179","Prepared Parasitology Microscope Slides","Semi Expendable","","","1","set","30 pieces/1 set","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1145","2022-10-0179","Prepared Plant Anatomy Microscope Slides","Semi Expendable","","","1","set","includes basic tissues and specialized structures","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1146","2022-10-197","Polypropylene Pipette Glass Stand","Semi Expendable","","","6","piece","Tarson#T161040","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1147","","Polyprolene Tote Tray, plastic","Semi Expendable","","","20","piece","white w/handle","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1148","","Poxygrid petri dish carrying rack","Semi Expendable","","","5","","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1149","10405130","Refractometer","Semi Expendable","","","5","set","portable handheld tr-scale: salinity: brix: specific gravity with calibration screw, battery operated","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1150","TS-03-12-027","Rock and Mineral Samples","Semi Expendable","","","1","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1151","TS-03-07-015","Ruler?","Semi Expendable","","","10","piece","plastic","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1152","","Safety Laboratory Eyewash and Shower - Stainless steel","Equipment","","","4","unit","Stainless steel","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1153","","Safety Laboratory Eyewash and Shower - Stainless steel","Semi Expendable","","","2","unit","Stainless steel","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1154","","Scalpel Holder # 3","Semi Expendable","","","50","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1155","","Scalpel Holder # 4","Semi Expendable","","","50","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1156","","Set: Forceps","Semi Expendable","","","50","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1157","","Set: Inoculating Needle","Semi Expendable","","","50","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1158","","Set: Inoculating Loop","Semi Expendable","","","50","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1159","TS-08-12-196","Spatula - ceramic/porcelain, about 5, china","Semi Expendable","","","6","piece","ceramic/porcelain, about 5, china","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1160","","Spatula - stainless coated","Semi Expendable","","","6","piece","stainless coated","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1161","","Spirometer","Semi Expendable","","","10","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1162","2022-09-147","Soil Test Kit","Semi Expendable","","","1","kit","20 tests","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1163","","Spot Plate","Semi Expendable","","","10","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1164","TS-03-07-016","Spring Balance?","Semi Expendable","","","5","piece","plastic","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1165","TS-04-04-091","Stage Micrometer/OJB Micrometer?","Semi Expendable","","","1","piece","MA 285","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1166","","Test Tube rack -  (blue plastic) -old","Semi Expendable","","","2","piece","(blue plastic) -old","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1167","","Test Tube rack -  wood, large, 25 mm, 12 holes","Semi Expendable","","","10","piece","wood, large, 25 mm, 12 holes","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1168","","Test Tube rack -  12 holes","Semi Expendable","","","25","piece","12 holes","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1169","TS-04-08-109","Tiril Burner?","Semi Expendable","","","4","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1170","TS-03-07-019","Triple Beam Balance?","Semi Expendable","","","5","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1171","TS-03-07-020","Tripod?","Semi Expendable","","","6","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1172","","Tripod?","Semi Expendable","","","4","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1173","TS-08-12-194","Universal Clamp Holder","Semi Expendable","","","17","piece","Heavy Duty, Steel,H8300","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1174","TS-03-07-021","Vernier Caliper? - manual, Japan","Semi Expendable","","","5","piece","manual, japan","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1175","","Vernier Caliper? - digital, Lutron Model: DC 515","Semi Expendable","","","5","piece","digital, Lutron Model: DC 515","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1176","2022-10-0176","Wall  Mounted Laboratory Drying Rack","Semi Expendable","","","20","piece","Material: Polypropylene","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1177","","Wash Bottle","Semi Expendable","","","8","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1178","","Wireless Temperature Sensor","Semi Expendable","","","5","unit","PASCO","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1179","","Alcohol Lamp Wick","Consumable","","","4","","10 pc/pack","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1180","","Autoclavable Plastic Bags","Consumable","","","208","piece","red, 12 x 16 inches","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1181","","Conical Tubes","Consumable","","","117","piece","50 ml,20s","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1182","","Conical tubes","Consumable","","","770","piece","15 ml, 20s","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1183","","Cover Slip","Consumable","","","7","box","Local, 100\'s","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1184","","Dropper","Consumable","","","2443","piece","plastic,3 ml, 500\'s","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1185","","Dropping Bottles","Consumable","","","30","piece","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1186","","Eppendorf tube","Consumable","","","2","pack","250 pcs/pack","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1187","","Face Mask","Consumable","","","12","box","blue,white","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1188","","Filter Paper","Consumable","","","3","box","wattman","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1189","","Gloves","Consumable","","","4","box","latex","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1190","","Kimwipes","Consumable","","","3","box","kimtech","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1191","","Hazardous Waste bag","Consumable","","","101","piece","yellow, large","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1192","","Hairnet","Consumable","","","6","pack","blue, 100s","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1193","","Micropippette tips","Consumable","","","10","box","White, 200 ul","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1194","","Micropippette tips","Consumable","","","10","box","blue","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1195","","Micropippette tips","Consumable","","","5","box","yellow","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1196","","Microscope Slides","Consumable","","","2","box","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1197","","Parafilm","Consumable","","","3","box","4 x 125\'","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1198","","Soil PH Strips","Consumable","","","5","kit","100 tests","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1199","","Wooden cotton applicator","Consumable","","","2","box","medpro","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1200","","Blender","Food Lab","","","3","unit","heavy duty,plastic chamber","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1201","","Electric stove","Food Lab","","","5","unit","Asahi,metallic","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1202","","Electric stove","Food Lab","","","4","unit","Standard","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1203","","Electronic weighing Scale","Food Lab","","","4","unit","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1204","","Food Dehydrator","Food Lab","","","2","unit","Electric, 200-220 V, stainless, touch LED display, overheating protection, automatic shutdown, adjustable temperature and timer removable stainless steel grill","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1205","","Fryer","Food Lab","","","0","1","electric, single","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1206","00-001-96-20","Moisture Analyzer","Food Lab","","","1","unit","Model: FD 660-Kett w/ accessories","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1207","","Oven","Food Lab","","","2","2","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1208","","Pressure Cooker","Food Lab","","","2","2","Turbo Cooker","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1209","","Refrigerator","Food Lab","","","1","1","Large,gray","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1210","","Refrigerator","Food Lab","","","1","1","small ,white","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1211","","Rice cooker","Food Lab","","","2","2","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1212","","Water Dispenser","Food Lab","","","2","2","Gray","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1213","","Water Meter Tester","Food Lab","","","3","3","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1214","","Plate draining tray","Food Lab","","","1","1","white","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1215","","Mittens","Food Lab","","","4","4","gray","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1216","","Straw,knife holder","Food Lab","","","2","2","stailess","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1217","","Knife","Food Lab","","","1","1 set","","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1218","","Chopping board","Food Lab","","","5","5","plastic,wood","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1219","","spatula","Food Lab","","","2","2","Plastic","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1220","","Strainer","Food Lab","","","1","set","small,stainless","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1221","","Spoon","Food Lab","","","6","6","stainless","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1222","","Fork","Food Lab","","","6","6","stainless","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1223","","Cake server/Slicer","Food Lab","","","1","1","stainless","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1224","","Laddle","Food Lab","","","2","2","stainless,brown holder","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1225","","Laddle","Food Lab","","","4","4","wood","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1226","","Sauce Pan with cover","Food Lab","","","2","2","stainless","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1227","","Colander","Food Lab","","","1","1","stainless","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1228","","Baking molder","Food Lab","","","3","3","round adjustable","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1229","","Baking molder","Food Lab","","","6","6","rectangular,non adjutable","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1230","","Wok Pan","Food Lab","","","2","2","non stick ,large","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1231","","muffin fans","Food Lab","","","4","4","black","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1232","","Tables","Food Lab","","","9","9","wood,glass top","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1233","","teachers table","Food Lab","","","1","1","wood","Available",NULL,"0");
INSERT INTO `scilab_inventory` VALUES("1234","","Stainless Counter","Food Lab","","","2","2","","Available",NULL,"0");



DROP TABLE IF EXISTS `scilab_material_requests`;

CREATE TABLE `scilab_material_requests` (
  `formID` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `description` varchar(50) NOT NULL,
  `issuedCondition` varchar(25) NOT NULL,
  `returnedCondition` varchar(25) NOT NULL,
  `returnedItemInspector` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `formID` (`formID`),
  CONSTRAINT `scilab_material_requests_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `scilab_material_requests` VALUES("33","10","550 Universal Interface","1","pieces","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("35","11","Digital Spectronic 20D +Spectrophotometer","1","unit","GB # 9000795","","","");
INSERT INTO `scilab_material_requests` VALUES("35","12","Wooden cotton applicator","1","box","medpro","","","");
INSERT INTO `scilab_material_requests` VALUES("36","13","DPS Desktop Video Camera","1","unit","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("36","14","Autoclave (Vertical)","1","pieces","Model: BKQ-Z50I (BIOBASE)","","","");
INSERT INTO `scilab_material_requests` VALUES("36","15","Parafilm","1","box","4 x 125\'","","","");
INSERT INTO `scilab_material_requests` VALUES("37","16","Analytical Balance","1","pieces","Shimadzu Unibloc, Model AP324Y","","","");
INSERT INTO `scilab_material_requests` VALUES("37","17","Hazardous Waste bag","3","piece","yellow, large","","","");
INSERT INTO `scilab_material_requests` VALUES("38","18","Absorbance Microplate Reader","1","pieces","Model: EL-10A, Elisa Reader","","","");
INSERT INTO `scilab_material_requests` VALUES("38","19","Wooden cotton applicator","1","box","medpro","","","");
INSERT INTO `scilab_material_requests` VALUES("39","20","Colony Counter, Automatic","1","unit","power: 100-240 V, Countless II FL SN:2185A17031142","","","");
INSERT INTO `scilab_material_requests` VALUES("39","21","Parafilm","1","box","4 x 125\'","","","");
INSERT INTO `scilab_material_requests` VALUES("43","22","Analytical Balance","1","pieces","Shimadzu Unibloc, Model AP324Y","","","");
INSERT INTO `scilab_material_requests` VALUES("44","23","Voltmeter","1","unit","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("44","24","Electron Dispersive X-ray (EDX)","1","unit","EDX System","","","");
INSERT INTO `scilab_material_requests` VALUES("44","25","Alcohol Lamp Wick","1","","10 pc/pack","","","");
INSERT INTO `scilab_material_requests` VALUES("44","26","Acetone","1","mL","0.1 M","","","");
INSERT INTO `scilab_material_requests` VALUES("44","27","Beaker","1","piece","pyrex, 250 ml","","","");
INSERT INTO `scilab_material_requests` VALUES("44","28","muffin fans","1","4","black","","","");
INSERT INTO `scilab_material_requests` VALUES("45","29","Drying Oven","1","unit","Model: JSOF-150 JSR","","","");
INSERT INTO `scilab_material_requests` VALUES("46","30","550 Universal Interface","1","pieces","(for PASCO Sensor)","","","");
INSERT INTO `scilab_material_requests` VALUES("47","31","Analytical Balance","1","pieces","Shimadzu Unibloc, Model AP324Y","","","");
INSERT INTO `scilab_material_requests` VALUES("47","32","Centrifuge, Benchtop","1","unit","DM0412 with adapter plugs","","","");
INSERT INTO `scilab_material_requests` VALUES("47","33","Glassware Washer","1","unit","Model: BK-LW120","","","");
INSERT INTO `scilab_material_requests` VALUES("47","34","Conical tubes","3","piece","15 ml, 20s","","","");
INSERT INTO `scilab_material_requests` VALUES("47","35","Aluminum Chloride","1","g","Solid form only","","","");
INSERT INTO `scilab_material_requests` VALUES("47","36","Beaker","10","piece","pyrex, 100 ml","","","");
INSERT INTO `scilab_material_requests` VALUES("48","37","Fourier Transform Infrared Spectrometer (FTIR)","1","unit","with software, Brand: PERKINELMER","","","");
INSERT INTO `scilab_material_requests` VALUES("49","38","DPS Desktop Video Camera","1","unit","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("49","39","Drying Oven","1","unit","Model: BOV-V70F (BIOBASE)","","","");
INSERT INTO `scilab_material_requests` VALUES("49","40","Parafilm","2","box","4 x 125\'","","","");
INSERT INTO `scilab_material_requests` VALUES("49","41","Micropippette tips","1","box","White, 200 ul","","","");
INSERT INTO `scilab_material_requests` VALUES("49","42","Boric Acid","1","g","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("49","43","Aluminum Potassium Sulfate","1","g","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("50","44","Wireless PH Sensor","1","unit","Brand: PASCO PS-3204","","","");
INSERT INTO `scilab_material_requests` VALUES("50","45","Drying Oven","1","unit","Model: BOV-V70F (BIOBASE)","","","");
INSERT INTO `scilab_material_requests` VALUES("51","46","Benchtop Circulating Chiller","1","pieces","DLSB 5/10","","","");
INSERT INTO `scilab_material_requests` VALUES("52","47","Force Table","4","unit","local","","","");
INSERT INTO `scilab_material_requests` VALUES("53","48","Analytical Balance","1","pieces","Shimadzu Unibloc, Model AP324Y","","","");
INSERT INTO `scilab_material_requests` VALUES("54","49","Gel Electrophoresis","1","unit","Cleaver Scientific","","","");
INSERT INTO `scilab_material_requests` VALUES("55","50","Electron Dispersive X-ray (EDX)","1","unit","EDX System","","","");
INSERT INTO `scilab_material_requests` VALUES("59","51","550 Universal Interface","1","pieces","(for PASCO Sensor)","","","");
INSERT INTO `scilab_material_requests` VALUES("61","52","Autoclavable Plastic Bags","6","piece","red, 12 x 16 inches","","","");
INSERT INTO `scilab_material_requests` VALUES("63","53","Homogenizer","1","unit","Benchtop, Brand/Model: Benckmark, D1030-E, Beadbug","","","");
INSERT INTO `scilab_material_requests` VALUES("63","54","Calorimeter Electric","1","unit","350 ml, supplied with resistors, thermometer and s","","","");
INSERT INTO `scilab_material_requests` VALUES("63","55","Hazardous Waste bag","5","piece","yellow, large","","","");
INSERT INTO `scilab_material_requests` VALUES("63","56","Cupric Sulfate","1","g","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("63","57","Graduated Cylinder","1","piece","250 ml","","","");
INSERT INTO `scilab_material_requests` VALUES("63","58","Colander","1","1","stainless","","","");
INSERT INTO `scilab_material_requests` VALUES("64","59","Autoclavable Plastic Bags","8","piece","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("68","60","Fourier Transform Infrared Spectrometer (FTIR)","1","unit","with software, Brand: PERKINELMER","","","");
INSERT INTO `scilab_material_requests` VALUES("71","61","550 Universal Interface","1","pieces","(for PASCO Sensor)","","","");
INSERT INTO `scilab_material_requests` VALUES("71","62","Conical tubes","1","piece","15 ml, 20s","","","");
INSERT INTO `scilab_material_requests` VALUES("71","63","Acetone","1","L","fsaj","","","");
INSERT INTO `scilab_material_requests` VALUES("71","64","Graduated Beaker","1","piece","30 ml Low form PMP (TPX®) Material, Crystal Clear,","","","");
INSERT INTO `scilab_material_requests` VALUES("84","65","Analytical Balance","1","pieces","Shimadzu Unibloc, Model AP324Y","","","");
INSERT INTO `scilab_material_requests` VALUES("84","66","Absorbance Microplate Reader","1","pieces","Model: EL-10A, Elisa Reader","","","");
INSERT INTO `scilab_material_requests` VALUES("85","67","Drying Oven","1","unit","Model: BOV-V70F (BIOBASE)","","","");
INSERT INTO `scilab_material_requests` VALUES("85","68","Bial Orcinol Reagent","10","ml","N/A","","","");
INSERT INTO `scilab_material_requests` VALUES("91","69","Drying Oven","1","unit","Model: BOV-V70F (BIOBASE)","","","");



DROP TABLE IF EXISTS `scilab_new_accounts`;

CREATE TABLE `scilab_new_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` varchar(50) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `institution` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `scilab_new_accounts` VALUES("1","08-2021-056","HANS CHRISTIAN","ASUNCION","LORENZO","hanschristian.lorenzo","781a7dc6c8c9811a8b00cfc84c414c14","Philippine Science High School - Ilocos Region Campus");
INSERT INTO `scilab_new_accounts` VALUES("2","08-2021-036","ROJAN JOEFEL","CARDONA","DUMLAO","rojanjuefel.dumlao","6eea9b7ef19179a06954edd0f6c05ceb","Philippine Science High School - Ilocos Region Campus");
INSERT INTO `scilab_new_accounts` VALUES("3","08-2021-106","NERSON DAVE","CALMA","TABION","nersondave.tabion","781a7dc6c8c9811a8b00cfc84c414c14","Philippine Science High School - Ilocos Region Campus");
INSERT INTO `scilab_new_accounts` VALUES("4","08-2021-093","LIANA GABRIELLE","TABUR","ROQUE","lianagabrielle.roque","6b69783a146f2e83e5e392a3c07f88f1","Philippine Science High School - Ilocos Region Campus");
INSERT INTO `scilab_new_accounts` VALUES("5","GUEST-1788490977","Gabriel James","","Valdez","valdezgabrieljames","963e1906fbb542844be4911d05a2f33b","test");
INSERT INTO `scilab_new_accounts` VALUES("6","08-2021-300","MARIA ELLYNE ANGELIQUE","GAOR","RAMOS","mariaellyneangelique.ramos","42f749ade7f9e195bf475f37a44cafcb","Philippine Science High School - Ilocos Region Campus");
INSERT INTO `scilab_new_accounts` VALUES("7","08-2021-102","JAMES ALLEN","REFUERZO","SELGA","jamesallen.selga","482c811da5d5b4bc6d497ffa98491e38","Philippine Science High School - Ilocos Region Campus");



DROP TABLE IF EXISTS `scilab_students_involved`;

CREATE TABLE `scilab_students_involved` (
  `formID` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `formID` (`formID`),
  CONSTRAINT `scilab_students_involved_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `scimath_answer_slides`;

CREATE TABLE `scimath_answer_slides` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_answer_event` (`event_id`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_categories`;

CREATE TABLE `scimath_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_event_name` (`event_id`,`name`),
  KEY `idx_categories_event_order` (`event_id`,`display_order`),
  CONSTRAINT `fk_categories_event` FOREIGN KEY (`event_id`) REFERENCES `scimath_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_competition_sessions`;

CREATE TABLE `scimath_competition_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `current_question_id` int(10) unsigned DEFAULT NULL,
  `display_state` enum('cover','question','time_up','ranking','final_results') NOT NULL DEFAULT 'cover',
  `current_round` smallint(5) unsigned DEFAULT NULL,
  `timer_duration_seconds` smallint(5) unsigned DEFAULT NULL,
  `timer_started_at` timestamp NULL DEFAULT NULL,
  `timer_paused_at` timestamp NULL DEFAULT NULL,
  `timer_remaining_seconds` smallint(6) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `round_started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_competition_sessions_event` (`event_id`),
  KEY `fk_competition_sessions_question` (`current_question_id`),
  CONSTRAINT `fk_competition_sessions_event` FOREIGN KEY (`event_id`) REFERENCES `scimath_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_competition_sessions_question` FOREIGN KEY (`current_question_id`) REFERENCES `scimath_questions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_contestants`;

CREATE TABLE `scimath_contestants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `team_code` varchar(20) DEFAULT NULL,
  `acronym` varchar(20) DEFAULT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `starting_score` int(11) NOT NULL DEFAULT 0,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contestants_event_code` (`event_id`,`team_code`),
  KEY `idx_contestants_event_order` (`event_id`,`display_order`),
  CONSTRAINT `fk_contestants_event` FOREIGN KEY (`event_id`) REFERENCES `scimath_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_event_settings`;

CREATE TABLE `scimath_event_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `default_points` int(10) unsigned NOT NULL DEFAULT 10,
  `default_time_seconds` smallint(5) unsigned NOT NULL DEFAULT 60,
  `ranking_order` enum('score_desc','score_asc') NOT NULL DEFAULT 'score_desc',
  `tie_break_mode` enum('none') NOT NULL DEFAULT 'none',
  `auto_show_ranking_after_score` tinyint(1) NOT NULL DEFAULT 0,
  `timer_warning_seconds` smallint(5) unsigned DEFAULT NULL,
  `display_theme` varchar(50) NOT NULL DEFAULT 'default',
  `extra_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_settings`)),
  `timer_audio_path` varchar(255) DEFAULT NULL,
  `banner_audio_path` varchar(255) DEFAULT NULL,
  `game_audio_path` varchar(255) DEFAULT NULL,
  `banner_audio_volume` tinyint(3) unsigned NOT NULL DEFAULT 100,
  `game_audio_volume` tinyint(3) unsigned NOT NULL DEFAULT 30,
  `times_up_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_settings_event_id` (`event_id`),
  CONSTRAINT `fk_event_settings_event` FOREIGN KEY (`event_id`) REFERENCES `scimath_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_events`;

CREATE TABLE `scimath_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `cover_image_path` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `status` enum('draft','ready','active','completed','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_events_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_promotional_slides`;

CREATE TABLE `scimath_promotional_slides` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('image','video','pdf','document') NOT NULL DEFAULT 'image',
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_promo_event_order` (`event_id`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_questions`;

CREATE TABLE `scimath_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `question_number` smallint(5) unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `answer_image_path` varchar(255) DEFAULT NULL,
  `points` int(10) unsigned DEFAULT NULL,
  `time_seconds` smallint(5) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_questions_event_number` (`event_id`,`question_number`),
  KEY `idx_questions_event_order` (`event_id`,`display_order`),
  KEY `idx_questions_category` (`category_id`),
  CONSTRAINT `fk_questions_category` FOREIGN KEY (`category_id`) REFERENCES `scimath_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_questions_event` FOREIGN KEY (`event_id`) REFERENCES `scimath_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_score_adjustments`;

CREATE TABLE `scimath_score_adjustments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `score_entry_id` int(10) unsigned NOT NULL,
  `previous_result` enum('correct','incorrect','no_answer','adjustment') DEFAULT NULL,
  `previous_points` int(11) DEFAULT NULL,
  `new_result` enum('correct','incorrect','no_answer','adjustment') NOT NULL,
  `new_points` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_score_adjustments_entry` (`score_entry_id`),
  CONSTRAINT `fk_score_adjustments_entry` FOREIGN KEY (`score_entry_id`) REFERENCES `scimath_score_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `scimath_score_entries`;

CREATE TABLE `scimath_score_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `contestant_id` int(10) unsigned NOT NULL,
  `result` enum('correct','incorrect','no_answer','adjustment') NOT NULL,
  `points_awarded` int(11) NOT NULL,
  `scored_by` varchar(100) DEFAULT NULL,
  `scored_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_score_entries_question_contestant` (`question_id`,`contestant_id`),
  KEY `idx_score_entries_event` (`event_id`),
  KEY `idx_score_entries_contestant` (`contestant_id`),
  CONSTRAINT `fk_score_entries_contestant` FOREIGN KEY (`contestant_id`) REFERENCES `scimath_contestants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_score_entries_event` FOREIGN KEY (`event_id`) REFERENCES `scimath_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_score_entries_question` FOREIGN KEY (`question_id`) REFERENCES `scimath_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS `section`;

CREATE TABLE `section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade` varchar(5) NOT NULL,
  `section` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `section` VALUES("1","7","Diamond");
INSERT INTO `section` VALUES("2","7","Ruby");
INSERT INTO `section` VALUES("3","7","Emerald");
INSERT INTO `section` VALUES("4","7","Sapphire");
INSERT INTO `section` VALUES("5","8","Adelfa");
INSERT INTO `section` VALUES("6","8","Dahlia");
INSERT INTO `section` VALUES("7","8","Camia");
INSERT INTO `section` VALUES("8","8","Sampaguita");
INSERT INTO `section` VALUES("9","9","Lithium");
INSERT INTO `section` VALUES("10","9","Beryllium");
INSERT INTO `section` VALUES("11","9","Cesium");
INSERT INTO `section` VALUES("12","10","Graviton");
INSERT INTO `section` VALUES("13","10","Electron");
INSERT INTO `section` VALUES("14","10","Photon");
INSERT INTO `section` VALUES("15","11","Mendel");
INSERT INTO `section` VALUES("16","11","Dalton");
INSERT INTO `section` VALUES("17","11","Newton");
INSERT INTO `section` VALUES("18","12","Alpha");
INSERT INTO `section` VALUES("19","12","Delta");
INSERT INTO `section` VALUES("20","12","Omega");
INSERT INTO `section` VALUES("21","9","Barium");
INSERT INTO `section` VALUES("22","10","Boson");
INSERT INTO `section` VALUES("24","11","Curie");
INSERT INTO `section` VALUES("25","12","Sigma");



DROP TABLE IF EXISTS `student`;

CREATE TABLE `student` (
  `LRN` varchar(50) NOT NULL,
  `lastname` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `birthdate` varchar(20) NOT NULL,
  `entryData` varchar(25) NOT NULL,
  `scholarshipCategory` varchar(25) NOT NULL,
  `batch` varchar(5) NOT NULL,
  PRIMARY KEY (`LRN`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `student` VALUES("100002110013","CAMACHO","HANAH","SANTOS","F","1/2/2006","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("100002120013","PAGUIRIGAN","RAJI MARCO","ROSALES","M","10/09/2007","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("100003160014","QUICQUIC","ZSOFIA CASSANDRA ","","F","6/3/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("100010170013","BARNIDO","RHYSZ GRACE NIEGUEL","VILLAREAL","F","11/12/2011","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("100011110007","AGBAYANI","JHAM RENZ","MORELLA","M","7/15/2006","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("100011150034","POLANGCO","KERWYN CLARK","ACOBA","M","1/29/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("100022090021","LAYAOEN","HRIZHA ALTHEA","TUNGPALAN","F","12/30/2003","ALTERNATE","P2","2022");
INSERT INTO `student` VALUES("100026180006","GARCIA","ALDREI JAMIR","LADERA","M","1/28/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("100046120032","APOSTOL","CINDY NICOLE ANTONETTE","DESCARGAR","F","6/15/2006","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("100050140026","RAMOS","MARIA ELLYNE ANGELIQUE","GAOR","F","4/20/2009","PRINCIPAL","","2027");
INSERT INTO `student` VALUES("100059150078","MADES","ETHAN JARRED","RABAGO","M","3/5/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("100059150081","CARLOS","HANNAH GRACE","CABINIAN","F","5/5/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("100059180041","MABUTI","VANESSA ADHEL","CALAMASA","F","10/12/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("100065180028","LINGOGAN","HAYLEY","CAL","F","2/5/14","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("100075090038","DIAZ","ELAIZA NICOLE","TAPAC","F","1/29/2004","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("100075090073","MANGABAT","ASTER BENEDICT","ARCAPERLAS","M","9/25/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("100075090094","SACAYANAN","JADON","DE LA CRUZ","M","12/12/2003","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("100075090104","VEGA","LOR REINE","PUYAOAN","F","8/31/2003","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("100075100030","BUMANGLAG","MANUEL LUCAS","ODUCAYEN","M","12/8/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("100075100066","IBAÑEZ","VIVIENNE ALEXI","SACRAMENTO","F","10/27/2004","ALTERNATE","P1","2023");
INSERT INTO `student` VALUES("100075110019","COLUMBANO","RICEL ATHEA ","SANTOS","F","7/28/2005","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("100075110026","ECLARIN","MARIA LOUISE ","ACOB","F","12/16/2005","ALTERNATE","P3","2024");
INSERT INTO `student` VALUES("100075130004","OBIEN","GEF KINGSLEY","GUMAYAGAY","M","10/11/2007","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("100075130016","CORPUZ","GEAN DRAEXI","DUQUE","F","02/26/2007","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("100075130038","COCSON","KAMISHCA NAZAREE","EDVER","F","1/9/2008","LATERAL","FULL","2026");
INSERT INTO `student` VALUES("100075140001","BARANGAN","ZYX LEIABE","ALONZO","M","2/24/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100075140004","LUMBO","RYDER LAUREN","GALINATO","M","8/26/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("100075140011","DOMINGO","YLEINA ZELENE","ARANCON","F","4/28/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("100075140015","GAOAT","MADELEINE GENE","GARCIA","F","10/31/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100075140017","DOMINGO","JOHN INVINZOR","OBIEN","M","11/8/2008","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("100075140036","LIJAUCO","JOSH MIKHAIL","ALEGRE","M","12/31/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100075140115","CACHERO","JOELLA LIZH","GARCIA","F","12/24/2007","LATERAL","FULL","2026");
INSERT INTO `student` VALUES("100075150007","LAXAMANA","CARL TIMOTHY","RINEN","M","4/7/2010","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("100075150015","CASTRO","AUBREY DOMINIQUE","VALDEZ","F","11/12/2009","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("100075150024","DIAZ","NEIL EDZEL","TAPAC","M","4/12/2010","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("100075150028","ASUNCION","AALIYAH KATE","JEREZ","F","6/8/2010","ALTERNATE","P1","2028");
INSERT INTO `student` VALUES("100075150034","PACIS","MYZELLE CHLOEI","PUYAOAN","F","7/5/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("100075150040","DUMLAO","ROJAN JOEFEL","CARDONA","M","1/13/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("100075150044","ACANTILADO","ELLHAIZA DIVINE","VEA","F","12/17/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100075160026","CASABAY ","BRAEANNA LOVEIGHN ","TABIOS ","F","1/2/2011","PRINCIPAL","FULL","2029");
INSERT INTO `student` VALUES("100075160082","ACANTILADO","ELIJAH CRISTOFF","CRUZ","M","6/12/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("100075170009","PASTOR","MAVERICH LOUISE","FLORES","M","11/14/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("100075170033","BLAS ","JASMINE GAILE","TAN","F","7/19/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("100075170036","PATRIMONIO","KEITH BAILEY","MACALINAO","F","05/12/2012","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("100075170048","SAIR","JENN MARIE ALYSSA","GALACGAC","F","09/08/2011","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("100075190007","MENDOZA","CARLYN SABINA GRACE","TEJADA","F","8/29/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("100075190020","AGANON","JON LORDLEY","YAPO","M","6/7/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100075190023","DE LOS REYES","ALIYA EXHELRINE DAE","BARRERA","F","8/17/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100075190085","BELARMINO","JOHN WAYNE ZYRUS","GUBATAN","M","2/6/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100075190086","MARIANO","SERGIE","AUSTRIA","M","12/1/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100078090040","CLEMENTE","CHRISTIAN LLOYD","ULIT","M","8/9/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100078090041","CLEMENTE","MARC DANIELLE","NEFALAR","M","10/16/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100078090165","VILLENA","IVY MITZ","GANIRON","F","7/1/2003","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("100078100069","ESPIRITU","JEFFEL JOSHUA","LABUTON","M","4/9/2005","ALTERNATE","FULL","2023");
INSERT INTO `student` VALUES("100078100137","REMIGIO","GABRIELA GRACE","ULIT","F","1/30/2005","ALTERNATE","FULL","2023");
INSERT INTO `student` VALUES("100078120047","SALES","SOFIA JEWEL","LACISTE","F","07/01/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("100078130030","NALUPTA IV","MARIANO RANZESCO","DALIDA","M","11/15/2006","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("100078160003","BAYANGGOS ","ZAREN YANIS ","MIRASOL ","M","6/3/2011","ALTERNATE ","P2","2029");
INSERT INTO `student` VALUES("100078190005","DAMO","GABEE","GAOAT","M","4/24/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100078190073","ACOBA","KIAN ANDREI","ALCOY","M","6/12/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100089190032","MADRID","DRAKE DRANREB","ADINA","M","6/19/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100096090019","CALDERON","LYRA JOIS","BALANAY","F","9/28/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100096090021","CANJA","NORPRI MAE","ABITONG","F","5/11/2004","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("100096100086","RONDUEN","JEMAICA","PUCAN","F","9/22/2004","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("100096110097","SACRO","DION MAVERYK ","GILLES","M","12/4/2005","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("100096120037","IGARTA","CARMELA NICOLE","SAGUN","F","12/31/2006","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("100096120060","DULDULAO","GABRIEL","PINEDA","M","09/30/2007","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("100096120092","SULMERIN","MCDILAN","REYES","M","11/25/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("100096140095","PAMBID","GILMAR JR. ","TAGUDIN","M","3/11/2009","ALTERNATE","FULL","2027");
INSERT INTO `student` VALUES("100096170017","SULMERIN","NOAH","REYES","M","9/30/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("100096180061","MIRASOL","REUBEN JAN","JAVELLONAR","M","10/6/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("100105120013","DE LUNA","JAYMEE ALLYSON","RIVERA","F","05/28/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("100105140037","OLIVEROS","GLAIZA MAE","ARGEL","F","11/28/2008","ALTERNATE","","2027");
INSERT INTO `student` VALUES("100125160003","MAMUAD ","FRECHELL  CZAREN ","ABES ","F","11/12/2010","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("100188110074","TOLLO","GRANT GABRIEL ","OAMIL","M","11/13/2005","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("100190090045","SULICIPAN","KATELEEN MAE","CASTRO","F","5/11/2004","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100190120017","PERMISON","SAMANTHA MIEL","DOMINGO","F","01/14/2007","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("100202180010","SALVADOR","HERA MARTEINGEL EVE","SURBAN","M","5/3/13","ALTERNATE","P2","2031");
INSERT INTO `student` VALUES("100208120006","AQUINO","MARK CEDRICK","ADAON","M","10/02/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("1002150101","ABINSAY","TRENT RYZZN","CACANANTA","M","06/23/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("100220150016","TORIBIO","NEIJI BRYE","RIEGO","M","7/27/2010","ALTERNATE","P2","2028");
INSERT INTO `student` VALUES("100227170004","VILLA","FRANCES AVERY","ABELLA","F","01/07/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("100227170008","SAPIAY","DAMARIS FELICITY","ANDRES","F","03/08/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("100240120016","CABIE","ANGEL GWYNETH","GAMIT","F","06/15/2007","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("100240140026","GAMAYO","IZABELLA THEOFEY ANNE","LAENO","F","09/15/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("100241090011","BUDUAN","RHEANEL GWEN","BUMANGLAG","F","2/18/2004","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("100260140080","LABUNTOG","ROBBIE MIGUEL","FLORES","M","03/12/2007","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("100260160041","CALAMAYAN ","ZCARINA MYTZ ","DAMO ","F","11/22/2010","ALTERNATE","P1","2029");
INSERT INTO `student` VALUES("100260190001","ANTONIO","JEDRICK","DULDULAO","M","2/1/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100271090034","SANTOS","MAKYLA DANIELLE","AGMATA","F","12/30/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100272140013","BUGUINA","MARK MEYNARD","VENTURA","M","08/08/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("100285150053","VICENTE","VIVIEN ANGELINE","DE VERA","F","2009-01-06","LATERAL","P3","2027");
INSERT INTO `student` VALUES("100285170028","VICENTE","VENIZE ALLISON","DE VERA","F","01/12/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("100305180025","CALZDA","HERZEKAIAH DARYLLE","ABARA","F","1/25/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("100305180032","RAMIREZ","DASHA ONIELLE","AGBAYANI","F","5/5/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("100305180036","VIDAD","JENINA APRIELLE","COSTALES","F","4/14/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("100312090002","ACACIO","DAVID","BAUTISTA","M","9/11/2002","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100312090015","CALUYA","JOHN PETER","BULOSAN","M","3/18/2004","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("100312120061","FIESTA","CHASSEY LAYNE","ZUNIGA","F","11/2/2006","ALTERNATE","FULL","2024");
INSERT INTO `student` VALUES("100312150049","SUBIA","CARMELA IYA","MATEO","F","9/19/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100312150051","CALIDA","JOSIAH JAYME","CIRON","M","7/30/2004","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("100331190004","CUDAO","CHRIS VIMBER","WANGET","M","11/6/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100352110006","GARCIA","PRINCESS AMOR ","GAMBOA","F","1/26/2006","ALTERNATE","P1","2024");
INSERT INTO `student` VALUES("100369150007","OSALVO","NIKAYLA EMERALD","BAUTISTA","F","5/27/2010","ALTERNATE","P2","2028");
INSERT INTO `student` VALUES("100370160019","DELA CUESTA ","CHYNA ANNE ","CARPIO ","F","2/4/2011","ALTERNATE ","FULL","2029");
INSERT INTO `student` VALUES("100373110022","PUGAL","MYKEL ANGELO ","LAURENTE","M","12/19/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("100373130011","PIDO","ALDRIN THEO","BIRGINIAS","M","07/07/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("100373190003","LAURENTE","CURT ANDREI","SANCHEZ","M","2/2/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100375090023","RABANG","JONATHAN PAUL","PALAPALA","M","8/24/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100375130020","TABORDA","AARON PRINCE","PACPACO","M","05/12/2008","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("100380190007","OBRERO","R\'PHAEL RYNE JOXEN","FRUTAS","M","5/8/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100385150046","PELAYO","GABRIEL MAR","ROJAS","M","2/26/10","LATERAL","","2028");
INSERT INTO `student` VALUES("100385160011","PABO","JOHN CARL","PORTENTO","M","2/2/11","LATERAL","FULL","2029");
INSERT INTO `student` VALUES("100385170024","PELAYO","JOHN CARLO","ROJAS","M","12/12/2011","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("100385170048","PAZ","ELIAH FAYE","URBAN ","F","11/04/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("100385170049","CALLEJO","KAYE CASSANDRA","HERRERA","F","2011-10-10","LATERAL","FULL","2030");
INSERT INTO `student` VALUES("100392110018","LAGMAY"," BEA MARU ","BIETE","F","6/17/2006","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("100392190019","OLPINDO","ELBENNE KASSANDRAH GENN","HATAP","F","11/17/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100393170008","LOPEZ","IVAN EZEKIAL","PANINGBATAN","M","4/17/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("100397140003","BERONILLA","KOBE BOY ","RAGIL","M","2/12/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100403140067","GALAMGAM","LEI ANGELO","ANTIPORDA","M","06/29/2008","PRINCIPAL","P1","2026");
INSERT INTO `student` VALUES("100403140072","SORIANO","ALCYON RON","SERRANO","M","03/19/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("100403150001","ANCHETA","STEFFEN MARRION","SUETOS","M","12/30/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100403150003","MAGBALETA","JOHN RAINIER","SUETOS","M","5/15/2009","PRINCIPAL","P1","2027");
INSERT INTO `student` VALUES("100403150075","JIMENEZ","SOPHIA YSABELLE","SAPADEN","F","1/28/2010","ALTERNATE","P2","2028");
INSERT INTO `student` VALUES("100403180045","RIMASO","DIVINE FAITH","PRESTO","F","12/14/12","PRINCIPAL","P2","2031");
INSERT INTO `student` VALUES("100404140006","DELA CRUZ","JAMAYCA","SAIT","F","11/16/2008","PRINCIPAL","P1","2027");
INSERT INTO `student` VALUES("100409140028","SANDI","CYBELE HERAH","SIMSIMAN","F","6/18/2009","PRINCIPAL","P1","2027");
INSERT INTO `student` VALUES("100417160021","MAGBANUA","SAMUEL ANTONIO","PERALTA","M","12/29/2009","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("100418100002","ARCE","LESTER MARC","CASTILLO","M","3/28/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("100420140010","GARCIA","BONNA PEARL","SAMELIN","F","8/10/2009","PRINCIPAL","P1","2027");
INSERT INTO `student` VALUES("100422190008","SERRANO","JERIEL BALTAZAR","LUNA","M","1/21/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100424130035","SILVANIA","HANNAH GRACE","ADRUNGAN","F","04/12/2008","PRINCIPAL","P1","2026");
INSERT INTO `student` VALUES("100425160015","WALIS ","KATE LOUISE ","VALDEZ","F","5/11/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("100436120042","RODRIGUEZ","OWEN GABRIEL","ANSING","M","05/27/2007","ALTERNATE","P1","2025");
INSERT INTO `student` VALUES("100436120201","RIBUCAN","BERNICE ","FAROL","F","2/27/2006","ALTERNATE","P2","2024");
INSERT INTO `student` VALUES("100436130195","PUNSALAN","GABRIEL DARWIN","ZARA","M","11/10/2006","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("100436130204","CUARESMA","MARIA CHEYENE","ASEJO","F","01/12/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("100436140064","PONCIANO","EMMANUEL","OASAN","M","12/31/2007","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("100436140107","SIGGAYO","RYENCE REUEL","VALDEZ","M","12/10/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("100436140162","BELLO","CHARIELYN JIRAH","","F","12/24/2008","ALTERNATE","FULL","2027");
INSERT INTO `student` VALUES("100436150119","SOY","CHRISTIAN BENEDICT","UNTARAN","M","10/26/2009","ALTERNATE","FULL","2027");
INSERT INTO `student` VALUES("100436150133","SANCHEZ","CHLOIE MAE","GALDONES","F","7/6/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("100436160070","LEGASPI","ELIN AMOR","GALLANDEZ","F","3/25/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("100436160076","MILLA","MEILLE","LOZANO","F","5/28/2010","ALTERNATE","P1","2028");
INSERT INTO `student` VALUES("100443130023","RAGASA","KIM JANARHEY","GALDONES","F","01/16/2008","PRINCIPAL","P1","2026");
INSERT INTO `student` VALUES("100449130056","SUYAO","ANGELEE","GALIMBA","F","02/06/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("100451130004","BUNGOLAN","ED NHAZARENE","VILLALOBOS","M","01/09/2008","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("100453140011","BUENO","BHELLA PATRICE","ALCAUSIN","F","12/7/2008","PRINCIPAL","FULL","2027");
INSERT INTO `student` VALUES("100456170044","RIAMBON","KURT RUSSEL","QUIOCHO","M","10/18/11","LATERAL","FULL","2030");
INSERT INTO `student` VALUES("100457190016","QUIOCHO","MIELLE CHRICZAE","DIGA","F","10/5/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100458130027","PAMANI","CHRISTINE","VERGARA","F","12/25/2007","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("100497120008","ASUNCION","ZLYNN ANNE","ASIA","F","08/04/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("100497150001","ASUNCION","CHRISTENZEN","ASIA","M","4/8/2010","ALTERNATE","FULL","2028");
INSERT INTO `student` VALUES("100497170020","ELNAJJAR","FATMA","VALDEZ","F","02/07/2012","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("100501140055","PAGAO","ELIJAH","","F","11/11/2008","ALTERNATE","FULL","2027");
INSERT INTO `student` VALUES("100502160005","UCLUSIN","JOHNELLE LAUREN","TABURA","F","5/23/11","LATERAL","","2029");
INSERT INTO `student` VALUES("100507170019","QUINTO","MARK DENZ","","M","02/09/2012","ALTERNATE","FULL","2030");
INSERT INTO `student` VALUES("100509150012","SIMON","ELOISA CLYDE DENISE","UJANO","F","11/3/2009","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("100510140018","UNCIANO","KREIGSAINT","TOLENTINO","M","7/18/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("100536160015","PIMENTEL","SHEKINA CARLA","QUILON","F","6/13/11","LATERAL","","2029");
INSERT INTO `student` VALUES("100541160004","CABRERA","AIDAN JUSTIN","BANGKIKIW","M","6/18/11","LATERAL","","2029");
INSERT INTO `student` VALUES("100542170011","DOFREDO","JOHN RUDY","BATIN","M","2011-10-30","LATERAL","FULL","2030");
INSERT INTO `student` VALUES("100550140021","SALVA","RALPH OWEN","AQUINO","M","11/10/2008","PRINCIPAL","","2027");
INSERT INTO `student` VALUES("100570180013","HERAÑA","JARED","MATCHO","M","10/30/12","ALTERNATE","FULL","2031");
INSERT INTO `student` VALUES("100582090057","RIVERA","NIKKI VALERIE","GOMINTONG","F","11/6/2003","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("100582150009","PASCUA","LUCKY","TAQUEBAN","M","9/18/2009","ALTERNATE","FULL","2028");
INSERT INTO `student` VALUES("100585100002","BARET","JELOME RENZ","GACUSAN","M","11/12/2004","ALTERNATE","P2","2023");
INSERT INTO `student` VALUES("100593140012","TORRICER","JONATHAN II","VENTURA","M","9/1/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100601150010","ULIT","REIZHER MIGNLEIGH","VITALIS","M","01/04/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("100603160012","PURISIMA","CAMILLE KATE","FLORES","F","8/2/11","LATERAL","","2029");
INSERT INTO `student` VALUES("100607170005","ALVIAR","AUBRELLE DAPHNE","JAVIER","F","4/26/2012","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("100608100026","DOMINGO","MARK JOSEPH","ASUNCION","M","6/29/2005","ALTERNATE","P1","2023");
INSERT INTO `student` VALUES("100608110026","PEREDO","JENNA ISABELLE ","RAPING","F","5/28/2006","ALTERNATE","P3","2024");
INSERT INTO `student` VALUES("100608120085","FUNTILA","NATASHA SOPHIA"," RAQUEPO","F","2/18/2006","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("100608130040","CAMPOS","JOHN CEDRICK","VILORIA","M","10/21/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("100608130087","AQUINO","ETHNEY CORINNE LIA","","F","09/29/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("100608140078","RAPISURA","EDRIC ENZI ","ALAGAO","M","1/3/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("100608140082","PEREDO","JENINA EILA","RAPING","F","4/3/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100608150002","DELA CRUZ","JOHN DANIEL","VILLON","M","11/28/2009","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("100608150008","VILORIA","CESAR NERU III","AQUITANIA","M","1/21/2010","ALTERNATE","P1","2028");
INSERT INTO `student` VALUES("100608170052","FRIALA","GWEN GERARD","GANIR","M","9/24/2011","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("100615090030","REMUCAL","KYLE CHESTER","REBULA","M","2/20/2004","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("100616130007","RIDULME","JOSAPHAT SERAPHIM","SY","M","09/09/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("100617140067","RIGUERA","KYRA NICOLE","-","F","1/23/2009","PRINCIPAL","FULL","2027");
INSERT INTO `student` VALUES("100617140074","RAMOS","RONLEYCK JOSHUA","REQUILMAN","M","01/03/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("100619110023","LEONES"," RYNNEL BRENT ","ARCONADO","M","10/9/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("100619120012","BILLONES","JEREMY REY","TURQUEZA","M","04/12/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("100623130038","AVILA","SAMANTHA MYANN","BILEN","F","05/22/2008","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("100625190002","BALIGOD","VAN ALEXANDER","VILLARUZ","M","4/24/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100627130027","MARTINEZ","BRIANNA NICORETTA","MOLINA","F","11/5/2007","LATERAL","P2","2026");
INSERT INTO `student` VALUES("100627180016","BURGONIO","SOPHIA CASTIELLE","GURION","F","11/6/13","PRINCIPAL","P1","2031");
INSERT INTO `student` VALUES("100638140017","REYES","DENISE SUZANNE","JUAN","F","7/7/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("100641130035","IBRAO","DIANNE ASHLEY","ABAT","F","06/14/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("100643090026","YORO","GREGORY ULRIC","ARRIETA","M","7/10/2004","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100644150001","IGARTA","ALLEISSY","GABRILLO","F","2/15/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("100650160015","LIVED ","BRANDON XYRIL ","SAN JOSE ","M","11/27/2010","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("100653090018","EUGENIO","HANNAH LEANDZEY","DOMINGO","F","11/24/2004","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("100653100010","BALBAS","MARK JULIUS","IBARRA","M","7/5/2005","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("100653130108","PERALTA","ZYANN JOSEPHINE","INES","F","08/12/2006","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("100653140082","VENTURA","MARCRIS GABRIEL","SAPLOR","M","06/14/2008","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("100653140090","EUGENIO","ANDREA LOUISE","DOMINGO","F","01/10/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("100653150090","ALBANO","ARIANNE KARYLLE","CONCEPCION","F","12/4/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100653150096","YADAO","ALEXA CZIEL","JARDELEZA","F","3/15/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100653160018","TAMARGO","NATHAN ARKHIN","INONG","M","8/18/11","LATERAL","","2029");
INSERT INTO `student` VALUES("100654170028","RAGUAL","REYAN ELAINE","YAGO","F","08/21/2007","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("100655170002","DE LEON","LANCE ABISHAI","PAINO","M","10/05/2011","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("100656140008","ABELLA","GAILE JIAN CLARYLLE","YERE","F","2/24/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100662140001","CORONEL","HANNAH ALTHEA","GAMIT","F","5/25/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100663130004","FLORES","JAN MARLOWE","REAL","M","02/19/2008","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("100663130021","RONDARIS","GAREL KIMBER","ARIZABAL","M","03/08/2008","ALTERNATE","P1","2026");
INSERT INTO `student` VALUES("100663150016","RAPACON","RIA LYN","MERCURIO","F","1/23/2010","ALTERNATE","P2","2028");
INSERT INTO `student` VALUES("100663170014","TOTTOC","PAUL ANDY","EDRA","M","3/15/2012","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("100671110003","ALCABEDOS","MONICO ","VILLA DEL REY","M","5/18/2006","ALTERNATE","FULL","2024");
INSERT INTO `student` VALUES("100681150007","LEE","AXLEDRANOEL","OMAOENG","M","6/17/2010","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("100696190003","CANTANO","PHIL ANTHONY","HAILAR","M","1/18/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100697120068","TAGASA","KARL","BUNAGAN","M","5/14/2003","LATERAL","P2","2022");
INSERT INTO `student` VALUES("100697120069","TAGASA","KAYE CLARISSE","BUNAGAN","F","9/5/2004","PRINCIPAL","P2","2023");
INSERT INTO `student` VALUES("100697150025","OLANIO","KAYE ANNE","AGUSEN","F","12/21/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100698190001","ABUBO","ALEX RAMONE","RILVERIA","M","5/13/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("100700140011","REBOLDELA","FREDMAR JOHN","DERIGE","M","12/29/2008","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("100715090039","DATO","FRANCIS CARL","RAGSAG","M","3/6/2004","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("100715100025","CAS","JHOANNA MAE","SORIA","F","1/25/2005","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("100715130011","CACABELOS","JAN NATHANIEL","ADOLFO","M","01/28/2007","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("100715150027","DEVIS","CLARISSE DANE","FILARCA","F","9/23/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("100715170007","LEONZON","JOHN MARK","CALIBUSO","M","10/20/2011","ALTERNATE","P3","2030");
INSERT INTO `student` VALUES("100715190050","MORLA","REYNHEL","LLANEZA","F","9/7/23","ALTERNATE","","2031");
INSERT INTO `student` VALUES("100720190003","MALATE","TRANCO NICO","PALOMARES","M","8/4/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("100721120038","TULLAS","ASHLEY JOY","RAMOS","F","12/26/2006","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("100721140010","PABLICO","JOFRAN","PILOT","M","12/28/2008","PRINCIPAL","FULL","2027");
INSERT INTO `student` VALUES("100721140021","PALOS","JAMES CESAR","REBEBES","M","12/15/2008","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("100721140028","RICABLANCA","HERLEICESTER","REYES","M","12/27/2008","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("100721140035","PASTOR","MHARYLL AUREEN","CORPUZ","F","12/1/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("100721150001","DAUZ","XYRIL ALEXIS","RIVAD","F","3/27/2010","ALTERNATE","FULL","2028");
INSERT INTO `student` VALUES("100721160023","LAURENTE ","LAURIE GAIL ","ARTATES ","F","6/22/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("100736160002","CASPE","ASHKE","REBURON","M","2/22/11","LATERAL","","2029");
INSERT INTO `student` VALUES("100737170016","DOTAROT","ALLISON SHAYNE","GONZALES","F","10/04/2011","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("100737190017","TABBOGA","JESSICA CLAIRE","BON","F","4/14/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("100738130019","TABBOGA","DENISE JEAN IRI","JIZ","F","11/14/2007","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("100740130078","FULLER","RAVIN DAILE","FONTANILLA","M","03/21/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("100740140049","BONDOC","EUNICA JUNEL","TADIOS","F","7/22/2009","ALTERNATE","","2027");
INSERT INTO `student` VALUES("100740150029","GARCIA","VHON HARVEY","TAAY","M","8/12/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("100740150058","GARCIA","JAZZY KURTLAYNE","WAKLIN","F","12/29/2009","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("100740160040","RECAIDO ","JILLIANNE MARIE ","CALUYA ","F","2/24/2011","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("100740160059","GARCIA","JESSYNIE CIANTE","WAKLIN","F","8/5/11","LATERAL","","2029");
INSERT INTO `student` VALUES("100741120007","FRUELDA","CHARLES ADRIAN","PEROS","M","10/20/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("100741150035","SANTIAGO","NIÑA ELAINE","NARCISO","F","1/12/2009","ALTERNATE","FULL","2028");
INSERT INTO `student` VALUES("100741170004","EDUARTE","NATHAN KYLE","PAZ","M","11/17/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("100772130024","LARDIZABAL","JENNIFER","LASTIMOZA","F","02/21/2008","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("100772130074","BERGADO","ANTHONETTE LOURICE","QUIMADO","F","05/05/2008","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("100786140102","PANGILINAN","DANIKA DANIELLE","MIRANDA","F","10/22/2009","PRINCIPAL","P1","2027");
INSERT INTO `student` VALUES("100793150049","SEVILLENA","ZYRILLE IVY","PACLEB","F","11/5/2009","ALTERNATE","FULL","2028");
INSERT INTO `student` VALUES("100793170062","BOBITA","ELIJAH ZEUS","AMICAY","M","11/28/11","LATERAL","","2030");
INSERT INTO `student` VALUES("100800120038","UNCIANO","JUSTICE GABRIEL ","ARCELLANA","M","3/14/2006","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("100800170014","SIDINGAN","RIZ LENNON","LUGOS","M","10/25/2011","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("100800170045","ALUDINO","CEDRIC JOSHUA","PACPACO","M","06/09/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("100801120064","MILO","ERIKA CASSANDRA ","MEDRIANO","F","10/26/2005","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("100812130016","CALUZA","JULYANNAH JHAZMINE","RIVERA","F","11/01/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("100844130001","GRANADO","MOSES ARMAN","DELA CRUZ","M","10/03/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("100855150032","CASIBANG","JOSHUA MIGUEL ","DACANAY","M","9/18/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("100855170021","CASIBANG","JASHMIN MARIEL","DACANAY","F","01/07/2012","ALTERNATE","FULL","2030");
INSERT INTO `student` VALUES("100875180012","CARILLA","SWEET ADRIANNE","CIRILO","F","1/25/13","ALTERNATE","P2","2031");
INSERT INTO `student` VALUES("100991180052","FLORES","JERICH KRISTIENNE","","F","2/17/13","PRINCIPAL","FULL","2031");
INSERT INTO `student` VALUES("101005170063","PATRICIO","JANBERT ","ALCANTARA","M","04/06/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("101015140018","RIVERA","GABRIEL AZHIEZ","VILLUAN","M","5/31/2009","PRINCIPAL","FULL","2027");
INSERT INTO `student` VALUES("101015140020","SELGA","JAMES ALLEN ","REFUERZO","M","4/5/2003","ALTERNATE","P2","2027");
INSERT INTO `student` VALUES("101016180003","MANANGBAO","JOSHA","LANGTIWAN","F","8/5/13","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("101026180009","BERSAMINA","ROYANNE ANGELO","DELA CRUZ","M","11/28/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("101030170111","PACULAN","RICHARD LARRY","DIZON","M","01/12/2012","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("101094150055","RAMOS","ALDOUS KENT","ABENES","M","11/6/2009","ALTERNATE","FULL","2028");
INSERT INTO `student` VALUES("101166140177","ARAFILES","ALMIRA","EDUARTE","F","08/23/2006","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("101166160072","ESTRELLA ","SHANAIRA GIANNE ZOE ","LAZARO ","F","12/7/2010","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("101166180174","MENDEZ","YLHYNNE HARP","RENDON","F","12/28/12","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("1011666120002","CAASI","FRITZ GERALD","ALPAJORA","M","10/28/2006","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("101264190034","GALSIM","KING ARIESTONE","GONZALES","M","","ALTERNATE - CLC","","2032");
INSERT INTO `student` VALUES("101267170059","MIRANDA","RENZ ARDAYNE","DE CADIZ","M","10/14/2011","ALTERNATE","FULL","2030");
INSERT INTO `student` VALUES("101305130049","ROMANO","KRYSTAL CHARIZZE","FERENAL","F","06/30/2008","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("101342090178","SAGUN","MARCO JONES","CANIEDO","M","11/5/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("101342130034","LAROCO","LEI VALERIE","PUDE","F","01/30/2007","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("101342130289","BRUAN","RHIAN DANNA","ODERO","F","08/25/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("101342130290","CAMBA","MICHAELLA KISHA","PAKINGAN","F","06/18/2007","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("101422110166","MAPILE","DECIER OLIVER","RAMOS","M","12/22/2005","LATERAL - G8","P3","2024");
INSERT INTO `student` VALUES("101422130202","DELA CRUZ","JESCHILIAH RIZA","CARONONGAN","F","","LATERAL","","2026");
INSERT INTO `student` VALUES("101483130062","DEGANO","MARIVIENE ALIXIA","VINOYA","F","7/13/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("101546130022","DELA CONCHA","MARK LAWRENCE ","FERNANDEZ","M","10/21/2005","ALTERNATE","FULL","2024");
INSERT INTO `student` VALUES("101749120140","LOPEZ","KAROLINA VICTORIA","DIONELA","F","03/13/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("101800120218","DELA PEÑA","JEAH","GAMBOA","F","03/11/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("101800190131","RAYNAS","REAHNA SAFFHIRE","SARMIENTO","F","3/16/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("101860120606","BIASCAN","TREBINE","NOVIDO","M","10/23/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("101860150156","NITURADA","JIRAH JOY","VIDUYA","F","9/12/2009","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("101860170252","CASCO","TIMOTHY JOSEPH","VIERNES","M","02/11/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("101860170255","BAUTISTA","ZARREN DOMINIQUE","VALDEZ","F","11/13/2011","ALTERNATE","FULL","2030");
INSERT INTO `student` VALUES("101860170271","DE GUZMAN","XAIRA BERLIOZ","MOTEA","F","11/09/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("101860180155","GARCIA","KHALEB MIGUEL","MATITO","M","4/24/13","PRINCIPAL","FULL","2031");
INSERT INTO `student` VALUES("101860190157","CORTEZ","TIMOTHY JOHN","GADINGAN","M","11/16/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("101860190177","SERAFICA","LEI AINDREAH","FERNANDEZ","F","10/28/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("101879150070","GABRIEL","SHEMAIAH YANICHEL","ALMAZAN","F","12/8/2009","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("101883130061","DELA CRUZ","DENISE CHLOE","KILLI","F","2009-03-11","LATERAL","P3","2028");
INSERT INTO `student` VALUES("101883140002","PONTAWE","PRECIOUS DAPHNE","FERNANDEZ","F","12/27/2008","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("101896150034","VARGAS","DINARA ZOE","ARADANAS","F","3/28/10","LATERAL","","2029");
INSERT INTO `student` VALUES("101896190027","BOMBILLA","MARKHENA RAIN","SALAPEO","F","7/9/2014","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("101914150115","VILLANUEVA","NATHANIEL ","GENESA","M","11/2/2005","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("101945180017","NICOMEDEZ","ZANDRIE LUKE","PETILLA","M","11/18/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("101945190182","NIÑAL","CARLISLE","","M","9/1/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("102125140015","SESO","PRINCESS ANGEL","CORPUZ","F","03/05/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("102126100096","MATIAS","HANNAH ASHLEY","NAVARRO","F","10/28/2004","PRINCIPAL","P2","2023");
INSERT INTO `student` VALUES("102126120003","MATURAN","RAJ DENISSE","NAMA","F","6/23/2006","ALTERNATE","FULL","2024");
INSERT INTO `student` VALUES("102126120021","DELELIS","KRISTINE ANGELINE","ARNIDOVAL","F","04/29/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("102128100011","DALUPANG","XANDER PAUL","PACABA","M","4/14/2005","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("102152090252","MENESES","LEVI","AQUINO","M","4/25/2004","ALTERNATE","P3","2022");
INSERT INTO `student` VALUES("102171090147","TIO","JAMES BENNEDICT","GAJETO","M","11/3/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("102185130136","BALDEMORO","JULIAN LUIS","PAULINO","M","11/7/2007","LATERAL","FULL","2026");
INSERT INTO `student` VALUES("102191100064","ESPAÑOL","ALLEYA VIANCA","PAMILACAN","F","10/9/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("102191100080","GUERRERO","JANIAH FAITH","MATEO","F","7/30/2005","LATERAL","P3","2023");
INSERT INTO `student` VALUES("102191120420","MIGUEL","JERRIELYN","AQIUNO","F","09/05/2006","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("102191130095","JUAN","CYRUS JACOB MARCHY","VITE","M","03/28/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("102191130168","AGUSTIN","ERICH D-YAN","COLOBONG","F","01/05/2008","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("102191140139","MABUNAY","YZAH","DELOS REYES","F","2/2/2009","ALTERNATE","FULL","2027");
INSERT INTO `student` VALUES("102191150106","ANDRES","PRINCESS ANGEL","TORIBIO","F","1/30/2010","ALTERNATE","P1","2028");
INSERT INTO `student` VALUES("102191190064","GAOIRAN","JESS HARRY","TUMAMAO","M","6/21/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("102193160029","RAVAL ","SOPHIA JEMIMA ","ATIENZA ","F","6/14/2011","ALTERNATE ","P2","2029");
INSERT INTO `student` VALUES("102198160023","UDARBE ","JELLIANE","GUTIERREZ","F","12/3/2010","ALTERNATE ","FULL","2029");
INSERT INTO `student` VALUES("102202090031","ARRIETA","JEMYMA THEA","ALDOS","F","7/27/2003","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("102202090135","HERNAEZ","ATHENA CASSANDRA","GREGORIOS","F","1/9/2004","ALTERNATE","P1","2022");
INSERT INTO `student` VALUES("102202090153","LEAÑO","CHRYSHEL YSA","PEDRONAN","F","4/22/2004","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("102202090228","SANTOS","EUEL NAZARENE","PASCUAL","M","7/24/2004","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("102202100006","ABLAN","SOPHIA ISABELLE","PAGUYO","F","12/17/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("102202100037","BALTAZAR","JOHN LLOYD","AGUINALDO","M","12/13/2004","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("102202100066","CID","ALEXANDER ZYKE","AGACID","M","1/2/2005","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("102202110027","BARROGA","CHRISTAN JAYNARD ","AGAMATA","M","1/17/2006","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("102202110077","PERALTA","PATRICIA KAYE","GUDOY","F","3/27/2006","ALTERNATE","FULL","2024");
INSERT INTO `student` VALUES("102202110081","HERNAEZ","ALIYAH JEORGINA ","GREGORIO","F","4/10/2006","ALTERNATE","P1","2024");
INSERT INTO `student` VALUES("102202120023","SAN AGUSTIN","GEOFF JUDE","TAPIRU","M","","LATERAL","","2025");
INSERT INTO `student` VALUES("102202120126","VILLAFLOR","EVRALD KEINTH","GARCIA","M","01/28/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("102202120195","ALEJANDRO","DWIGHT JAMES","AGUSTIN","M","9/19/2005","ALTERNATE","P3","2024");
INSERT INTO `student` VALUES("102202120205","CATUBAY","KALEI YASHA ","MACAGBA","F","8/23/2005","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("102202120213","SANGUAL"," MARIA ELIZA ","NATIVIDAD","F","7/25/2006","ALTERNATE","P2","2024");
INSERT INTO `student` VALUES("102202120227","SENENSE","ALYSSA MAYE","TAGALICUD","F","5/7/2005","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("102202120319","SENENSE","FRANCINE GAILE","TAGALICUD","F","1/8/2004","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("102202130017","BACISTER","RON JOSHUA","JASON","M","02/16/2007","ALTERNATE","P1","2025");
INSERT INTO `student` VALUES("102202130221","CONTRERAS","GHIENIE PEARL","IBARRA","F","7/1/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("102202140006","MANRIQUE","BERNICE VIII VIIXXXIE","GARCIA","F","05/03/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("102202140034","PASION","JERIANN JADEN","LAO","M","08/20/2007","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("102202150016","MALIPOT","NEO CARLO","VERBO","M","1/7/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("102202150021","AGONOY","JAMELLA DANE","BARENG","F","12/27/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("102202150106","ORCINO","SOPHIA FELIZA","LABUCAY","F","12/19/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("102202180117","ABUNAGA","MACLORAN JOY","PEDRONAN","M","5/19/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("102202180137","VALDEZ","YEHOSHUA GABRIEL","ALVARADO","M","1/6/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("102211130031","PACHECO","ALTHEO","ACOBA","M","06/17/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("102211160019","BORJA ","SAMANTHA MHEL ","CABANG ","F","8/14/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("102212190028","TAMAYO","MIA JECA RAFAELLE","BALLESTEROS","F","8/9/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("102217180162","BULATAO","JHORESA MAE","ROSARIO","F","10/12/12","PRINCIPAL","P2","2031");
INSERT INTO `student` VALUES("102283090124","BUSCAINO","SHERRY HEART","ROLDAN","F","1/19/2004","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("102283090197","DE GUZMAN","RACHEL ANNE LOUISE","BANIAGO","F","12/11/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("102283090474","QUIMSON","REIGN KYLA","MALICDEM","F","5/22/2004","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("102283090498","REYES","DANIELLE VAE","REPE","F","8/28/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("102283100022","AGBULOS","JASON","QUITOLES","M","8/30/2005","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("102283100305","GANCEÑA","MISHA RAE NEINA","LAGMAY","F","7/3/2005","PRINCIPAL","P2","2023");
INSERT INTO `student` VALUES("102283120119","MALBOG","NELL ADISON","SARMIENTO","M","07/09/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("102283120125","MAMACLAY","AVRICK JANLEY","SUNDIAN","M","05/31/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("102283120136","JOSON","CHAREE CLAIRE","SISON","F","10/24/2007","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("102283120168","QUIMSON","REIGN JEWEL","MALICDEM","F","05/27/2007","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("102283120194","NARTATES","YSABELA","MANZANO","F","07/16/2007","ALTERNATE","P1","2025");
INSERT INTO `student` VALUES("102283120734","MARTIN","EYTHAN MATTHEW","CRUZ","M","1/15/2004","ALTERNATE","P2","2023");
INSERT INTO `student` VALUES("102283120940","MARTIN","NATHAN GABRYLL ","CRUZ","M","8/4/2005","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("102283130075","SY","KIRK VINCENT","DOCTOLERO","M","01/18/2007","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("102283130214","ESCAÑO","MARC LEONARD","ARINDUQUE","M","06/12/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("102283130325","BALLESTEROS","HANS CHRISTIAN","FERNANDEZ","M","","LATERAL","","2026");
INSERT INTO `student` VALUES("102283130406","TOLENTINO","ERNEST","PASCUAL","M","11/14/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("102283130530","DELA CRUZ","CRISTEL JEAN","PERIAS","F","08/15/2008","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("102283130563","HERNANDEZ","ATHEENA SOPHIA","GARCIA","F","12/08/2007","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("102283140404","LOSTE","MONTRELL VICTOR","DE VERA","M","3/27/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("102283140407","SOLIS","MARC ARJAY","ERASUSTA","M","5/23/2009","PRINCIPAL","P1","2027");
INSERT INTO `student` VALUES("102283140501","TABION","NERSON DAVE","CALMA","M","3/3/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("102283140505","DAUS","MIKAELA","SIMEON","F","2008-11-18","LATERAL","P3","2027");
INSERT INTO `student` VALUES("102283140642","UMINGA","IRISH JEILL","NAZARRO","F","11/17/2004","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("102283140667","SIBORBORO","KYLE KEEANE AEUANE","OCAMPO","F","06/09/2007","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("102283150010","QUIMSON","AGUSTIN JHEDRIAN III","MALICDEM","M","1/30/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("102283150028","VALEROSO","TERESSE SUSYNE","REBOLLIDO","F","1/5/2010","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("102283150031","ANDRADA","CIV","MONTEMAYOR","M","3/10/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("102283150032","CAPIÑA","SHAIRA GRACE","RICO","F","10/11/2009","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("102283150177","LOPEZ","DENISE ANNE","BALLESTEROS","F","9/28/2010","ALTERNATE","FULL","2028");
INSERT INTO `student` VALUES("102283150237","VILLA CRUZ","IRISH FEBY RUTH","ULALAN","F","","LATERAL","","2028");
INSERT INTO `student` VALUES("102283150457","CASTAÑAGA","VENICE MAYE","PERALTA","F","12/25/2009","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("102283160001","ABULAG","NHYREN YOHANCE","GUMTANG","M","5/1/2011","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("102283160011","SANCHEZ"," MIKHAELA ANGELIE","QUIMING","F","6/26/2011","ALTERNATE ","P2","2029");
INSERT INTO `student` VALUES("102283160012","ASUNCION","ELIEZHA LOUISE","AGUSTIN","F","7/7/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("102283160023","PENULLAR","HANNAH JODI","SENADO","F","11/21/2010","ALTERNATE","FULL","2029");
INSERT INTO `student` VALUES("102283160024","SIMON","LIA SAMANTHA","BRAVO","F","6/13/11","LATERAL","","2029");
INSERT INTO `student` VALUES("102283160055","SALVADOR","RJ LIE","GEPANAYAO","F","6/23/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("102283160196","REVILLA","JERSHELL HAN","AGUSTIN","F","9/15/11","LATERAL","","2029");
INSERT INTO `student` VALUES("102283160334","CABAMONGAN ","ROXANNE JOYCE ","DADIVAS ","F","12/14/2010","ALTERNATE ","P1","2029");
INSERT INTO `student` VALUES("102283160338","PERALTA","MATH R STHYPHYN LORDWYGNE","TABION","M","9/22/2011","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("102283170022","CUERDO","KRISTEL ANN","SALVADOR","F","09/09/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("102283170028","GINEZ","JEANINE RASJA","MARZO","F","6/27/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("102283170088","CASTAÑEDA","JIREH ALLYAH","PADUA","F","11/05/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("102283170111","DELA CRUZ ","MHAYELLE ANGELINE","ADUCA","F","11/29/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("102283170114","PARAMIO","REINE DIANE","CABOTAJE","F","1/30/2012","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("102283170197","ANTONIO","ABEGAIL","BALLESTEROS","F","11/23/2011","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("102283170213","VILLA CRUZ","HANNAH GRACE","ULALAN","F","01/06/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("102283190102","VILLANUEVA","MIGUEJEV JOSHUA","ROCABO","M","5/9/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("102306150038","MAMARADLO","ALEXA NICOLE","TEJADA","F","","LATERAL","","2028");
INSERT INTO `student` VALUES("102311130104","SANTOS","AYANNA ALEXABEA","LORETO","F","07/25/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("102608130008","PALACAY","REYNANTE","APOSTOL","M","07/20/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("102608130063","GABRIEL","MARK ANGEL","MARZAN","M","01/14/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("102608140080","TOMAS","KAREN HILLARY FRANZCEN","GRANDE","F","08/20/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("102608140096","RABBON","IVY JANE ","CASTILLO","F","6/1/2009","ALTERNATE","FULL","2027");
INSERT INTO `student` VALUES("102608150031","BALINTEC","QUEEN ANGEL","SAGUN","F","12/28/2009","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("102608150042","PASTOR","RAIAN VON","AGARPAO","M","2/15/2009","PRINCIPAL","FULL","2027");
INSERT INTO `student` VALUES("102608160013","EDRA ","ZYAN VIELLE ","VILORIA","F","6/20/2011","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("102608160017","SUGUITAN ","ALIYAH REIGN ","PABLO ","F","10/18/2010","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("102608190015","TAMBOA","KHEILA CAZANDRA","CORPUZ","F","7/3/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("102609190006","RACCA","XIAN VHIEN","TURDA","M","9/14/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("102616180004","AGRA","CARL WESLEY JOHN","CASTILLO","M","1/13/2012","ALTERNATE","P2","2030");
INSERT INTO `student` VALUES("102618170008","PACLOB","VEENA MARIELLI","UTRERA","F","04/09/2012","ALTERNATE","P3","2030");
INSERT INTO `student` VALUES("102627190005","CARAG","CHARLES DWYANE","SANIDAD","M","5/16/2024","ALTERNATE - CVC","","2032");
INSERT INTO `student` VALUES("102859140057","BATTULAYAN","AZELLE MARIE","DOMINGO","F","08/29/2007","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("102861180034","MUAN","ZYRYLLE MAE","","F","5/31/2012","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("102918140003","BALLION","RAYGAN JR.","CALEJA","M","8/19/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("102922130019","AGPULDO","FD BENJAMIN REI","AGTANG","M","06/06/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("103840190170","MENDOZA","XZAVIER NIKLAUS","DOMINGUIANO","M","3/13/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("104012180009","IBE","SEYANTH MARGHELLE COLEEN","RAMEL","F","11/9/12","PRINCIPAL","FULL","2031");
INSERT INTO `student` VALUES("104406120559","MERCADO","KARL ISAAC","VIDAD","M","11/18/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("104988130064","RACHO","RAPH MICHAEL","DELA CRUZ","M","12/04/2006","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("105891170022","VILLANUEVA","ART JABEZ","CORNELIO","M","8/17/2012","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("106392190009","SEVILLA","GABRIEL ACHILLES","LAXAMANA","M","7/7/2014","ALTERNATE - CLC","","2032");
INSERT INTO `student` VALUES("106797190043","AGUSTIN","MIKAELLA","ONG","F","12/25/2013","ALTERNATE - CLC","","2032");
INSERT INTO `student` VALUES("107165150420","JAVILLONAR","CYREINE MEIR","SANCHEZ","F","8/23/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("108470130282","FARINAS","STEPHANIE","CARLET","F","04/16/2008","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("108863190123","OBMERGA","MAILA HENRIETTE","LABAGNOY","F","3/9/2014","ALTERNATE - CLC","","2032");
INSERT INTO `student` VALUES("109494150013","PAGATPATAN","CHARLIE","GUEVARA","M","","ALTERNATE","P2","2027");
INSERT INTO `student` VALUES("112011190037","BALAJADIA","ALLIAH SHYLA","BALOLOY","F","8/5/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("120010150340","FAGELA","JAN FRANCIS","DAJAO","M","1/1/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("12345","Ngayaan","June","Maog","M","1995-06-14","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("130010130022","HADUCA","CZARINA JOY","CORPUZ","F","08/14/2008","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("134979130012","GATAN","RALPH LOUIE","OREILLY","M","01/08/2007","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("134979130055","REBAULA","ZANDREI RYLLE","BAÑEZ","M","11/15/2007","PRINCIPAL","P1","2026");
INSERT INTO `student` VALUES("134979150087","ROSARIO","KRISTAL FAITH","DEL PILAR","F","12/16/2008","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("134979150113","BAAL","JAN FRANCIA","VIADO","F","2/19/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("135008160010","BISQUERA","ROWELY","PISCO","F","12/12/10","LATERAL","","2029");
INSERT INTO `student` VALUES("135019090028","DACQUEL","HEZIKIAH","EDUARTE","M","3/19/2004","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("135033130005","BRAGAS","PAOLO SABINO","ESCALA","M","02/23/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("135163110041","VIADO","CHRISTINE JOY","BERNESE","F","12/24/2005","ALTERNATE","P1","2024");
INSERT INTO `student` VALUES("135163130055","VIADO","KRIXIA AYNE","BERNASE","F","11/25/2007","LATERAL","P2","2026");
INSERT INTO `student` VALUES("135245190031","CENU","GWYNETH NAOMI ARVA","BAGASOL","F","10/4/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("135343190015","CAOLE","AMIRAH LIA","BATAY-AN","F","12/30/2013","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("136396120108","AMISTAD","JULYNA PEARL","NAOE","F","09/21/2007","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("136397170033","ARCONADO","ZACCHARY JHAY","TABAYAG","M","7/19/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("136417150034","LLANES","JIAN MARQO","ROSAL","M","6/25/2009","PRINCIPAL","P1","2027");
INSERT INTO `student` VALUES("136417190041","REGLOS","JOAQUIN PABLO","BANTUGAN","M","11/8/2013","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("136457180369","ISIDORO","JAMILLE LAURICE","CALANTOC","F","10/17/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("136457180370","ISIDORO","JILLIANE CLABI","CALANTOC","F","10/17/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("136496190135","SACLAUSA","KHISHA FAITH","","F","10/28/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("136534130301","RAMOS","KELVIN ANGEL","FERNANDO","M","08/02/2007","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("136541190405","CAGANDAHAN","JUSTEEN JEN ROLIEJN","CASTILLO","F","11/5/2013","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("136574110163","SINGCO","JELAINE KATE","SAVELLANO","F","1/9/2006","LATERAL - G9","P2","2024");
INSERT INTO `student` VALUES("136574130500","SINGCO","LAUREN MAE","SAVELLANO","F","07/19/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("136665130306","ANTIPORDA","KARLVIN","BEDUYA","M","02/14/2008","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("136676090140","EUGENIO","MAIA RUTH","SALUD","F","10/31/2003","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("136676140393","EUGENIO","MICAHL SOPHIA","SALUD","F","2008-11-08","LATERAL","P3","2027");
INSERT INTO `student` VALUES("136738120435","ALFILER","DDAVID DUNE","CASTRO","M","04/26/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("150522190016","SUMAGAYSAY","JUSTIN","SINGSON","M","5/29/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("200185120022","MACULAM","RALPH JHENRIECH","BAPTISTA","M","12/30/2006","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("219010150021","CACERES","GENOAH DARIEL","LIBATIQUE","M","12/18/2008","PRINCIPAL","","2027");
INSERT INTO `student` VALUES("400003160003","APUAN ","KCHYEAN RYE ","VILLA ","M","10/28/2010","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("400005150014","RAGONJAN","AYESHA PAULINE","DAGDAGAN","F","10/15/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400008150032","RAMON","CHLOE YZABELLE","DARISAN","F","6/6/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("400008150142","AGANUS","JANN KELLY","BADANGUIO","M","12/1/2003","ALTERNATE","P3","2022");
INSERT INTO `student` VALUES("400010150062","AGULAY","MA. LEA JULIANA","QUIAOIT","F","05/17/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400010150097","BUMANGLAG"," FITZ LORD OWEN","","M","11/14/2005","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("400010150100","DELA CRUZ","FRITZ GABRIEL","BUMANGLAG","M","9/20/2005","ALTERNATE","P3","2024");
INSERT INTO `student` VALUES("400010150149","MALIGSAY","KYN ACHILLES","SAGUN","M","11/6/2004","ALTERNATE","FULL","2023");
INSERT INTO `student` VALUES("400011150031","TAGABI","SYOVHAN DENISE","MACUSI","F","2010-05-20","LATERAL","P3","2028");
INSERT INTO `student` VALUES("400011150072","NOLASCO","JOHNZEN ACE","ANDRES","M","09/25/2007","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("400011150086","VALENZUELA","SAMANTHA KYLE","YAGO","F","01/14/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400011150097","TAGABI","SEBASTIAN DYRK","MACUSI","M","12/18/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("400024150032","SULAYAO","LUKE ALEXANDER","VALDEZ","M","08/18/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400024160029","SULAYAO ","NATHALIE RAINE ","VALDEZ","F","11/10/2011","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("400027150008","ARGUELLES ","VICTRISHA ANNE ","NAVARRO ","F","10/13/2011","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("400027150012","FERALES","MYEISHA RHIANNE","BENAVIDEZ","F","1/24/2011","PRINCIPAL","FULL","2029");
INSERT INTO `student` VALUES("400027150072","TOLENTINO","SAMANTHA NICOLA","PALOMARES","F","09/11/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400027150778","FLORENDO","LIAM GRANT","RAMIREZ","M","10/28/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400027150781","GUIRA","JANOS IÑIGO GABRIEL","GILLEGO","M","7/30/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("400027150788","ALBANO","KASSANDRA ELLEANNA","CAMACHO","F","1/28/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400027150810","DIRECTO","MARIANNE CHRISTINE","DE VERA","F","11/28/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400027150825","PO","ATHAN ELIJAH","CHAN","M","2010-07-05","LATERAL","P3","2028");
INSERT INTO `student` VALUES("400027150834","CABANG","THERESE ANGELA","ROSALES","F","11/8/2009","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400027150836","FORMOSO","ZYAN INGRID","ARDIENTE","F","3/1/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400027150842","TAJON","JIREH PATRISSE","TABANGCURA","F","1/10/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400027150854","CALSIS","ARICCIA MARI","RAGUINDIN","F","4/17/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400027150855","GADIA","CATHLYN YZABELLA","PRADO","F","6/20/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400027150863","BORJE","LARDY ZEDRIXE","BASCONCILLO","M","12/23/2009","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("400027150868","REFUERZO","LENIN VON","GRACIA","M","3/17/2010","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("400027150897","BERNASOL","SOPHIA REYRYLL","CARTA","F","2009-12-04","LATERAL","P3","2028");
INSERT INTO `student` VALUES("400027150901","ROMANO","YANA YSABELLE","PURISIMA","F","8/3/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("400027150903","TAPUCOL","JESSICA ELISE","RIALUBIN","F","9/9/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("400027150914","LAZO","VANISHA AERIN IVANKA YZABELLE","GANTE","F","3/16/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400027150921","VILLEGAS","MARTINA SIMONE","PALACAY","F","11/27/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400027150930","CABUG","FRANCIS IBSEN","TABARANGAO","M","4/15/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("400027150931","GO","JOHN ANGELO","SILVA","M","3/25/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400027150935","REANCHO","CRISTAN RUDGER","ROSALES","M","8/2/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400027150945","TAPUCOL","SAMANTHA LOUISE","RIALUBIN","F","9/2/2009","PRINCIPAL","Full","2027");
INSERT INTO `student` VALUES("400027150954","PESCADOR","JAMES DAVID","PASTOR","M","10/13/2007","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400027150964","NGO","THANH MAI","TACAZON","F","06/20/2008","PRINCIPAL","P1","2026");
INSERT INTO `student` VALUES("400027150993","CHAN","MIGHTY JET","GORRE","M","06/23/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400027150995","DE LA CRUZ","LORENZO SANTINO","RIALUBIN","M","","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400027151008","BALISBISANA","JAMIELA","VELASCO","F","04/10/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400027151010","PALISOC","WYNAMOR ANNICA","VITAMOG","F","03/23/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("400027151018","FLORENDO","JAMES GERALD II","RAMIREZ","M","11/2/2007","LATERAL","P3","2026");
INSERT INTO `student` VALUES("400027151056","ORTEZA","FLORAENNE JANINE","NAVASCA","F","01/29/2007","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("400027151062","SOLIVEN","JOANNA CHARMAGNE","TAGAYUNA","F","","LATERAL","FULL","2025");
INSERT INTO `student` VALUES("400027151107","BATIN","DARNELLI MARI","CRISTE","F","09/06/2006","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("400027151113","CHAN","RIESHA CAITLYN","LAO","F","10/16/2007","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("400027151123","AMOYO","LARISSA","MANZANAS","F","04/29/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("400027151140","TABULA","PHEE MARGARETTE","TIRA","F","06/14/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("400027151142","ANINAG","DANERALE","FAYPON","M","01/19/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("400027151148","PO","BRENT MATTHEW","CHAN","M","06/22/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("400027151171","AÑES","MARY CAITLIN KASSANDRA","RAMOS","F","9/22/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("400027151179","MANLAPAZ","JODI EMRIAN SALVACION ","BAGCAL","F","8/25/2005","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("400027151200","FIGUERRES","WINONAH MICHAYLA ","QUINTAL","F","2/25/2006","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("400027151201","FLORENDO","JAMIE ALTHEA ","RAMIREZ","F","2/15/2006","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("400027151251","ALMOITE","PATRICK LOUIS ","ALMAJANO","M","1/21/2006","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("400027151257","MORETA","GENESIS GABRIEL","DELA PEÑA","M","6/9/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("400027151258","PLANA","LAZARUS HURL","SEARES","M","12/18/2005","ALTERNATE","P2","2024");
INSERT INTO `student` VALUES("400027151267","VELASCO","GIO CARLO ","LAZO","M","6/12/2006","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("400027151300","RABINO","CLEIGEND NEIL","PICHAY","M","1/17/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400027151302","SABALBURO","LANZ ENRICO","LASMARIAS","M","4/18/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400027151305","ADAN","MEKAELA","REOTUTAR","F","8/2/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("400027151321","VARGAS","ZJMILKIAH REDWYNE","PIAMONTE","F","9/6/2004","ALTERNATE","P3","2023");
INSERT INTO `student` VALUES("400027151330","ISAGUIRRE","JAIRUS CARLE","CASTILLO","M","7/15/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400027151333","PAZ","ETIENNE RODERICK","BAJET","M","10/12/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400027151340","SATURNO","LORENZO MIGUEL","BUENAVISTA","M","11/24/2004","PRINCIPAL","P2","2023");
INSERT INTO `student` VALUES("400027151350","DOMENDEN","JEHAN","GANIBAN","F","1/8/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400027151355","NGO","MINH THU","TACAZON","F","8/6/2005","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("400027151380","AGDEPPA","ALYSSA GABRIELLA CAMILLE","ISAIS","F","8/30/2004","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("400027151413","VELASCO","IVAN LUIGI","LAZO","M","2/26/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400027151448","REALIN","NINA LOUCHEL","RIVERA","F","5/9/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400027151449","ALEGRE","KATRINA ALEXANDRA","PONCE","F","11/5/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("400027151488","NAVARRO","CHICCO GABRIELLE","MONTEMAYOR","M","3/14/2004","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("400027151500","BANIQUED","EIRENE DOMINICA","PLETE","F","6/26/2004","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("400027151516","CHUA","MA.KRISLAINE CHLOIE","VARILLA","F","1/26/2004","PRINCIPAL","P2","2023");
INSERT INTO `student` VALUES("400027151521","SESUCA","ELLA KATRINA","ROSARIO","F","10/5/2003","ALTERNATE","P2","2022");
INSERT INTO `student` VALUES("400027151538","GREGORIOS","LJ MIGS","PACIS","M","3/26/2004","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("400027151539","LAYA","KURT ADRIAN","PICHAY","M","5/25/2004","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("400027151552","ABERGAS","JEWEL ANN","ARTATES","F","9/18/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("400027151557","CHAN","RAYA COLLEEN","LAO","F","3/16/2004","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("400027151564","ONTIVEROS","MARY THERESE","PUNIO","F","5/13/2004","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("400027151571","YAGO","JAN MARGAUX","GILLEGO","F","7/26/2004","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("400027160008","SOLOMON ","GERIC ALEXANDER ","BAZARTE ","M","10/30/2010","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("400027160016","PABLICO","ANGELA BEATRICE","RAPACON ","F","1/29/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400027160018","CALSIS ","AMEERAH RICCEL ","RAGUINDIN ","F","5/16/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400027160031","BASQUEZ","YHUAN CAYL","TACTAY","M","12/21/2010","ALTERNATE","P3","2029");
INSERT INTO `student` VALUES("400027160043","GAMILDE ","IVES THEODORE","REBULLO ","M","3/11/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400027160046","SOLIVEN ","CLYDE ","TAGAYUNA ","M","5/13/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400027160048","AVELINO ","ANNIAH DENNISE ","PAJARO ","F","9/17/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400027160054","PASTOR ","NICOLE ","ARCA ","F","4/28/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400027160065","ANDAY","FRANZIELLE ANGEQLIQUE","RAGUINDIN","F","12/22/09","LATERAL","","2028");
INSERT INTO `student` VALUES("400027160097","MANALILI","VINCENT ANGELO","TAGORDA","M","05/30/2007","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("400027170057","REALIN","SEV GUNDCEL","RIVERA","M","1/17/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400027170069","FLORENTINO","LUCAS","ASTOM","M","06/12/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400027170078","FAGEL","HAZEL ANGELIE","PUGAT","F","07/17/2007","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("400027170085","REOTUTAR","RIHANA FAYE","SAVELLANO","F","4/29/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400027170100","TABARANGAO","CHELSIE AVYNA","CALITRABA","F","8/26/2011","ALTERNATE","P1","2030");
INSERT INTO `student` VALUES("400027180009","ALBANO","RICK EZEKIEL","CAMACHO","M","5/11/13","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("400027180016","PLANA","LEMUEL HARI","SEARES","M","11/15/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("400027180017","QUITORIANO","FRED III","SACLAG","M","9/8/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400027180022","ITCHON","GABRIEL CONSTANTINE","RAGASA","M","3/22/13","PRINCIPAL","P1","2031");
INSERT INTO `student` VALUES("400027180033","FORTUNA","JEANA LIZ","RAYMUNDO","F","9/30/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400027180058","AVELINO","ANNABELLA DEIENE","PAJARO","F","5/30/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400027180071","LAITAN","LIAM JACOB","PADER","M","10/25/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400027180076","TOLENTINO","SAFIYAH GABRIELLE","PALOMARES","F","3/8/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400027180079","ALMERIDO","KRISH PAULEEN","FERINO","F","4/8/13","PRINCIPAL","P2","2031");
INSERT INTO `student` VALUES("400027180082","PALAPALA","JOSEPHINE","GARCIA","F","8/13/13","PRINCIPAL","FULL","2031");
INSERT INTO `student` VALUES("400027180087","SY","MIGUELL JANN","PIRA","M","8/7/12","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("400027190012","CORPUZ","NIGUEL YURI","PASTOR","M","4/27/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400027190016","VALDEZ","JEUS AZAIAH","MANZANO","M","12/22/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("400027190018","RANCHES","FRANCIS ANTHONY","QUE","M","12/21/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400027190039","ORTEZA","FROLAENNE JANELE","NAVASCA","F","1/11/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400027190044","BERGONIA","MERCENARIO","DONATO","M","1/4/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400027190060","SATURNO","IÑIGO MIGUEL","BUENAVISTA","M","1/14/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400027190064","QUITORIANO","CHLOE","SACLAG","F","5/12/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400027190068","TAJON","ALTHEA RAPHA","TABANGCURA","F","6/29/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400027190070","FULLER","DARREN ACHELAUS","FONTANILLA","M","11/28/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("400031150017","DUCUSIN","ANGELA GRACE","","F","2/22/2010","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("400031150056","FORTUNA ","JOHN LIAM","RAYMUNDO","M","11/2/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400031150106","RODRIGUEZ","CLARA MA. FRANCESCA","VALDEZ","F","8/5/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("4000311501115","COLCOL","SHIRLEY MAY","GALACIO","F","03/10/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400031150129","RODRIGUEZ","GERARDO MARCO","VALDEZ","M","02/24/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400031150191","REBOLLIDO","JOHN MOISES","DANIOAN","M","","LATERAL","","2025");
INSERT INTO `student` VALUES("400031150249","COLCOL","DRANREB JES","GALACIO","M","12/24/2005","ALTERNATE","P3","2024");
INSERT INTO `student` VALUES("40003115032","ALADIN","DONN JONATHAN CHARLES","JARDIN","M","9/9/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400031150327","CHUA","SHANE MARSEY","NAVARRO","F","12/28/2004","ALTERNATE","P3","2023");
INSERT INTO `student` VALUES("400031150350","WU","ALLEN DAVID","ABAYA","M","11/16/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("400031150416","BELLO","GABRIELLE MARIE","CORTES","F","11/29/2003","ALTERNATE","P3","2022");
INSERT INTO `student` VALUES("400031150427","FORTUNA","JESCIA LORIN","RAYMUNDO","F","10/3/2004","ALTERNATE","P3","2022");
INSERT INTO `student` VALUES("400031170010","TUNGPALAN","CARL KENZO","FRESNOZA","M","12/29/2011","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("400031180036","BELLO","DON JETHRO","CORTES","M","10/24/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400037190007","BOBON","JUAN MIGUEL","TUQUIERO","M","10/5/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400039150178","ALONES","JOHANNAH YVONNE","DAÑO","F","5/20/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400039150348","BURGONIO","RAYMART BIEN","BALALLO","M","3/3/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400039150570","AQUINO","KURT STANLEY","PAREL","M","2010-02-13","LATERAL","P3","2028");
INSERT INTO `student` VALUES("400039150581","DUCUSIN","MARIAN CHRISTINA","TAGAYUN","F","6/4/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400039160026","AGDEPPA","JOAQUIN LORENZO","CHUA","M","07/02/2011","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("400039160039","ORBETA","QWYNN OAUIE","LOZANO","F","8/16/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400039180006","FLORENDO","MATTHEUS","DIGA","M","8/1/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400049180024","GINES","JANA KYLIN","BANGAYAN","F","3/9/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400052150382","RACHO","KURT JHAIRUZ","SISON","M","2/19/10","LATERAL","","2028");
INSERT INTO `student` VALUES("400056190001","BALDERAS","JOBHAL","COLOMA","M","9/11/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400060150020","NG","NATHAN PIERRE","LAO","M","4/27/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400061150029","CONCEPCION","GABRIEL","QUIÑOLA","M","6/22/2010","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("400061150282","PASION","CHRISTINE JAMIE ","ALCARAZ","F","12/20/2005","ALTERNATE","P3","2024");
INSERT INTO `student` VALUES("400061190012","JURADO","TIFFANY GRACE","LAPID","F","10/21/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400067150179","RANCHES","FRANC LOUIS ","ABALOS","M","11/1/2004","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("400076180009","FAJARDO","MAIRIN PAULA","BUEN","F","5/2/13","ALTERNATE","","2031");
INSERT INTO `student` VALUES("400081160012","SIAPNO","QUIBEN RAYDEN","REYES","M","10/11/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400091180001","BODESTYNE","LEYAM","BIE","M","9/21/13","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("400101150153","SABADO","MIEKO ALLYSON","PEDRO","F","1/9/2011","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400101190018","GOLDARA","ZOILA GWEN","CATUNGAL","F","2/3/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400101190050","LAM-OSEN","XIFEL","PELIAS","F","12/8/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400102160018","SOBREMONTE","AVRYN GLODESIND","ARREOLA","F","3/21/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400110150190","FIESTA","LLEARA CHRISTEL ROVEA ","LOMIBAO","F","12/8/2005","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("400110150235","DY","RONALD ROBBIE","SY","M","3/24/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400112150123","FERNANDEZ","JADE ERIN","ALMOITE","F","04/13/2007","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("400112150126","MERIN","DANIELLE MAXIMA","PINZON","F","12/19/2006","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("400112150584","ANDRES","LEIAN LORELY","","F","12/6/2004","ALTERNATE","P1","2023");
INSERT INTO `student` VALUES("400112150811","ERGINO","MARIA CHASTINE","NONESA","F","5/19/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400112150856","NAVOR","FRANCES ALEXA","MARAÑON","F","11/23/2009","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("400115150179","MARANAN","GREGORY JR.","CARULLA","M","","LATERAL","","2026");
INSERT INTO `student` VALUES("400115150202","ALCONCEL","CARLOS DAVID","FORMOSO","M","01/30/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("400117150221","TORIO","RHYNE CHESTER","ALMIROL","M","12/6/2003","ALTERNATE","P1","2022");
INSERT INTO `student` VALUES("400120150033","CHU","JAPHETH JAYDEN","DELA CRUZ","M","11/28/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("400126160001","ARENAS","PAUL ARKEEN THEO","ESTOQUE","M","5/29/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400135150284","RABAGO","AISIZ","DE VERA","F","9/19/2003","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("400135160015","ANCHETA","LYRRA ANTONETTE","GA","F","08/03/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400144150035","VENTURA","ERICH MARENEL","TAAN","F","3/3/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400144150051","ALBINO","ANIKA YUMI","PANINGBATAN","F","04/09/2006","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("400144150076","VENTURA","ERIKA HEART","TAAN","F","4/19/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400154150005","GILMO ","EZEKIEL JERAYNE ","PATUNGAN ","M","5/13/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400155150137","JUNIO","ISABELLA KEITH","RANAY","F","1/9/10","LATERAL","","2028");
INSERT INTO `student` VALUES("400178190004","BUGARIN","MATTHEW ANDI VAN","MISLANG","M","1/6/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400181150105","MANLONGAT","LOUISE GERARDINE","CUISON","F","2010-08-11","LATERAL","P1","2028");
INSERT INTO `student` VALUES("400193150067","AQUINO","PRINCESS DIANE","BASCAO","F","9/9/2009","PRINCIPAL","FULL","2027");
INSERT INTO `student` VALUES("400193150070","GENTELIZO","JEIANAH","ANTONIO","F","11/4/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400193170013","ANQUILLANO","JAMES","AGUILAR","M","9/27/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400210150343","DELA PEÑA","JOHN ROFEL","FERRER","M","6/25/2005","PRINCIPAL","P2","2023");
INSERT INTO `student` VALUES("400210150346","ESTRADA","RUIZ GABRIELL","PEDROSA","M","8/31/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("400210150547","AQUINO","CARL JOSHUA","PLACIDO","M","12/26/2007","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400210150601","FERNANDEZ","HASADIAH","SERRANO","F","09/21/2007","PRINCIPAL","FULL","2026");
INSERT INTO `student` VALUES("400210150725","LOPEZ","ZAHRALLAIN","GARCIA","F","11/9/2010","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("400210190012","ROQUE","JOHN EZEKIEL","DELA PENA","M","5/16/2014","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("400227190047","OSORIO","OLATHEA NAOMI","MASIGLAT","F","8/6/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400233160002","ALCAIN ","SEAN JARED ","RUGNAO ","M","8/12/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("400233160005","MARTINEZ ","MARK DENVER ","FRANDO ","M","4/21/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400233160008","CORONEL ","MOIRA NICOLE ","GAMIT ","F","6/22/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("400233160020","FORTUNA","LUCAS JOSEEM","PERUNA","M","7/28/12","LATERAL","P1","2030");
INSERT INTO `student` VALUES("400233160036","STA. MARIA","KHALELLE CHRISTINE","CREDO","F","12/23/11","LATERAL","","2030");
INSERT INTO `student` VALUES("400233170013","AGABIN","ANNELIESSE","ZAPATA","F","11/14/11","LATERAL","FULL","2030");
INSERT INTO `student` VALUES("400233180010","PADRON","KHANLY OIRAM","PACIS","F","10/2/12","ALTERNATE","P2","2031");
INSERT INTO `student` VALUES("400253180031","ESCAÑO","KILAUEA AYHESSA","SANCHEZ","F","7/8/20214","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("400258150171","TAMONDONG","EETHAN EMMANUEL","LOPEZ","M","5/19/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400258150432","DE GUZMAN","ERVIN PETER","FABIAÑA","M","6/6/2006","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("400258150476","VILLOSTAS","CHYNELLE ZIARAH","CALIMLIM","F","11/13/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("400258180039","FABIA","CARLO GREGORICHIV","SABIT","M","4/3/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400261160014","VIDAL","HANNIKA JOLEE","NABUA","F","10/6/2008","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("400263150054","ESTIMADA","ALAINA YUMIRA","JULIAN","F","5/22/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("400267150076","PINLAC ","PRINCE WILLIAM ","BAJO ","M","3/29/2010","ALTERNATE","","2028");
INSERT INTO `student` VALUES("400267150082","BALTAZAR","JHOE LLEANNE","ALEGRE","F","11/5/09","LATERAL","","2028");
INSERT INTO `student` VALUES("400267150133","VILLANUEVA","JACOB ZACH","GANAY","M","2/27/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400267150155","CALIMLIM","CARMELA JEANNE","FERNANDEZ","F","12/8/2007","LATERAL","P3","2026");
INSERT INTO `student` VALUES("400267150267","CRUZ","SERGEI RAFAEL"," MAMARIL","M","7/6/2005","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("400267150342","TIRAO","JAIPSALMER","NERIZON","M","9/13/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("400267150414","QUINTO","DAPHNE BLOSSOM","SARZABA","F","6/12/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("400267150765","ANG-ANGCO ","RODOLFO III","TIGNO ","M","7/25/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400267150799","SISON","PRINCESS KYLIE ","BAUTISTA ","F","12/17/2010","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("400267170002","BALTAZAR ","GEOF JOAQUIN","ALEGRE","M","4/16/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400267180027","LAGUITAN","LUKE EDWRICK","DAROYA","M","7/18/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400269150179","TOLENTINO","GIANNA MICHAELA ","JIMENEZ","F","1/7/2006","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("400270150306","FERRER","KRISTINE MAE ","VILLARUZ","F","11/15/2004","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("400270150384","VALLO","DANIELLA","LANDINGIN","F","12/13/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("40027150977","AGDEPPA","ATHENA SOFIA CHRISTINE","ISAIS","F","05/08/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400274150021","IMPROGO ","ROSALIE BERNICE ","CABUANG ","F","2/23/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("400274150195","FABITO","KYLA MAE","MARIÑAS","F","10/08/2011","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("400276150005","NARCISE","ETHAN LEM","QUETURAS","M","07/11/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400276150007","PATRICIO ","JAMILA SOPHIA ","ABAD","F","6/16/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("400276150008","RANADA","BARTOLO","SANTIAGO","M","1/19/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400276150009","SALUD","EIKA GRACE","PASCUAL","F","11/09/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400276150030","RANADA ","ALFONSO ","SANTIAGO ","M","11/12/2012","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400276150046","BAGUIAO","ZABINE CHLOE","SIMON","F","7/16/2010","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("400276150053","LUCAS","MELRJEWEL JETTHRO","SABUG","M","9/14/2009","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400276150056","PATRICIO","JANIEL CHLOE","ABAD","F","11/24/2009","ALTERNATE","FULL","2028");
INSERT INTO `student` VALUES("400276150060","SERRAON","ENRIKA GABRIELLI","BUMANGLAG","F","12/27/2009","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("400276150105","BARCENAS","ZYRUS JOSE","ULEP","M","04/04/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("400276150162","CANSANCIO","SEDRIC EDMAR","LACAR","M","8/12/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400276170020","TUMANENG","RICHARD LOUIS","NAVALTA","M","05/03/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400276180002","ANCHETA","MATT LOUIE","TAMAYO","M","2/14/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400276180022","MAGUNDAYAO","XAVIER NATE","GAOIRAN","M","11/25/12","ALTERNATE","","2031");
INSERT INTO `student` VALUES("400276180024","PEDRO","GRACEAL ALVHEE","LINGAN","M","10/12/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("400276190008","PATRICIO","JAMIE SHEY","ABAD","F","4/30/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400277150118","GARDUQUE","DOUTZEN","PIZA","F","06/03/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("400277180015","TAPEC","ZABREENAH FAE","ACAPUYA","F","9/1/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("400278150323","BUDUAN","ZYANN ABCDE ","CACPAL","F","9/26/2005","ALTERNATE","P1","2024");
INSERT INTO `student` VALUES("400280150016","BALTAZAR ","ARRIANA XENILLE","BALANAY ","F","12/22/2010","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("400280150030","MAPUGAY","PRINCE JWILL","PASCUAL","M","11/11/2009","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("400280150068","LORENZO","HANS CHRISTIAN","ASUNCION","M","12/23/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400280150083","JAYOMA","NAAIL JAM","Felipe","M","05/10/2008","ALTERNATE","P1","2026");
INSERT INTO `student` VALUES("400280150093","GARVIDA","ADRIENNE","TOLENTINO","F","08/29/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400280150102","HERMANO","VERNIEL KING","PASCUA","M","04/09/2007","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("400280150109","ESTAVILLO","HYACINTHE RIOFEL","VALDEZ","F","07/25/2007","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("400280150129","CO","KIMI RYANNE","RAMOS","F","06/23/2007","PRINCIPAL","P1","2026");
INSERT INTO `student` VALUES("400280150144","MILAN","VLADIMIR ","SORIANO","M","7/30/2006","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("400280150156","WAPAN","MAXINE RUTH ","AGUSTIN","F","12/14/2005","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("400280150158","AGUSTIN ","MYKE GENRY ","MALIJANA","M","10/23/2005","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("400280150174","ROSARIO","AUDREY JASMINE","PAGAL","F","3/31/2006","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("400280150198","GAMAYO","SAMANTHA CHLOE","ACOSTA","F","10/2/2004","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("400280150204","SALAZAR","KARYLLE ANNE","SALVADOR","F","5/1/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400280150230","CASTILLO","ALEXANDRA","LAFORGA","F","9/1/2003","ALTERNATE","P3","2022");
INSERT INTO `student` VALUES("400280160009","DELA CRUZ ","APOLLO DEVON PIETHRO","CO","M","9/15/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400280160013","GUILLERMO","ANTI-NEPHI-LEHI","DAQUIOAG","M","11/15/2011","ALTERNATE","P2","2030");
INSERT INTO `student` VALUES("400280160026","GELERA","JAMIR ANDREI","VISITACION","M","4/19/13","ALTERNATE","","2031");
INSERT INTO `student` VALUES("400280160032","ROBLES","JOVAN WESLEY","BUMANGLAG","M","9/15/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("400280160038","ESTAVILLO","SHAOELLE HERSHEY","VALDEZ","F","10/11/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400280180001","ANICETE","ZACH HARVEY","NAGATA","M","4/23/13","PRINCIPAL","P2","2031");
INSERT INTO `student` VALUES("400280180004","ACOSTA","EREN CASTER","AGUSTIN","M","9/2/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("400280180010"," ASUNCION","HANNAH IRISH","DOMINGO","F","10/17/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("400280180013","AMISTAD","JURIZ","QUIOCHO","F","1/5/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400280180021","CALAPIT","JADEL ANDRE","RAMOS","M","11/19/12","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("400280180023","LORENZO","HUNIO MIGUEL","ASUNCION","M","6/13/13","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("400280180026","VILLANUEVA","ADANNA JHAENYL","SIBAYAN","F","2/22/13","ALTERNATE","","2031");
INSERT INTO `student` VALUES("400280180028","BAGCAL","MA. RAE ANGELI","SIBAL","F","10/19/12","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("400280180030","GUILLERMO"," DIDACHI KAI DIATHIKES","DAQUIOAG ","F","4/25/13","ALTERNATE","P2","2031");
INSERT INTO `student` VALUES("400280190005","ESTAVILLO","LEOJ VAN SOLO","VALDEZ","M","12/18/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400280190007","MANALO","GUILLERMO JESUS","CARIAGA","M","1/7/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400280190009","MIGUEL","DYLAN APOLLO","GAMENG","M","7/29/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("400280190013","GARVIDA","ALFRED VINCENT","TOLENTINO","M","3/22/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400282160021","RASCO","AZHIYA LYRIC","PASCUAL","F","07/05/2012","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("400282160024","ROGEL","YSABELLA ANN RAINE","DANCEL","F","10/16/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400282180009","PALAFOX","CALYX ATHAN","GACES","M","9/11/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("400287190004","RUIZ","FERDINAND LEO","MARTILLANO","M","11/29/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400288150044","BENIGNO ","SHYNEEZA RHYN ","BULONG ","F","7/3/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("400288150055","ALEJANDRO ","JOHN CARLO ","AGUSTIN ","M","1/4/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400288150056","CABALLERO ","FRANCO POLO ","BALOALOA","M","5/7/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("400288150062","DORONIO ","MIKAELA LORAINE ","PALALAY ","F","3/19/2011","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("400288150064","MARTIN ","YURI VENICE ","MARTIN ","F","3/2/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("400288150099","YAPO","RHIAN MARGAUX","CASTRO","F","1/5/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400288150161","TAPAOAN","THERESE ISABEL","LAVARIAS","F","8/9/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400288150275","POBLACION","HANNAH DAFFODIL","PANTE","F","09/08/2007","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("400288150388","DOCTOLERO","DIXON ","GANOTISI","M","6/15/2006","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("400288150412","VERGARA","LAWRENCE","HERNANDEZ","M","3/18/2005","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("400288150501","EDRA","TRACY JOY","RESPICIO","F","6/26/2004","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("400288160008","GASPAR ","MC ZIAN ","AGBALOG ","M","7/30/2011","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("400288160010","BORROMEO","ANGELINA JOYCE ","ATUD ","F","11/10/2010","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("400288160024","JAVIER ","SYDNEY JHORIEN ","MATEO ","F","2/7/2011","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("400288170003","DOCTOLERO","BRYDEN JAZZ","TUMANENG","M","5/21/2012","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("400288170008","BULONG","CHLOE ANNE","FLOJO","F","2012-06-07","LATERAL","FULL","2030");
INSERT INTO `student` VALUES("400288170020","ALBERTO","KHALEL ALGREN","MATUTE","M","07/09/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("400288180022","ASUNCION","KASHARA FEMME","VALENCIA","F","12/7/12","ALTERNATE","","2031");
INSERT INTO `student` VALUES("400288180039","JAMES","ABRIENNE DHARLENE","DABALOS","F","8/10/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400288190009","VICENTE","JAMES LORENZ","MATEO","M","4/24/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400289150084","MANGAOIL","FRANCES PAULEEN ","PASION","F","9/28/2005","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("400289150107","PIAWAN","ALIYAH LHUK","BARANGAN","F","11/16/2005","LATERAL","P3","2023");
INSERT INTO `student` VALUES("400289160002","ALCARAZ","AZTON JULIAN","LAZO","M","7/4/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400289170005","MANUEL","ACE BYANT KOBE","CAGAOAN","M","11/03/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400289190002","ANCHETA","ISAIAH LIAM","ESPEJO","M","2/9/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400293150015","PAMEROL","RIVECA","TAN","F","2009-02-04","LATERAL","P3","2027");
INSERT INTO `student` VALUES("400293150048","KAU","CARL ADRIAN","DELA CRUZ","M","9/30/2003","ALTERNATE","P2","2022");
INSERT INTO `student` VALUES("400293190007","THIBODEAU","JAYCE","DOMINGUEZ","M","8/8/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400298150133","ROSARIO","NATHANIEL ","GINES","M","8/29/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400298150264","PERALTA","ZUFIA TZIETEL ","FERMIN","F","7/19/2005","ALTERNATE","P3","2024");
INSERT INTO `student` VALUES("400300150587","SABANGAN","RODRIGO JR.","ALTERADO","M","03/24/2008","PRINCIPAL","P1","2026");
INSERT INTO `student` VALUES("400300150633","SABANGAN","EMMANUEL ERIK","ALTERADO","M","9/22/2005","LATERAL - G9","P1","2024");
INSERT INTO `student` VALUES("400303150083","DANGAT","WYNETH ANN","SOLIS","F","","LATERAL","","2026");
INSERT INTO `student` VALUES("400303150084","FERRER","ISSEY JHESTIN","CALANGIAN","F","4/22/2008","LATERAL","P3","2026");
INSERT INTO `student` VALUES("400303150162","AGBUYA","TATIANA KAYELA MARI","BAUTISTA","F","6/18/2003","ALTERNATE","P1","2022");
INSERT INTO `student` VALUES("400303150164","BIGAY","AMARNA ELAINE","ROSARIO","F","5/28/2003","ALTERNATE","P3","2022");
INSERT INTO `student` VALUES("400309190013","POQUIZ","JYLLEANNA VIENNE","ACOSTA","F","9/8/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400313150226","DEOLA","VESTA SOPHIA","CANILANG","F","3/15/2004","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("400313180017","CAOILE","JANNA MEIL","RESUELLO","F","9/1/2012","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("400315150065","SALAZAR","SOFIA VENICE","NGO","F","6/14/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("400315150125","SALAZAR","JOSH","NGO","M","3/11/2006","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("400315150132","DELA CRUZ","JERVI ELIJAH","SOLIVEN","M","3/2/2004","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("400315150137","BEGONIA","ABEGAIL","FERNANDEZ","F","8/2/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("4003191501612","TOMINES","ZYRE GABRIEL RAIN","GODOY","M","10/29/2006","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("400319150371","JARA","JOANNA MARIE","","F","12/16/2009","ALTERNATE","P1","2028");
INSERT INTO `student` VALUES("400319150505","PILLOS","CLARIZ","SINAMBAN","F","11/13/2005","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("400319150624","VALDEZ","GABRIEL JAMES","","M","4/20/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("400319150692","RIVERA","MARC","CADAVA","M","5/17/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("400319150706","NATIVIDAD","NADINE","VILLANUEVA","F","2010-09-27","LATERAL","P2","2028");
INSERT INTO `student` VALUES("400319160050","VILORIA","LANAYA SAOIRSE","GARCIA","F","4/23/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400319170010","REYES","CHARICE MAE","LONGA","F","05/03/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("400319180001","EGUSQUIZA","JOSIAH WESLEY","SUYAT","M","1/22/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("400321160019","ALIMORONG ","JULIENNE LULI ","BUEN ","F","10/3/2010","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("400321180011","IDANAN","ELISHA REI","SUBIDO","F","8/8/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("400321190011","PACKER","THOMAS MATTHEW","BOMBAY","M","12/27/2013","ALTERNATE - CLC","","2032");
INSERT INTO `student` VALUES("400321190021","LOMBOY","CALISTA ANDRIANNA","CHING","F","10/19/2013","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("400324150197","CUYO","JURRIEN KREIGRON","PURCIL","M","3/5/2011","PRINCIPAL","FULL","2029");
INSERT INTO `student` VALUES("400324160018","VALENCIA","GWENN JOY","MOYA","F","7/31/11","LATERAL","","2029");
INSERT INTO `student` VALUES("400363150038","RAVELO","ZAIDE EMMANUEL II","BALANAY","M","1/2/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("400363160003","ATIENZA","FRANCIS JINRO","DOMINGO","M","12/27/2011","ALTERNATE","FULL","2030");
INSERT INTO `student` VALUES("400363180010","RAMOS","ALFRED ZIANDRICK","PABONAN","M","1/13/13","PRINCIPAL","FULL","2031");
INSERT INTO `student` VALUES("400561190031","VENTURA","ALEXANDRA BEATRIZ","GALINDON","F","12/22/2013","ALTERNATE - CVC","","2032");
INSERT INTO `student` VALUES("400561190039","ASUNCION","JV IMMANUEL","CAUILAN","M","1/17/2014","ALTERNATE - CVC","","2032");
INSERT INTO `student` VALUES("400787160008","AGBISIT","CHELSEY","REYES","F","9/30/2011","ALTERNATE","FULL","2030");
INSERT INTO `student` VALUES("400787160037","ALONZO ","AIYESHA GWENNE ","MERU ","F","8/20/2010","ALTERNATE ","FULL","2029");
INSERT INTO `student` VALUES("400921180001","DAYAG","JURIS PETER","CABILES","M","6/19/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("401217150410","ETRATA","JANINA CHESCA","SANTOS","F","12/07/2006","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("401408150114","ULIBAS","CLYDE","ALCALDE","M","","LATERAL","","2026");
INSERT INTO `student` VALUES("401408170007","ULIBAS","CHLOE","ALCALDE","F","08/09/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("401515150761","DAVID","BIANCA FRANCINE","OLEGARIO","F","04/08/2006","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("401565150067","FLANDEZ","GRECKO ROCIO","CAVITEÑO","M","2/22/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("401665150663","SALAMANGKIT","ELISHA QUINN","TUBIEROS","M","7/7/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("402095150379","CALUYA","EISEN JEREMY","GALANO","M","7/24/2004","ALTERNATE","P2","2023");
INSERT INTO `student` VALUES("402871150829","LU","GENE LYXA","ROSALES","F","5/25/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("403092150295","ABAD","MICA ELLA","RAFANAN","F","6/1/2004","ALTERNATE","FULL","2023");
INSERT INTO `student` VALUES("403105150482","NOLASCO","BEA ALEXA","DELA ROSA","F","","LATERAL","","2026");
INSERT INTO `student` VALUES("406107150173","MAGALEM","KAICEE","LAPADA","F","03/03/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("406108150183","DAYAG","SHAAN GRAAL","CARIÑO","F","12/6/2004","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("406153190033","CAMBA","ZIA YSABELLE","DADPAAS","F","6/10/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("406211180127","ELACION","LAUREN FEI","REYES","F","10/11/13","ALTERNATE","","2031");
INSERT INTO `student` VALUES("406238150001","BATO","KAISER DALE","AQUITANIA","M","5/17/2010","ALTERNATE","P1","2028");
INSERT INTO `student` VALUES("406257150151","TABANGCURA","PRINCESS","SUMAGAYSAY","F","07/05/2007","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("406338151423","MANIBOG","CLYDE DENZEL","RAMOS","M","02/22/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("406393150227","ARTACHO","JULIANNE ROSE","PACIS","F","10/26/2003","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("406401150065","CERALDE","KLIVE FRANKIE","SANTOS","F","","LATERAL","","2026");
INSERT INTO `student` VALUES("406483190036","BALTAZAR","ALEXANDER JAMES","OLIVEROS","M","11/4/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("406512180003","AGUDA","NICHOLAS CASSIEL","TUBO","M","2/8/13","ALTERNATE","","2031");
INSERT INTO `student` VALUES("406555150292","REFUERZO","MANNAH ISABELLA","BUDAC","F","12/21/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("406555170008","PIPO","HAILEY JAZMINE","PINOL ","F","7/4/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("406583152223","VINLUAN","IRISH RYLE","TIBALAO","F","4/7/2021","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("406620150681","LLEMOS","AALIYAH","VACARIZAS","F","","LATERAL","","2026");
INSERT INTO `student` VALUES("406626150026","VALENTIN","JEAN IANCH LUMIERE","TOLENTINO","M","9/13/2003","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("406632150664","QUEROL","BEA MARIE","FANO","F","6/3/2005","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("406644150256","TOLOSA","CYRUS","PEÑASAS","M","09/16/2006","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("406678150711","VELASCO","PAZ MARGARETTE ","VERBO","F","11/10/2005","ALTERNATE","P2","2024");
INSERT INTO `student` VALUES("406905150504","TORINA","MONIQUE","ESPEJO","F","05/25/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("406905180102","SINGSON","ALYSON MARISSE","-","F","6/26/13","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("406908190194","AGARANO","JESSRELLE","CAPIRAL","F","1/10/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("406916150002","NAJORDA","LIANN CHLOE","TUNGPALAN","F","11/07/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("406946150570","RALLOJAY","MARIA LORWIN","RAFANAN","F","10/15/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("407032150511","RACADIO","ANDREI ELIJAH","PAUCHANO","M","01/03/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("407186150060","REYES","CARL CHESTER","TAGNAWA","M","2010-05-19","LATERAL","P2","2028");
INSERT INTO `student` VALUES("407211150341","BLANCO","EEZA MARGARET ","LOPEZ","F","4/26/2005","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("407330150013","CALIXTO","JUSTIN SETH","RAMOS","M","4/16/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("407330150030","CALIXTO","JEDIDIAH","RAMOS","M","09/21/2007","PRINCIPAL","FULL","2025");
INSERT INTO `student` VALUES("407827160025","REPATO","EARLAND JOSH","RABO","M","5/29/2011","ALTERNATE","P3","2029");
INSERT INTO `student` VALUES("407909170002","JOSEF","FRIXLLIE","MATEO ","F","12/04/2011","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("408143160001","CAINDEC ","AEIZEN MIEN ","DOMINGO ","F","7/5/2011","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("408262190008","BISTAYAN","SIENNA BLAIRE","RAMON","F","2/8/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("408783170028","SICADA","LIAM RAI","DAR","M","05/08/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("408836180006","TACLA","MAYRIEL ANN","SALCEDO","F","6/19/13","ALTERNATE","FULL","2031");
INSERT INTO `student` VALUES("410001150107","CABANES","JUNE MAVERICK","MACATO","M","05/27/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("410001150110","JOSE","MARQUIS DRE DANISH","LAGUATAN","M","05/21/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("410001150182","CAMACHO","ALEXIS NICK ","MACALIPIS","M","3/25/2006","ALTERNATE","FULL","2024");
INSERT INTO `student` VALUES("410001150205","GALO","SHANON FRANCES ","ASUNCION","F","6/1/2006","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("410001150272","PORTELA","ELYSHA FAITH","SEBASTIAN","F","10/2/2004","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("410001190022","SALES","CHLOE VERONE","AGUINALDO","F","4/7/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("410002180015","LEZORA","SOPHIA LEI","ALABA","F","12/3/12","PRINCIPAL","P1","2031");
INSERT INTO `student` VALUES("410005150139","GALANO","RUTH ABIGAIL","BASIG","F","01/29/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("410005150239","MANGLAL-LAN","ALEEZA KATJA","ABUNDO","F","9/16/2003","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("410005160010","SALCEDO ","JARETH JAMISON ","MELCHOR","M","1/9/2011","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("410005180061","LUMANG","JOHANNA RAFAELLE MIKYLIE","","F","12/15/12","ALTERNATE","","2031");
INSERT INTO `student` VALUES("410005190016","BALLESTEROS","JOURDANE ANGELA MAE","","F","5/1/2024","ALTERNATE","","2032");
INSERT INTO `student` VALUES("410007150028","BALUTAN","VINCENZO","JAVIER","M","2/12/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("410007150072","BALUTAN","GIORGIA ROSALINDA","JAVIER","F","08/03/2007","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("410013160007","NAGASANGAN","JAZELLE VERONICA","VALDEZ","F","8/18/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("410014150012","RAMOS","LLANNI REIN PAULEEN","GAJETON","F","1/31/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("410014150061","TABIJE","SHANLEY ABRIENNE","BLANCO","F","12/9/2010","PRINCIPAL","P2","2029");
INSERT INTO `student` VALUES("410014150063","ARELLANO","NATHANIEL WAYNE","CACHERO","M","11/12/2009","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("410014150073","GALUEGO","AMBER JADE","PABUSTAN","F","10/9/2009","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("410014150075","TACDERAN","MIA CAZANDRA LEY","BUDUAN","F","12/20/2009","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("410014150081","PERMISON","ZYAN MIGUEL","DOMINGO","M","12/1/2009","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("410014150085","AQUINO","CHANTEL GOLD","ABELLA","F","1/3/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("410014160002","SALAZAR ","MARTIN LEVI ALEXANDER ","BADIANG ","M","5/17/2011","ALTERNATE ","P2","2029");
INSERT INTO `student` VALUES("410014160033","BENSAN","JULIA MARGARET","ALEGADO","F","9/4/12","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("410014160038","PRIETO","CZARINA MIEL","CACHERO","F","1/22/13","PRINCIPAL","P1","2031");
INSERT INTO `student` VALUES("410014160041","TACDERAN","MARGAUX ALESSANDRA","BUDUAN","F","1/1/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("410014180005","ADORA","MARICON LORAINE","SISON","F","12/8/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("410014190009","LIJAUCO","SOPHIA ARABELLA","ALEGRE","F","11/9/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("410015150001","AQUINO ","JAZZIE KRAM ","SALCEDO ","F","3/20/2011","ALTERNATE","P2","2029");
INSERT INTO `student` VALUES("410015190005","MASALLO","JAYDEN DOMINIC","PERALTA","M","6/6/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("410017150003","AGBUNAG ","ANDREI VINCE ","DIZA","M","12/18/2010","ALTERNATE","P3","2029");
INSERT INTO `student` VALUES("410017160023","YBANEZ ","SHIKYNA MICAH ","CALIVO ","F","10/20/2011","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("410018150003","BONOAN","GUILLEN VICTORIA","TACOTACO","F","9/19/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("410018150011","PASION","JACOB MIGUEL","SALVADOR","M","11/14/2011","ALTERNATE","P3","2030");
INSERT INTO `student` VALUES("410018150039","GUIEB ","LANCE KEVIN ","ANDRES ","M","11/11/2010","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("410018160006","DIEGO","KHRYSTER GARNET","AGTARAP ","M","9/6/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("410018160011","LAZO","LUIZ ACHILLES","DOMINGO","M","8/28/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("410020150028","AGUSTIN","REESE JOLIEL","FORONDA","F","10/17/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("410020150047","BALINBIN","VENISE CRESTEL","MALVAR","F","04/19/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("410020190017","CACATIAN","SEAN FRANCIS","CASTRO","M","9/18/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("410022150020","ICALIA","SHEKINAH CAYLE","DOMINGO","F","3/25/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("410022180033","ULEP","AVANA DAINEL","JOAQUIN","F","2/14/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("410024190007","ANCHETA","LUCAS JACOB","TAYLAN","M","8/25/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("410025150024","MAGBUAL","KESLEY THEIA","GUZMAN","F","1/10/2010","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("410505150004","GUERRERO","MARC  MICHAEL","ABIERA","M","2/10/2010","ALTERNATE","P1","2028");
INSERT INTO `student` VALUES("410505150027","CORPUZ ","LIAM MARKO","BAUTISTA","M","11/28/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("410505150031","PIZARRO ","AARON JOSEF ","DEFIESTA","M","7/16/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("410505150048","CORPUZ ","REI LANDER","BAUTISTA","M","11/28/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("410505180027","AGBAYANI","AALIYAH RAYNE","FLORDELIZA","F","1/10/14","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("410506150004","ASIONG ","JOSH CHRISTOPHER","BUTED ","M","1/28/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("410506150006","PAZ ","ANGELO RODERICK ","BAJET ","M","6/9/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("410506150015","ACENA","HANNAH JHOEFEL","QUEMI","F","4/17/2011","PRINCIPAL","P3","2029");
INSERT INTO `student` VALUES("410506150017","BALISBISANA","JANICKA","VELASCO","F","04/08/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("410506150077","PIZARRO ","KYLE CHRYSLER ","PINPIN ","M","2/28/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("410506150086","ASIONG ","MARY MISHCAH AUDRIE ","BUTED ","F","11/5/2010","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("410506150087","BALISBISANA","JEANINA","VELASCO","F","6/30/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("410506150090","DE VERA ","AYIANNAH RHYCEL ","YUMANG ","F","6/23/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("410506150096","ALIBIN","LUTHER FRANCOIS","REOTUTAR","M","2/8/2010","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("410506150097","BOONGALING","DANERIC MATTHEW","ITALIN","M","5/19/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("410506150109","LACUESTA","ALEXANDRA SOPHIA","SANTIAGO","F","2/15/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("410506160012","RIBAC","PAUL CARLO","RAGASA","M","01/12/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("410506160050","BILGERA","ERICH KRISTEL","GARCIA","F","9/25/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("410506160057","BARCENA","ACHILLES MARION","ATENDIDO","M","2/18/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("410506170008","BALMACEDA","ANTHONETTE BELLE","TORRES","F","4/24/2012","ALTERNATE","P1","2030");
INSERT INTO `student` VALUES("410506180025","BILGERA","PRINCESS ERIKA","GARCIA","F","6/1/13","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("410506180070","QUERUBIN","FIONA CASSANDRA","DULATRE","F","5/6/13","ALTERNATE","P2","2031");
INSERT INTO `student` VALUES("410506180105","LAZO","ENRICO MIGUEL","RANJO","M","10/22/12","PRINCIPAL","P2","2031");
INSERT INTO `student` VALUES("410506190048","FOLLANTE","ED RAENIQUE","ALLAGADAN","M","5/14/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("410510150026","MONTERO ","LHIAN LEONICE ","CACHOLA ","F","1/2/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("410510180008","MONTERO","LLAMRE JEILEEN","CABACUNGAN","F","12/17/12","ALTERNATE","P2","2031");
INSERT INTO `student` VALUES("410510180045","LAGMAY","BRIAN SILVERIS","CAUTON","M","7/24/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("410512150001","BASQUEZ","ZHIAN DWIN","TACTAY","M","11/4/12","ALTERNATE","","2031");
INSERT INTO `student` VALUES("410512150003","LAURENTE ","ISAAC GABRIEL ANGELO","REBIBIS","M","1/15/2012","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("410512150020","RAPACON ","GABRIELLE NATHAN ","ALEJAR ","M","7/13/2010","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("410512150037","PURISIMA","HANNAH CHRISTINE ","","F","2/26/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("410512150038","SONIDO ","LUZELLE JAINE ","","F","1/7/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("410512150094","TONGSON","GABRIEL","ETALIN","M","5/14/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("410512150186","PADRON-HANSEN","HAAGEN KRISTIAN","","M","6/13/2005","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("410512170002","FRAILE","JAN GABRIELL","","M","1/13/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("410512170004","PILAR","MARCUS ZURIEL","TIERRA","M","06/12/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("410512180031","PASCA","NORMAN VICTOR","ASUNCION","M","5/3/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("410512190007","NG","VIVIEN CLAIRE","LAO","F","4/21/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("410512190023","ROSARIO","VELINDA ALEXEJ","RAGASA","F","9/18/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("410512200013","ALMIENDA","FREYA BREANNE","ARCE","F","10/18/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("410514150034","BAÑEZ","ANGELIC JASMIN ","CABANTING ","F","2/7/2011","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("410514150036","CORPUZ ","ALEXANDREY JOY ","MANZANO ","F","2/3/2011","ALTERNATE ","P1","2029");
INSERT INTO `student` VALUES("410514170024","MENDOZA","ANGIELENE","ATIENZA","F","10/29/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("410516150006","JULARBAL","GIO AEDRICK","SAVELLA","M","2/26/2010","ALTERNATE","P2","2028");
INSERT INTO `student` VALUES("410516160015","SONG","CHARLES SUNG-IN","GARMA","M","1/2/2011","PRINCIPAL","P1","2029");
INSERT INTO `student` VALUES("410516170010","FUENTESFINA","GIANNA BELLA","SALVADOR","F","06/02/2012","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("410516170019","MATUTE","GUILIAN VICTOR","SAGUN","M","7/28/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("410516170020","RONDON","RAIJAN","BALADJAY","M","12/27/2011","ALTERNATE","FULL","2030");
INSERT INTO `student` VALUES("410516180036","BRILLANTES","KAIRDENGARD","TACATA","M","12/21/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("410517150018","IDICA","KADE ZXANDER","VILLA","M","09/11/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("410517150021","YADAO ","RHIONNE LEWIS ","JARDELEZA","M","11/10/2010","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("410517150029","ARROCENA","ALLORA ISABELLA","GINES","F","10/28/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("410517150079","LIVED","RAINNE SHANNELLE","REVITA","F","5/21/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("410517180001","EUGENIO","FRANCES LEAZEL","DOMINGO","F","5/20/13","ALTERNATE","","2031");
INSERT INTO `student` VALUES("410518150057","FLARIZ","HON JASPER","CORPUZ","M","9/12/2009","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("410518180003","PALOMARES","ADRIEL JAMES","-","M","6/8/13","PRINCIPAL","FULL","2031");
INSERT INTO `student` VALUES("410518180008","CABUENA","MONICA LOUISE","LACMAY","F","10/18/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("410518180029","ACAPUYAN","ZIA CASANDRA","FALLET","F","9/11/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("410518180047","MEMIJE","YOONA GENE","REYNO","F","12/16/13","ALTERNATE","P3","2031");
INSERT INTO `student` VALUES("410518190004","RAMOS","ZABDIEL CARLOS","REQUILMAN","M","4/16/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("410521150002","BRANDON ","NIKKI ","DOLORES ","F","7/24/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("410521160005","GOROSPE ","AL RAYVIR ","RODRIGUEZ ","M","10/23/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("410522150031","MENDOZA","HAZEL ANJELA","DUMOT","F","12/13/2009","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("410522190003","RUBIS","DAVID TIMOTHY","DULIN","M","12/21/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("410523180010","LUCERO","LANCE JACOB","DE GUZMAN","M","7/23/13","PRINCIPAL","P1","2031");
INSERT INTO `student` VALUES("410530180007","IWANG","MARC JASON","TULLIONG","M","3/23/13","PRINCIPAL","P1","2031");
INSERT INTO `student` VALUES("410531160005","ALEJANDRO","VIVIEN ADRIENNE","SANDI","F","7/30/2011","PRINCIPAL","P2","2029");
INSERT INTO `student` VALUES("410531180006","RAQUEPO","STEPHEN ANTHONY","SERA","M","11/10/12","ALTERNATE","","2031");
INSERT INTO `student` VALUES("411002190002","BENLOT","THEO ABE","CATALAN","M","11/6/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("411004170004","DACANAY","MICHAEL SIMON","TORRES","M","7/23/2011","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("411004190012","UYCHOCO","KRISTA LEANN","MADRIAGA","F","6/7/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("411032150022","BARTOLOME","LAVIGNA","PUZON","F","","PRINCIPAL","FULL","2027");
INSERT INTO `student` VALUES("411039150144","LIBUNAO","JOMS","MAMACLAY","M","6/21/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("411039150171","FRUTAS","SAMANTHA WYNSLETTE","VALDEZ","F","06/08/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("411039150221","PASION","BETTINAH MIRANDA ANGELIQUE ","CARGANILLO","F","2/15/2006","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("411039150222","PURUGGANAN","MACY ","ORPILLA","F","8/16/2005","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("411039150237","ULANDAY","ENRICO JAMEEL","FERRARO","M","7/21/2004","ALTERNATE","P1","2023");
INSERT INTO `student` VALUES("411039150242","DOCTOLERO","CARLOS JAIMEL","BERNABE","M","3/26/2004","ALTERNATE","P1","2022");
INSERT INTO `student` VALUES("411039150248","GATUZ","KQUEVHIN JOHN","CARREON","M","12/9/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("411039150251","RAQUEPO","ANTHONY RICHARD","NACE","M","11/7/2003","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("411039180045","BASTO","LLOYD BRANDO","DE LEON","M","6/10/11","LATERAL","P3","2029");
INSERT INTO `student` VALUES("411535150127","RABAYA","JESIE STAR","CABANSAG","F","4/28/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("411535150177","RABAYA","JAILA ESTHER","CABANSAG","F","01/29/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("411535170015","RABAYA","JOSEPH ETHAN","CABANSAG","M","07/07/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("411535170017","ALFONSO","WENDY MAE","ARNESTO","F","8/4/11","LATERAL","P1","2030");
INSERT INTO `student` VALUES("411539150007","PASCUA","JHURGEN KEITH SELWYN","DELA CRUZ","M","7/21/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("411539150094","FERRER","FRANCES MITCHIL","DE VERA","F","10/17/10","LATERAL","","2029");
INSERT INTO `student` VALUES("411539160003","ROMERO ","JACOB ","VENTENILLA ","M","9/2/2010","ALTERNATE ","P2","2029");
INSERT INTO `student` VALUES("411552150076","TABIOS","LANCE GIAN","VILLAFAÑE","M","6/22/2009","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("411552150084","SAGUN","JULIANNE","DOLLAGA","F","9/17/2009","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("411552150094","VILLANUEVA","RHYSS THEO","RODRIGUEZA","M","9/15/2011","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("411552150100","CAMERO ","JILLIAN AIMAR ","CAROLINO ","F","9/6/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("411552170003","CALICDAN","HESECHE","CARILLO","M","12/13/2011","ALTERNATE","P3","2030");
INSERT INTO `student` VALUES("411552180005","DELA CRUZ ","MAXENE YZABEL","GUIALA","F","05/11/2013","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("411553150019","ARTATES","AZZAN ","ARIENDA ","F","5/17/2009","ALTERNATE","","2028");
INSERT INTO `student` VALUES("411558190013","AGATEP","AESHINE AIKHEE","MINA","F","4/28/2014","ALTERNATE - CLC","","2032");
INSERT INTO `student` VALUES("412005150011","FLORES","HANNA SAMANTHA","","F","1/10/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("412006150156","SORIANO","IANNE FRANCHEZKA LOUISE"," PACABA","F","12/11/2006","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("412006150457","SORIANO","YUMI ARABELA SHYRE","PACABA","F","9/7/11","LATERAL","","2029");
INSERT INTO `student` VALUES("412006190015","ACOSTA","RAILEY SABRINA","MAURI","F","8/2/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("412007190019","MATUTE","LANCE JADEN","NAVA","M","1/4/2014","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("412009150091","BASCOS","KHYLENE AINSSLEY","DELA CRUZ","F","1/10/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("412009150146","PLUTERIA","PJ WILHELM","MALLARE","M","09/20/2005","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("412016150021","DY","VINCE ALLEN","BAUTISTA","M","5/6/2010","ALTERNATE","P1","2028");
INSERT INTO `student` VALUES("412043150035","JOCUTAN","PRECIOUS MAXINNE","ANCHO","F","5/15/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("412043150090","DORONGON","VIC JOBERT","FABRO","M","10/16/2007","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("412043150092","INIBA","CARLITOS VINCE","LAIGO","M","01/22/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("412043150124","ARANICO","PRINCE RHENNER TIMOTHY","CABRERA","M","1/4/2006","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("412043150127","BIG-ASAN","ALFONSO MIGUEL ","MANDUYOG","M","9/1/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("412043170008","PAUNLAGAO","JAIRELL YSHER","MANALO","M","11/05/2011","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("412072150044","AQUINO","JAMES EMMANUEL","ARELLANO","M","4/4/2011","ALTERNATE","P3","2029");
INSERT INTO `student` VALUES("412072170035","BIASCAN","AMBER","MENESES","F","5/22/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("412075190001","MENDOZA","ISAAC","VILLAREAL","M","6/4/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("412513150056","FRANCISCO","ANGELICA","SAMSON","F","1/25/2012","ALTERNATE","P2","2030");
INSERT INTO `student` VALUES("412514150074","BALANON","SEAN ROI","LAMBINO","M","5/26/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("413000000000","ACAIN","RHAMONA LOISEGOLD","BOJADO","F","12/31/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("413002150051","LEGASPI","RONDEL","SAMSON","M","1/29/2010","ALTERNATE","P2","2028");
INSERT INTO `student` VALUES("413002150081","SANTOS","MAHONRI JERWIN","DELA CRUZ","M","9/15/11","LATERAL","P1","2030");
INSERT INTO `student` VALUES("413003150005","FAMORCA ","GERIELLE ","GAOAT","F","7/1/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("413006150002","BARTOLOME ","JOSHUA ANGELO ","","M","8/11/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("413006150012","AQUINO","ULYZA SURI","RAMOS","F","9/30/2011","ALTERNATE","P3","2029");
INSERT INTO `student` VALUES("413006150017","AGOJO ","ALI NIKOLAI ","ALEJANDRO ","M","9/1/2010","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("413006150029","MAGANA ","ALTHEA VHIEL ","LISTA ","F","4/9/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("413006150040","SALES","HANAYA FELIZ","ABIAN","F","7/27/2010","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("413006170003","CASTRO","FRINZESCA NAEAM","CASTILLO","F","01/06/2012","PRINCIPAL","P1","2030");
INSERT INTO `student` VALUES("413006170004","ESPIRITU","GENIE DEE","PAGUIRIGAN","F","4/23/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("413006170009","BENEMERITO","JENINE MAY","ORTEGA","F","05/02/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("413006170025","BARTOLOME ","VICTORIA LOUISE","","F","1/25/2012","PRINCIPAL","FULL","2030");
INSERT INTO `student` VALUES("413006180002","AQUINO","UM ATHENA","RAMOS","F","11/21/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("413006180024","ANCHETA","FRANZ ZOEY","AGCAOILI","F","5/31/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("413006190001","NAVARRO","AISLES-KRXERN","LUIS","M","11/18/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("413006190010","RAMELB","MARCUSZ RAPHAEL","ANCHETA","M","3/31/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("413006190025","BUENO","WAYNE GRAYBACK","BORROMEO","M","11/4/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("413007150018","PANALIGAN","ADRIQUE BRYLLIE","HERNANE","F","2/25/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("413008180003","JUAN","RAMIEL ERIK","VALENCIA","M","10/16/12","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("413009150004","DELA CRUZ ","RENZ ARON ","ABLAO ","M","7/19/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("413501190010","INAY","GABE ALEXANDREI","FERRER","M","8/4/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("413502150030","MANESE","ETHAN GIL","TIMBREZA","M","6/23/2010","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("414012150062","PALAGUD","PRUDENCE STEFANI","LOLOY","F","4/6/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("415011150015","SAGUIRRE","RHAEN ABRHEI","PICO","M","2/11/11","LATERAL","","2029");
INSERT INTO `student` VALUES("415011160026","CANONIZADO","SYENNE CYZARISH","RARALIO","F","4/12/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("415011180012","PIO","JOSH REIGHNELL","SACAPULO","M","3/4/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("41501118030","SAGUIRRE","RALPH DAVID","PICO","M","2/19/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("415017170042","Rufo","Arjhon Joash","Bautista","M","2012-10-02","LATERAL","FULL","2030");
INSERT INTO `student` VALUES("415023160044","GAPUSAN","AVERY REIGNE","GUILLEN","F","7/10/13","ALTERNATE","","2031");
INSERT INTO `student` VALUES("415023180014","CORPUZ","AURORA LENIE","AGCAOILI","F","1/31/13","PRINCIPAL","FULL","2031");
INSERT INTO `student` VALUES("415023190012","LABII","AMIRA LOUISE","DALO","F","7/25/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("415023190019","IBARRA","HERRON KIER","ACOB","M","7/3/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("415048150005","DE GUZMAN","YOHANN DEMETRIUS","CABRERA","M","11/27/12","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("415048160012","VILLANUEVA","MA. PIATRICE","QUE","F","7/11/11","LATERAL","P3","2029");
INSERT INTO `student` VALUES("415048170007","CASILANA","INESOPHIA LIELLE","TORRADO","F","4/30/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("415048190008","ATAYAN","MYRRHA LY ANNE","DELA ROSA","F","3/15/2014","ALTERNATE","","2032");
INSERT INTO `student` VALUES("415051150007","BAGASOL ","CHARLEEN AMEERA ","SAGUN ","F","8/13/2010","ALTERNATE","FULL","2029");
INSERT INTO `student` VALUES("415051150013","MOMOG","LANCE JONATHAN ","BAGASOL ","M","3/16/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("415057170011","BUSAL","RAFAEL RENZO","MONJE","M","4/20/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("415057170021","VALDEZ","LUCIANA XANTHE","DABALOS","F","3/31/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("41552150033","DEL FIERRO","JORELL KERSTAN","MILANTE","M","11/18/2007","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("415528190001","VILLEGAS","JOHNDIE","PACANO","M","6/29/2014","ALTERNATE - CVC","","2032");
INSERT INTO `student` VALUES("415548190010","BACCAY","CHARMES ANDREI","ACUPAN","F","2/17/2014","ALTERNATE - CVC","","2032");
INSERT INTO `student` VALUES("424433150002","GUNDRAN","CARLISLE GENE","INES","M","10/17/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("425624150169","REYES","MARIAH THERESE ","CANDARI","F","8/15/2008","ALTERNATE","P2","2027");
INSERT INTO `student` VALUES("425624180010","URSULUM","NATHANIEL PIO HERMANN ","DUMAYAG","M","3/30/13","PRINCIPAL","","2031");
INSERT INTO `student` VALUES("478501150170","JALOG","KENT","DIZON","M","03/18/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("478501150184","BERNARDINO","APOLE YZELLA","FRANDO","F","09/06/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("479002160009","MANICAD ","ZYHYRELLE FEVRY ","ZARATE ","F","2/9/2011","ALTERNATE ","P3","2029");
INSERT INTO `student` VALUES("479002190003","SAMPAYAN","VON LOUIS SIEGFRIED","SONGALIA","M","10/20/2013","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("481536151005","LEDDA","SHEIKHA CASSIANA NORAINE","TABUCOL","F","5/9/2006","ALTERNATE","P2","2024");
INSERT INTO `student` VALUES("482522150110","AGBAYANI","SHANDRAH DANE","RODRIGUEZ","F","11/28/2009","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("482549150300","RAMOS","VITO ANTONIO","DE GUZMAN","M","09/09/2006","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("482549180031","CABBAT","LORDWAYNE","SAMBOLANAY","M","3/9/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("482649150126","SOLMORO","KEONA MALIA","","F","4/12/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("482955150061","NOBLEZA","NOSHKA ADELAIDE","CAMIA","F","4/2/2010","PRINCIPAL","FULL","2028");
INSERT INTO `student` VALUES("483011150188","REBOLDERA","SEAN MELCHOR","GARCIA","M","2009-12-11","LATERAL","P3","2028");
INSERT INTO `student` VALUES("483014180022","QUESTIN","ZACK ANDREI","VIDAD","M","9/6/12","ALTERNATE","","2031");
INSERT INTO `student` VALUES("484045150144","NITURA","RENZEN GABRIEL","ABAINZA","M","4/20/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("484560160036","MENDOZA","AMANDA GABRIELLE","LIMBAGO","F","3/23/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("485546150020","RALLOJAY ","LOWI RANE ","RAFANAN ","F","5/18/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("486016170128","SANTOS","MEG ADDISON","QUINTO","F","2009-10-18","LATERAL","P3","2028");
INSERT INTO `student` VALUES("488055150031","BERNAL","JETAIME ANDRE RIVER","MUNSAYAC","M","06/17/2007","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("496003180015","BUMACHI","ARTHUS HUNTER","ESQUEJO","M","2/2/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("496004190006","GALAY","MEDI AVEGALE","MANZANO","F","9/15/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("496005180008","GAMISERA","VINCEL GHEADRIC","VILLACORTA","M","6/5/13","PRINCIPAL","P1","2031");
INSERT INTO `student` VALUES("496007160027","LAY-YAG ","WINTER KAZIA ","BASCOS ","F","8/26/2011","ALTERNATE ","FULL","2029");
INSERT INTO `student` VALUES("496010160031","RODRIGUEZ","ROEI EZEKIEL ","ANSING ","M","9/22/2010","ALTERNATE ","P1","2029");
INSERT INTO `student` VALUES("496013150008","IÑIGO","RICHARD RUSSELL ","APILADO ","M","5/10/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("496013150014","AMMOGAO ","LADY FAYE KASSANDRA ","ASTRERO ","F","8/20/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("496013150018","ILAO ","JAERA PIERETTE ","ANTALAN ","F","3/30/2011","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("496013150021","RAMOS ","CASSANDRA MAE ","BELLO ","F","6/28/2011","ALTERNATE ","FULL","2029");
INSERT INTO `student` VALUES("496013150041","TURALVA","EWANN IRVEN GRANT","HIDALGO","M","7/4/2010","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("496501150162","ESTEBAN","ESTEFENN OLLAN","ALO","M","9/21/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("496503150016","SIGNEY ","MICHAEL JR. ","CHIU ","M","12/18/2010","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("496503150021","ONG ","JILLIAN ","SANTOS ","F","6/8/2011","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("496503150049","SIGNEY","GABRIEL","CHIU","M","2008-12-21","LATERAL","P3","2027");
INSERT INTO `student` VALUES("496503150105","TAN","JASHER PAUL DANIEL ","QUINITIO","M","9/19/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("496503150110","CAMBA","NICOLE ANN","CARACAS","F","7/11/2005","ALTERNATE","P3","2024");
INSERT INTO `student` VALUES("496503150127","AQUINO","PENELOPE CHELSEA","HUMILDE","F","8/2/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("496503150129","ESTRADA","NICHOLE","NAGAL","F","9/8/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("496503150137","HILOMEN","GWEN ADRIAN","DUQUE","M","7/27/2003","ALTERNATE","P3","2022");
INSERT INTO `student` VALUES("496503150284","BOTARDO","JANA ROZ","PADILLA","F","10/10/2004","LATERAL","P3","2023");
INSERT INTO `student` VALUES("496503150287","ESON","REXIE LYAN JODENNIE","OLIVO","F","3/15/2004","PRINCIPAL","P2","2023");
INSERT INTO `student` VALUES("496503190012","TORIO","JOSH CALEB","BITO","M","12/20/2013","ALTERNATE - CARC","","2032");
INSERT INTO `student` VALUES("500348190008","RIOTOC","NATHAN KEITH","RAGIL","M","4/28/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("500370160096","FINEZ","MAEVE","CAPUA","F","1/6/2011","PRINCIPAL","FULL","2029");
INSERT INTO `student` VALUES("500370170147","RAMOS","MARK DENNIS","PATOC","M","12/22/2010","PRINCIPAL","FULL","2029");
INSERT INTO `student` VALUES("500441170095","RAGUNTON","XIAN JAY","LIBONGEN","M","06/12/2012","ALTERNATE","FULL","2030");
INSERT INTO `student` VALUES("500591180029","TALOSIG","ALBERT ANTONIE","CALZADA","M","12/30/12","PRINCIPAL","FULL","2031");
INSERT INTO `student` VALUES("500591180045","BALANON","FATIMA","SAMPAYAN","F","01/21/2007","ALTERNATE","FULL","2025");
INSERT INTO `student` VALUES("501002150025","DAGDAG","LESTER JOHN","","M","5/5/2010","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("501002150046","ABINSAY","TYRELL GRISHAM","CACANANTA","M","3/28/2010","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("501002150171","ABINSAY","BRAVEHEART RENZY ","CACANANTA","M","2/18/2006","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("600000150008","DE VERA","XYZY","DADUYO","F","5/28/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600000150009","GABRIEL ","GAYLE ANGELI ","OCAMPO","F","7/9/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("600000150016","QUILALA","CELINA YSABEL","BARROGA","F","3/6/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600000150023","CASTRO","IZAIAH ISHE","DUMAYAG","M","4/22/2005","ALTERNATE","FULL","2023");
INSERT INTO `student` VALUES("600000150026","LABAGNOY","CLYDE","GONZALES","M","1/28/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("600000150032","QUIMOYOG","MARCUS DWAYNE","VERDADERO","M","2/17/2005","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("600000150041","CABACUNGAN","MERIZA HAYLIN JOY","JAVATE","F","3/24/2005","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("600000150042","CACATIAN","SHYNELLE EZRA","SORIANO","F","2/25/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("600000150045","ESTRELLA","JULIA ANGELINE","LANSI","F","4/28/2005","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("600000150073","ESTRELLA","JASMINE ANNE","LANSI","F","11/18/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("600000150076","MANUEL","AMY JOSIPHIA","ABUNDO","F","9/15/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("600000150077","NATIVIDAD","SHEKAINAH","BALCITA","F","9/26/2003","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("600000150094","MADRIAGA","ICHIRO","RAMOS","M","03/10/2007","ALTERNATE","FULL","2026");
INSERT INTO `student` VALUES("600000150113","ORPIA","JULIANNE","BALASTA","F","09/28/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("600000150123","MACANSANTOS","MOW OLIVER","ALPASAN","M","4/3/2010","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("600000150127","TARIGA","WYNYL STAN","SALVADOR","M","11/28/2009","ALTERNATE","P2","2028");
INSERT INTO `student` VALUES("600000150134","ORANI","SHEENA MARIE","","F","8/25/2010","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("600000150143","LY","GYLLEAN TAN ","AUSTRIA","M","7/4/2006","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("600000150147","RAMOS","VAN RAFAEL JOAKIN ","GAJETON","M","5/4/2006","LATERAL - G8","P3","2024");
INSERT INTO `student` VALUES("600000150153","ABUCE","BRENNA JANINE ","ALEGRE","F","9/13/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("600000150169","ROSARIO","YNODA YSAVIELLE ","BATUGO","F","11/19/2005","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("600000150273","ECLARIN","THEONE GENESIS","GALANO","M","12/19/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("600000150298","FARINAS","SOFIA DENISE","FERRERAS","F","05/10/2007","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("600000150299","GABRIEL","MARIA NATHALIE","FIESTA","F","08/22/2007","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("600000150300","MIRANDA","DENISSE MARTHIE","ARCAYNA","F","09/20/2006","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("60000150283","CASTRO","CLIFF DARREN","CACHERO","M","03/24/2007","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("600003150031","NAVALTA","JAEMY DENIELLE","GALISTE","F","12/12/2008","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("600003150130","LICAY","WILMAR JANSEN","MAYRINA","M","7/8/2003","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("600003170009","NILLO","MO GABRIEL ALEXZANDER","PACAT","M","7/13/2012","ALTERNATE","P2","2030");
INSERT INTO `student` VALUES("600004150090","FLORES","KRYSTEL ALEA","GAGUCAS","F","9/18/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600004150100","RULLAMAS","RACHAELLE RAINE","ADUAN","F","2/17/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("600004150173","BARAJAS","ARTEMIS","LAROCO","F","09/06/2007","PRINCIPAL","P2","2025");
INSERT INTO `student` VALUES("600004150178","GUZMAN","HANNAH SOPHIA","REYNA","F","12/22/2006","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("600005150160","ANCHETA","AMIEL KYLE","ABAD","M","6/14/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600005150187","ALIMPIA","ELIJAH JEROHAM NIKULAE","MATA","M","7/31/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("600005150190","CACHILA","CHARLES JR.","TAGURA","M","7/19/2009","ALTERNATE","","2027");
INSERT INTO `student` VALUES("600005150193","GAMAYO","EZEKIEL","BULAUN","M","2009-05-02","LATERAL","P3","2027");
INSERT INTO `student` VALUES("600005150197","PLANTA","ANGEL MARK ANTIN","PASCUA","M","4/21/2009","PRINCIPAL","","2027");
INSERT INTO `student` VALUES("600005150200","TRIUNFANTE","MIKHAIL JAN","TADENA","M","4/26/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600005150212","CARIAGA","LYANDREI","BERNARDO","M","12/13/2008","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("600005150213","DALAFU","MITCBEL VINCENT","FORMOSO","M","8/19/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600005150216","FLOR","JACOB MIGUEL","LINSANGAN","M","1/29/2009","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600005150221","NICOLAS","FIEL SIGMUND","BACTIN","M","10/12/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600005150224","ULEP","JOHN MARTIN","BUMANGLAG","M","1/3/2009","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("600005150230","COLOMA","MARVIN JAMES","RUIZ","M","8/25/2004","PRINCIPAL","P1","2023");
INSERT INTO `student` VALUES("600005150258","NAGASANGAN","ANGELA NICOLE","FAIGMANI","F","10/7/2004","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("600005150263","TUMANENG","RAPHAELLA LOUISE","NAVALTA","F","6/2/2005","ALTERNATE","P1","2023");
INSERT INTO `student` VALUES("600005150269","BLANCO","RUBEN RUFINO III","JOSE","M","11/25/2006","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("600005150276","RANADA","FIORENZO GAVRIL","VELASCO","M","03/02/2007","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("600005150295","RIVERA","MARIANNE HADIYA","GACUSAN","F","04/15/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("600005150302","AVILA","AARON","ALMAZAN","M","03/01/2007","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("600005150314","MENDIOLA","SIDNEY LAWRENCE","MAGDASOC","M","07/31/2007","ALTERNATE","P2","2025");
INSERT INTO `student` VALUES("600005150348","DELA COSTA","MADELLE ANGELIE","PASCUAL","F","11/18/2003","ALTERNATE","P2","2022");
INSERT INTO `student` VALUES("600005150361","TURQUEZA","VINCE THERESE","ALEJANDRO","F","8/28/2003","PRINCIPAL","P2","2022");
INSERT INTO `student` VALUES("600005150374","AGULLANA ","MARC CHRISTIAN ","RUMBAOA","M","7/3/2006","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("600005150386","RAMBAUD","ANTHONY JAMES ","RUIZ","M","10/25/2005","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("600005150399","MATEO","ALYSSA GABRIELLE ","ACERET","F","3/27/2005","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("600005150400","PILAR","MIKHAIL HATEYA ","ESPIRITU","F","5/27/2005","PRINCIPAL","FULL","2024");
INSERT INTO `student` VALUES("600005150404","SALES","LAETISHA YSABEL"," CASTILLO","F","3/20/2006","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("600005150408","BAGCAL","KRYSTIAN LLOYD ","RANAY","M","3/28/2005","ALTERNATE","P2","2024");
INSERT INTO `student` VALUES("600005150418","RABE","AIDAN RAE HERACLEO III ","BENITO","M","10/13/2005","PRINCIPAL","P3","2024");
INSERT INTO `student` VALUES("600005150421","AGONOY","REIVEN KATE ","DELA CRUZ","F","12/23/2005","PRINCIPAL","P2","2024");
INSERT INTO `student` VALUES("600005150423","ALBANO","MOIRA EUNICE ","","F","8/31/2005","PRINCIPAL","P1","2024");
INSERT INTO `student` VALUES("600005150443","NATATA","RALPH JACOB","AGCAOILI","M","10/26/2008","PRINCIPAL","P2","2026");
INSERT INTO `student` VALUES("600005150458","GALANO","ANGELA JESSHIELLE","ABUY","F","05/02/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("600005150463","SATURNINO","CHJALSMYR CELES","NACNAC","F","06/25/2007","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("600005150465","TUGAS","RIANNA SOFIA","DOMINGO","F","03/01/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("600005150466","UMLAS","KIMBERLY ARDELLE","AURELIO","F","01/28/2008","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("600005150485","MIGUEL","VENICE KEITH","BANIAGA","F","08/23/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("600005150492","REYES","THRIXIE MARGRET","BRIONES","F","10/16/2007","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("600005150496","VALDEZ","SHAUNDEI WAYNE","ESPOSO","F","11/07/2008","ALTERNATE","P3","2026");
INSERT INTO `student` VALUES("600005150501","BAUTISTA","JABEZ NEMUEL","BATAC","M","7/8/2004","PRINCIPAL","FULL","2023");
INSERT INTO `student` VALUES("600005150507","LEGASPI","CAIZHER JHON","CASTRO","M","10/3/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("600005150508","LO","FRANZ ANGELO DANIEL","SABADO","M","6/14/2004","ALTERNATE","P1","2023");
INSERT INTO `student` VALUES("600005150523","FLOR","ARIANA LEI","LINSANGAN","F","11/8/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("600005150526","LEAÑO","MORIELLE AYEN","ALEJANDRO","F","12/5/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("600005150529","RABAGO","ISABELLA MARI THERESE","PEREZ","F","12/18/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("600005150533","RIVERA","MONIQUE LOURIZ","DOMINGO","F","8/25/2004","PRINCIPAL","P3","2023");
INSERT INTO `student` VALUES("600005150541","LAENO","ARIANNE GAYLE","SANTIAGO","F","09/29/2006","PRINCIPAL","P1","2025");
INSERT INTO `student` VALUES("600005150552","HERNANDEZ","ALEXANDER ROSS","TORRES","M","9/28/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("600005150565","CASTILLO","RACHEL MAE","GIRON","F","1/26/2004","PRINCIPAL","FULL","2022");
INSERT INTO `student` VALUES("600005150583","REMIGIO","ALIAH CRIZZEL","CASTRO","F","3/7/2004","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("600005150584","REYES","THRISHA MONIQUE","BRIONES","F","8/28/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("600005150585","ROCIMO","JANA MERIL","REMIGIO","F","4/30/2004","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("600005150586","TAMAYO","EIRENE ROSGEN","YALONG","F","9/21/2003","PRINCIPAL","P3","2022");
INSERT INTO `student` VALUES("600005150588","ALCANTARA","JVRI KIAN","SALES","M","3/6/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("600005150590","CARLOS","SAGE ADRIEL GAVIN","VIERNES","M","3/30/2010","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("600005150596","SALES","AVELINO III","CASTILLO","M","12/4/2009","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("600005150599","VILLALON","EMMANUEL JAMES EMIL","PEREZ","M","12/25/2009","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("600005150603","CABRALES","SHAN LEXI","PILAR","F","11/19/2009","PRINCIPAL","P2","2028");
INSERT INTO `student` VALUES("600005150607","FELIPE","JAYRED ELISHABEL","GARO","F","3/29/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("600005150614","TOMAS","JOHANNA SHAE","RABANG","F","1/27/2010","PRINCIPAL","P3","2028");
INSERT INTO `student` VALUES("600005150615","TUNAC","PRINCESS MICAH","ACOBA","F","5/30/2010","PRINCIPAL","P1","2028");
INSERT INTO `student` VALUES("600005150943","SABLAY","MARJORIE VENEIZE","ORCINO","F","10/11/2007","PRINCIPAL","P3","2026");
INSERT INTO `student` VALUES("600005152076","ROA","MARCUS ISAAC","SABAN","M","01/18/2007","PRINCIPAL","P3","2025");
INSERT INTO `student` VALUES("60000515308","GALANO","ANGELO PHILIPPE","ABUY","M","09/02/2007","ALTERNATE","P3","2025");
INSERT INTO `student` VALUES("600005160021","RIVERA ","KING VENUSTO ","CASIANO ","M","10/24/2010","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("600005160024","ALIMPIA ","ELISHA JEMIMAH NIKULEN","MATA ","F","8/30/2010","PRINCIPAL ","P3","2029");
INSERT INTO `student` VALUES("600005160026","BLAS ","PRINCESS ELIJA ","CURAMENG ","F","11/24/2010","ALTERNATE ","P2","2029");
INSERT INTO `student` VALUES("600005160032","FILART ","VELLA MIETCHIE ","VERLIM ","F","11/24/2010","ALTERNATE ","P2","2029");
INSERT INTO `student` VALUES("600005160034","JAVIER ","CIONE VERONICA ","AGRA ","F","4/13/2011","PRINCIPAL ","FULL","2029");
INSERT INTO `student` VALUES("600005160035","LAENO ","K ","SANTIAGO ","F","8/1/2010","PRINCIPAL ","P1","2029");
INSERT INTO `student` VALUES("600005160042","VILLALON","ALEXANDREA JEAN MARI","PEREZ","F","1/18/2011","PRINCIPAL","P3","2029");
INSERT INTO `student` VALUES("600005160043","DE LOR CASA ","SEBASTIAN REN ","ARRIOLA ","M","10/3/2010","PRINCIPAL ","P2","2029");
INSERT INTO `student` VALUES("600005170005","FUNTILA","AVERY ERIN","TAMAYO","M","10/09/2011","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("600005170007","LEJANO","JED","BADUA","M","1/18/2012","PRINCIPAL","P2","2030");
INSERT INTO `student` VALUES("600005170009","NICOLAS","CIAN ADRIEL","DUQUEZ","M","4/19/2012","PRINCIPAL","P3","2030");
INSERT INTO `student` VALUES("600005180006","BERMUDEZ","JUANCHO ANTHONY","PASCUAL","M","10/11/12","ALTERNATE","","2031");
INSERT INTO `student` VALUES("600005180016","BABOR","ALEXISSE NICOLE","BARTOLOME","F","10/30/12","ALTERNATE","","2031");
INSERT INTO `student` VALUES("600005190005","GUILLERMO","VAN LIAM","HERNANDO","M","9/26/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("600005190015","FABIAN","PSALMA ABRIANNA EMERALD","GARCIA","F","10/16/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("600005190019","MACUGAY","SAMANTHA EVE","SIMEON","F","10/11/2013","ALTERNATE","","2032");
INSERT INTO `student` VALUES("600005190032","AGUINALDO","EANAH AYESHA","MORATA","F","7/30/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("600005190034","ANDRES","ATHENA SOPHIE","LLANTADA","F","9/23/2013","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("600009150015","RABANAL","DANIELA","CHAN","F","4/22/12","LATERAL","P3","2030");
INSERT INTO `student` VALUES("600009150036","DULAY","DON ANDRE","DEL CASTILLO","M","3/6/2010","ALTERNATE","P3","2028");
INSERT INTO `student` VALUES("600009150074","FONTEJON ","CJ NATHALIE ","PALMA ","F","4/11/2011","ALTERNATE ","P1","2029");
INSERT INTO `student` VALUES("600009150292","ALCONIS","CASSANDRA VENERANDA","DE PERALTA","F","11/14/2008","ALTERNATE","P3","2027");
INSERT INTO `student` VALUES("600009150293","AMANO","LABREA ANICKA","MACAGBA","F","10/24/2008","PRINCIPAL","P1","2027");
INSERT INTO `student` VALUES("600009150295","ARGUELLES","STEFFANY AUBREY","REBIBIS","F","9/22/2008","PRINCIPAL","P2","2027");
INSERT INTO `student` VALUES("600009150319","ROQUE","LIANA GABRIELLE","TABUR","F","10/15/2008","PRINCIPAL","P3","2027");
INSERT INTO `student` VALUES("600009150332","ESCOBAR","WAYNE JAN","SOTELO","M","01/31/2008","ALTERNATE","P2","2026");
INSERT INTO `student` VALUES("600009150336","FERNANDO","OSCAR MANNY LUIS","CARAVEO","M","02/19/2007","ALTERNATE","P2","2026");
INSERT INTO `student` VALUES("600009150487","AWA","LYNDEE MEAVEY","REYES","F","4/24/2005","PRINCIPAL","P2","2023");
INSERT INTO `student` VALUES("600009150536","QUEYPO"," MA. THERESA RUTH ","CANTUBA","F","9/5/2005","ALTERNATE","P1","2024");
INSERT INTO `student` VALUES("600009150538","REAL","ARON APREAL","RABARA","F","3/24/2006","ALTERNATE","P1","2024");
INSERT INTO `student` VALUES("600009150558","ALCONIS","CHRISTINE JULIA","DE PERALTA","F","1/28/2004","PRINCIPAL","P1","2022");
INSERT INTO `student` VALUES("600009150573","SEGURA","ERIQUESSEN JORJA","RIGUNAY","F","5/6/2003","ALTERNATE","FULL","2022");
INSERT INTO `student` VALUES("600009180032","SALAYO","PHOENIX KHALEESI","ALHASHIMI","F","12/3/12","PRINCIPAL","P2","2031");
INSERT INTO `student` VALUES("600009180035","AQUINO","ZANNA ELISE","RAGUCOS","F","3/3/13","PRINCIPAL","P3","2031");
INSERT INTO `student` VALUES("600009190025","ULITA","JOHN ADRIAN","NOLASCO","M","7/11/2014","PRINCIPAL","","2032");
INSERT INTO `student` VALUES("700001180036","ESTONIDO","JAIRUS GABRIEL","BALAOAN","M","2/14/13","PRINCIPAL","P1","2031");
INSERT INTO `student` VALUES("700013181075","REYES","CATHERINA VALERIE","GALDIANO","F","06/02/2012","PRINCIPAL","FULL","2030");




DROP TABLE IF EXISTS `student_directory`;

CREATE TABLE `student_directory` (
  `LRN` varchar(50) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gradeLevel` varchar(2) NOT NULL,
  `section` varchar(50) NOT NULL,
  `sy` varchar(10) NOT NULL,
  `studentEmail` varchar(100) NOT NULL,
  `studentPhoneNumber` varchar(20) NOT NULL,
  `homeAddress` varchar(255) NOT NULL,
  `permanentAddress` varchar(255) NOT NULL,
  `town` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `religion` varchar(100) NOT NULL,
  `father` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fatherPhoneNumber` varchar(25) NOT NULL,
  `fatherEmail` varchar(100) NOT NULL,
  `fatherOccupation` varchar(100) NOT NULL,
  `fatherPlaceOfWork` varchar(255) NOT NULL,
  `mother` varchar(255) NOT NULL,
  `motherPhoneNumber` varchar(25) NOT NULL,
  `motherEmail` varchar(100) NOT NULL,
  `motherOccupation` varchar(100) NOT NULL,
  `motherPlaceOfWork` varchar(255) NOT NULL,
  `previousSchoolAttended` varchar(255) NOT NULL,
  `schoolType` varchar(10) NOT NULL,
  `schoolAddress` varchar(255) NOT NULL,
  `honors` varchar(100) NOT NULL,
  `guardian` varchar(255) NOT NULL,
  `guardianRelationship` varchar(100) NOT NULL,
  `guardianPhoneNumber` varchar(25) NOT NULL,
  `guardianEmail` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sd_lrn_sy` (`LRN`,`sy`),
  KEY `idx_sd_sy_grade_sec` (`sy`,`gradeLevel`,`section`)
) ENGINE=InnoDB AUTO_INCREMENT=7608 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



DROP TABLE IF EXISTS `subject`;

CREATE TABLE `subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectCode` varchar(50) NOT NULL,
  `subjectDescription` varchar(255) NOT NULL,
  `subjectUnit` varchar(10) NOT NULL,
  `subjectAcademicUnit` varchar(100) NOT NULL,
  `subjectGradeLevel` varchar(2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `subject` VALUES("1","IS 1","Integrated Science: Investigating Our Surrounding","1.7","Integrated Science","7","Inactive");
INSERT INTO `subject` VALUES("2","Math 1","Elementary Algebra","1.7","Mathematics","7","Inactive");
INSERT INTO `subject` VALUES("3","Computer Science 1","Foundations of Information and Communication Technology","1","Computer Science","7","Inactive");
INSERT INTO `subject` VALUES("4","English 1","Communication Arts 1 and Philippine Literature","1.3","English","7","Inactive");
INSERT INTO `subject` VALUES("5","Filipino 1","Kasanayan sa Komunikasyon (Wika at Panitikan)","1","Filipino","7","Inactive");
INSERT INTO `subject` VALUES("6","Social Science 1","Philippine History","1","Social Science/Values Education","7","Inactive");
INSERT INTO `subject` VALUES("7","Values Education 1","Foundation of Values Education","0.7","Social Science/Values Education","7","Inactive");
INSERT INTO `subject` VALUES("8","PEHM 1","PEHM 1","1","PEHM","7","Inactive");
INSERT INTO `subject` VALUES("9","ADTech 1","Art, Drafting and Technology: Basic Skills in Design and Technology","1","Technology","7","Inactive");
INSERT INTO `subject` VALUES("10","Earth Science 1","Earth Science 1","0.7","Physics","8","Inactive");
INSERT INTO `subject` VALUES("11","IS 2","Integrated Science 2: Exploring and Understanding the Interconnections of Science","2","Integrated Science","8","Inactive");
INSERT INTO `subject` VALUES("12","Computer Science 2","Introduction to Computational Thinking","1","Computer Science","8","Inactive");
INSERT INTO `subject` VALUES("13","English 2","Communication Arts 2 and Afro-Asian Literature","1.3","English","8","Inactive");
INSERT INTO `subject` VALUES("14","Filipino 2","Kasanayan sa Komunikasyon at Pagpapahalaga sa Noli Me Tangere","1","Filipino","8","Inactive");
INSERT INTO `subject` VALUES("15","Social Science 2","World History","1","Social Science/Values Education","8","Inactive");
INSERT INTO `subject` VALUES("16","Values Education 2","Foundation of Human Actions","0.7","Social Science/Values Education","8","Inactive");
INSERT INTO `subject` VALUES("17","PEHM 2","PEHM 2","1","PEHM","8","Inactive");
INSERT INTO `subject` VALUES("18","ADTech 2","An Intro. to the Design Process-Resistant Materials and Electronics","1","Technology","8","Inactive");
INSERT INTO `subject` VALUES("19","Math 2","Intermediate Algebra","1.7","Mathematics","8","Inactive");
INSERT INTO `subject` VALUES("20","Biology 1","Fundamentals of Biology 1","1","Biology","9","Inactive");
INSERT INTO `subject` VALUES("21","Chemistry 1","General Inorganic Chemistry 1","1","Chemistry","9","Inactive");
INSERT INTO `subject` VALUES("22","Physics 1","Fundamentals of Physics 1","1","Physics","9","Inactive");
INSERT INTO `subject` VALUES("23","Math 3","Mathematics 3","1","Mathematics","9","Inactive");
INSERT INTO `subject` VALUES("24","Computer Science 3","Client-Side Web Development","1","Computer Science","9","Inactive");
INSERT INTO `subject` VALUES("25","English 3","Communication Arts and English and American Literature","1","English","9","Inactive");
INSERT INTO `subject` VALUES("26","Filipino 3","Retorika, at Pasususri at Pagpapahalagang Pampanitikan","1","Filipino","9","Inactive");
INSERT INTO `subject` VALUES("27","Social Science 3","World History 2","1","Social Science/Values Education","9","Inactive");
INSERT INTO `subject` VALUES("28","PEHM 3","PEHM 3","1","PEHM","9","Inactive");
INSERT INTO `subject` VALUES("29","Statistics 1","Introduction to Statistics","1","Mathematics","9","Inactive");
INSERT INTO `subject` VALUES("30","Research 1","Science, Technology, Engineering, and Mathematics (STEM)","1","Research","10","active");
INSERT INTO `subject` VALUES("31","Biology 2","Fundamentals of Biology 2","1","Biology","10","active");
INSERT INTO `subject` VALUES("32","Chemistry 2","Introduction to Organic/Inorganic Chemistry 2","1","Chemistry","10","active");
INSERT INTO `subject` VALUES("33","Physics 2","Fundamentals of Physics 2","1","Physics","10","active");
INSERT INTO `subject` VALUES("34","Math 4","Mathematics 4","1.3","Mathematics","10","active");
INSERT INTO `subject` VALUES("35","Computer Science 4","Object-Oriented Programming","1","Computer Science","10","active");
INSERT INTO `subject` VALUES("36","English 4","Communication Arts 4 and World Literature","1","English","10","active");
INSERT INTO `subject` VALUES("37","Filipino 4","Kasaysayan at Pag-unlad ng Panitikang Filipino","1","Filipino","10","active");
INSERT INTO `subject` VALUES("38","Social Science 4","Phil. Government and Politics/Constitution","1","Social Science/Values Education","10","active");
INSERT INTO `subject` VALUES("39","PEHM 4","PEHM 4","1","PEHM","10","active");
INSERT INTO `subject` VALUES("40","Elective","","1","","10","active");
INSERT INTO `subject` VALUES("41","English 5","Effective Communication for Pre-University Students 1","1","English","11","active");
INSERT INTO `subject` VALUES("42","Filipino 5","Filipino sa Agham, Matematika at Teknolohiya","1","Filipino","11","active");
INSERT INTO `subject` VALUES("43","Math 5","Differential Calculus","1","Mathematics","11","active");
INSERT INTO `subject` VALUES("44","Research 2","Knowledge Integration, Application, and Extension","2","Research","11","active");
INSERT INTO `subject` VALUES("45","Science Core 1","Science Core 1","1.7","","11","active");
INSERT INTO `subject` VALUES("46","Social Science 5","Economics","1","Social Science/Values Education","11","active");
INSERT INTO `subject` VALUES("47","Elective","","1.7","","11","active");
INSERT INTO `subject` VALUES("48","English 6","Effective Communication for Pre-University Students 2","1","English","12","active");
INSERT INTO `subject` VALUES("49","Filipino 6","Pananaliksik sa Filipino","1","Filipino","12","active");
INSERT INTO `subject` VALUES("50","Math 6","Integrated Calculus and Linear Algebra","1","Mathematics","12","active");
INSERT INTO `subject` VALUES("51","Research 3","Research for a Sustainable Development","2","Research","12","active");
INSERT INTO `subject` VALUES("52","Science Core 2","Science Core 2","1.7","","12","active");
INSERT INTO `subject` VALUES("53","Social Science 6","Civic Engagement and Leadership","1","Social Science/Values Education","12","active");
INSERT INTO `subject` VALUES("54","Elective","","1.7","","12","active");
INSERT INTO `subject` VALUES("55","Values Education 3","Values Education 3","0.7","Social Science/Values Education","9","Inactive");
INSERT INTO `subject` VALUES("56","Values Education 4","Values Education 4","0.7","Social Science/Values Education","10","active");
INSERT INTO `subject` VALUES("57","CS Elective (Full Stack Web Dev)","Full Stack Web Development (Elective)","1","Computer Science","10","active");
INSERT INTO `subject` VALUES("58","Chem Elective (Envi. and Food Chem)","Environmental and Food Chemistry (Elective)","1","Chemistry","10","active");
INSERT INTO `subject` VALUES("59","CS Elective (Robotics)","Robotics (Elective)","1","Computer Science","10","active");
INSERT INTO `subject` VALUES("60","Physics Elective (Earth Sci.)","Earth Science (Elective)","1","Physics","10","active");
INSERT INTO `subject` VALUES("61","Biology Elective (PubHealth)","Public Health (Elective)","1","Biology","10","active");
INSERT INTO `subject` VALUES("62","Chem 3 Elective","Chemistry 3 (Elective)","1.7","Chemistry","11","active");
INSERT INTO `subject` VALUES("63","Bio 3 (Elective)","Biology 3 (Elective)","1.7","Biology","11","active");
INSERT INTO `subject` VALUES("64","Agri 1","Agriculture 1 (Elective)","1.7","Biology","11","active");
INSERT INTO `subject` VALUES("65","Computer Science 5","Data Structures and Algorithms (Elective)","1.7","Computer Science","11","active");
INSERT INTO `subject` VALUES("66","DMT","Design and Make Technologies (Elective)","1.7","Technology","11","active");
INSERT INTO `subject` VALUES("67","Engineering","Engineering (Elective)","1.7","Technology","11","active");
INSERT INTO `subject` VALUES("68","Science Core 1 (Biology)","Science Core 1 (Biology)","1.7","Biology","11","active");
INSERT INTO `subject` VALUES("69","Science Core 1 (Physics)","Science Core 1 (Physics)","1.7","Physics","11","active");
INSERT INTO `subject` VALUES("70","Science Core 1 (Chemistry)","Science Core 1 (Chemistry)","1.7","Chemistry","11","active");
INSERT INTO `subject` VALUES("71","Science Core 2 (Biology)","Science Core 2 (Biology)","1.7","Biology","12","active");
INSERT INTO `subject` VALUES("72","Science Core 2 (Physics)","Science Core 2 (Physics)","1.7","Physics","12","active");
INSERT INTO `subject` VALUES("73","Science Core 2 (Physics)","Science Core 2 (Chemistry)","1.7","Chemistry","12","active");
INSERT INTO `subject` VALUES("74","Chem 4 Elective","Chemistry 4 (Elective)","1.7","Chemistry","12","active");
INSERT INTO `subject` VALUES("75","Bio 4 Elective","Biology 4 (Elective)","1.7","Biology","12","active");
INSERT INTO `subject` VALUES("76","Agri 1","Agriculture 1 (Elective)","1.7","Biology","12","active");
INSERT INTO `subject` VALUES("77","Computer Science 5","Data Structures and Algorithms (Elective)","1.7","Computer Science","12","active");
INSERT INTO `subject` VALUES("78","-","-","1.7","Technology","12","active");
INSERT INTO `subject` VALUES("79","Engineering","Engineering (Elective)","1.7","Technology","12","active");
INSERT INTO `subject` VALUES("80","DMT","Design and Make Technologies (Elective)","1.7","Technology","12","active");
INSERT INTO `subject` VALUES("81","Technology Elective (DNF)","Design and Fabrication (Elective)","1","Technology","10","active");
INSERT INTO `subject` VALUES("82","SCALEg11","Service Creativity Action Leadership Enhancement Program (G11)","0","SCALE","11","active");
INSERT INTO `subject` VALUES("83","SCALEg12","Service Creativity Action Leadership Enhancement Program (G12)","0","SCALE","12","active");
INSERT INTO `subject` VALUES("84","Integrated Science","Introduction to Science and Earth Systems","1.7","Integrated Science","7","active");
INSERT INTO `subject` VALUES("85","Computer Science 1","Introduction to Computing","1","Computer Science","7","active");
INSERT INTO `subject` VALUES("86","Mathematics 1","Algebra 1","1.7","Mathematics","7","active");
INSERT INTO `subject` VALUES("87","ADTech 1","Art, Design, and Technology: Basic Principles and Processes","1","Technology","7","active");
INSERT INTO `subject` VALUES("88","English 1","Communication Arts 1","1.3","English","7","active");
INSERT INTO `subject` VALUES("89","Filipino 1","Wika at and Panitikang Pilipino","1","Filipino","7","active");
INSERT INTO `subject` VALUES("90","Social Science 1","Philippine History","1","Social Science/Values Education","7","active");
INSERT INTO `subject` VALUES("91","PEHM 1","PEHM 1","1","PEHM","7","active");
INSERT INTO `subject` VALUES("92","Values Education 1","Adolescent Living and Character Building","0.7","Social Science/Values Education","7","active");
INSERT INTO `subject` VALUES("93","Biology 1","Fundamentals of Biology 1","1","Biology","8","active");
INSERT INTO `subject` VALUES("94","Chemistry 1","General Chemistry 1","1","Chemistry","8","active");
INSERT INTO `subject` VALUES("95","Physics 1","Introduction to Physics","1","Physics","8","active");
INSERT INTO `subject` VALUES("96","Computer Science 2","Coding in a Connected World: Development of Computational Thinking Skills","1","Computer Science","8","active");
INSERT INTO `subject` VALUES("97","Mathematics 2A","Algebra 2","1","Mathematics","8","active");
INSERT INTO `subject` VALUES("98","Mathematics 2B","Geometry","1","Mathematics","8","active");
INSERT INTO `subject` VALUES("99","ADTech 2","Art, Design, and Technology: Resistant Materials and Electronics","1","Technology","8","active");
INSERT INTO `subject` VALUES("100","English 2","Communications Arts 2","1.3","English","8","active");
INSERT INTO `subject` VALUES("101","Filipino 2","Komunikasyon at ang Panitikang Pilipino","1","Filipino","8","active");
INSERT INTO `subject` VALUES("102","Social Science 2","World History","1","Social Science/Values Education","8","active");
INSERT INTO `subject` VALUES("103","PEHM 2","PEHM 2","1","PEHM","8","active");
INSERT INTO `subject` VALUES("104","Values Education 2","Toward Adolescent Wholeness","0.7","Social Science/Values Education","8","active");
INSERT INTO `subject` VALUES("105","Earth Science 1","Earth Systems, Energy, and Change","1","Physics","8","active");
INSERT INTO `subject` VALUES("106","Earth Science 1","Earth Systems, Energy, and Change","1","Physics","7","active");
INSERT INTO `subject` VALUES("107","Biology 2","Fundamentals of Biology 2","1.7","Biology","9","active");
INSERT INTO `subject` VALUES("108","Chemistry 2","General Chemistry 2","1.7","Chemistry","9","active");
INSERT INTO `subject` VALUES("109","Physics 2","Fundamentals of Physics 1","1.7","Physics","9","active");
INSERT INTO `subject` VALUES("110","Computer Science 3","Object-oriented Programming","1","Computer Science","9","active");
INSERT INTO `subject` VALUES("111","Mathematics 3","Algebra 3","1","Mathematics","9","active");
INSERT INTO `subject` VALUES("112","Statistics 1","Descriptive Statistics and Probability","1","Mathematics","9","active");
INSERT INTO `subject` VALUES("113","English 3","Communication Arts 3","1","English","9","active");
INSERT INTO `subject` VALUES("114","Filipino 3","Retorika, Pagsusuri at ang Noli Me Tangere","1","Filipino","9","active");
INSERT INTO `subject` VALUES("115","Social Science 3","Citizenship and the Economy","1","Social Science/Values Education","9","active");
INSERT INTO `subject` VALUES("116","PEHM 3","PEHM 3","1","PEHM","9","active");
INSERT INTO `subject` VALUES("117","Values Education 3","Values and Standards for a Meaningful Life","0.7","Social Science/Values Education","9","active");



DROP TABLE IF EXISTS `subject_teacher`;

CREATE TABLE `subject_teacher` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employeeID` varchar(20) NOT NULL,
  `subjectID` varchar(11) NOT NULL,
  `sy` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=245 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subject_teacher` VALUES("8","16-057","13","2022-2023");
INSERT INTO `subject_teacher` VALUES("9","14-043","4","2022-2023");
INSERT INTO `subject_teacher` VALUES("10","16-058","4","2022-2023");
INSERT INTO `subject_teacher` VALUES("11","11-015","25","2022-2023");
INSERT INTO `subject_teacher` VALUES("12","16-058","36","2022-2023");
INSERT INTO `subject_teacher` VALUES("13","04-022","41","2022-2023");
INSERT INTO `subject_teacher` VALUES("14","05-025","48","2022-2023");
INSERT INTO `subject_teacher` VALUES("20","14-045","49","2022-2023");
INSERT INTO `subject_teacher` VALUES("21","21-079","5","2022-2023");
INSERT INTO `subject_teacher` VALUES("23","04-021","42","2022-2023");
INSERT INTO `subject_teacher` VALUES("24","14-045","42","2022-2023");
INSERT INTO `subject_teacher` VALUES("25","06-028","42","2022-2023");
INSERT INTO `subject_teacher` VALUES("26","06-028","14","2022-2023");
INSERT INTO `subject_teacher` VALUES("27","18-074","14","2022-2023");
INSERT INTO `subject_teacher` VALUES("28","04-021","37","2022-2023");
INSERT INTO `subject_teacher` VALUES("29","18-074","37","2022-2023");
INSERT INTO `subject_teacher` VALUES("30","21-080","26","2022-2023");
INSERT INTO `subject_teacher` VALUES("32","03-003","8","2022-2023");
INSERT INTO `subject_teacher` VALUES("33","11-020","8","2022-2023");
INSERT INTO `subject_teacher` VALUES("34","03-003","17","2022-2023");
INSERT INTO `subject_teacher` VALUES("35","15-053","17","2022-2023");
INSERT INTO `subject_teacher` VALUES("36","11-020","17","2022-2023");
INSERT INTO `subject_teacher` VALUES("37","03-003","28","2022-2023");
INSERT INTO `subject_teacher` VALUES("38","21-019","28","2022-2023");
INSERT INTO `subject_teacher` VALUES("39","21-019","39","2022-2023");
INSERT INTO `subject_teacher` VALUES("40","15-053","39","2022-2023");
INSERT INTO `subject_teacher` VALUES("41","09-041","6","2022-2023");
INSERT INTO `subject_teacher` VALUES("44","16-060","15","2022-2023");
INSERT INTO `subject_teacher` VALUES("46","17-064","27","2022-2023");
INSERT INTO `subject_teacher` VALUES("50","21-076","38","2022-2023");
INSERT INTO `subject_teacher` VALUES("53","21-078","46","2022-2023");
INSERT INTO `subject_teacher` VALUES("55","15-027","53","2022-2023");
INSERT INTO `subject_teacher` VALUES("56","16-056","7","2022-2023");
INSERT INTO `subject_teacher` VALUES("57","21-078","55","2022-2023");
INSERT INTO `subject_teacher` VALUES("58","16-056","55","2022-2023");
INSERT INTO `subject_teacher` VALUES("59","09-039","22","2022-2023");
INSERT INTO `subject_teacher` VALUES("64","11-049","33","2022-2023");
INSERT INTO `subject_teacher` VALUES("65","09-039","10","2022-2023");
INSERT INTO `subject_teacher` VALUES("66","21-014","10","2022-2023");
INSERT INTO `subject_teacher` VALUES("67","11-049","10","2022-2023");
INSERT INTO `subject_teacher` VALUES("72","08-037","32","2022-2023");
INSERT INTO `subject_teacher` VALUES("77","17-066","21","2022-2023");
INSERT INTO `subject_teacher` VALUES("85","11-031","11","2022-2023");
INSERT INTO `subject_teacher` VALUES("86","21-014","11","2022-2023");
INSERT INTO `subject_teacher` VALUES("87","17-034","11","2022-2023");
INSERT INTO `subject_teacher` VALUES("91","05-024","1","2022-2023");
INSERT INTO `subject_teacher` VALUES("92","11-040","1","2022-2023");
INSERT INTO `subject_teacher` VALUES("93","20-030","1","2022-2023");
INSERT INTO `subject_teacher` VALUES("114","18-077","31","2022-2023");
INSERT INTO `subject_teacher` VALUES("121","05-026","20","2022-2023");
INSERT INTO `subject_teacher` VALUES("122","16-061","51","2022-2023");
INSERT INTO `subject_teacher` VALUES("123","17-034","51","2022-2023");
INSERT INTO `subject_teacher` VALUES("124","04-018","51","2022-2023");
INSERT INTO `subject_teacher` VALUES("136","14-048","12","2022-2023");
INSERT INTO `subject_teacher` VALUES("137","12-006","12","2022-2023");
INSERT INTO `subject_teacher` VALUES("138","20-073","24","2022-2023");
INSERT INTO `subject_teacher` VALUES("152","08-038","18","2022-2023");
INSERT INTO `subject_teacher` VALUES("153","16-033","18","2022-2023");
INSERT INTO `subject_teacher` VALUES("160","08-038","9","2022-2023");
INSERT INTO `subject_teacher` VALUES("161","16-004","9","2022-2023");
INSERT INTO `subject_teacher` VALUES("169","12-002","43","2022-2023");
INSERT INTO `subject_teacher` VALUES("171","10-046","34","2022-2023");
INSERT INTO `subject_teacher` VALUES("173","12-002","50","2022-2023");
INSERT INTO `subject_teacher` VALUES("174","16-012","50","2022-2023");
INSERT INTO `subject_teacher` VALUES("175","16-012","29","2022-2023");
INSERT INTO `subject_teacher` VALUES("176","21-065","19","2022-2023");
INSERT INTO `subject_teacher` VALUES("177","03-007","19","2022-2023");
INSERT INTO `subject_teacher` VALUES("178","16-055","2","2022-2023");
INSERT INTO `subject_teacher` VALUES("179","06-013","23","2022-2023");
INSERT INTO `subject_teacher` VALUES("180","21-065","23","2022-2023");
INSERT INTO `subject_teacher` VALUES("199","16-060","16","2022-2023");
INSERT INTO `subject_teacher` VALUES("200","21-076","16","2022-2023");
INSERT INTO `subject_teacher` VALUES("201","22-082","16","2022-2023");
INSERT INTO `subject_teacher` VALUES("202","22-082","56","2022-2023");
INSERT INTO `subject_teacher` VALUES("203","21-073","61","2022-2023");
INSERT INTO `subject_teacher` VALUES("204","20-073","57","2022-2023");
INSERT INTO `subject_teacher` VALUES("205","21-062","58","2022-2023");
INSERT INTO `subject_teacher` VALUES("206","14-032","59","2022-2023");
INSERT INTO `subject_teacher` VALUES("207","03-005","60","2022-2023");
INSERT INTO `subject_teacher` VALUES("208","20-030","64","2022-2023");
INSERT INTO `subject_teacher` VALUES("209","20-030","76","2022-2023");
INSERT INTO `subject_teacher` VALUES("211","14-048","65","2022-2023");
INSERT INTO `subject_teacher` VALUES("212","14-048","77","2022-2023");
INSERT INTO `subject_teacher` VALUES("213","16-033","66","2022-2023");
INSERT INTO `subject_teacher` VALUES("214","16-033","80","2022-2023");
INSERT INTO `subject_teacher` VALUES("215","16-004","67","2022-2023");
INSERT INTO `subject_teacher` VALUES("216","21-065","67","2022-2023");
INSERT INTO `subject_teacher` VALUES("217","21-065","79","2022-2023");
INSERT INTO `subject_teacher` VALUES("218","16-004","79","2022-2023");
INSERT INTO `subject_teacher` VALUES("219","21-062","74","2022-2023");
INSERT INTO `subject_teacher` VALUES("220","22-022","75","2022-2023");
INSERT INTO `subject_teacher` VALUES("221","21-073","68","2022-2023");
INSERT INTO `subject_teacher` VALUES("222","16-061","68","2022-2023");
INSERT INTO `subject_teacher` VALUES("223","22-022","71","2022-2023");
INSERT INTO `subject_teacher` VALUES("225","21-062","73","2022-2023");
INSERT INTO `subject_teacher` VALUES("226","05-024","69","2022-2023");
INSERT INTO `subject_teacher` VALUES("227","03-005","72","2022-2023");
INSERT INTO `subject_teacher` VALUES("228","12-006","35","2022-2023");
INSERT INTO `subject_teacher` VALUES("229","14-048","35","2022-2023");
INSERT INTO `subject_teacher` VALUES("230","14-032","3","2022-2023");
INSERT INTO `subject_teacher` VALUES("231","12-006","3","2022-2023");
INSERT INTO `subject_teacher` VALUES("232","14-048","3","2022-2023");
INSERT INTO `subject_teacher` VALUES("233","20-073","3","2022-2023");
INSERT INTO `subject_teacher` VALUES("236","14-051","30","2022-2023");
INSERT INTO `subject_teacher` VALUES("237","23-031","30","2022-2023");
INSERT INTO `subject_teacher` VALUES("238","09-017","62","2022-2023");
INSERT INTO `subject_teacher` VALUES("239","09-017","70","2022-2023");
INSERT INTO `subject_teacher` VALUES("240","23-031","70","2022-2023");
INSERT INTO `subject_teacher` VALUES("241","08-037","44","2022-2023");
INSERT INTO `subject_teacher` VALUES("242","14-051","44","2022-2023");
INSERT INTO `subject_teacher` VALUES("243","18-077","44","2022-2023");
INSERT INTO `subject_teacher` VALUES("244","23-031","44","2022-2023");



DROP TABLE IF EXISTS `user_account`;

CREATE TABLE `user_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `studentID` (`studentID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_account` VALUES("10","102202150016","neocarlo.malipot@irc.pshs.edu.ph","9fcbc01d9cbc96bfac7de30de3ccddd3");



SET FOREIGN_KEY_CHECKS=1;
