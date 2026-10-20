#!/bin/bash
set -euo pipefail
mkdir -p /test-bin
printf '#!/bin/bash\nexit 0\n' > /test-bin/apt-get
cat > /test-bin/curl <<'CURL'
#!/bin/bash
set -euo pipefail
if [ "${DOWNLOAD_FAIL:-false}" = true ]; then exit 22; fi
cp "${DOWNLOAD_FIXTURE:-/fixtures/server.zip}" "${@: -1}"
CURL
chmod +x /test-bin/*
export PATH="/test-bin:$PATH"
cd /mnt/server
printf '%s\n' '{"custom_setting":"preserve","Server":{"Port":19050}}' > config.json
mkdir -p saves data mods/CustomerMod/server
printf 'saved world\n' > saves/preserve.json
printf 'admin database\n' > data/web.db
printf 'custom mod\n' > mods/CustomerMod/server/custom.dll
printf 'custom config\n' > mods/OblivionMP.Chat/server/customer-settings.json
sha256sum config.json saves/preserve.json data/web.db mods/CustomerMod/server/custom.dll mods/OblivionMP.Chat/server/customer-settings.json > /tmp/preserve.sha256
printf 'broken binary\n' > server
bash /checks/install.sh
sha256sum --check /tmp/preserve.sha256
unzip -p /fixtures/server.zip server | sha256sum > /tmp/expected-binary
sha256sum server | cut -d ' ' -f1 > /tmp/actual-binary
cut -d ' ' -f1 /tmp/expected-binary | cmp - /tmp/actual-binary
test -x server
printf 'PASS: reinstall restores binaries and preserves customer state\n'
sha256sum server config.json saves/preserve.json data/web.db > /tmp/unchanged.sha256
printf 'invalid archive' > /tmp/corrupt.zip
if DOWNLOAD_FIXTURE=/tmp/corrupt.zip bash /checks/install.sh > /tmp/checksum.log 2>&1; then
    echo 'FAIL: corrupt archive accepted'; exit 1
fi
sha256sum --check /tmp/unchanged.sha256
printf 'PASS: checksum failure leaves installation unchanged\n'
if DOWNLOAD_FAIL=true bash /checks/install.sh > /tmp/download.log 2>&1; then
    echo 'FAIL: failed download accepted'; exit 1
fi
sha256sum --check /tmp/unchanged.sha256
printf 'PASS: download failure leaves installation unchanged\n'
