-- ============================================================
-- AUTOSAV — Seeds : company_types + brands
-- LIVRABLE 5
-- ============================================================

-- ------------------------------------------------------------
-- sav_company_types : types d'entreprises
-- ------------------------------------------------------------
INSERT INTO `sav_company_types`
    (`ctp_code`, `ctp_label`, `ctp_description`, `ctp_can_hold`, `ctp_sort_order`, `ctp_is_active`)
VALUES
    ('CONSTRUCTEUR',    'Constructeur',           'Constructeur automobile (fabricant)',                             0, 1,  1),
    ('IMPORTATEUR',     'Importateur',            'Importateur national ou régional représentant une ou des marques', 0, 2,  1),
    ('CONCESSIONNAIRE', 'Concessionnaire',        'Point de vente agréé rattaché à un importateur',                  0, 3,  1),
    ('REPARATEUR',      'Réparateur agréé',       'Réparateur agréé par un constructeur ou importateur',             0, 4,  1),
    ('INDEPENDANT',     'Indépendant',            'Garage ou atelier indépendant sans agrément réseau',              0, 5,  1),
    ('HOLDING',         'Holding',                'Société holding détenant d'autres entités du groupe',             1, 6,  1);

-- ------------------------------------------------------------
-- sav_brands : marques automobiles
-- ------------------------------------------------------------
INSERT INTO `sav_brands`
    (`brd_code`, `brd_name`, `brd_is_active`)
VALUES
    ('VOLKSWAGEN',  'Volkswagen',       1),
    ('AUDI',        'Audi',             1),
    ('SEAT',        'SEAT',             1),
    ('SKODA',       'Škoda',            1),
    ('PORSCHE',     'Porsche',          1),
    ('RENAULT',     'Renault',          1),
    ('DACIA',       'Dacia',            1),
    ('PEUGEOT',     'Peugeot',          1),
    ('CITROEN',     'Citroën',          1),
    ('DS',          'DS Automobiles',   1),
    ('OPEL',        'Opel',             1),
    ('FIAT',        'Fiat',             1),
    ('ALFA_ROMEO',  'Alfa Romeo',       1),
    ('JEEP',        'Jeep',             1),
    ('FORD',        'Ford',             1),
    ('BMW',         'BMW',              1),
    ('MINI',        'MINI',             1),
    ('MERCEDES',    'Mercedes-Benz',    1),
    ('TOYOTA',      'Toyota',           1),
    ('KIA',         'Kia',              1),
    ('HYUNDAI',     'Hyundai',          1),
    ('HONDA',       'Honda',            1),
    ('NISSAN',      'Nissan',           1),
    ('MAZDA',       'Mazda',            1),
    ('SUZUKI',      'Suzuki',           1);

-- ------------------------------------------------------------
-- sav_settings : paramètres L5
-- ------------------------------------------------------------
INSERT INTO `sav_settings` (`stg_key`, `stg_value`, `stg_type`, `stg_group`, `stg_label`, `stg_description`, `stg_is_public`)
VALUES
    ('companies.per_page',    '25',   'integer', 'companies', 'Entreprises par page',             'Nombre d'entreprises par page dans les listings',  0),
    ('brands.per_page',       '25',   'integer', 'brands',    'Marques par page',                 'Nombre de marques par page dans les listings',     0),
    ('companies.allow_self_holding', '0', 'boolean', 'companies', 'Autoriser société = holding', 'Une société peut-elle être sa propre holding',      0);
