-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: kyivst16.mysql.ukraine.com.ua:3306
-- Время создания: Ноя 08 2025 г., 18:47
-- Версия сервера: 8.4.6-6
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `kyivst16_symphony`
--

-- --------------------------------------------------------

--
-- Структура таблицы `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `role` varchar(16) NOT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `api_tokens`
--

INSERT INTO `api_tokens` (`id`, `token`, `role`, `user_id`) VALUES
(1, '35d0230671ad5b306d19e95888c46734', 'root', NULL),
(2, '98574b3438a02edac83c50b02e4b7533', 'user', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20251108134001', '2025-11-08 16:10:04', 4),
('DoctrineMigrations\\Version20251108134417', '2025-11-08 16:00:12', 4);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `login` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_token` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `phone`, `pass`, `created_by_token`) VALUES
(2, 'dima11', '92345678', '9234561', 2),
(5, 'dima2', '92345678', '923456', 1),
(7, 'dima3', '92345678', '923456', 2);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_token` (`token`);

--
-- Индексы таблицы `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_login_pass` (`login`,`pass`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
