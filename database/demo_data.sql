-- --------------------------------------------------------
-- Project Nirvoya: Demo Data (optional)
-- Run AFTER schema.sql, on a fresh database.
--
-- Demo accounts (password for all: demo1234)
--   Admin  : admin@nirvoya.test
--   Member : member@nirvoya.test
--   Member : sadia@nirvoya.test
--
-- All people, phone numbers and reports below are fictional.
-- --------------------------------------------------------

USE nirvoya_db;

-- ---------- Users ----------
INSERT INTO Users (email, password_hash, phone_number, full_name, date_of_birth, blood_group) VALUES
('admin@nirvoya.test', '$2y$10$4xlbqWIiyfKMkjQEwoxbeefRhLqWZ.zsZz93A16wTAmOonHFszsbq', '+8801700000000', 'Nirvoya Admin', '1990-01-01', 'O+');
SET @admin = LAST_INSERT_ID();
INSERT INTO Admins (employee_id, department) VALUES (@admin, 'Safety Operations');

INSERT INTO Users (email, password_hash, phone_number, full_name, date_of_birth, blood_group) VALUES
('member@nirvoya.test', '$2y$10$Fx7Lb21PJj1zcR52643Iy.4TpcXL3I4oWa/dDieydLkR4CN96/XBW', '+8801700000001', 'Ayesha Rahman', '2002-05-14', 'B+');
SET @ayesha = LAST_INSERT_ID();
INSERT INTO Members (member_id) VALUES (@ayesha);

INSERT INTO Users (email, password_hash, phone_number, full_name, date_of_birth, blood_group) VALUES
('sadia@nirvoya.test', '$2y$10$igbxO8cXztpTLLpwAbWG3OJahbjw/lEWLmHwf.vHT/SY26JGi8lhe', '+8801700000002', 'Sadia Islam', '2001-11-02', 'A+');
SET @sadia = LAST_INSERT_ID();
INSERT INTO Members (member_id) VALUES (@sadia);

-- ---------- Trusted Contacts ----------
INSERT INTO Trusted_Contacts (member_id, name, phone, relationship) VALUES
(@ayesha, 'Rahima Khatun', '+8801700000011', 'Parent'),
(@ayesha, 'Tanvir Rahman', '+8801700000012', 'Sibling'),
(@ayesha, 'Farhana Akter', '+8801700000013', 'Friend'),
(@sadia,  'Nasrin Islam',  '+8801700000021', 'Parent');

-- ---------- Journeys ----------
INSERT INTO Journeys (member_id, share_token, start_loc, end_loc, start_time, end_time, status, current_lat, current_lng) VALUES
(@ayesha, 'fac5ed3dff4f107ed7ed0b1732caf4eb', 'University', 'Home',       NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY + INTERVAL 40 MINUTE, 'Completed', 23.79370000, 90.40660000),
(@ayesha, '0907657db499038d476f827893bb469f', 'Office',     'Gym',        NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY + INTERVAL 25 MINUTE, 'Completed', 23.78060000, 90.41630000),
(@ayesha, 'ba6e4bf4b93df83c832d2e37b0bd0085', 'Home',       'Coaching Centre', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 30 MINUTE, 'Completed', 23.77780000, 90.40510000);

-- A journey still in progress (inserted on its own so LAST_INSERT_ID() points at it)
INSERT INTO Journeys (member_id, share_token, start_loc, end_loc, start_time, end_time, status, current_lat, current_lng) VALUES
(@sadia,  '8e7b4e2485b83bb0f2afd28ceb00299a', 'Mirpur 10',  'Banani',     NOW() - INTERVAL 20 MINUTE, NULL, 'Active', 23.79490000, 90.40050000);
SET @sadia_journey = LAST_INSERT_ID();

-- ---------- SOS Alerts ----------
INSERT INTO SOS_Alerts (member_id, journey_id, alert_time, gps_lat, gps_lng, status) VALUES
(@sadia,  @sadia_journey, NOW() - INTERVAL 5 MINUTE, 23.79490000, 90.40050000, 'Pending'),
(@ayesha, NULL,           NOW() - INTERVAL 5 DAY,    23.78050000, 90.42670000, 'Resolved');

-- ---------- Incident Reports ----------
SET @t_verbal   = (SELECT type_id FROM Incident_Types WHERE type_name = 'Verbal Harassment');
SET @t_stalking = (SELECT type_id FROM Incident_Types WHERE type_name = 'Stalking');
SET @t_assault  = (SELECT type_id FROM Incident_Types WHERE type_name = 'Physical Assault');
SET @t_lighting = (SELECT type_id FROM Incident_Types WHERE type_name = 'Poor Lighting');
SET @t_cyber    = (SELECT type_id FROM Incident_Types WHERE type_name = 'Cyberbullying');

-- Verified (shown on the community map)
INSERT INTO Incidents (member_id, employee_id, description, incident_time, gps_lat, gps_lng, end_lat, end_lng, status) VALUES
(@ayesha, @admin, 'Followed from the bus stop to the lane behind the market.', NOW() - INTERVAL 9 DAY, 23.79370000, 90.40660000, 23.79900000, 90.41300000, 'Verified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_stalking);

INSERT INTO Incidents (member_id, employee_id, description, incident_time, gps_lat, gps_lng, status) VALUES
(@ayesha, @admin, 'Group of men passing comments near the footbridge every evening.', NOW() - INTERVAL 8 DAY, 23.77780000, 90.40510000, 'Verified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_verbal);

INSERT INTO Incidents (member_id, employee_id, description, incident_time, gps_lat, gps_lng, status) VALUES
(@sadia, @admin, 'Street lights on this road have been out for weeks.', NOW() - INTERVAL 7 DAY, 23.80690000, 90.36870000, 'Verified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_lighting);

INSERT INTO Incidents (member_id, employee_id, description, incident_time, gps_lat, gps_lng, status) VALUES
(@sadia, @admin, 'Bag snatching attempt, victim was pushed to the ground.', NOW() - INTERVAL 6 DAY, 23.78050000, 90.42670000, 'Verified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_assault);

INSERT INTO Incidents (member_id, employee_id, description, incident_time, gps_lat, gps_lng, status) VALUES
(@ayesha, @admin, 'Dark stretch with no lighting near the park entrance.', NOW() - INTERVAL 5 DAY, 23.82000000, 90.42100000, 'Verified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_lighting);

INSERT INTO Incidents (member_id, employee_id, description, incident_time, gps_lat, gps_lng, end_lat, end_lng, status) VALUES
(@sadia, @admin, 'Rickshaw puller kept following after being refused.', NOW() - INTERVAL 4 DAY, 23.76300000, 90.39200000, 23.76900000, 90.39900000, 'Verified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_stalking);

INSERT INTO Incidents (member_id, employee_id, description, incident_time, gps_lat, gps_lng, status) VALUES
(@ayesha, @admin, 'Catcalling outside the shopping mall gate.', NOW() - INTERVAL 3 DAY, 23.81340000, 90.42400000, 'Verified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_verbal);

-- Awaiting admin review
INSERT INTO Incidents (member_id, description, incident_time, gps_lat, gps_lng, status) VALUES
(@sadia, 'Harassing messages after sharing a ride in this area.', NOW() - INTERVAL 1 DAY, 23.79490000, 90.41430000, 'Unverified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_cyber);

INSERT INTO Incidents (member_id, description, incident_time, gps_lat, gps_lng, status) VALUES
(@ayesha, 'A man grabbed my arm and would not let go near the bus counter.', NOW() - INTERVAL 10 HOUR, 23.82900000, 90.42000000, 'Unverified');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_assault);

-- Rejected by admin
INSERT INTO Incidents (member_id, employee_id, description, incident_time, gps_lat, gps_lng, status) VALUES
(@sadia, @admin, 'test test', NOW() - INTERVAL 2 DAY, 23.77800000, 90.38000000, 'False Report');
INSERT INTO Incident_Categories VALUES (LAST_INSERT_ID(), @t_verbal);
