-- ----------------------------------------------------------------------------
-- PROCEDIMENTO PARA GERAR 100 EVENTOS DE TESTE
-- (Rode este script uma vez no seu banco de dados Spark)
-- ----------------------------------------------------------------------------

DELIMITER $$

DROP PROCEDURE IF EXISTS GerarEventosTeste$$

CREATE PROCEDURE GerarEventosTeste()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE cat_id INT;
    DECLARE user_id INT;
    DECLARE parque_id INT;
    DECLARE data_evt DATE;
    DECLARE titulo VARCHAR(255);
    DECLARE desc_texto VARCHAR(255);
    
    -- Loop para criar 100 registros
    WHILE i <= 100 DO
        
        -- 1. Sorteia uma categoria (de 1 a 8)
        -- (Assumindo que você tem ids de 1 a 8 na tabela Categorias)
        SET cat_id = FLOOR(1 + (RAND() * 8));
        
        -- 2. Sorteia um usuário (de 1 a 5) e parque (1 ou 2)
        SET user_id = FLOOR(1 + (RAND() * 5));
        SET parque_id = FLOOR(1 + (RAND() * 2));
        
        -- 3. Define uma data futura aleatória (entre hoje e +180 dias)
        SET data_evt = DATE_ADD(CURDATE(), INTERVAL FLOOR(RAND() * 180) DAY);
        
        -- 4. Gera um nome criativo baseado na categoria
        CASE cat_id
            WHEN 1 THEN SET titulo = CONCAT('Torneio Amador de ', ELT(FLOOR(1 + (RAND() * 3)), 'Futebol', 'Vôlei', 'Basquete'), ' - Edição ', i);
            WHEN 2 THEN SET titulo = CONCAT('Aulão de ', ELT(FLOOR(1 + (RAND() * 3)), 'Yoga', 'Pilates', 'Meditação'), ' ao Ar Livre ', i);
            WHEN 3 THEN SET titulo = CONCAT('Show Acústico: ', ELT(FLOOR(1 + (RAND() * 3)), 'Rock', 'MPB', 'Samba'), ' no Parque ', i);
            WHEN 4 THEN SET titulo = CONCAT('Exposição Cultural: ', ELT(FLOOR(1 + (RAND() * 2)), 'Arte Moderna', 'Fotografia'), ' ', i);
            WHEN 5 THEN SET titulo = CONCAT('Festival Gastronômico: ', ELT(FLOOR(1 + (RAND() * 3)), 'Massas', 'Hambúrguer', 'Vegano'), ' ', i);
            WHEN 6 THEN SET titulo = CONCAT('Encontro de Lazer: ', ELT(FLOOR(1 + (RAND() * 2)), 'Jogos de Tabuleiro', 'Pipa'), ' ', i);
            WHEN 7 THEN SET titulo = CONCAT('Dia da Família no Parque - Edição ', i);
            ELSE SET titulo = CONCAT('Encontro da Comunidade ', i);
        END CASE;

        SET desc_texto = 'Venha participar deste evento incrível! Traga seus amigos e aproveite o dia no parque.';

        -- 5. Insere o evento
        -- Nota: Estamos deixando imagem_path como NULL propositalmente em alguns casos
        -- para testar se o seu código PHP está carregando a imagem default corretamente.
        INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao, imagem_path, idCategoria)
        VALUES (
            parque_id, 
            user_id, 
            titulo, 
            data_evt, 
            '10:00:00', 
            '14:00:00', 
            desc_texto, 
            CONCAT('uploads/eventos/evento_teste_', i, '.jpg'), -- Caminho fictício (vai testar seu fallback)
            cat_id
        );

        SET i = i + 1;
    END WHILE;
END$$

DELIMITER ;

-- Executa o procedimento para criar os 100 eventos
CALL GerarEventosTeste();

-- Remove o procedimento após o uso para limpar o banco
DROP PROCEDURE GerarEventosTeste;