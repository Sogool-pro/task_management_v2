-- MySQL/MariaDB migration: add groups.task_id link for task chat cleanup.
-- Recommended: run via run_migration_group_task_link.php (idempotent checks).

ALTER TABLE groups
ADD COLUMN IF NOT EXISTS task_id INT NULL;

SET @has_fk := (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = 'groups'
    AND constraint_name = 'groups_task_id_fkey'
    AND constraint_type = 'FOREIGN KEY'
);

SET @sql := IF(
  @has_fk = 0,
  'ALTER TABLE groups ADD CONSTRAINT groups_task_id_fkey FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PostgreSQL partial index replacement:
-- use a composite index for (type, task_id) on MySQL/MariaDB.
SET @has_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'groups'
    AND index_name = 'idx_groups_task_chat_task_id'
);

SET @sql := IF(
  @has_idx = 0,
  'CREATE INDEX idx_groups_task_chat_task_id ON groups (type, task_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
