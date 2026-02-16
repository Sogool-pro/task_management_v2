-- Workspace invites table (MySQL/MariaDB)
-- Run this once to enable employee self-join via invitation links.

SET NAMES utf8mb4;
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS workspace_invites (
    id int NOT NULL AUTO_INCREMENT,
    organization_id int NOT NULL,
    invited_by int NOT NULL,
    email varchar(255) NOT NULL,
    full_name varchar(120) DEFAULT NULL,
    role varchar(20) NOT NULL DEFAULT 'employee',
    token varchar(64) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending',
    expires_at datetime NOT NULL,
    accepted_at datetime DEFAULT NULL,
    accepted_user_id int DEFAULT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT workspace_invites_pkey PRIMARY KEY (id),
    CONSTRAINT workspace_invites_token_key UNIQUE (token),
    CONSTRAINT workspace_invites_org_fk FOREIGN KEY (organization_id) REFERENCES organizations (id),
    CONSTRAINT workspace_invites_invited_by_fk FOREIGN KEY (invited_by) REFERENCES users (id),
    CONSTRAINT workspace_invites_accepted_user_fk FOREIGN KEY (accepted_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_workspace_invites_org_status ON workspace_invites (organization_id, status);
CREATE INDEX idx_workspace_invites_email_status ON workspace_invites (email, status);
