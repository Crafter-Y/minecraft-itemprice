ALTER TABLE auctions ADD COLUMN timeCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP();
ALTER TABLE auctions ADD COLUMN notMaintained BOOLEAN DEFAULT false;
ALTER TABLE auctions ADD COLUMN reliable BOOLEAN DEFAULT false;
ALTER TABLE auctions ADD COLUMN mostlyAvailable BOOLEAN DEFAULT false;

ALTER TABLE shops ADD COLUMN defaultNotMaintained BOOLEAN DEFAULT false;
ALTER TABLE shops ADD COLUMN defaultReliable BOOLEAN DEFAULT false;
ALTER TABLE shops ADD COLUMN defaultMostlyAvailable BOOLEAN DEFAULT false;

ALTER TABLE shops ADD COLUMN isLimited BOOLEAN DEFAULT false;

ALTER TABLE auctions ADD COLUMN identifier VARCHAR(64) DEFAULT NULL;
ALTER TABLE auctions ADD COLUMN unique_md5 CHAR(32) AS 
(
    MD5(
        CONCAT_WS('X', 
            timeCreated,
            ifnull(identifier, 0)
        )
    )
);
ALTER TABLE auctions ADD UNIQUE (unique_md5);