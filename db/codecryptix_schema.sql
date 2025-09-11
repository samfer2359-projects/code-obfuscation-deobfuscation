CREATE DATABASE codecryptix;

\c codecryptix

CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE codesnippet (
    code_id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    original_code TEXT NOT NULL,
    language VARCHAR(50) NOT NULL,
    date_uploaded TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT codesnippet_user_id_fkey FOREIGN KEY (user_id) 
        REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE obfuscation (
    obj_id SERIAL PRIMARY KEY,
    code_id INT NOT NULL UNIQUE,
    obfuscated_code TEXT NOT NULL,
    method_used VARCHAR(100) NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT obfuscation_code_id_fkey FOREIGN KEY (code_id) 
        REFERENCES codesnippet(code_id) ON DELETE CASCADE
);

CREATE TABLE deobfuscation (
    deobj_id SERIAL PRIMARY KEY,
    obj_id INT NOT NULL UNIQUE,
    deobfuscated_code TEXT NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT deobfuscation_obj_id_fkey FOREIGN KEY (obj_id) 
        REFERENCES obfuscation(obj_id) ON DELETE CASCADE
);

CREATE TABLE session_log (
    log_id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    action TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    log_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT session_log_user_id_fkey FOREIGN KEY (user_id) 
        REFERENCES users(user_id) ON DELETE CASCADE
);

