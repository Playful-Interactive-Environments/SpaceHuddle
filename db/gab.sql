-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 07. Mai 2021 um 10:00
-- Server-Version: 10.4.18-MariaDB
-- PHP-Version: 8.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `gab`
--
CREATE DATABASE IF NOT EXISTS `gab` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gab`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hierarchy`
--

CREATE TABLE `hierarchy` (
  `group_idea_id` int(11) NOT NULL,
  `sub_idea_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `idea`
--

CREATE TABLE `idea` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `state` varchar(255) NOT NULL,
  `time_stamp` datetime NOT NULL DEFAULT current_timestamp(),
  `keywords` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` longblob DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `module`
--

CREATE TABLE `module` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `state` varchar(255) NOT NULL,
  `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `module_state`
--

CREATE TABLE `module_state` (
  `module_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `state` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `participant`
--

CREATE TABLE `participant` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `browser_key` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `symbol` varchar(255) DEFAULT NULL,
  `ip_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `resource`
--

CREATE TABLE `resource` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` longblob DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `selection_group`
--

CREATE TABLE `selection_group` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `selection_group_idea`
--

CREATE TABLE `selection_group_idea` (
  `selection_group_id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session`
--

CREATE TABLE `session` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `connection_key` char(10) NOT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `creation_date` date NOT NULL DEFAULT current_timestamp(),
  `public_screen_module_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session_role`
--

CREATE TABLE `session_role` (
  `session_id` int(11) NOT NULL,
  `login_id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task`
--

CREATE TABLE `task` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `task_type` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `order` int(11) NOT NULL,
  `active_module_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `topic`
--

CREATE TABLE `topic` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `active_task_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `voting`
--

CREATE TABLE `voting` (
  `task_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `detail_rating` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `hierarchy`
--
ALTER TABLE `hierarchy`
  ADD PRIMARY KEY (`group_idea_id`,`sub_idea_id`),
  ADD KEY `sub_idea_id` (`sub_idea_id`);

--
-- Indizes für die Tabelle `idea`
--
ALTER TABLE `idea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `participant_id` (`participant_id`);

--
-- Indizes für die Tabelle `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indizes für die Tabelle `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indizes für die Tabelle `module_state`
--
ALTER TABLE `module_state`
  ADD PRIMARY KEY (`module_id`,`participant_id`),
  ADD KEY `participant_id` (`participant_id`);

--
-- Indizes für die Tabelle `participant`
--
ALTER TABLE `participant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `browser_key` (`browser_key`),
  ADD UNIQUE KEY `session_id+ip_hash` (`session_id`,`ip_hash`) USING BTREE,
  ADD KEY `session_id` (`session_id`);

--
-- Indizes für die Tabelle `resource`
--
ALTER TABLE `resource`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indizes für die Tabelle `selection_group`
--
ALTER TABLE `selection_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indizes für die Tabelle `selection_group_idea`
--
ALTER TABLE `selection_group_idea`
  ADD PRIMARY KEY (`selection_group_id`,`idea_id`),
  ADD KEY `idea_id` (`idea_id`);

--
-- Indizes für die Tabelle `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `connection_key` (`connection_key`),
  ADD KEY `public_screen_module_id` (`public_screen_module_id`);

--
-- Indizes für die Tabelle `session_role`
--
ALTER TABLE `session_role`
  ADD PRIMARY KEY (`session_id`,`login_id`),
  ADD KEY `login_id` (`login_id`);

--
-- Indizes für die Tabelle `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `active_module_id` (`active_module_id`);

--
-- Indizes für die Tabelle `topic`
--
ALTER TABLE `topic`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `active_task_id` (`active_task_id`);

--
-- Indizes für die Tabelle `voting`
--
ALTER TABLE `voting`
  ADD PRIMARY KEY (`task_id`,`participant_id`,`idea_id`),
  ADD KEY `participant_id` (`participant_id`),
  ADD KEY `idea_id` (`idea_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `idea`
--
ALTER TABLE `idea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `module`
--
ALTER TABLE `module`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `participant`
--
ALTER TABLE `participant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `resource`
--
ALTER TABLE `resource`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `selection_group`
--
ALTER TABLE `selection_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `session`
--
ALTER TABLE `session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `task`
--
ALTER TABLE `task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `topic`
--
ALTER TABLE `topic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `hierarchy`
--
ALTER TABLE `hierarchy`
  ADD CONSTRAINT `hierarchy_ibfk_1` FOREIGN KEY (`group_idea_id`) REFERENCES `idea` (`id`),
  ADD CONSTRAINT `hierarchy_ibfk_2` FOREIGN KEY (`sub_idea_id`) REFERENCES `idea` (`id`);

--
-- Constraints der Tabelle `idea`
--
ALTER TABLE `idea`
  ADD CONSTRAINT `idea_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`),
  ADD CONSTRAINT `idea_ibfk_2` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`);

--
-- Constraints der Tabelle `module`
--
ALTER TABLE `module`
  ADD CONSTRAINT `module_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`);

--
-- Constraints der Tabelle `module_state`
--
ALTER TABLE `module_state`
  ADD CONSTRAINT `module_state_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`),
  ADD CONSTRAINT `module_state_ibfk_2` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`);

--
-- Constraints der Tabelle `participant`
--
ALTER TABLE `participant`
  ADD CONSTRAINT `participant_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`);

--
-- Constraints der Tabelle `resource`
--
ALTER TABLE `resource`
  ADD CONSTRAINT `resource_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`);

--
-- Constraints der Tabelle `selection_group`
--
ALTER TABLE `selection_group`
  ADD CONSTRAINT `selection_group_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`);

--
-- Constraints der Tabelle `selection_group_idea`
--
ALTER TABLE `selection_group_idea`
  ADD CONSTRAINT `selection_group_idea_ibfk_1` FOREIGN KEY (`selection_group_id`) REFERENCES `selection_group` (`id`),
  ADD CONSTRAINT `selection_group_idea_ibfk_2` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`);

--
-- Constraints der Tabelle `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `session_ibfk_1` FOREIGN KEY (`public_screen_module_id`) REFERENCES `module` (`id`);

--
-- Constraints der Tabelle `session_role`
--
ALTER TABLE `session_role`
  ADD CONSTRAINT `session_role_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`),
  ADD CONSTRAINT `session_role_ibfk_2` FOREIGN KEY (`login_id`) REFERENCES `login` (`id`);

--
-- Constraints der Tabelle `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`),
  ADD CONSTRAINT `task_ibfk_2` FOREIGN KEY (`active_module_id`) REFERENCES `module` (`id`);

--
-- Constraints der Tabelle `topic`
--
ALTER TABLE `topic`
  ADD CONSTRAINT `topic_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`),
  ADD CONSTRAINT `topic_ibfk_2` FOREIGN KEY (`active_task_id`) REFERENCES `task` (`id`);

--
-- Constraints der Tabelle `voting`
--
ALTER TABLE `voting`
  ADD CONSTRAINT `voting_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`),
  ADD CONSTRAINT `voting_ibfk_2` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`),
  ADD CONSTRAINT `voting_ibfk_3` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
