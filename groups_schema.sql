CREATE TABLE IF NOT EXISTS groups (
  id INT NOT NULL AUTO_INCREMENT,
  name TEXT NOT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  type VARCHAR(50) NOT NULL DEFAULT 'group',
  task_id INT NULL,
  CONSTRAINT groups_pkey PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS group_members (
  id INT NOT NULL AUTO_INCREMENT,
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  role VARCHAR(20) DEFAULT 'member',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT group_members_pkey PRIMARY KEY (id),
  CONSTRAINT group_members_role_check CHECK (role IN ('leader','member')),
  CONSTRAINT group_members_group_user_key UNIQUE (group_id, user_id),
  CONSTRAINT fk_group_members_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
  CONSTRAINT fk_group_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS group_messages (
  id INT NOT NULL AUTO_INCREMENT,
  group_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT group_messages_pkey PRIMARY KEY (id),
  CONSTRAINT fk_group_messages_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
  CONSTRAINT fk_group_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS group_message_attachments (
  id INT NOT NULL AUTO_INCREMENT,
  message_id INT NOT NULL,
  attachment_name TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT group_message_attachments_pkey PRIMARY KEY (id),
  CONSTRAINT fk_group_msg_attach_msg FOREIGN KEY (message_id) REFERENCES group_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

