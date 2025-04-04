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
-- Datenbank: `spacehuddle`
--
CREATE DATABASE IF NOT EXISTS `spacehuddle` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `spacehuddle`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hierarchy`
--

CREATE TABLE IF NOT EXISTS `hierarchy` (
                             `category_idea_id` char(36) NOT NULL,
                             `sub_idea_id` char(36) NOT NULL,
                             `order` int(11) NOT NULL DEFAULT 0,
                             `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `idea`
--

CREATE TABLE IF NOT EXISTS `idea` (
                        `id` char(36) NOT NULL,
                        `task_id` char(36) NOT NULL,
                        `participant_id` char(36) DEFAULT NULL,
                        `state` varchar(255) NOT NULL,
                        `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
                        `keywords` varchar(255) NOT NULL,
                        `description` text DEFAULT NULL,
                        `image` longblob DEFAULT NULL,
                        `image_timestamp` datetime NULL DEFAULT NULL,
                        `link` varchar(500) DEFAULT NULL,
                        `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                        `order` int(11) NOT NULL DEFAULT 0,
                        `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE IF NOT EXISTS `user` (
                        `id` char(36) NOT NULL,
                        `username` varchar(255) NOT NULL,
                        `password` varchar(255) NOT NULL,
                        `role` varchar(10) NOT NULL DEFAULT 'user',
                        `confirmed` TINYINT(1) DEFAULT 0,
                        `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
                        `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                        `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `module`
--

CREATE TABLE IF NOT EXISTS `module` (
                          `id` char(36) NOT NULL,
                          `task_id` char(36) NOT NULL,
                          `module_name` varchar(255) NOT NULL,
                          `order` int(11) NOT NULL,
                          `state` varchar(255) NOT NULL,
                          `sync_public_participant` TINYINT(1),
                          `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                          `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `module_state`
--

CREATE TABLE IF NOT EXISTS `module_state` (
                                `module_id` char(36) NOT NULL,
                                `participant_id` char(36) NOT NULL,
                                `state` varchar(255) NOT NULL,
                                `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `participant`
--

CREATE TABLE IF NOT EXISTS `participant` (
                               `id` char(36) NOT NULL,
                               `session_id` char(36) NOT NULL,
                               `browser_key` varchar(255) NOT NULL,
                               `state` varchar(255) NOT NULL DEFAULT '''ACTIVE''',
                               `color` varchar(255) DEFAULT NULL,
                               `symbol` varchar(255) DEFAULT NULL,
                               `ip_hash` varchar(255) NOT NULL,
                               `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                               `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `random_idea`
--

CREATE TABLE IF NOT EXISTS `random_idea` (
                               `idea_id` char(36) NOT NULL,
                               `participant_id` char(36) NOT NULL,
                               `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `resource`
--

CREATE TABLE IF NOT EXISTS `resource` (
                            `id` char(36) NOT NULL,
                            `session_id` char(36) NOT NULL,
                            `title` varchar(255) NOT NULL,
                            `image` longblob DEFAULT NULL,
                            `link` varchar(255) DEFAULT NULL,
                            `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `selection`
--

CREATE TABLE IF NOT EXISTS `selection` (
                             `id` char(36) NOT NULL,
                             `topic_id` char(36) NOT NULL,
                             `name` varchar(255) NOT NULL,
                             `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `selection_idea`
--

CREATE TABLE IF NOT EXISTS `selection_idea` (
                                  `selection_id` char(36) NOT NULL,
                                  `idea_id` char(36) NOT NULL,
                                  `order` int(11) NOT NULL DEFAULT 0,
                                  `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session`
--

CREATE TABLE IF NOT EXISTS `session` (
                           `id` char(36) NOT NULL,
                           `title` varchar(255) NOT NULL,
                           `description` text DEFAULT NULL,
                           `subject` varchar(255) DEFAULT NULL,
                           `theme` varchar(255) DEFAULT NULL,
                           `topic_activation` varchar(255) DEFAULT NULL,
                           `connection_key` varchar(255) NOT NULL,
                           `visibility` VARCHAR(10) NOT NULL DEFAULT 'private',
                           `max_participants` int(11) DEFAULT NULL,
                           `expiration_date` date DEFAULT NULL,
                           `creation_date` date NOT NULL DEFAULT current_timestamp(),
                           `modification_date` datetime NOT NULL DEFAULT current_timestamp(),
                           `public_screen_module_id` char(36) DEFAULT NULL,
                           `allow_anonymous` TINYINT(1) DEFAULT 0,
                           `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session_role`
--

CREATE TABLE IF NOT EXISTS `session_role` (
                                `session_id` char(36) NOT NULL,
                                `user_id` char(36) NOT NULL,
                                `role` varchar(255) NOT NULL,
                                `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task`
--

CREATE TABLE IF NOT EXISTS `task` (
                        `id` char(36) NOT NULL,
                        `topic_id` char(36) NOT NULL,
                        `task_type` varchar(255) NOT NULL,
                        `name` varchar(255) DEFAULT NULL,
                        `description` text DEFAULT NULL,
                        `keywords` varchar(50) DEFAULT NULL,
                        `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                        `order` int(11) NOT NULL DEFAULT 0,
                        `state` varchar(255) DEFAULT NULL,
                        `dependenceStart` int(11) NOT NULL DEFAULT 0,
                        `dependenceDuration` int(11) NOT NULL DEFAULT 1,
                        `expiration_time` datetime DEFAULT NULL,
                        `active_module_id` char(36) DEFAULT NULL,
                        `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_participant_state`
--

CREATE TABLE IF NOT EXISTS `task_participant_state` (
                                          `id` char(36) NOT NULL,
                                          `task_id` char(36) NOT NULL,
                                          `participant_id` char(36) NOT NULL,
                                          `count` int(11) NOT NULL DEFAULT 1,
                                          `state` varchar(255) DEFAULT NULL,
                                          `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                                          `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_participant_iteration`
--

CREATE TABLE IF NOT EXISTS `task_participant_iteration` (
                                              `id` char(36) NOT NULL,
                                              `task_id` char(36) NOT NULL,
                                              `participant_id` char(36) NOT NULL,
                                              `iteration` int(11) NOT NULL DEFAULT 1,
                                              `state` varchar(255) DEFAULT NULL,
                                              `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                                              `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_participant_iteration_step`
--

CREATE TABLE IF NOT EXISTS `task_participant_iteration_step` (
                                                   `id` char(36) NOT NULL,
                                                   `task_id` char(36) NOT NULL,
                                                   `participant_id` char(36) NOT NULL,
                                                   `iteration` int(11) NOT NULL DEFAULT 1,
                                                   `step` int(11) NOT NULL DEFAULT 1,
                                                   `idea_id` char(36) NULL DEFAULT NULL,
                                                   `state` varchar(255) DEFAULT NULL,
                                                   `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                                                   `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `topic`
--

CREATE TABLE IF NOT EXISTS `topic` (
                         `id` char(36) NOT NULL,
                         `session_id` char(36) NOT NULL,
                         `title` varchar(255) NOT NULL,
                         `description` text DEFAULT NULL,
                         `order` int(11) NOT NULL DEFAULT 0,
                         `state` varchar(255) DEFAULT NULL,
                         `active_task_id` char(36) DEFAULT NULL,
                         `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vote`
--

CREATE TABLE IF NOT EXISTS `vote` (
                        `id` char(36) NOT NULL,
                        `task_id` char(36) NOT NULL,
                        `participant_id` char(36) DEFAULT NULL,
                        `idea_id` char(36) DEFAULT NULL,
                        `rating` int(11) NOT NULL,
                        `detail_rating` float DEFAULT NULL,
                        `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                        `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
                        `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tutorial`
--

CREATE TABLE IF NOT EXISTS `tutorial` (
                            `user_id` CHAR(36) NOT NULL,
                            `step` VARCHAR(255) NOT NULL,
                            `type` VARCHAR(255) NOT NULL,
                            `order` int(11) NOT NULL DEFAULT 0,
                            `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Tabellenstruktur für Tabelle `tutorial_participant`
--

CREATE TABLE IF NOT EXISTS `tutorial_participant` (
                            `participant_id` CHAR(36) NOT NULL,
                            `step` VARCHAR(255) NOT NULL,
                            `type` VARCHAR(255) NOT NULL,
                            `order` int(11) NOT NULL DEFAULT 0,
                            `modification_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Tabellenstruktur für Tabelle `clone_helper`
--

CREATE TABLE IF NOT EXISTS `clone_helper` (
                                `target_id` CHAR(36) NOT NULL,
                                `source_id` CHAR(36) NOT NULL,
                                `table_name` VARCHAR(255) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `hierarchy`
--
ALTER TABLE `hierarchy`
    ADD PRIMARY KEY (`category_idea_id`,`sub_idea_id`),
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
-- Indizes für die Tabelle `selection`
--
ALTER TABLE `selection`
    ADD PRIMARY KEY (`id`),
  ADD KEY `selection_ibfk_topic` (`topic_id`);

--
-- Indizes für die Tabelle `selection_idea`
--
ALTER TABLE `selection_idea`
    ADD PRIMARY KEY (`selection_id`,`idea_id`),
  ADD KEY `selection_idea_ibfk_idea` (`idea_id`);

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
-- Indizes für die Tabelle `task_participant_state`
--
ALTER TABLE `task_participant_state`
    ADD PRIMARY KEY (`id`),
  ADD KEY `task_participant_state_ibfk_task` (`task_id`),
  ADD KEY `task_participant_state_ibfk_participant` (`participant_id`);

--
-- Indizes für die Tabelle `task_participant_iteration`
--
ALTER TABLE `task_participant_iteration`
    ADD PRIMARY KEY (`id`),
  ADD KEY `task_participant_iteration_ibfk_task` (`task_id`),
  ADD KEY `task_participant_iteration_ibfk_participant` (`participant_id`);

--
-- Indizes für die Tabelle `task_participant_iteration_step`
--
ALTER TABLE `task_participant_iteration_step`
    ADD PRIMARY KEY (`id`),
  ADD KEY `task_participant_iteration_step_ibfk_task` (`task_id`),
  ADD KEY `task_participant_iteration_step_ibfk_participant` (`participant_id`),
  ADD KEY `task_participant_iteration_step_ibfk_idea` (`idea_id`);

--
-- Indizes für die Tabelle `topic`
--
ALTER TABLE `topic`
    ADD PRIMARY KEY (`id`),
  ADD KEY `topic_ibfk_session` (`session_id`),
  ADD KEY `topic_ibfk_active_task` (`active_task_id`);

--
-- Indizes für die Tabelle `vote`
--
ALTER TABLE `vote`
    ADD PRIMARY KEY (`id`),
  ADD KEY `vote_ibfk_task` (`task_id`),
  ADD KEY `vote_ibfk_participant` (`participant_id`),
  ADD KEY `vote_ibfk_idea` (`idea_id`);

--
-- Indizes für die Tabelle `tutorial`
--
ALTER TABLE `tutorial` ADD PRIMARY KEY(`user_id`, `step`, `type`);

--
-- Indizes für die Tabelle `tutorial_participant`
--
ALTER TABLE `tutorial_participant` ADD PRIMARY KEY(`participant_id`, `step`, `type`);

--
-- Indizes für die Tabelle `clone_helper`
--
ALTER TABLE `clone_helper` ADD PRIMARY KEY(`target_id`);

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `hierarchy`
--
ALTER TABLE `hierarchy`
    ADD CONSTRAINT `hierarchy_ibfk_category_idea` FOREIGN KEY (`category_idea_id`) REFERENCES `idea` (`id`),
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
-- Constraints der Tabelle `selection`
--
ALTER TABLE `selection`
    ADD CONSTRAINT `selection_ibfk_topic` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`);

--
-- Constraints der Tabelle `selection_idea`
--
ALTER TABLE `selection_idea`
    ADD CONSTRAINT `selection_idea_ibfk_idea` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`),
  ADD CONSTRAINT `selection_idea_ibfk_selection` FOREIGN KEY (`selection_id`) REFERENCES `selection` (`id`);

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
-- Constraints der Tabelle `task_participant_state`
--
ALTER TABLE `task_participant_state`
    ADD CONSTRAINT `task_participant_state_unique` UNIQUE (`task_id`, `participant_id`),
    ADD CONSTRAINT `task_participant_state_ibfk_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`),
    ADD CONSTRAINT `task_participant_state_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`);

--
-- Constraints der Tabelle `task_participant_iteration`
--
ALTER TABLE `task_participant_iteration`
    ADD CONSTRAINT `task_participant_iteration_unique` UNIQUE (`task_id`, `participant_id`, `iteration`),
    ADD CONSTRAINT `task_participant_iteration_ibfk_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`),
    ADD CONSTRAINT `task_participant_iteration_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`);

--
-- Constraints der Tabelle `task_participant_iteration_step`
--
ALTER TABLE `task_participant_iteration_step`
    ADD CONSTRAINT `task_participant_iteration_step_unique` UNIQUE (`task_id`, `participant_id`, `iteration`, `step`),
    ADD CONSTRAINT `task_participant_iteration_step_ibfk_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`),
    ADD CONSTRAINT `task_participant_iteration_step_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`),
    ADD CONSTRAINT `task_participant_iteration_step_ibfk_idea` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`);

--
-- Constraints der Tabelle `topic`
--
ALTER TABLE `topic`
    ADD CONSTRAINT `topic_ibfk_active_task` FOREIGN KEY (`active_task_id`) REFERENCES `task` (`id`),
  ADD CONSTRAINT `topic_ibfk_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`);

--
-- Constraints der Tabelle `vote`
--
ALTER TABLE `vote`
    ADD CONSTRAINT `vote_ibfk_idea` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`),
  ADD CONSTRAINT `vote_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`),
  ADD CONSTRAINT `vote_ibfk_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`);

--
-- Constraints der Tabelle `tutorial`
--
ALTER TABLE `tutorial`
    ADD CONSTRAINT `tutorial_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`);

--
-- Constraints der Tabelle `tutorial_participant`
--
ALTER TABLE `tutorial_participant`
    ADD CONSTRAINT `tutorial_ibfk_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant`(`id`);

-- --------------------------------------------------------

--
-- Struktur des Views `session_permission`
--

CREATE OR REPLACE VIEW session_permission (
    user_id,
    user_type,
    user_state,
    session_id,
    role
) AS SELECT
    user_id,
    'USER' COLLATE utf8mb4_unicode_ci AS user_type,
    'ACTIVE' COLLATE utf8mb4_unicode_ci AS user_state,
    session_id,
    role
 FROM session_role
 UNION ALL
 SELECT
     id as user_id,
     'PARTICIPANT' COLLATE utf8mb4_unicode_ci AS user_type,
     state COLLATE utf8mb4_unicode_ci AS user_state,
     session_id,
     'PARTICIPANT' COLLATE utf8mb4_unicode_ci AS role
 from participant
 where state = 'ACTIVE'
 UNION ALL
 SELECT
     id as user_id,
     'PARTICIPANT' COLLATE utf8mb4_unicode_ci AS user_type,
     state COLLATE utf8mb4_unicode_ci AS user_state,
     session_id,
     'INACTIVE' COLLATE utf8mb4_unicode_ci AS role
 from participant
 where state = 'INACTIVE';

/*CREATE OR REPLACE VIEW session_permission (
    user_id,
    user_type,
    user_state,
    session_id,
    role
) AS SELECT
    user_id,
    'USER' COLLATE utf8mb4_unicode_ci AS user_type,
    'ACTIVE' COLLATE utf8mb4_unicode_ci AS user_state,
    session_id,
    role
FROM session_role
UNION ALL
SELECT
    id as user_id,
    'PARTICIPANT' COLLATE utf8mb4_unicode_ci AS user_type,
    state COLLATE utf8mb4_unicode_ci AS user_state,
    session_id,
    CASE state
        WHEN 'ACTIVE' THEN 'PARTICIPANT'
        WHEN 'INACTIVE' THEN 'INACTIVE'
        END AS role
from participant;*/

CREATE OR REPLACE VIEW hierarchy_idea(
    parent_idea_id,
    child_idea_id,
    `order`
) AS SELECT
    hierarchy.category_idea_id,
    hierarchy.sub_idea_id,
    hierarchy.`order`
FROM
    hierarchy
INNER JOIN idea parent_idea ON
    hierarchy.category_idea_id = parent_idea.id
INNER JOIN idea child_idea ON
    hierarchy.sub_idea_id = child_idea.id
WHERE
    parent_idea.task_id = child_idea.task_id;

CREATE OR REPLACE VIEW hierarchy_task(
    category_idea_id,
    sub_idea_id,
    `order`,
    task_id
) AS SELECT
     hierarchy.category_idea_id,
     hierarchy.sub_idea_id,
     hierarchy.`order`,
     parent_idea.task_id
 FROM
     hierarchy
 INNER JOIN idea parent_idea ON
     hierarchy.category_idea_id = parent_idea.id;

CREATE OR REPLACE VIEW vote_result(
    task_id,
    idea_id,
    sum_rating,
    sum_detail_rating,
    avg_rating,
    avg_detail_rating,
    count_rating,
    count_detail_rating,
    count_participant
) AS SELECT
    vote.task_id,
    vote.idea_id,
    SUM(vote.rating) AS sum_rating,
    SUM(vote.detail_rating) AS sum_detail_rating,
    AVG(vote.rating) AS avg_rating,
    AVG(vote.detail_rating) AS avg_detail_rating,
    COUNT(vote.rating) AS count_rating,
    COUNT(vote.detail_rating) AS count_detail_rating,
    COUNT(distinct vote.participant_id) AS count_participant
FROM
    vote
GROUP BY
    vote.idea_id,
    vote.task_id
ORDER BY
    vote.task_id,
    sum_detail_rating
DESC;

CREATE OR REPLACE VIEW vote_result_detail(
    task_id,
    idea_id,
    rating,
    detail_rating,
    count_participant
) AS SELECT
    vote.task_id,
    vote.idea_id,
    vote.rating,
    vote.detail_rating,
    COUNT(distinct vote.participant_id) AS count_participant
 FROM
     vote
 GROUP BY
     vote.idea_id,
     vote.task_id,
     vote.rating,
     vote.detail_rating
 ORDER BY
     vote.task_id,
     count_participant
DESC;

CREATE OR REPLACE VIEW vote_result_hierarchy(
    task_id,
    parent_idea_id,
    idea_id,
    sum_rating,
    sum_detail_rating,
    avg_rating,
    avg_detail_rating,
    count_rating,
    count_detail_rating,
    count_participant
) AS SELECT
    vote_result.task_id,
    hierarchy_idea.parent_idea_id,
    vote_result.idea_id,
    vote_result.sum_rating,
    vote_result.sum_detail_rating,
    vote_result.avg_rating,
    vote_result.avg_detail_rating,
    vote_result.count_rating,
    vote_result.count_detail_rating,
    vote_result.count_participant
FROM
    vote_result
INNER JOIN hierarchy_idea ON
    hierarchy_idea.child_idea_id = vote_result.idea_id;

CREATE OR REPLACE VIEW vote_result_hierarchy_detail(
    task_id,
    parent_idea_id,
    idea_id,
    rating,
    detail_rating,
    count_participant
) AS SELECT
    vote_result_detail.task_id,
    hierarchy_idea.parent_idea_id,
    vote_result_detail.idea_id,
    vote_result_detail.rating,
    vote_result_detail.detail_rating,
    vote_result_detail.count_participant
FROM
    vote_result_detail
INNER JOIN hierarchy_idea ON
    hierarchy_idea.child_idea_id = vote_result_detail.idea_id;

CREATE OR REPLACE VIEW vote_result_parent(
    task_id,
    idea_id,
    sum_rating,
    sum_detail_rating,
    avg_rating,
    avg_detail_rating,
    count_rating,
    count_detail_rating,
    count_participant
) AS SELECT
    vote.task_id,
    hierarchy_idea.parent_idea_id,
    SUM(vote.rating) AS sum_rating,
    SUM(vote.detail_rating) AS sum_detail_rating,
    AVG(vote.rating) AS avg_rating,
    AVG(vote.detail_rating) AS avg_detail_rating,
    COUNT(vote.participant_id) AS count_rating,
    COUNT(vote.participant_id) AS count_detail_rating,
    COUNT(distinct vote.participant_id) AS count_participant
FROM
    vote
INNER JOIN hierarchy_idea ON
    hierarchy_idea.child_idea_id = vote.idea_id
GROUP BY
    vote.task_id,
    hierarchy_idea.parent_idea_id
ORDER BY
    vote.task_id,
    sum_detail_rating
DESC;

CREATE OR REPLACE VIEW vote_result_parent_detail(
    task_id,
    idea_id,
    rating,
    detail_rating,
    count_participant
) AS SELECT
    vote.task_id,
    hierarchy_idea.parent_idea_id,
    vote.rating,
    vote.detail_rating,
    COUNT(distinct vote.participant_id) AS count_participant
FROM
    vote
INNER JOIN hierarchy_idea ON
    hierarchy_idea.child_idea_id = vote.idea_id
GROUP BY
    vote.task_id,
    hierarchy_idea.parent_idea_id,
    vote.rating,
    vote.detail_rating
ORDER BY
    vote.task_id,
    count_participant
DESC;

CREATE OR REPLACE VIEW selection_view (type, id, task_id, topic_id, name, detail_type, modules, modification_date) AS
SELECT
    'SELECTION' COLLATE utf8mb4_unicode_ci AS type,
    selection.id,
    task.id as task_id,
    selection.topic_id,
    selection.name,
    task.task_type COLLATE utf8mb4_unicode_ci AS detail_type,
    '' COLLATE utf8mb4_unicode_ci AS modules,
    selection.modification_date
FROM
    selection
LEFT JOIN task ON
    task.task_type = 'SELECTION'
    AND task.topic_id = selection.topic_id
    AND JSON_CONTAINS(task.parameter, JSON_QUOTE(selection.id), '$.selectionId')
UNION ALL
SELECT
    'TASK' COLLATE utf8mb4_unicode_ci AS type,
    id,
    id as task_id,
    topic_id,
    name,
    task_type COLLATE utf8mb4_unicode_ci AS detail_type,
    ( SELECT GROUP_CONCAT(module.module_name)
      FROM module
      WHERE module.task_id = task.id
    ) AS modules,
    modification_date
FROM
    task
WHERE
    task_type IN ('BRAINSTORMING', 'CATEGORISATION', 'INFORMATION', 'PLAYING')
UNION ALL
SELECT
    'VOTE' COLLATE utf8mb4_unicode_ci AS type,
    id,
    id as task_id,
    topic_id,
    name,
    task_type COLLATE utf8mb4_unicode_ci AS detail_type,
    ( SELECT GROUP_CONCAT(module.module_name)
      FROM module
      WHERE module.task_id = task.id
    ) AS modules,
    modification_date
FROM
    task
WHERE
    task.task_type = 'VOTING'
UNION ALL
SELECT
    'HIERARCHY' COLLATE utf8mb4_unicode_ci AS type,
    idea.id,
    idea.task_id,
    task.topic_id,
    idea.keywords AS name,
    task.task_type COLLATE utf8mb4_unicode_ci AS detail_type,
    '' COLLATE utf8mb4_unicode_ci AS modules,
    idea.modification_date
FROM
    idea
INNER JOIN task ON
    task.id = idea.task_id
WHERE
    EXISTS(
    SELECT
        1
    FROM
        hierarchy
    WHERE
        hierarchy.category_idea_id = idea.id
);

CREATE OR REPLACE VIEW selection_view_idea_selection (parent_id, idea_id, `order`) AS
SELECT
        selection_id AS type_id,
        idea_id,
        `order`
FROM
    selection_idea;

CREATE OR REPLACE VIEW selection_view_idea_task (parent_id, idea_id, `order`) AS
SELECT
        task_id AS type_id,
        id AS idea_id,
        `order`
FROM
    idea
WHERE
    NOT EXISTS(
    SELECT
        1
    FROM
        hierarchy
    INNER JOIN idea parent_idea ON
        hierarchy.category_idea_id = parent_idea.id
    WHERE
        hierarchy.sub_idea_id = idea.id AND parent_idea.task_id = idea.task_id
);

CREATE OR REPLACE VIEW selection_view_idea_vote (parent_id, idea_id, `order`) AS
SELECT
        task_id AS type_id,
        idea_id,
        sum_detail_rating
FROM
    vote_result;

CREATE OR REPLACE VIEW selection_view_idea_hierarchy (parent_id, idea_id, `order`) AS
SELECT
        category_idea_id AS type_id,
        sub_idea_id AS idea_id,
        `order`
FROM
    hierarchy;

CREATE OR REPLACE VIEW selection_view_idea (type, parent_id, idea_id, `order`) AS
SELECT
    'SELECTION' COLLATE utf8mb4_unicode_ci AS type,
    parent_id,
    idea_id,
    `order`
FROM
    selection_view_idea_selection
UNION ALL
SELECT
    'TASK' COLLATE utf8mb4_unicode_ci AS type,
    parent_id,
    idea_id,
    `order`
FROM
    selection_view_idea_task
UNION ALL
SELECT
    'VOTE' COLLATE utf8mb4_unicode_ci AS type,
    parent_id,
    idea_id,
    `order`
FROM
    selection_view_idea_vote
UNION ALL
SELECT
    'HIERARCHY' COLLATE utf8mb4_unicode_ci AS type,
    parent_id,
    idea_id,
    `order`
FROM
    selection_view_idea_hierarchy;

CREATE OR REPLACE VIEW synchro_task (id) AS
SELECT
    task.id
FROM
    task
WHERE
    (
        EXISTS(
            SELECT
                 1
            FROM
                 module
            WHERE
                 module.task_id = task.id AND module.sync_public_participant
        )
);

CREATE OR REPLACE VIEW participant_task (id) AS
SELECT
    task.id
FROM
    task
        INNER JOIN topic ON topic.id = task.topic_id
        INNER JOIN session ON session.id = topic.session_id
WHERE
    (
            EXISTS(
                SELECT
                    1
                FROM
                    module
                WHERE
                        module.task_id = task.id AND session.public_screen_module_id = module.id
                ) AND EXISTS(
                SELECT
                    1
                FROM
                    module
                WHERE
                        module.task_id = task.id AND module.sync_public_participant
                )
        ) OR (
            task.state IN('ACTIVE', 'READ_ONLY')
        AND (task.expiration_time IS NULL OR task.expiration_time >= CURRENT_TIMESTAMP())
        AND NOT EXISTS(
        SELECT
            1
        FROM
            module
        WHERE
                module.task_id = task.id AND module.sync_public_participant
        )
    );

CREATE OR REPLACE VIEW participant_topic (id, participant_id) AS
SELECT
    topic.id,
    participant.id as participant_id
FROM
    topic
INNER JOIN session ON session.id = topic.session_id
INNER JOIN participant ON participant.session_id = session.id
WHERE
    (
        topic.state IN('ACTIVE') AND(
            UPPER(session.topic_activation) = 'ALWAYS' OR(
                NOT EXISTS(
                SELECT
                    1
                FROM
                    topic AS previous_topic
                WHERE
                    previous_topic.state IN('ACTIVE') AND
                    previous_topic.session_id = topic.session_id AND previous_topic.order < topic.order
            ) OR(
                (
                    SELECT
                        COUNT(topic.id)
                    FROM
                        topic AS previous_topic
                    WHERE
                        previous_topic.state IN('ACTIVE') AND
                        previous_topic.session_id = topic.session_id AND
                        previous_topic.order < topic.order
                    ) =(
                        SELECT
                            COUNT(topic.id)
                        FROM
                            topic AS previous_topic
                        WHERE
                            previous_topic.state IN('ACTIVE') AND
                            previous_topic.session_id = topic.session_id AND
                            previous_topic.order < topic.order AND
                            NOT EXISTS(
                                SELECT
                                    1
                                FROM
                                    task
                                INNER JOIN participant_task ON participant_task.id = task.id
                                WHERE
                                    task.topic_id = previous_topic.id AND NOT EXISTS(
                                    SELECT
                                        1
                                    FROM
                                        task_participant_state
                                    WHERE
                                        task_participant_state.participant_id = participant.id AND
                                        task_participant_state.task_id = task.id AND(
                                            task_participant_state.state = 'FINISHED' OR
                                            (
                                                UPPER(session.topic_activation) = 'AFTER_PREVIOUS_STARTED' AND
                                                EXISTS(
                                                    SELECT
                                                        1
                                                    FROM
                                                        task_participant_iteration
                                                    WHERE
                                                        task_participant_iteration.state != 'IN_PROGRESS' AND
                                                        task_participant_iteration.participant_id = participant.id AND
                                                        task_participant_iteration.task_id = task.id
                                                )
                                            )
                                        )
                                )
                            )
                    )
                )
            )
        )
    );


CREATE OR REPLACE VIEW user_module (user_id, task_type, module_name) AS
SELECT DISTINCT
    user_id,
    task_type,
    module_name
FROM
    module
        INNER JOIN task ON task.id = module.task_id
        INNER JOIN topic ON topic.id = task.topic_id
        INNER JOIN session_role ON session_role.session_id = topic.session_id;

CREATE OR REPLACE VIEW task_input (task_id, input_type, input_id, max_count, filter, `order`) AS
SELECT
    id AS task_id,
    JSON_UNQUOTE(
        JSON_EXTRACT(
            parameter,
            CONCAT('$.input[', idx, '].view.type')
        )
    ) AS input_type,
    JSON_UNQUOTE(
        JSON_EXTRACT(
            parameter,
            CONCAT('$.input[', idx, '].view.id')
        )
    ) AS input_id,
    NULLIF(
        LOWER(
            JSON_EXTRACT(
                    parameter,
                    CONCAT('$.input[', idx, '].maxCount')
                )
        ), 'null'
    ) AS max_count,
    JSON_EXTRACT(
        parameter,
        CONCAT('$.input[', idx, '].filter')
    ) AS filter,
    JSON_UNQUOTE(
        JSON_EXTRACT(
            parameter,
            CONCAT('$.input[', idx, '].order')
        )
    ) AS `order`
FROM
    task
JOIN(
        SELECT 0 AS idx
        UNION
        SELECT
            1 AS idx
        UNION
        SELECT
            2 AS idx
        UNION
        SELECT
            3 AS idx
        UNION
        SELECT
            4 AS idx
        UNION
        SELECT
            5 AS idx
        UNION
        SELECT
            6 AS idx
        UNION
        SELECT
            7 AS idx
        UNION
        SELECT
            8 AS idx
        UNION
        SELECT
            9 AS idx
        UNION
        SELECT
            10 AS idx
        UNION
        SELECT
            11 AS idx
        UNION
        SELECT
            12 AS idx
        UNION
        SELECT
            13 AS idx
        UNION
        SELECT
            14 AS idx
        UNION
        SELECT
            15 AS idx
        UNION
        SELECT
            16 AS idx
        UNION
        SELECT
            17 AS idx
        UNION
        SELECT
            18 AS idx
        UNION
        SELECT
            19 AS idx
        UNION
        SELECT
            20 AS idx
    ) AS INDEXES
WHERE
    JSON_EXTRACT(
            parameter,
            CONCAT('$.input[', idx, ']')
        ) IS NOT NULL;

CREATE OR REPLACE VIEW task_selection (task_id, task_type, selection_id) AS
SELECT
    id AS task_id,
    task_type,
    JSON_UNQUOTE(
        JSON_EXTRACT(
            parameter,
            CONCAT('$.selectionId[', idx, ']')
        )
    ) AS selection_id
FROM
    task
JOIN(
        SELECT 0 AS idx
        UNION
        SELECT
            1 AS idx
        UNION
        SELECT
            2 AS idx
        UNION
        SELECT
            3 AS idx
        UNION
        SELECT
            4 AS idx
        UNION
        SELECT
            5 AS idx
        UNION
        SELECT
            6 AS idx
        UNION
        SELECT
            7 AS idx
        UNION
        SELECT
            8 AS idx
        UNION
        SELECT
            9 AS idx
        UNION
        SELECT
            10 AS idx
        UNION
        SELECT
            11 AS idx
        UNION
        SELECT
            12 AS idx
        UNION
        SELECT
            13 AS idx
        UNION
        SELECT
            14 AS idx
        UNION
        SELECT
            15 AS idx
        UNION
        SELECT
            16 AS idx
        UNION
        SELECT
            17 AS idx
        UNION
        SELECT
            18 AS idx
        UNION
        SELECT
            19 AS idx
        UNION
        SELECT
            20 AS idx
    ) AS INDEXES
WHERE
    JSON_EXTRACT(
        parameter,
        CONCAT('$.selectionId[', idx, ']')
    ) IS NOT NULL
AND
    JSON_UNQUOTE(
        JSON_EXTRACT(
            parameter,
            CONCAT('$.selectionId[', idx, ']')
        )) != '';

CREATE OR REPLACE VIEW task_info (task_id, task_type, participant_count) AS
SELECT
    task_id,
    task_type,
    COUNT(DISTINCT participant_id) AS participant_count
FROM
    (
        SELECT
            state.task_id,
            state.participant_id
        FROM
            `task_participant_state` AS state
        UNION
        SELECT
            vote.task_id,
            vote.participant_id
        FROM
            vote
        UNION
        SELECT
            idea.task_id,
            idea.participant_id
        FROM
            idea
    ) AS task_info
        INNER JOIN task ON task.id = task_info.task_id
GROUP BY
    task_id;

CREATE OR REPLACE VIEW user_state (id, username, creation_date, confirmed, own_sessions, shared_sessions) AS
SELECT
    user.id,
    user.username,
    user.creation_date,
    user.confirmed,
    (
        SELECT
            COUNT(*)
        FROM
            session_role
        WHERE
                session_role.user_id = user.id AND session_role.role = 'owner'
    ) AS own_sessions,
    (
        SELECT
            COUNT(*)
        FROM
            session_role
        WHERE
                session_role.user_id = user.id AND session_role.role != 'owner'
    ) AS shared_sessions
FROM
    user;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
