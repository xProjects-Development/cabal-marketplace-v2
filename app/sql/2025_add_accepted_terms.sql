ALTER TABLE `users`
  ADD COLUMN `accepted_terms_at` DATETIME NULL DEFAULT NULL AFTER `status`,
  ADD INDEX `idx_users_accepted_terms_at` (`accepted_terms_at`);
