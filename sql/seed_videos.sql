-- Seed de videos para el curso (6 lecciones)
-- Los drive_file_id son ejemplos - reemplazar con IDs reales de Google Drive

INSERT INTO videos (course_id, title, description, drive_file_id, ord) VALUES
(1, 'Introducción a la Ganadería Regenerativa', 'Fundamentos y principios básicos de la ganadería regenerativa, su importancia para el medio ambiente y la sostenibilidad.', '1ABC123def456GHI789jkl', 1),
(1, 'Sistemas de Pastoreo Intensivo', 'Técnicas avanzadas de pastoreo rotacional y su impacto en la regeneración del suelo.', '2DEF456ghi789JKL012mno', 2),
(1, 'Manejo del Suelo y Biodiversidad', 'Estrategias para mejorar la salud del suelo y promover la biodiversidad en el ecosistema.', '3GHI789jkl012MNO345pqr', 3),
(1, 'Genética y Selección Animal', 'Principios de selección genética para ganado resistente y adaptado al clima local.', '4JKL012mno345PQR678stu', 4),
(1, 'Manejo Holístico del Agua', 'Técnicas para optimizar el uso del agua y crear sistemas resilientes ante sequías.', '5MNO345pqr678STU901vwx', 5),
(1, 'Certificación y Mercados', 'Proceso de certificación orgánica y acceso a mercados premium para productos regenerativos.', '6PQR678stu901VWX234yza', 6);

-- Verificar que se insertaron correctamente
SELECT COUNT(*) as total_videos FROM videos WHERE course_id = 1;
