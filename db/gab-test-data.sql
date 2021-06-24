
--
-- Daten für Tabelle `user`
--

INSERT INTO `user` (`id`, `username`, `password`) VALUES
('0509a7a0-22e7-4f17-997e-c13c0baae0b8', 'xxx', 'b45cffe084dd3d20d928bee85e7b0f21'),
('4f66324b-3905-42a8-b5d1-0418e1d65f19', 'testuser', '5d9c68c6c50ed3d02a2fcf54f63993b6');


--
-- Daten für Tabelle `session`
--

INSERT INTO `session` (`id`, `title`, `connection_key`, `max_participants`, `expiration_date`, `creation_date`, `public_screen_module_id`) VALUES
('0dc5ee31-4866-4945-a2b2-c44e951c940a', 'first session', 'ZP4L4QFX', 57, '2021-10-31', '2021-05-11', NULL),
('a87ad118-7d00-4d7b-bbf7-2aa8d0dcdb83', 'second session', 'FPSBB840', 50, '2021-10-01', '2021-05-11', NULL);


--
-- Daten für Tabelle `session_role`
--

INSERT INTO `session_role` (`session_id`, `user_id`, `role`) VALUES
('0dc5ee31-4866-4945-a2b2-c44e951c940a', '4f66324b-3905-42a8-b5d1-0418e1d65f19', 'MODERATOR'),
('a87ad118-7d00-4d7b-bbf7-2aa8d0dcdb83', '4f66324b-3905-42a8-b5d1-0418e1d65f19', 'MODERATOR');


--
-- Daten für Tabelle `participant`
--

INSERT INTO `participant` (`id`, `session_id`, `browser_key`, `color`, `symbol`, `ip_hash`) VALUES
('2b80635f-0de7-46ed-8746-28286cecb22b', 'a87ad118-7d00-4d7b-bbf7-2aa8d0dcdb83', 'FPSBB840.TF5I4WJR', '#46CC6F', 'circle', '421aa90e079fa326b6494f812ad13e79'),
('927d6df3-0b2d-4fa9-b0e2-a0c245065fd1', '0dc5ee31-4866-4945-a2b2-c44e951c940a', 'ZP4L4QFX.OK3Y3D4Z', '#C39E29', 'triangle', '421aa90e079fa326b6494f812ad13e79');


--
-- Daten für Tabelle `topic`
--

INSERT INTO `topic` (`id`, `session_id`, `title`, `description`, `active_task_id`) VALUES
('3383ad4d-0bac-4a18-9b9a-36f164082d41', '0dc5ee31-4866-4945-a2b2-c44e951c940a', 'first question', NULL, NULL),
('b6dd67ea-0ae6-4f83-9e10-73ea066ce702', '0dc5ee31-4866-4945-a2b2-c44e951c940a', 'second question', NULL, NULL);


--
-- Daten für Tabelle `task`
--

INSERT INTO `task` (`id`, `topic_id`, `task_type`, `name`, `parameter`, `order`, `state`, `active_module_id`) VALUES
('3b106e9a-7719-4e03-9603-adac327bd5b5', '3383ad4d-0bac-4a18-9b9a-36f164082d41', 'CATEGORISATION', 'group', '{\"test\":\"abc\"}', 2, 'ACTIVE', NULL),
('535ca0d5-a12c-423b-b43a-a8a19d098e19', '3383ad4d-0bac-4a18-9b9a-36f164082d41', 'BRAINSTORMING', 'create ideas', NULL, 1, 'ACTIVE', NULL),
('7c981e07-0cbc-465a-aa7e-3c8815d58926', '3383ad4d-0bac-4a18-9b9a-36f164082d41', 'SELECTION', 'first selection', '{}', 0, 'WAIT', NULL),
('b1dc33e9-9608-4ab9-8fd5-45f9f744c7ca', '3383ad4d-0bac-4a18-9b9a-36f164082d41', 'VOTING', 'first voting', NULL, 0, 'ACTIVE', NULL);

--
-- Daten für Tabelle `module`
--

INSERT INTO `module` (`id`, `task_id`, `module_name`, `order`, `state`, `parameter`) VALUES
('22d2ae59-19c0-47fa-b95b-33ac4ae1c839', 'b1dc33e9-9608-4ab9-8fd5-45f9f744c7ca', 'VOTING', 1, 'ACTIVE', NULL),
('2ca91e6e-7ec7-4578-b8af-c84707738290', '3b106e9a-7719-4e03-9603-adac327bd5b5', 'GROUPING', 1, 'ACTIVE', NULL),
('e01501e9-d58c-4bef-9c8f-7775ffc8ed29', '535ca0d5-a12c-423b-b43a-a8a19d098e19', 'BRAINSTORMING', 1, 'ACTIVE', NULL),
('f06ef02c-6fed-47e8-961f-9b9eb58d8265', '7c981e07-0cbc-465a-aa7e-3c8815d58926', 'SELECTION', 1, 'ACTIVE', NULL);


--
-- Daten für Tabelle `idea`
--

INSERT INTO `idea` (`id`, `task_id`, `participant_id`, `state`, `timestamp`, `keywords`, `description`, `image`, `link`) VALUES
('1eb2db21-71c8-4a41-bb0c-b642d61aa172', '3b106e9a-7719-4e03-9603-adac327bd5b5', NULL, 'NEW', '2021-05-17 13:45:28', 'first group', 'test grouping', NULL, NULL),
('282e525b-3c9f-41f7-89c5-c881bde0228e', '3b106e9a-7719-4e03-9603-adac327bd5b5', NULL, 'NEW', '2021-05-17 13:46:03', 'second group', NULL, NULL, NULL),
('4b93e92a-3dc0-47c4-9063-789f7c17f7fb', '535ca0d5-a12c-423b-b43a-a8a19d098e19', '927d6df3-0b2d-4fa9-b0e2-a0c245065fd1', 'NEW', '2021-05-11 15:25:47', 'first idea', NULL, NULL, NULL),
('879db026-0b9f-4b5c-a5fe-916e8b63f110', '3b106e9a-7719-4e03-9603-adac327bd5b5', NULL, 'NEW', '2021-05-17 15:55:23', 'next group', NULL, NULL, NULL),
('f7a4610d-f5a2-4c94-b9e1-7a02cf7c1406', '535ca0d5-a12c-423b-b43a-a8a19d098e19', '927d6df3-0b2d-4fa9-b0e2-a0c245065fd1', 'NEW', '2021-05-11 13:29:38', 'second idea', NULL, NULL, NULL);


--
-- Daten für Tabelle `selection_group`
--

INSERT INTO `selection_group` (`id`, `topic_id`, `name`) VALUES
('7c6052e7-e904-4263-a503-e89df346fffc', '3383ad4d-0bac-4a18-9b9a-36f164082d41', 'first selection');

--
-- Daten für Tabelle `voting`
--

INSERT INTO `voting` (`id`, `task_id`, `participant_id`, `idea_id`, `rating`, `detail_rating`) VALUES
('734bfc9b-b844-43f8-896e-4c9d660fb589', 'b1dc33e9-9608-4ab9-8fd5-45f9f744c7ca', '927d6df3-0b2d-4fa9-b0e2-a0c245065fd1', '4b93e92a-3dc0-47c4-9063-789f7c17f7fb', 3, 2.9),
('92aeb03d-874f-4527-8891-b714731d985b', 'b1dc33e9-9608-4ab9-8fd5-45f9f744c7ca', '927d6df3-0b2d-4fa9-b0e2-a0c245065fd1', '4b93e92a-3dc0-47c4-9063-789f7c17f7fb', 1, 0.4);

--
-- Daten für Tabelle `resource`
--

INSERT INTO `resource` (`id`, `session_id`, `title`, `image`, `link`) VALUES
('559a6e17-25cf-4997-9581-acb231327821', '0dc5ee31-4866-4945-a2b2-c44e951c940a', 'test resource', NULL, 'https://pie-lab.at/projects/game-changer/game-changer-top.jpg');
