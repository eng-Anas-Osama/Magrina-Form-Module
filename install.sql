CREATE TABLE IF NOT EXISTS `PREFIX_custom_government` (
    `id_government` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL,
    PRIMARY KEY (`id_government`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_custom_state` (
    `id_state` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_government` int(10) unsigned NOT NULL,
    `name` varchar(64) NOT NULL,
    PRIMARY KEY (`id_state`),
    KEY `government_state` (`id_government`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_custom_zone` (
    `id_zone` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL,
    PRIMARY KEY (`id_zone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_custom_zone_government` (
    `id_zone` int(10) unsigned NOT NULL,
    `id_government` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id_zone`,`id_government`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;