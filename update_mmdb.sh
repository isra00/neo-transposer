#!/bin/sh

if [ -z "$NT_MAXMIND_LICENSE_KEY" ]; then
  echo "Environment variable NT_MAXMIND_LICENSE_KEY must be set before calling this script" >&2
  exit 1
fi

echo "Trying to download last MaxMind GeoLite2-Country database..."
wget -q "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=${NT_MAXMIND_LICENSE_KEY}&suffix=tar.gz" -O last-mmdb.tar.gz
tar xfz last-mmdb.tar.gz
rm -rf last-mmdb.tar.gz
folder=$(ls -d GeoLite2-Country_*)
mv -f ./$folder/GeoLite2-Country.mmdb ./GeoLite2-Country.mmdb
rm -rf ./$folder
echo "Updated from version $folder\n"