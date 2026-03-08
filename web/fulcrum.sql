-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : jeu. 30 oct. 2025 à 20:39
-- Version du serveur : 10.11.6-MariaDB
-- Version de PHP : 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `fulcrum`
--

-- --------------------------------------------------------

--
-- Structure de la table `current_answer`
--

CREATE TABLE `current_answer` (
  `id_c_answer` int(11) NOT NULL,
  `id_c_user` int(11) NOT NULL,
  `answer_c_answer` int(11) NOT NULL,
  `time_c_answer` double NOT NULL DEFAULT 0,
  `points_c_answer` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `question`
--

CREATE TABLE `question` (
  `id_question` int(11) NOT NULL,
  `name_question` varchar(100) NOT NULL,
  `text_question` text NOT NULL,
  `1_question` varchar(100) NOT NULL,
  `2_question` varchar(100) DEFAULT NULL,
  `3_question` varchar(100) DEFAULT NULL,
  `4_question` varchar(100) DEFAULT NULL,
  `correct_question` int(11) NOT NULL DEFAULT 1,
  `time_question` int(11) NOT NULL DEFAULT 30,
  `point_question` int(11) NOT NULL DEFAULT 100,
  `image_question` text DEFAULT 'img/default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `question`
--

INSERT INTO `question` (`id_question`, `name_question`, `text_question`, `1_question`, `2_question`, `3_question`, `4_question`, `correct_question`, `time_question`, `point_question`, `image_question`) VALUES
(1, 'Ezra\'s life 1', 'Quelle est la planète d\'origine d\'Ezra ?', 'Tatooine', 'Naboo', 'Lothal', 'Alderaan', 3, 30, 100, 'img/ezra.jpg'),
(2, 'Clone War History 1', 'Lors de la bataille de Kamino, qu\'est-ce que venaient chercher les Séparatistes ?', 'La vengeance', '\r\nL\'ADN du premier clone', 'Détruire Kamino', 'Ils sont juste méchants', 2, 60, 100, 'img/HoloDestructionTipocaCity.jpg'),
(3, 'Clone War History 2', 'Lorsque Ashoka fut accusé d\'un meurtre qui était le vrai responsable de cet acte ainsi que l\'attentat du temple Jedi ?', 'Barriss Offee', 'Rahm Kota', 'Anakin Skywalker', 'Jar Jar Binks', 1, 30, 100, 'img/barriss-offee.jpg'),
(4, 'Ahsoka Tano\' Life', 'De quelle couleur sont les sabres laser d\'Ahsoka Tano pendant les événements de la série The Clone Wars ?', 'Des sabres blancs', 'Des sabres jaunes', 'Des sabres bleus', 'Des sabres verts', 4, 30, 100, 'img/ahsoka.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `quiz`
--

CREATE TABLE `quiz` (
  `id_quiz` int(11) NOT NULL,
  `id_test` int(11) NOT NULL,
  `name_quiz` varchar(255) DEFAULT NULL,
  `id_question_quiz` int(11) DEFAULT NULL,
  `order_quiz` int(11) NOT NULL DEFAULT 999,
  `done_question` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `quiz`
--

INSERT INTO `quiz` (`id_quiz`, `id_test`, `name_quiz`, `id_question_quiz`, `order_quiz`, `done_question`) VALUES
(1, 1, 'StarWars101', 2, 2, 0),
(2, 1, 'StarWars101', 1, 3, 0),
(6, 1, 'StarWars101', 3, 1, 0),
(7, 1, 'StarWars101', 4, 2, 0),
(8, 2, 'test 2', 4, 999, 0),
(9, 2, 'test 2', 2, 2, 0),
(10, 2, 'test 2', 1, 3, 0);

-- --------------------------------------------------------

--
-- Structure de la table `store_answer`
--

CREATE TABLE `store_answer` (
  `id_s_answer` int(11) NOT NULL,
  `id_s_user` int(11) NOT NULL,
  `id_s_question` int(11) NOT NULL,
  `answer_s_answer` int(11) NOT NULL DEFAULT 0,
  `time_s_answer` double NOT NULL DEFAULT 0,
  `points_s_answer` int(11) NOT NULL DEFAULT 0,
  `start_time_s_answer` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `name_user` varchar(100) NOT NULL,
  `point_user` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id_user`, `name_user`, `point_user`) VALUES
(1, 'Ezra', 0),
(2, 'Yoda', 0),
(3, 'Anakin', 0),
(4, 'Kallus', 0),
(5, 'Obi', 0);

-- --------------------------------------------------------

--
-- Structure de la table `variable`
--

CREATE TABLE `variable` (
  `id_var` int(11) NOT NULL,
  `name_var` varchar(11) NOT NULL,
  `value_var` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `variable`
--

INSERT INTO `variable` (`id_var`, `name_var`, `value_var`) VALUES
(1, 'quiz', 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `current_answer`
--
ALTER TABLE `current_answer`
  ADD PRIMARY KEY (`id_c_answer`),
  ADD KEY `id_c_user` (`id_c_user`);

--
-- Index pour la table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`id_question`);

--
-- Index pour la table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`id_quiz`),
  ADD KEY `id_question_quiz` (`id_question_quiz`);

--
-- Index pour la table `store_answer`
--
ALTER TABLE `store_answer`
  ADD PRIMARY KEY (`id_s_answer`) USING BTREE,
  ADD KEY `store_answer_ibfk_1` (`id_s_question`),
  ADD KEY `store_answer_ibfk_2` (`id_s_user`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`);

--
-- Index pour la table `variable`
--
ALTER TABLE `variable`
  ADD PRIMARY KEY (`id_var`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `current_answer`
--
ALTER TABLE `current_answer`
  MODIFY `id_c_answer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT pour la table `question`
--
ALTER TABLE `question`
  MODIFY `id_question` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `id_quiz` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `store_answer`
--
ALTER TABLE `store_answer`
  MODIFY `id_s_answer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=562;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `variable`
--
ALTER TABLE `variable`
  MODIFY `id_var` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `current_answer`
--
ALTER TABLE `current_answer`
  ADD CONSTRAINT `current_answer_ibfk_1` FOREIGN KEY (`id_c_user`) REFERENCES `user` (`id_user`);

--
-- Contraintes pour la table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`id_question_quiz`) REFERENCES `question` (`id_question`);

--
-- Contraintes pour la table `store_answer`
--
ALTER TABLE `store_answer`
  ADD CONSTRAINT `store_answer_ibfk_1` FOREIGN KEY (`id_s_question`) REFERENCES `question` (`id_question`),
  ADD CONSTRAINT `store_answer_ibfk_2` FOREIGN KEY (`id_s_user`) REFERENCES `user` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
