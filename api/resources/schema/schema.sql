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

CREATE TABLE `hierarchy` (
                             `category_idea_id` char(36) NOT NULL,
                             `sub_idea_id` char(36) NOT NULL,
                             `order` int(11) NOT NULL DEFAULT 0
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
                        `link` varchar(500) DEFAULT NULL,
                        `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                        `order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE `user` (
                        `id` char(36) NOT NULL,
                        `username` varchar(255) NOT NULL,
                        `password` varchar(255) NOT NULL,
                        `confirmed` BIT(1) DEFAULT 0,
                        `creation_date` datetime NOT NULL DEFAULT current_timestamp()
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
                          `sync_public_participant` BIT(1),
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
-- Tabellenstruktur für Tabelle `selection`
--

CREATE TABLE `selection` (
                             `id` char(36) NOT NULL,
                             `topic_id` char(36) NOT NULL,
                             `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `selection_idea`
--

CREATE TABLE `selection_idea` (
                                  `selection_id` char(36) NOT NULL,
                                  `idea_id` char(36) NOT NULL,
                                  `order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session`
--

CREATE TABLE `session` (
                           `id` char(36) NOT NULL,
                           `title` varchar(255) NOT NULL,
                           `description` text DEFAULT NULL,
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
                        `description` text DEFAULT NULL,
                        `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                        `order` int(11) NOT NULL DEFAULT 0,
                        `state` varchar(255) DEFAULT NULL,
                        `expiration_time` datetime DEFAULT NULL,
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
                         `description` text DEFAULT NULL,
                         `order` int(11) NOT NULL DEFAULT 0,
                         `active_task_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vote`
--

CREATE TABLE `vote` (
                        `id` char(36) NOT NULL,
                        `task_id` char(36) NOT NULL,
                        `participant_id` char(36) DEFAULT NULL,
                        `idea_id` char(36) NOT NULL,
                        `rating` int(11) NOT NULL,
                        `detail_rating` float DEFAULT NULL,
                        `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vote`
--

CREATE TABLE `tutorial` (
    `user_id` CHAR(36) NOT NULL,
    `step` VARCHAR(255) NOT NULL,
    `type` VARCHAR(255) NOT NULL,
    `order` int(11) NOT NULL DEFAULT 0
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
    CASE state
        WHEN 'ACTIVE' THEN 'PARTICIPANT'
        WHEN 'INACTIVE' THEN 'INACTIVE'
        END AS role
from participant;

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

CREATE OR REPLACE VIEW vote_result(
    task_id,
    idea_id,
    sum_rating,
    sum_detail_rating,
    avg_rating,
    avg_detail_rating,
    count_rating,
    count_detail_rating
) AS SELECT
    vote.task_id,
    vote.idea_id,
    SUM(vote.rating) AS sum_rating,
    SUM(vote.detail_rating) AS sum_detail_rating,
    AVG(vote.rating) AS avg_rating,
    AVG(vote.detail_rating) AS avg_detail_rating,
    COUNT(vote.rating) AS count_rating,
    COUNT(vote.detail_rating) AS count_detail_rating
FROM
    vote
GROUP BY
    vote.idea_id,
    vote.task_id
ORDER BY
    vote.task_id,
    sum_detail_rating
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
    count_detail_rating
) AS SELECT
    vote_result.task_id,
    hierarchy_idea.parent_idea_id,
    vote_result.idea_id,
    vote_result.sum_rating,
    vote_result.sum_detail_rating,
    vote_result.avg_rating,
    vote_result.avg_detail_rating,
    vote_result.count_rating,
    vote_result.count_detail_rating
FROM
    vote_result
INNER JOIN hierarchy_idea ON
    hierarchy_idea.child_idea_id = vote_result.idea_id;

CREATE OR REPLACE VIEW selection_view (type, id, task_id, topic_id, name, detail_type) AS
SELECT
    'SELECTION' COLLATE utf8mb4_unicode_ci AS type,
    selection.id,
    task.id as task_id,
    selection.topic_id,
    selection.name,
    '' COLLATE utf8mb4_unicode_ci AS detail_type
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
    task_type COLLATE utf8mb4_unicode_ci AS detail_type
FROM
    task
WHERE
    task_type IN ('BRAINSTORMING', 'CATEGORISATION', 'INFORMATION')
UNION ALL
SELECT
    'VOTE' COLLATE utf8mb4_unicode_ci AS type,
    id,
    id as task_id,
    topic_id,
    name,
    '' COLLATE utf8mb4_unicode_ci AS detail_type
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
    task.task_type COLLATE utf8mb4_unicode_ci AS detail_type
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
        task.state IN('ACTIVE', 'READ_ONLY') AND(
        task.expiration_time IS NULL OR task.expiration_time >= CURRENT_TIMESTAMP())
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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
