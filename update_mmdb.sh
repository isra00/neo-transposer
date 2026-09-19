#!/bin/sh
set -e

give_up_or_keep_current() {
  rm -f last-mmdb.tar.gz
  if [ -f GeoLite2-Country.mmdb ]; then
    echo "$1 Keeping the GeoLite2-Country.mmdb already present." >&2
    exit 0
  fi
  echo "$1 Creating fallback GeoLite2-Country.mmdb." >&2
  touch GeoLite2-Country.mmdb
  exit 0
}

if [ -z "$NT_MAXMIND_LICENSE_KEY" ]; then
  give_up_or_keep_current "Environment variable NT_MAXMIND_LICENSE_KEY is not set."
fi

echo "Trying to download last MaxMind GeoLite2-Country database..."

if ! wget -q "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=${NT_MAXMIND_LICENSE_KEY}&suffix=tar.gz" -O last-mmdb.tar.gz; then
  give_up_or_keep_current "MaxMind download failed (a 401 means an invalid or expired NT_MAXMIND_LICENSE_KEY)."
fi

if ! tar xfz last-mmdb.tar.gz 2>/dev/null; then
  give_up_or_keep_current "MaxMind returned something that is not a tar.gz (probably an error page)."
fi

rm -f last-mmdb.tar.gz
folder=$(ls -d GeoLite2-Country_*)
mv -f "./$folder/GeoLite2-Country.mmdb" ./GeoLite2-Country.mmdb
rm -r "./$folder"
echo "Updated from version $folder"
