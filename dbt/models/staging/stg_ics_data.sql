{{ config(materialized='view') }}

WITH raw_data AS (
    -- Lee directamente de la tabla original de la hackaton
    SELECT * FROM {{ source('hack26', 'cohort') }}
)

SELECT
    -- Mantén las IDs como están (añade las correctas)
    id,
    
    -- Aqui hacemos la MAGIA original: Convertimos NULLs en 0
    COALESCE(columna_nula_1, 0) AS columna_nula_1_limpia,
    
    -- TRADUCCIÓN A NÚMEROS (Feature Engineering para Machine Learning)
    -- Traducimos el Sexo a 1 y 0, abarcando las opciones en catalán o castellano
    CASE 
        WHEN LOWER(sexe) IN ('home', 'h', 'm', 'masculin', 'male', '1') THEN 1
        WHEN LOWER(sexe) IN ('dona', 'd', 'f', 'femeni', 'female', '0') THEN 0
        ELSE NULL 
    END AS sexe_num,

    -- Traducimos Crónico
    CASE 
        WHEN LOWER(cronic) IN ('sí', 'si', 'yes', 'y', 's', '1', 'true') THEN 1 
        WHEN LOWER(cronic) IN ('no', 'n', '0', 'false') THEN 0 
        ELSE 0 -- Asumimos 0 si viene vacío o raro
    END AS cronic_num

FROM raw_data
