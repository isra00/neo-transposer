#!/bin/sh

echo "Trying to download last MaxMind GeoLite2-Country database..."
curl "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=${NT_MAXMIND_LICENSE_KEY}&suffix=tar.gz" -s --output last-mmdb.tar.gz
tar -xf last-mmdb.tar.gz
rm -rf last-mmdb.tar.gz
folder=$(ls -d GeoLite2-Country_*)
mv -f ./$folder/GeoLite2-Country.mmdb ./GeoLite2-Country.mmdb
rm -rf ./$folder
echo "Updated from version $folder\n"