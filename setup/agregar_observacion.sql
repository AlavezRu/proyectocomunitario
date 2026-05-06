-- Script para agregar campo de observación a las tablas de asambleas y tequios
-- Ejecutar este script si las tablas ya existen

-- Agregar columna observacion a la tabla asamblea
ALTER TABLE IF EXISTS asamblea 
ADD COLUMN IF NOT EXISTS observacion TEXT DEFAULT NULL;

-- Agregar columna observacion a la tabla tequio
ALTER TABLE IF EXISTS tequio 
ADD COLUMN IF NOT EXISTS observacion TEXT DEFAULT NULL;

-- Comentarios (opcional, para documentación)
COMMENT ON COLUMN asamblea.observacion IS 'Observaciones sobre lo que se hizo bien en la asamblea';
COMMENT ON COLUMN tequio.observacion IS 'Observaciones sobre lo que se hizo bien en el tequio';
