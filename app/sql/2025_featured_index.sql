-- Add/ensure is_featured column + index on nfts
ALTER TABLE `nfts`
  ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  ADD INDEX IF NOT EXISTS `idx_nfts_is_featured` (`is_featured`);
