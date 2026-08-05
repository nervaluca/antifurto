-- Schema per la sincronizzazione multi-dispositivo di Manutenzioni Antifurto (Ten Solutions)
-- Da eseguire una sola volta su phpMyAdmin (Aruba), sul database che deciderai di usare.
-- Compatibile con MySQL 5.7+ / MariaDB.

CREATE TABLE IF NOT EXISTS hager_clienti (
  id VARCHAR(64) PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  indirizzo VARCHAR(255) DEFAULT '',
  telefono VARCHAR(64) DEFAULT '',
  note TEXT,
  updated_at BIGINT NOT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hager_centrali (
  id VARCHAR(64) PRIMARY KEY,
  clienteId VARCHAR(64) NOT NULL,
  modello VARCHAR(64) DEFAULT '',
  modelloCustom VARCHAR(255) DEFAULT '',
  matricola VARCHAR(128) DEFAULT '',
  dataInstallazione VARCHAR(16) DEFAULT '',
  zone VARCHAR(128) DEFAULT '',
  note TEXT,
  updated_at BIGINT NOT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_updated (updated_at),
  INDEX idx_cliente (clienteId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hager_sensori (
  id VARCHAR(64) PRIMARY KEY,
  centraleId VARCHAR(64) NOT NULL,
  categoria VARCHAR(32) DEFAULT '',
  etichetta VARCHAR(255) DEFAULT '',
  modello VARCHAR(128) DEFAULT '',
  zona VARCHAR(64) DEFAULT '',
  batteria VARCHAR(255) DEFAULT '',
  autonomiaAnni INT DEFAULT NULL,
  dataInstallazione VARCHAR(16) DEFAULT '',
  dataSostituzione VARCHAR(16) DEFAULT '',
  note TEXT,
  updated_at BIGINT NOT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_updated (updated_at),
  INDEX idx_centrale (centraleId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hager_interventi (
  id VARCHAR(64) PRIMARY KEY,
  centraleId VARCHAR(64) NOT NULL,
  data VARCHAR(16) DEFAULT '',
  tecnico VARCHAR(255) DEFAULT '',
  tipo VARCHAR(64) DEFAULT '',
  batterie TEXT,
  note TEXT,
  updated_at BIGINT NOT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_updated (updated_at),
  INDEX idx_centrale (centraleId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
