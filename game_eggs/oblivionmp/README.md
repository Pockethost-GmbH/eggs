# OblivionMP

ReadyM multiplayer for The Elder Scrolls IV: Oblivion Remastered. This Pockethost egg installs the Linux x64 **1.0.0-rc.2** server and its bundled SDK, Chat, MarkerMod, Quests, and SeedLoader. Players use the ReadyM Launcher.

- [Installation](https://docs.ready.mp/oblivion-mp/docs/Server/installation)
- [Release notes](https://docs.ready.mp/releases/oblivionmp-sdk-100-rc2)
- [Known issues](https://docs.ready.mp/oblivion-mp/docs/known-issues)

## Runtime

Uses the existing Pockethost standard runtime image pinned to digest `sha256:9d3bbc945d2d6545ac44e1d6059afb0391d12a203e984d76cd6a3d0300f037d3`. Its source installs .NET 10. Nodes need access to our private ECR registry. No custom runtime image or Wardogs readiness wrapper is required.

Startup is `exec ./server`, as the container user in `/home/container`.

## Allocation and settings

Allocate the same port number for **UDP game traffic and TCP HTTP traffic**. The egg writes the allocation port to both `Server.Port` and `ServerList.PublicPort`; the release default is 9050. Our Wings allocation handling publishes both protocols.

Public listing defaults to disabled. Before enabling it, set **Public Host** to the public IP or hostname routed to the allocation. Do not enter a container IP or private node address. This egg assumes the public port equals the allocated port; a deployment with different external port translation needs a separate advertised-port setting.

The panel controls the server name, password, player limit, listing toggle, public host, game mode label, description, country, optional verification key, and telemetry setting. These values are applied at startup. Other JSON settings remain untouched. The game mode field is a listing label; it does not install a mode.

This egg targets Pockethost's panel fork, which converts validated boolean and numeric environment values for JSON replacement. A stock panel needs its own compatibility check.

The initial limit is eight players; it is an operator-controlled starting value, not a measured capacity recommendation. The server documents a range of 1-64. Measure real player load before selecting plans.

## Admin setup

Open `http://PUBLIC_HOST:ALLOCATED_PORT`, enter the one-time PIN from the server console, and complete ReadyM sign-in. Validate the web bind and sign-in redirect through the real public address before release. Keep setup PINs and license keys out of shared test logs.

[Admin instructions](https://docs.ready.mp/oblivion-mp/docs/Server/admin-panel) · [Listing instructions](https://docs.ready.mp/oblivion-mp/docs/Server/publishing-your-server)

## Installation, persistence, and updates

The installer downloads a fixed official URL and checks SHA-256 before modifying release files. The recorded hash identifies the archive inspected on 2026-10-20; it is not a vendor-signed checksum.

Reinstall restores release-owned files and preserves:

- Existing `config.json`, including custom settings.
- `saves/` and `data/`, including the admin database.
- Custom mod folders and extra files absent from the release archive.

Files shipped inside the five bundled mod folders are release-owned and are overwritten on reinstall. Put custom mods in separate folders. The package currently has no bundled user configuration files beyond the root `config.json`; custom modifications to shipped files should be backed up.

There is no automatic version update. A new release requires reviewing its archive, updating the pinned URL/checksum, and testing compatibility. Back up the stopped server before changing versions. rc.1 custom mods require a rebuild for rc.2; Chat's package version is rc.3. Restore the matching backup when rolling back across incompatible state changes.

The upstream defaults are five-minute autosaves and a 30-second shutdown drain. The candidate stop setting sends SIGINT. Verify an actual save and graceful exit before rollout; do not substitute an immediate kill.

This first egg installs files directly. Do not add WARP binary layers underneath that installation: existing writable files would mask them. A WARP rollout should use a separate clean server or a planned migration, with a verified published revision and a config-only installer.

## Validation status (2026-10-20)

Reproducible checks are in [verification](verification/README.md).

Passed locally:

- Real installer-container download, archive integrity/checksum, extraction, and executable permissions.
- Reinstall restores a modified binary while preserving config, saves, admin data, and custom mod files.
- Corrupt archive and failed download stop before modifying installed files.
- Actual Pockethost PTDL parser/model fill, variable defaults, typed settings, quoted names/passwords, port substitution, and stop-signal conversion.
- JSON, Bash syntax, and diff whitespace checks.

Still required before rollout:

- Run the exact published runtime image; ECR access was blocked by an expired local AWS session during preparation.
- Confirm the candidate readiness text `Running server on port` occurs after successful initialization, and verify SIGINT save/exit/restart behavior.
- External TCP/UDP access, admin sign-in, two-player join/mod sync, public listing, real save/restore, and resource measurements.

No live panel import, game-server deployment, or WARP revision publication has been performed. This is beta software: consult upstream known issues, including incomplete character persistence.
