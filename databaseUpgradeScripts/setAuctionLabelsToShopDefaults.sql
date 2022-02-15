UPDATE auctions
INNER JOIN shops ON shops.id = auctions.shopId 
SET
    auctions.notMaintained = shops.defaultNotMaintained,
    auctions.reliable = shops.defaultReliable,
    auctions.mostlyAvailable = shops.defaultMostlyAvailable;