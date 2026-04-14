CREATE TABLE IF NOT EXISTS users (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL,
    email        TEXT    UNIQUE NOT NULL,
    password     TEXT    NOT NULL,
    role         TEXT    NOT NULL DEFAULT 'agent',
    avatar_color TEXT    DEFAULT '#E53935',
    is_active    INTEGER DEFAULT 1,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS responses (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    agent_id             INTEGER NOT NULL,
    interviewee_name     TEXT    NOT NULL,
    interviewee_phone    TEXT,
    financial_preference TEXT    NOT NULL,
    risk_level           TEXT    DEFAULT 'medium',
    latitude             REAL,
    longitude            REAL,
    notes                TEXT,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS response_challenges (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    response_id INTEGER NOT NULL,
    challenge   TEXT    NOT NULL,
    FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE
);

-- Senha padrão para todos: password
INSERT OR IGNORE INTO users (name,email,password,role,avatar_color) VALUES
    ('Administrador','admin@finsight.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','#E53935'),
    ('Agente João','agent@finsight.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','agent','#FB8C00'),
    ('Agente Maria','maria@finsight.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','agent','#43A047');

INSERT OR IGNORE INTO responses (id,agent_id,interviewee_name,interviewee_phone,financial_preference,risk_level,latitude,longitude) VALUES
    (1,2,'Carlos Silva','(11) 98765-4321','credit','high',-23.5505,-46.6333),
    (2,2,'Ana Oliveira','(11) 91234-5678','investment','low',-23.5489,-46.6388),
    (3,3,'Pedro Santos','(11) 99876-5432','savings','medium',-23.5520,-46.6400),
    (4,2,'Lucia Ferreira','(11) 97654-3210','loan','high',-23.5510,-46.6350),
    (5,3,'Roberto Costa','(11) 92345-6789','card','medium',-23.5530,-46.6360),
    (6,2,'Fernanda Lima','(11) 93456-7890','credit','medium',-23.5515,-46.6340),
    (7,3,'Marcos Souza','(11) 94567-8901','investment','low',-23.5495,-46.6370);

INSERT OR IGNORE INTO response_challenges (response_id,challenge) VALUES
    (1,'debt'),(1,'no_credit'),(1,'fraud_risk'),
    (2,'lack_control'),
    (3,'low_income'),(3,'lack_control'),
    (4,'debt'),(4,'low_income'),(4,'illiteracy'),
    (5,'fraud_risk'),(5,'lack_control'),
    (6,'debt'),(6,'lack_control'),
    (7,'lack_control');
