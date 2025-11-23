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

-- 1. CRIA UMA NOVA TABELA PARA AS AVALIAÇÕES DE EVENTOS
CREATE TABLE Avaliacao_evento (
    idAvaliacao INT PRIMARY KEY AUTO_INCREMENT,
    idEvento INT NOT NULL,
    idUsuario INT NOT NULL,
    nota INT NOT NULL,
    comentario TEXT NULL,
    imagem_path VARCHAR(255) NULL,
    data_avaliacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(idEvento) REFERENCES Evento (idEvento),
    FOREIGN KEY(idUsuario) REFERENCES Usuario (idUsuario)
);

-- 2. MELHORA A TABELA DE PARTICIPANTES
ALTER TABLE Participantes
ADD COLUMN status ENUM('inscrito', 'participou') NOT NULL DEFAULT 'inscrito';

-- 3. (Opcional) Atualiza os registros existentes para o novo status
UPDATE Participantes SET status = 'inscrito';

-- Adiciona o novo status 'ativo' à coluna existente
ALTER TABLE Participantes 
MODIFY COLUMN status ENUM('inscrito', 'ativo', 'participou') NOT NULL DEFAULT 'inscrito';

UPDATE Usuario SET cpf = REPLACE(REPLACE(cpf, '.', ''), '-', '');

INSERT INTO Usuario 
    (nome, username, tipo, email, senha, data_nasc, status) 
VALUES 
    ('Vladmin', 'vladmin', 'admin', 'admin@admin.com', '$2y$10$ATL08KCn0PCG/nLm8ESkS.HmbMaKoUXpgnTTaKZrkUDeqmynyAmPe', '2000-01-01', 'ativo');

-- INSERINDO NOVOS EVENTOS PARA TESTE

-- Evento Futuro 1 (criado pelo usuário 1)
INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao, imagem_path) VALUES
(1, 1, 'Piquenique Comunitário', '2025-10-25', '14:00:00', '17:00:00', 'Vamos nos reunir para um piquenique no gramado central. Traga sua toalha, um lanche para compartilhar e sua alegria!', 'uploads/evento_piquenique.jpg');

-- Evento Futuro 2 (criado pelo usuário 2)
INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao, imagem_path) VALUES
(2, 2, 'Aula de Yoga ao Pôr do Sol', '2025-11-15', '17:30:00', '18:30:00', 'Uma aula de Hatha Yoga para todos os níveis, aproveitando a energia do fim de tarde. Traga seu tapete!', 'uploads/evento_yoga.jpg');

-- Evento Passado (criado pelo usuário 1)
INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao, imagem_path) VALUES
(1, 1, 'Torneio de Vôlei de Areia', '2025-09-20', '09:00:00', '13:00:00', 'Torneio amador de vôlei de areia. Inscrições abertas para duplas masculinas e femininas no local.', 'uploads/evento_volei.jpg');

-- Evento que acontecerá HOJE (criado pelo usuário 2)
INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao, imagem_path) VALUES
(2, 2, 'Caminhada Matinal no Parque', CURDATE(), '08:00:00', '09:00:00', 'Vamos começar o dia com uma caminhada leve pelo parque. Ponto de encontro no portão principal.', NULL);


-- INSCREVENDO USUÁRIOS NOS EVENTOS PARA TESTAR "MEUS EVENTOS" E "HISTÓRICO"

-- Supondo que o "Piquenique" tenha o idEvento 2 (ajuste se for diferente)
-- Usuário 2 se inscreve no evento do usuário 1
INSERT INTO Participantes (idUsuario, idEvento, dataInscricao, dataParticipacao, status) VALUES
(2, 2, CURDATE(), CURDATE(), 'inscrito');

-- Supondo que o "Torneio de Vôlei" tenha o idEvento 4 (ajuste se for diferente)
-- Usuário 2 participou do evento passado
INSERT INTO Participantes (idUsuario, idEvento, dataInscricao, dataParticipacao, status) VALUES
(2, 4, '2025-09-18', '2025-09-20', 'participou');

-- Supondo que a "Caminhada Matinal" tenha o idEvento 5 (ajuste se for diferente)
-- Usuário 1 se inscreve no evento de hoje
INSERT INTO Participantes (idUsuario, idEvento, dataInscricao, dataParticipacao, status) VALUES
(1, 5, CURDATE(), CURDATE(), 'inscrito');

-- INSERINDO AVALIAÇÕES DE TESTE PARA A TABELA 'Avaliacao_evento'

-- Usuário 2 avaliando o evento "Torneio de Vôlei de Areia" (idEvento = 4)
INSERT INTO Avaliacao_evento (idEvento, idUsuario, nota, comentario, imagem_path) VALUES
(4, 2, 5, 'O torneio foi incrível! Super bem organizado e o pessoal muito animado. Com certeza participarei dos próximos!', NULL);

-- Usuário 1 avaliando o mesmo evento "Torneio de Vôlei de Areia" (idEvento = 4)
-- Adicionando um segundo comentário para o mesmo evento
INSERT INTO Avaliacao_evento (idEvento, idUsuario, nota, comentario, imagem_path) VALUES
(4, 1, 4, 'Muito bom, mas poderia ter mais quadras disponíveis. A espera foi um pouco longa.', NULL);

-- Usuário 1 avaliando o evento "Capoeira Daora" (idEvento = 1), incluindo uma imagem
INSERT INTO Avaliacao_evento (idEvento, idUsuario, nota, comentario, imagem_path) VALUES
(1, 1, 5, 'Energia contagiante! O mestre é excelente e o grupo super acolhedor. Recomendo a todos!', 'uploads/avaliacoes/foto_capoeira_teste.jpg');

-- Usuário 2 avaliando o evento "Piquenique Comunitário" (idEvento = 2)
INSERT INTO Avaliacao_evento (idEvento, idUsuario, nota, comentario, imagem_path) VALUES
(2, 2, 4, 'Ótima iniciativa! Conheci muita gente legal. Só faltou um pouco de sombra no local escolhido.', NULL);

ALTER TABLE Participantes MODIFY COLUMN dataParticipacao DATE NULL DEFAULT NULL;

CREATE TABLE Categorias (
    idCategoria INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    imagem_url VARCHAR(255) NOT NULL,
    cor_fundo VARCHAR(7) NOT NULL -- Para a cor da faixa (ex: '#A020F0')
);

ALTER TABLE Evento
ADD COLUMN idCategoria INT NULL,
ADD FOREIGN KEY (idCategoria) REFERENCES Categorias(idCategoria);

INSERT INTO Categorias (nome, imagem_url, cor_fundo) VALUES
('Esportes', 'uploads/categorias/esportes.png', '#FF00B2'),
('Saúde', 'uploads/categorias/saude.png', '#33FF00'),
('Música', 'uploads/categorias/musica.png', '#FFCC00'),
('Cultura', 'uploads/categorias/cultura.png', '#0700DF'),
('Gastronomia', 'uploads/categorias/gastronomia.png', '#FF9500'),
('Lazer', 'uploads/categorias/lazer.png', '#FF3300'),
('Família', 'uploads/categorias/familia.png', ''),
('Outros', 'uploads/categorias/outros.png', '#00BBFF');
