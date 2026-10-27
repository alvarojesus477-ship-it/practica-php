CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    password   VARCHAR(255) NOT NULL,
);

CREATE TABLE posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL FOREIGN KEY (user_id) REFERENCES users(id),
);

CREATE TABLE comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL FOREIGN KEY (post_id) REFERENCES posts(id),
    user_id     INT NOT NULL FOREIGN KEY (user_id) REFERENCES users(id),
    content     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
