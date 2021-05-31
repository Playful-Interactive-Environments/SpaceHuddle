-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 11. Mai 2021 um 10:17
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
CREATE DATABASE IF NOT EXISTS `gab` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gab`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hierarchy`
--

CREATE TABLE `hierarchy` (
  `group_idea_id` char(36) NOT NULL,
  `sub_idea_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `idea`
--

CREATE TABLE `idea` (
  `id` char(36) NOT NULL,
  `task_id` char(36) NOT NULL,
  `participant_id` char(36) DEFAULT NULL,
  `state` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `keywords` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` longblob DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE `user` (
  `id` char(36) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `module`
--

CREATE TABLE `module` (
  `id` char(36) NOT NULL,
  `task_id` char(36) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `state` varchar(255) NOT NULL,
  `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `module_state`
--

CREATE TABLE `module_state` (
  `module_id` char(36) NOT NULL,
  `participant_id` char(36) NOT NULL,
  `state` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `participant`
--

CREATE TABLE `participant` (
  `id` char(36) NOT NULL,
  `session_id` char(36) NOT NULL,
  `browser_key` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL DEFAULT '''ACTIVE''',
  `color` varchar(255) DEFAULT NULL,
  `symbol` varchar(255) DEFAULT NULL,
  `ip_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `random_idea`
--

CREATE TABLE `random_idea` (
  `idea_id` char(36) NOT NULL,
  `participant_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `resource`
--

CREATE TABLE `resource` (
  `id` char(36) NOT NULL,
  `session_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` longblob DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `selection_group`
--

CREATE TABLE `selection_group` (
  `id` char(36) NOT NULL,
  `topic_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `selection_group_idea`
--

CREATE TABLE `selection_group_idea` (
  `selection_group_id` char(36) NOT NULL,
  `idea_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session`
--

CREATE TABLE `session` (
  `id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `connection_key` char(10) NOT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `creation_date` date NOT NULL DEFAULT current_timestamp(),
  `public_screen_module_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session_role`
--

CREATE TABLE `session_role` (
  `session_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task`
--

CREATE TABLE `task` (
  `id` char(36) NOT NULL,
  `topic_id` char(36) NOT NULL,
  `task_type` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `order` int(11) NOT NULL,
  `state` varchar(255) DEFAULT NULL,
  `active_module_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `topic`
--

CREATE TABLE `topic` (
  `id` char(36) NOT NULL,
  `session_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `active_task_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `voting`
--

CREATE TABLE `voting` (
  `id` char(36) NOT NULL,
  `task_id` char(36) NOT NULL,
  `participant_id` char(36) DEFAULT NULL,
  `idea_id` char(36) NOT NULL,
  `rating` int(11) NOT NULL,
  `detail_rating` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `hierarchy`
--
ALTER TABLE `hierarchy`
  ADD PRIMARY KEY (`group_idea_id`,`sub_idea_id`),
  ADD KEY `hierarchy_ibfk_sub_idea` (`sub_idea_id`);

--
-- Indizes für die Tabelle `idea`
--
ALTER TABLE `idea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idea_ibfk_task` (`task_id`),
  ADD KEY `idea_ibfk_participant` (`participant_id`);

--
-- Indizes für die Tabelle `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indizes für die Tabelle `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_ibfk_task` (`task_id`);

--
-- Indizes für die Tabelle `module_state`
--
ALTER TABLE `module_state`
  ADD PRIMARY KEY (`module_id`,`participant_id`),
  ADD KEY `module_state_ibfk_participant` (`participant_id`);

--
-- Indizes für die Tabelle `participant`
--
ALTER TABLE `participant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `browser_key` (`browser_key`),
  ADD UNIQUE KEY `session_id+ip_hash` (`session_id`,`ip_hash`) USING BTREE;

--
-- Indizes für die Tabelle `random_idea`
--
ALTER TABLE `random_idea`
  ADD PRIMARY KEY (`idea_id`,`participant_id`),
  ADD KEY `random_idea_ibfk_participant` (`participant_id`);

--
-- Indizes für die Tabelle `resource`
--
ALTER TABLE `resource`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_ibfk_session` (`session_id`);

--
-- Indizes für die Tabelle `selection_group`
--
ALTER TABLE `selection_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `selection_group_ibfk_topic` (`topic_id`);

--
-- Indizes für die Tabelle `selection_group_idea`
--
ALTER TABLE `selection_group_idea`
  ADD PRIMARY KEY (`selection_group_id`,`idea_id`),
  ADD KEY `selection_group_idea_ibfk_idea` (`idea_id`);

--
-- Indizes für die Tabelle `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `connection_key` (`connection_key`),
  ADD KEY `session_ibfk_public_screen` (`public_screen_module_id`);

--
-- Indizes für die Tabelle `session_role`
--
ALTER TABLE `session_role`
  ADD PRIMARY KEY (`session_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_ibfk_topic` (`topic_id`),
  ADD KEY `task_ibfk_active_module` (`active_module_id`);

--
-- Indizes für die Tabelle `topic`
--
ALTER TABLE `topic`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_ibfk_session` (`session_id`),
  ADD KEY `topic_ibfk_active_task` (`active_task_id`);

--
-- Indizes für die Tabelle `voting`
--
ALTER TABLE `voting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voting_ibfk_task` (`task_id`),
  ADD KEY `voting_ibfk_participant` (`participant_id`),
  ADD KEY `voting_ibfk_idea` (`idea_id`);

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `hierarchy`
--
ALTER TABLE `hierarchy`
  ADD CONSTRAINT `hierarchy_ibfk_group_idea` FOREIGN KEY (`group_idea_id`) REFERENCES `idea` (`id`),
  ADD CONSTRAINT `hierarchy_ibfk_sub_idea` FOREIGN KEY (`sub_idea_id`) REFERENCES `idea` (`id`);

--
-- Constraints der Tabelle `idea`
--
ALTER TABLE `idea`
  ADD CONSTRAINT `idea_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`),
  ADD CONSTRAINT `idea_ibfk_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`);

--
-- Constraints der Tabelle `module`
--
ALTER TABLE `module`
  ADD CONSTRAINT `module_ibfk_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`);

--
-- Constraints der Tabelle `module_state`
--
ALTER TABLE `module_state`
  ADD CONSTRAINT `module_state_ibfk_module` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`),
  ADD CONSTRAINT `module_state_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`);

--
-- Constraints der Tabelle `participant`
--
ALTER TABLE `participant`
  ADD CONSTRAINT `participant_ibfk_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`);

--
-- Constraints der Tabelle `random_idea`
--
ALTER TABLE `random_idea`
  ADD CONSTRAINT `random_idea_ibfk_idea` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`),
  ADD CONSTRAINT `random_idea_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`);

--
-- Constraints der Tabelle `resource`
--
ALTER TABLE `resource`
  ADD CONSTRAINT `resource_ibfk_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`);

--
-- Constraints der Tabelle `selection_group`
--
ALTER TABLE `selection_group`
  ADD CONSTRAINT `selection_group_ibfk_topic` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`);

--
-- Constraints der Tabelle `selection_group_idea`
--
ALTER TABLE `selection_group_idea`
  ADD CONSTRAINT `selection_group_idea_ibfk_idea` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`),
  ADD CONSTRAINT `selection_group_idea_ibfk_selection_group` FOREIGN KEY (`selection_group_id`) REFERENCES `selection_group` (`id`);

--
-- Constraints der Tabelle `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `session_ibfk_public_screen` FOREIGN KEY (`public_screen_module_id`) REFERENCES `module` (`id`);

--
-- Constraints der Tabelle `session_role`
--
ALTER TABLE `session_role`
  ADD CONSTRAINT `session_role_ibfk_role` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `session_role_ibfk_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`);

--
-- Constraints der Tabelle `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `task_ibfk_active_module` FOREIGN KEY (`active_module_id`) REFERENCES `module` (`id`),
  ADD CONSTRAINT `task_ibfk_topic` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`);

--
-- Constraints der Tabelle `topic`
--
ALTER TABLE `topic`
  ADD CONSTRAINT `topic_ibfk_active_task` FOREIGN KEY (`active_task_id`) REFERENCES `task` (`id`),
  ADD CONSTRAINT `topic_ibfk_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`);

--
-- Constraints der Tabelle `voting`
--
ALTER TABLE `voting`
  ADD CONSTRAINT `voting_ibfk_idea` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`),
  ADD CONSTRAINT `voting_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`),
  ADD CONSTRAINT `voting_ibfk_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
