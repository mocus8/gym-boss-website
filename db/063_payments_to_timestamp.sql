-- Переводит системные поля expires_at и last_sync_at в timestamp

ALTER TABLE `payments` MODIFY `expires_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `payments` MODIFY `last_sync_at` timestamp NULL DEFAULT NULL;