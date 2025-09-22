CREATE DATABASE Spark;

USE Spark;

CREATE TABLE Usuario 
( 
 idUsuario INT PRIMARY KEY AUTO_INCREMENT,
 nome VARCHAR(255) NOT NULL,  
 tipo VARCHAR(50) NOT NULL,  
 email VARCHAR(200) NOT NULL,  
 senha VARCHAR(320) NOT NULL,
 cpf VARCHAR (14),
 genero VARCHAR(30),
 data_nasc DATE NOT NULL,
 UNIQUE(email),
 UNIQUE(cpf)
); 

CREATE TABLE Parque 
( 
 idParque INT PRIMARY KEY AUTO_INCREMENT,  
 nome VARCHAR(255) NOT NULL,  
 endereco VARCHAR(255) NOT NULL,  
 cep VARCHAR(9) NOT NULL,
 UNIQUE(endereco)
); 

CREATE TABLE Avaliacao_parque 
(
 idAvaliacao INT PRIMARY KEY AUTO_INCREMENT,  
 idUsuario INT NOT NULL,  
 idParque INT NOT NULL,  
 nota INT NOT NULL,
 comentario VARCHAR(280),
 FOREIGN KEY(idUsuario) REFERENCES Usuario (idUsuario),
 FOREIGN KEY(idParque) REFERENCES Parque (idParque)
); 

CREATE TABLE Instituicao 
( 
 idInstituicao INT PRIMARY KEY AUTO_INCREMENT,  
 idUsuario INT NOT NULL,  
 nome VARCHAR(255) NOT NULL,  
 senha VARCHAR(320) NOT NULL,  
 email VARCHAR(200) NOT NULL,  
 cnpj VARCHAR(18) NOT NULL,  
 tipo VARCHAR(20) NOT NULL,
 FOREIGN KEY(idUsuario) REFERENCES Usuario (idUsuario),
 UNIQUE(cnpj),
 UNIQUE(email),
 UNIQUE(nome)
); 

CREATE TABLE Evento 
( 
 idEvento INT PRIMARY KEY AUTO_INCREMENT,  
 idParque INT NOT NULL,  
 idInstituicao INT,
 idUsuario INT,
 nome VARCHAR(255) NOT NULL,  
 dia DATE NOT NULL,
 horario_inicio TIME NOT NULL,  
 horario_termino TIME NOT NULL,  
 descricao VARCHAR(255),  
 FOREIGN KEY(idParque) REFERENCES Parque (idParque),
 FOREIGN KEY(idInstituicao) REFERENCES Instituicao (idInstituicao),
 FOREIGN KEY(idUsuario) REFERENCES Usuario (idUsuario)
); 

CREATE TABLE Participantes 
( 
 idUsuario INT NOT NULL,  
 idEvento INT NOT NULL,  
 idParticipantes INT PRIMARY KEY AUTO_INCREMENT,  
 dataInscricao DATE NOT NULL,  
 dataParticipacao DATE NOT NULL  
); 

INSERT INTO Parque ( nome, endereco, cep) VALUES
('Parque do Carmo – Olavo Egydio Setúbal', 'Av. Afonso de Sampaio e Sousa, 951', '03590-000'),
('Parque Jacuí', 'Rua Catleias, 911, Vila Jacuí, União de Vila Nova
', '05459-900');

INSERT INTO Usuario (nome, tipo, email, senha, cpf, genero, data_nasc) VALUES
('Murilo Jackson da Silva','Comum', 'murilobaskas72@gmail.com', 'corinthiansbasquete', '22442300826', 'Masculino', '2006-08-09'),
('Marcelo Rezende Plínio', 'Comum', 'rezendog.adr@gmail.com', 'aliancaDoResende', '29996243877', 'Outro', '2007-08-05');

INSERT INTO Avaliacao_parque (idUsuario, idParque, nota, comentario) VALUES
(1, 1, 4, 'Os banheiros são muito bons, todos possuem sabonete!'),
(2, 2, 3, 'O parque é bom, porém o horário de funcionamento deixa muito a desejar...');

INSERT INTO  Instituicao (idUsuario, nome, senha, email, cnpj, tipo) VALUES
(1, 'Capoeira Social', 'capoeiradaora', 'capoeirasocial@gmail.com', '63.658.239/0001-73', 'institucional');

INSERT INTO Evento (idParque, IdInstituicao, nome, dia, horario_inicio, horario_termino, descricao) VALUES
(2, 1, 'Capoeira Daora', '2025-05-25', '09:00:00', '10:30:00', 'Prepare-se para uma sessão de treino intensa e muito produtiva! Nos encontraremos em frente à administração. Não esqueça sua garrafa de água e alimente-se bem antes de sair de casa');

INSERT INTO Participantes (idUsuario, idEvento, dataInscricao, dataParticipacao) VALUES
(1, 1, '2025-05-01', '2025-05-03');
