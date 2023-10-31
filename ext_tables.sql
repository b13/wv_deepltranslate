
CREATE TABLE pages
(
	tx_wvdeepltranslate_content_not_checked tinyint unsigned DEFAULT 0 NOT NULL,
	tx_wvdeepltranslate_translated_time     int(10) NOT NULL DEFAULT 0,
	glossary_information                    int(11) unsigned default '0' not null
);
