-- BANCO OFICIAL DO SPARK ATUALIZADO 16/09

CREATE DATABASE Spark;
USE Spark;

-- Tabela Usuario (Estrutura já estava correta)
CREATE TABLE Usuario ( 
    idUsuario INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    avatar_path VARCHAR(255) NULL,
    biografia TEXT NULL,
    tipo ENUM('comum', 'instituicao', 'admin') NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cpf VARCHAR (14) UNIQUE,
    genero VARCHAR(30),
    data_nasc DATE NOT NULL,
    data_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    status ENUM('ativo', 'inativo', 'suspenso') NOT NULL DEFAULT 'ativo'
);

-- Tabela Parque
CREATE TABLE Parque ( 
    idParque INT PRIMARY KEY AUTO_INCREMENT, 
    nome VARCHAR(255) NOT NULL, 
    endereco VARCHAR(255) NOT NULL, 
    cep VARCHAR(9) NOT NULL
); 

-- Tabela Avaliacao_parque
CREATE TABLE Avaliacao_parque (
    idAvaliacao INT PRIMARY KEY AUTO_INCREMENT, 
    idUsuario INT NOT NULL, 
    idParque INT NOT NULL, 
    nota INT NOT NULL,
    comentario VARCHAR(280),
    FOREIGN KEY(idUsuario) REFERENCES Usuario (idUsuario),
    FOREIGN KEY(idParque) REFERENCES Parque (idParque)
); 

-- Tabela Instituicao
CREATE TABLE Instituicao ( 
    idInstituicao INT PRIMARY KEY AUTO_INCREMENT, 
    idUsuario INT NOT NULL, 
    nome VARCHAR(255) NOT NULL UNIQUE, 
    senha VARCHAR(320) NOT NULL, 
    email VARCHAR(200) NOT NULL UNIQUE, 
    cnpj VARCHAR(18) NOT NULL UNIQUE, 
    tipo VARCHAR(20) NOT NULL,
    FOREIGN KEY(idUsuario) REFERENCES Usuario (idUsuario)
); 

-- Tabela Evento (CORRIGIDA: imagem_path adicionado diretamente aqui)
CREATE TABLE Evento ( 
    idEvento INT PRIMARY KEY AUTO_INCREMENT, 
    idParque INT NOT NULL, 
    idInstituicao INT,
    idUsuario INT,
    nome VARCHAR(255) NOT NULL, 
    dia DATE NOT NULL,
    horario_inicio TIME NOT NULL, 
    horario_termino TIME NOT NULL, 
    descricao VARCHAR(255), 
    imagem_path VARCHAR(255) NULL, -- Coluna adicionada aqui!
    FOREIGN KEY(idParque) REFERENCES Parque (idParque),
    FOREIGN KEY(idInstituicao) REFERENCES Instituicao (idInstituicao),
    FOREIGN KEY(idUsuario) REFERENCES Usuario (idUsuario)
); 

-- Tabela Participantes
CREATE TABLE Participantes ( 
    idUsuario INT NOT NULL, 
    idEvento INT NOT NULL, 
    idParticipantes INT PRIMARY KEY AUTO_INCREMENT, 
    dataInscricao DATE NOT NULL, 
    dataParticipacao DATE NOT NULL 
); 

-- Inserindo dados nos Parques
INSERT INTO Parque (nome, endereco, cep) VALUES
('Parque do Carmo – Olavo Egydio Setúbal', 'Av. Afonso de Sampaio e Sousa, 951', '03590-000'),
('Parque Jacuí', 'Rua Catleias, 911, Vila Jacuí, União de Vila Nova', '05459-900');

-- Inserindo dados nos Usuários (CORRIGIDO: adicionado a coluna e valores para 'username' e 'tipo')
INSERT INTO Usuario (nome, username, tipo, email, senha, cpf, genero, data_nasc) VALUES
('Murilo Jackson da Silva', 'murilojackson', 'comum', 'murilobaskas72@gmail.com', 'corinthiansbasquete', '22442300826', 'Masculino', '2006-08-09'),
('Marcelo Rezende Plínio', 'marcelorezende', 'comum', 'rezendog.adr@gmail.com', 'aliancaDoResende', '29996243877', 'Outro', '2007-08-05');

-- Inserindo dados nas Avaliações
INSERT INTO Avaliacao_parque (idUsuario, idParque, nota, comentario) VALUES
(1, 1, 4, 'Os banheiros são muito bons, todos possuem sabonete!'),
(2, 2, 3, 'O parque é bom, porém o horário de funcionamento deixa muito a desejar...');

-- Inserindo dados na Instituição
INSERT INTO Instituicao (idUsuario, nome, senha, email, cnpj, tipo) VALUES
(1, 'Capoeira Social', 'capoeiradaora', 'capoeirasocial@gmail.com', '63.658.239/0001-73', 'institucional');

-- Inserindo dados no Evento (CORRIGIDO: 'idInstituicao' com 'i' minúsculo para consistência)
INSERT INTO Evento (idParque, idInstituicao, nome, dia, horario_inicio, horario_termino, descricao) VALUES
(2, 1, 'Capoeira Daora', '2025-05-25', '09:00:00', '10:30:00', 'Prepare-se para uma sessão de treino intensa e muito produtiva! Nos encontraremos em frente à administração. Não esqueça sua garrafa de água e alimente-se bem antes de sair de casa');

-- Inserindo dados nos Participantes
INSERT INTO Participantes (idUsuario, idEvento, dataInscricao, dataParticipacao) VALUES
(1, 1, '2025-05-01', '2025-05-03');

-- Os comandos ALTER TABLE foram removidos pois as colunas já foram adicionadas nos comandos CREATE TABLE.