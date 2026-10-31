#!/usr/bin/env bash
# Construiește arhiva instalabilă a pluginului Grădina Ielelor:
#   dist/gradina-ielelor-integration-<versiune>.zip  (+ .sha256 alături)
#
# - versiunea se citește din antetul pluginului (linia „Version:”);
# - folderul pluginului este la rădăcina arhivei (așa cum îl cere WordPress la „Încarcă modul”);
# - fișierele de dezvoltare nu intră în arhivă;
# - arhiva se verifică: unzip -t, php -l (și node --check, dacă există node) pe ce este ÎN arhivă,
#   pozele din binary-manifest.tsv (prezente și cu mărimea corectă);
# - la final se afișează amprenta SHA-256.
# Aceeași sursă dă aceeași arhivă (aceeași amprentă), local și în GitHub Actions.
#
# Utilizare:  tools/build-zip.sh [--allow-missing-images] [--help]
#   --allow-missing-images  construiește și dacă lipsesc poze; arhiva primește sufixul „-INCOMPLET”
#                           și NU se instalează (WordPress șterge folderul vechi, deci și pozele lui).
set -euo pipefail

SLUG="gradina-ielelor-integration"
ALLOW_MISSING=0

for arg in "$@"; do
    case "$arg" in
        --allow-missing-images) ALLOW_MISSING=1 ;;
        -h|--help) sed -n '2,15p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *) echo "Opțiune necunoscută: $arg (vezi --help)" >&2; exit 2 ;;
    esac
done

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="$ROOT/$SLUG"
MAIN="$PLUGIN_DIR/$SLUG.php"
MANIFEST="$ROOT/binary-manifest.tsv"
OUT="$ROOT/dist"

fail() { echo "EROARE: $*" >&2; exit 1; }
warn() { echo "ATENȚIE: $*" >&2; }

# --- Unelte necesare -------------------------------------------------------------------------
for tool in php zip unzip tar find sort; do
    command -v "$tool" >/dev/null 2>&1 || fail "lipsește comanda „$tool”. Instaleaz-o sau construiește arhiva din GitHub Actions (workflow „Build”)."
done
if command -v sha256sum >/dev/null 2>&1; then
    sha256() { sha256sum "$1" | cut -d' ' -f1; }
elif command -v shasum >/dev/null 2>&1; then
    sha256() { shasum -a 256 "$1" | cut -d' ' -f1; }
else
    fail "lipsește sha256sum (sau shasum)."
fi
HAVE_NODE=0
command -v node >/dev/null 2>&1 && HAVE_NODE=1

[ -f "$MAIN" ] || fail "nu găsesc fișierul principal al pluginului: $MAIN"

# --- Versiunea -------------------------------------------------------------------------------
VERSION="$(sed -n 's/^[[:space:]/*#@]*Version:[[:space:]]*//p' "$MAIN" | head -n1 | tr -d '\r' | sed 's/[[:space:]]*$//')"
[ -n "$VERSION" ] || fail "nu găsesc linia „Version:” în antetul $SLUG.php."
[[ "$VERSION" =~ ^[0-9]+(\.[0-9]+){1,3}([-+][0-9A-Za-z.-]+)?$ ]] || fail "versiune nevalidă în antet: „$VERSION”."

CONST_VERSION="$(sed -n "s/.*define([[:space:]]*'GI_VERSION'[[:space:]]*,[[:space:]]*'\([^']*\)'.*/\1/p" "$MAIN" | head -n1)"
if [ -n "$CONST_VERSION" ] && [ "$CONST_VERSION" != "$VERSION" ]; then
    fail "antetul spune $VERSION, dar GI_VERSION este $CONST_VERSION. Trebuie să fie egale (GI_VERSION reîmprospătează CSS/JS în browsere)."
fi
README_TXT="$PLUGIN_DIR/readme.txt"
if [ -f "$README_TXT" ]; then
    STABLE="$(sed -n 's/^Stable tag:[[:space:]]*//p' "$README_TXT" | head -n1 | tr -d '\r' | sed 's/[[:space:]]*$//')"
    [ -z "$STABLE" ] || [ "$STABLE" = "$VERSION" ] || warn "readme.txt are „Stable tag: $STABLE”, antetul are $VERSION."
fi

# O arhivă veche a aceleiași versiuni nu trebuie să rămână în dist/ dacă această construire eșuează.
rm -f "$OUT/$SLUG-$VERSION.zip" "$OUT/$SLUG-$VERSION.zip.sha256" "$OUT/$SLUG-$VERSION-INCOMPLET.zip" "$OUT/$SLUG-$VERSION-INCOMPLET.zip.sha256"

# --- Fișiere care nu au voie să existe în plugin (chei, parole, baze de date) ----------------
SECRETS="$(cd "$ROOT" && find "$SLUG" -type f \( -iname '*.key' -o -iname '*.pem' -o -iname '*.cer' -o -iname '*.crt' \
    -o -iname '*.p12' -o -iname '*.pfx' -o -iname '.env' -o -iname '.env.*' -o -iname 'wp-config.php' \
    -o -iname '*.sql' -o -iname '*.sql.gz' -o -iname 'id_rsa*' -o -iname 'id_ed25519*' \) -print)"
[ -z "$SECRETS" ] || fail "am găsit fișiere care par chei, parole sau baze de date în plugin:
$SECRETS
Acestea nu se pun niciodată în plugin sau în depozit. Scoate-le (cheile NETOPIA se încarcă direct în WordPress)."

# --- Copie curată, fără fișierele de dezvoltare ----------------------------------------------
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
STAGE="$WORK/stage"
CHECK="$WORK/check"
mkdir -p "$STAGE" "$CHECK"

EXCLUDES=(
    '.git' '.gitignore' '.gitattributes' '.github' '.ftp-deploy-sync-state.json'
    '.DS_Store' 'Thumbs.db' 'desktop.ini' '__MACOSX'
    'node_modules' '.idea' '.vscode' 'tests'
    '*.md' '*.map' '*.log' '*.zip' '*.bak' '*.orig' '*.rej' '*.tmp' '*.swp' '*.swo' '*~'
    'package.json' 'package-lock.json' 'yarn.lock' 'pnpm-lock.yaml' 'composer.json' 'composer.lock'
    'phpcs.xml' 'phpcs.xml.dist' '.phpcs.xml.dist' 'phpunit.xml' 'phpunit.xml.dist'
    '.editorconfig' '.eslintrc*' '.prettierrc*' '.stylelintrc*' '.browserslistrc'
    '*.psd' '*.ai' '*.sketch' '*.xcf'
)
TAR_EXCLUDES=()
for p in "${EXCLUDES[@]}"; do TAR_EXCLUDES+=(--exclude="$p"); done
tar -C "$ROOT" "${TAR_EXCLUDES[@]}" -cf - "$SLUG" | tar -C "$STAGE" -xf -

SRC_COUNT="$(cd "$ROOT" && find "$SLUG" -type f | wc -l | tr -d ' ')"
STAGE_COUNT="$(cd "$STAGE" && find "$SLUG" -type f | wc -l | tr -d ' ')"

# Arhivă reproductibilă: permisiuni, date și ordine fixe.
if [ -n "${SOURCE_DATE_EPOCH:-}" ]; then
    EPOCH="$SOURCE_DATE_EPOCH"
else
    EPOCH="$(git -C "$ROOT" log -1 --format=%ct -- "$SLUG" 2>/dev/null || true)"
    [ -n "$EPOCH" ] || EPOCH=315532800   # 1980-01-01, cea mai veche dată posibilă în zip
fi
find "$STAGE" -type d -exec chmod 755 {} +
find "$STAGE" -type f -exec chmod 644 {} +
find "$STAGE" -exec touch -h -d "@$EPOCH" {} +

# --- Poze: toate cele din binary-manifest.tsv, cu mărimea corectă ----------------------------
MISSING=()
if [ -f "$MANIFEST" ]; then
    while IFS=$'\t' read -r rel _src size _mime || [ -n "$rel" ]; do
        rel="${rel%$'\r'}"; size="${size%$'\r'}"
        [ -n "$rel" ] || continue
        case "$rel" in \#*) continue ;; esac
        f="$STAGE/$SLUG/$rel"
        if [ ! -f "$f" ]; then
            MISSING+=("lipsește $rel")
        elif [ -n "$size" ] && [ "$(wc -c < "$f" | tr -d ' ')" != "$size" ]; then
            MISSING+=("$rel are $(wc -c < "$f" | tr -d ' ') octeți, manifestul spune $size (poză provizorie sau manifest neactualizat?)")
        fi
    done < "$MANIFEST"
else
    warn "nu există binary-manifest.tsv; pozele nu pot fi verificate."
fi

COMPLETE=1
SUFFIX=""
if [ "${#MISSING[@]}" -gt 0 ]; then
    printf '  - %s\n' "${MISSING[@]}" >&2
    if [ "$ALLOW_MISSING" -eq 1 ]; then
        COMPLETE=0
        SUFFIX="-INCOMPLET"
        warn "poze lipsă sau greșite: ${#MISSING[@]}. Arhiva va avea sufixul $SUFFIX și NU se instalează."
    else
        fail "poze lipsă sau greșite: ${#MISSING[@]} (lista de mai sus). Pune pozele în $SLUG/assets/ sau actualizează binary-manifest.tsv. Doar pentru test: --allow-missing-images."
    fi
fi

# --- Arhiva ----------------------------------------------------------------------------------
mkdir -p "$OUT"
ZIP_NAME="$SLUG-$VERSION$SUFFIX.zip"
ZIP="$OUT/$ZIP_NAME"
rm -f "$ZIP" "$ZIP.sha256"
(
    cd "$STAGE"
    find "$SLUG" -print | LC_ALL=C sort | TZ=UTC zip -X -q "$ZIP" -@
)

# --- Verificări pe conținutul arhivei --------------------------------------------------------
bad() { rm -f "$ZIP" "$ZIP.sha256"; fail "$*"; }

unzip -tq "$ZIP" >/dev/null || bad "unzip -t a găsit erori în arhivă."

ENTRIES="$(unzip -Z1 "$ZIP")"
OUTSIDE="$(grep -v "^$SLUG/" <<< "$ENTRIES" || true)"
[ -z "$OUTSIDE" ] || bad "arhiva are fișiere în afara folderului $SLUG/:
$OUTSIDE"
grep -qx "$SLUG/$SLUG.php" <<< "$ENTRIES" || bad "lipsește $SLUG/$SLUG.php din arhivă."
LEAK="$(grep -E '(^|/)(\.git|\.github|node_modules|\.DS_Store)(/|$)|\.(md|map|zip|log)$' <<< "$ENTRIES" || true)"
[ -z "$LEAK" ] || bad "au intrat fișiere de dezvoltare în arhivă:
$LEAK"

unzip -q "$ZIP" -d "$CHECK"
PHP_FILES=0
PHP_ERRORS=""
while IFS= read -r -d '' f; do
    PHP_FILES=$((PHP_FILES + 1))
    if ! out="$(php -l "$f" 2>&1)"; then
        PHP_ERRORS+="${f#$CHECK/}: $out"$'\n'
    fi
done < <(find "$CHECK" -type f -name '*.php' -print0)
[ -z "$PHP_ERRORS" ] || bad "php -l a găsit erori:
$PHP_ERRORS"

JS_FILES=0
JS_NOTE="neverificat (lipsește node)"
if [ "$HAVE_NODE" -eq 1 ]; then
    JS_ERRORS=""
    while IFS= read -r -d '' f; do
        JS_FILES=$((JS_FILES + 1))
        if ! out="$(node --check "$f" 2>&1)"; then
            JS_ERRORS+="${f#$CHECK/}: $out"$'\n'
        fi
    done < <(find "$CHECK" -type f -name '*.js' -print0)
    [ -z "$JS_ERRORS" ] || bad "node --check a găsit erori:
$JS_ERRORS"
    JS_NOTE="$JS_FILES fișiere, fără erori"
else
    warn "node lipsește: JS-ul nu a fost verificat (GitHub Actions îl verifică)."
fi

# --- Amprenta și rezumatul ------------------------------------------------------------------
SHA="$(sha256 "$ZIP")"
printf '%s  %s\n' "$SHA" "$ZIP_NAME" > "$ZIP.sha256"
BYTES="$(wc -c < "$ZIP" | tr -d ' ')"
HUMAN="$(awk -v b="$BYTES" 'BEGIN { if (b >= 1048576) printf "%.1f MB", b/1048576; else printf "%.0f KB", b/1024 }')"
FILES_IN_ZIP="$(grep -cv '/$' <<< "$ENTRIES" || true)"

echo
echo "Plugin:      $SLUG $VERSION"
echo "Arhivă:      ${ZIP#$ROOT/}"
echo "Mărime:      $HUMAN ($BYTES octeți), $FILES_IN_ZIP fișiere ($((SRC_COUNT - STAGE_COUNT)) fișiere de dezvoltare lăsate deoparte)"
echo "Verificări:  unzip -t OK; php -l: $PHP_FILES fișiere, fără erori; node --check: $JS_NOTE"
if [ "$COMPLETE" -eq 1 ]; then
    echo "Poze:        toate cele din binary-manifest.tsv, cu mărimea corectă"
else
    echo "Poze:        INCOMPLET (${#MISSING[@]} lipsă) - arhivă doar pentru test, NU o instala"
fi
echo "SHA-256:     $SHA"

if [ -n "${GITHUB_OUTPUT:-}" ]; then
    {
        echo "version=$VERSION"
        echo "zip=${ZIP#$ROOT/}"
        echo "zip_name=$ZIP_NAME"
        echo "sha256=$SHA"
        echo "bytes=$BYTES"
        echo "complete=$([ "$COMPLETE" -eq 1 ] && echo true || echo false)"
    } >> "$GITHUB_OUTPUT"
fi
