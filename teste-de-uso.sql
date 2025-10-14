-- Adiciona 10 novos usuários de teste
INSERT INTO `Usuario` (`nome`, `username`, `tipo`, `email`, `senha`, `data_nasc`, `status`) VALUES
('Carla Souza', 'carla_souza', 'comum', 'carla@email.com', '$2y$10$ExemploDeHashSeguro123...', '1995-03-15', 'ativo'),
('Bruno Lima', 'bruno_lima', 'comum', 'bruno@email.com', '$2y$10$ExemploDeHashSeguro123...', '1992-07-22', 'ativo'),
('Fernanda Costa', 'fe_costa', 'comum', 'fernanda@email.com', '$2y$10$ExemploDeHashSeguro123...', '1998-11-05', 'ativo'),
('Lucas Martins', 'lucas_martins', 'comum', 'lucas@email.com', '$2y$10$ExemploDeHashSeguro123...', '1990-01-30', 'ativo'),
('Juliana Alves', 'juh_alves', 'comum', 'juliana@email.com', '$2y$10$ExemploDeHashSeguro123...', '1999-09-10', 'ativo'),
('Rafael Oliveira', 'rafa_oliveira', 'comum', 'rafael@email.com', '$2y$10$ExemploDeHashSeguro123...', '1988-06-25', 'ativo'),
('Amanda Santos', 'amanda_santos', 'comum', 'amanda@email.com', '$2y$10$ExemploDeHashSeguro123...', '1996-04-12', 'ativo'),
('Gabriel Pereira', 'gabs_pereira', 'comum', 'gabriel@email.com', '$2y$10$ExemploDeHashSeguro123...', '1993-08-01', 'ativo'),
('Mariana Ferreira', 'mari_ferreira', 'comum', 'mariana@email.com', '$2y$10$ExemploDeHashSeguro123...', '2000-12-03', 'ativo'),
('Thiago Rodrigues', 'thiago_rodrigues', 'comum', 'thiago@email.com', '$2y$10$ExemploDeHashSeguro123...', '1991-02-18', 'ativo');

-- Adiciona 6 novos eventos de teste (usando os parques com id 1 e 2)
-- Eventos Futuros
INSERT INTO `Evento` (`idParque`, `idUsuario`, `nome`, `dia`, `horario_inicio`, `horario_termino`, `descricao`, `imagem_path`) VALUES
(1, 3, 'Trilha Ecológica no Carmo', '2025-11-08', '09:30:00', '12:00:00', 'Vamos explorar as trilhas menos conhecidas do Parque do Carmo. Ponto de encontro na entrada principal.', 'uploads/evento_trilha.jpg'),
(2, 4, 'Oficina de Fotografia de Natureza', '2025-11-22', '15:00:00', '17:00:00', 'Aprenda a capturar a beleza da fauna e flora do Parque Jacuí. Traga sua câmera ou celular.', 'uploads/evento_fotografia.jpg'),
(1, 5, 'Roda de Leitura Coletiva', '2025-12-06', '11:00:00', '12:30:00', 'Leitura e discussão do livro "Grande Sertão: Veredas". Venha compartilhar suas impressões.', NULL);

-- Eventos Passados
INSERT INTO `Evento` (`idParque`, `idUsuario`, `nome`, `dia`, `horario_inicio`, `horario_termino`, `descricao`, `imagem_path`) VALUES
(2, 6, 'Concerto de Jazz ao Ar Livre', '2025-09-27', '16:00:00', '18:00:00', 'Uma tarde relaxante ao som de clássicos do jazz com a banda local. Traga sua canga!', 'uploads/evento_jazz.jpg'),
(1, 7, 'Feira de Adoção de Pets', '2025-09-13', '10:00:00', '16:00:00', 'Encontre seu novo melhor amigo! Cães e gatos resgatados esperando por um lar.', 'uploads/evento_adocao.jpg'),
(2, 8, 'Campeonato de Skate Amador', '2025-08-30', '13:00:00', '18:00:00', 'Competição de skate nas categorias iniciante e amador. Inscrições no local.', 'uploads/evento_skate.jpg');

-- Inscreve os usuários em VÁRIOS eventos
-- NOTA: Assumindo que os IDs de usuário começam em 3 e os de evento em 6. Ajuste se necessário.
-- Eventos Futuros (status 'inscrito')
INSERT INTO `Participantes` (`idUsuario`, `idEvento`, `dataInscricao`, `status`) VALUES
(3, 6, CURDATE(), 'inscrito'), -- Carla na Trilha
(4, 6, CURDATE(), 'inscrito'), -- Bruno na Trilha
(5, 6, CURDATE(), 'inscrito'), -- Fernanda na Trilha
(4, 7, CURDATE(), 'inscrito'), -- Bruno na Oficina de Fotografia
(6, 7, CURDATE(), 'inscrito'), -- Rafael na Oficina de Fotografia
(8, 8, CURDATE(), 'inscrito'); -- Gabriel na Roda de Leitura

-- Eventos Passados (status 'participou')
INSERT INTO `Participantes` (`idUsuario`, `idEvento`, `dataInscricao`, `dataParticipacao`, `status`) VALUES
(9, 9, '2025-09-25', '2025-09-27', 'participou'), -- Mariana no Concerto de Jazz
(10, 9, '2025-09-26', '2025-09-27', 'participou'), -- Thiago no Concerto de Jazz
(3, 10, '2025-09-10', '2025-09-13', 'participou'), -- Carla na Feira de Adoção
(5, 11, '2025-08-28', '2025-08-30', 'participou'), -- Fernanda no Campeonato de Skate
(8, 11, '2025-08-29', '2025-08-30', 'participou'); -- Gabriel no Campeonato de Skate

-- Adiciona avaliações para os eventos que já passaram
-- NOTA: Assumindo que os IDs de evento são 9, 10 e 11. Ajuste se necessário.
INSERT INTO `Avaliacao_evento` (`idEvento`, `idUsuario`, `nota`, `comentario`) VALUES
(9, 9, 5, 'Simplesmente perfeito! A banda era fantástica e o clima no parque estava ótimo.'),
(9, 10, 4, 'Muito bom, mas o som poderia estar um pouco mais alto.'),
(10, 3, 5, 'Consegui adotar um gatinho! Melhor evento do ano, muito bem organizado.'),
(11, 5, 3, 'O nível dos skatistas era muito alto, mas a organização demorou para começar.'),
(11, 8, 5, 'Manobras incríveis! Evento show de bola, que tenha mais vezes!');