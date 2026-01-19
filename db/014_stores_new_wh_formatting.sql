-- Меняем формат текста рабочих часов магазинов (только текст, без html)

UPDATE stores
    SET work_hours = REPLACE(work_hours, '<br>', '\n');
