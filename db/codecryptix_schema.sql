CREATE DATABASE codecryptix;

\c codecryptix

CREATE TABLE users (
    user_id INTEGER NOT NULL DEFAULT nextval('users_user_id_seq'::regclass),
    email VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    date_created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_pkey PRIMARY KEY (user_id),
    CONSTRAINT users_email_key UNIQUE (email),
    CONSTRAINT users_username_key UNIQUE (username)
);

CREATE TABLE codesnippet (
    code_id INTEGER NOT NULL DEFAULT nextval('codesnippet_code_id_seq'::regclass),
    user_id INTEGER NOT NULL,
    original_code TEXT NOT NULL,
    language VARCHAR(50) NOT NULL,
    date_uploaded TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT codesnippet_pkey PRIMARY KEY (code_id),
    CONSTRAINT codesnippet_user_id_fkey FOREIGN KEY (user_id) 
        REFERENCES public.users(user_id) ON DELETE CASCADE
);

CREATE TABLE obfuscation (
    obj_id INTEGER NOT NULL DEFAULT nextval('obfuscation_obj_id_seq'::regclass),
    code_id INTEGER NOT NULL,
    obfuscated_code TEXT NOT NULL,
    method_used VARCHAR(100) NOT NULL,
    timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT obfuscation_pkey PRIMARY KEY (obj_id),
    CONSTRAINT obfuscation_code_id_key UNIQUE (code_id),
    CONSTRAINT obfuscation_code_id_fkey FOREIGN KEY (code_id) 
        REFERENCES public.codesnippet(code_id) ON DELETE CASCADE
);

CREATE TABLE deobfuscation (
    deobj_id INTEGER NOT NULL DEFAULT nextval('deobfuscation_deobj_id_seq'::regclass),
    obj_id INTEGER NOT NULL,
    deobfuscated_code TEXT NOT NULL,
    timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT deobfuscation_pkey PRIMARY KEY (deobj_id),
    CONSTRAINT deobfuscation_obj_id_key UNIQUE (obj_id),
    CONSTRAINT deobfuscation_obj_id_fkey FOREIGN KEY (obj_id) 
        REFERENCES public.obfuscation(obj_id) ON DELETE CASCADE
);

CREATE TABLE session_log (
    log_id INTEGER NOT NULL DEFAULT nextval('session_log_log_id_seq'::regclass),
    user_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    log_time TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT session_log_pkey PRIMARY KEY (log_id),
    CONSTRAINT session_log_user_id_fkey FOREIGN KEY (user_id) 
        REFERENCES public.users(user_id) ON DELETE CASCADE
);

