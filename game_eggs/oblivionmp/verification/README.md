# Reproduce the local checks

Run from `game_eggs/oblivionmp`. Requires Docker and Python 3. The runtime itself is not exercised by these checks.

## Installer

Create a temporary workspace and extract the embedded installer from the egg:

```sh
work=$(mktemp -d)
mkdir "$work/server"
python3 -c 'import json,sys; print(json.load(open("egg-oblivionmp.json"))["scripts"]["installation"]["script"], end="")' > "$work/install.sh"
cp verification/test-reinstall.sh "$work/test-reinstall.sh"
```

Run the fresh install (downloads the pinned public release):

```sh
docker run --rm --platform linux/amd64 \
  -v "$work/server:/mnt/server" -v "$work:/checks:ro" \
  ghcr.io/parkervcp/installers:debian bash /checks/install.sh
```

Download the same release archive for offline reinstall/failure checks:

```sh
curl -fLsS https://readycodestorage.blob.core.windows.net/oblivion-server-releases/oblivion-server-linux64-1.0.0-rc.2.zip -o "$work/server.zip"
docker run --rm --platform linux/amd64 --network none \
  -v "$work/server:/mnt/server" -v "$work:/checks:ro" \
  -v "$work/server.zip:/fixtures/server.zip:ro" \
  ghcr.io/parkervcp/installers:debian bash /checks/test-reinstall.sh
```

These checks use the real archive, checksum, and unzip. The offline harness replaces apt and curl to exercise deterministic reinstall, checksum rejection, and download failure. It modifies only the temporary server directory. Do not point it at an existing game server.

## Panel parser

`check-panel.php` uses the real API checkout and Composer dependencies without booting the application or connecting to a database. Supply a local PHP 8.2+ image with the extensions needed by that checkout:

```sh
docker run --rm --network none \
  -v "$API_CHECKOUT:/api:ro" -v "$PWD:/egg:ro" \
  --entrypoint php "$PHP_TEST_IMAGE" /egg/verification/check-panel.php
```

Checks cover PTDL import/model fill, defaults, boolean/integer conversion, special characters in names/passwords, allocation ports, SIGINT conversion, and invalid slot limits.
