-- Agregar columna color_mapa a la tabla comunero si no existe
ALTER TABLE comunero 
ADD COLUMN IF NOT EXISTS color_mapa VARCHAR(7) DEFAULT '#3b82f6';

-- Actualizar los registros existentes con colores predeterminados (opcional)
-- Si deseas colores diferentes para los existentes, ejecuta:
/*
UPDATE comunero SET color_mapa = 
    CASE (id_comunero % 8)
        WHEN 0 THEN '#3b82f6'  -- Azul
        WHEN 1 THEN '#10b981'  -- Verde
        WHEN 2 THEN '#f59e0b'  -- Ámbar
        WHEN 3 THEN '#ec4899'  -- Rosa
        WHEN 4 THEN '#8b5cf6'  -- Púrpura
        WHEN 5 THEN '#06b6d4'  -- Cyan
        WHEN 6 THEN '#ef4444'  -- Rojo
        WHEN 7 THEN '#6366f1'  -- Índigo
    END
WHERE color_mapa = '#3b82f6';
*/
