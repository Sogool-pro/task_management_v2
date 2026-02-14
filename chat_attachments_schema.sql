CREATE TABLE IF NOT EXISTS chat_attachments (
  attachment_id INT NOT NULL AUTO_INCREMENT,
  chat_id INT NOT NULL,
  attachment_name VARCHAR(255) NOT NULL,
  CONSTRAINT chat_attachments_pkey PRIMARY KEY (attachment_id),
  CONSTRAINT chat_attachments_chat_id_fkey
    FOREIGN KEY (chat_id)
    REFERENCES chats(chat_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

