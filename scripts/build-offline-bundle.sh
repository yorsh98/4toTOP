#!/bin/sh
# Builds everything the conversion tools need on a server WITHOUT internet access (no pip, no apt, no npm).
# Run it on a machine with internet; see docs/despliegue-herramientas-pdf.md for the installation steps.
#
#   scripts/build-offline-bundle.sh windows [python-version] [--full]               (default 3.13)
#       Python installer, wheels for win_amd64, Tesseract and Ghostscript installers, Poppler, and the Spanish
#       and orientation (osd) language data. The bundled Python fixes the version, so the wheels always match.
#
#   scripts/build-offline-bundle.sh linux <python-version> <ubuntu-version> [--full]
#       Wheels for the server's python3 (`python3 --version`), and .deb packages (with dependencies) of
#       Tesseract + Spanish, Ghostscript, Poppler and python3-venv, downloaded with Docker in a clean
#       ubuntu:<version> container that has python3, as an Ubuntu Server does.
#
#   --full also adds LibreOffice (Word a PDF) and FFmpeg with FFprobe (M4A a MP3, Cortar audio), if the server lacks them.
#
# Output: build/offline-<platform>-py<version>.zip (Windows) or .tar.gz (Linux).
# Note: LibreOffice's mature ("still") branch is chosen on purpose: the oldest one in /stable.
set -eu

platform="${1:-}"
root="$(cd "$(dirname "$0")/.." && pwd)"
lock="$root/scripts/requirements.lock.txt"
pip="${PIP:-pip}"

full=false
for argument in "$@"; do [ "$argument" = --full ] && full=true; done

case "$platform" in
    windows)
        version="${2:-3.13}"
        [ "$version" = --full ] && version=3.13
        pip_platforms="--platform win_amd64"
        ;;
    linux)
        version="${2:?Indica la versión de python3 del servidor, p. ej. 3.12}"
        ubuntu="${3:?Indica la versión de Ubuntu del servidor, p. ej. 24.04}"
        pip_platforms="--platform manylinux2014_x86_64 --platform manylinux_2_27_x86_64 --platform manylinux_2_28_x86_64 --platform manylinux_2_34_x86_64"
        ;;
    *) sed -n '2,18p' "$0"; exit 2 ;;
esac

tag="py$(echo "$version" | tr -d .)"
name="offline-$platform-$tag"
out="$root/build/$name"
rm -rf "$out"
mkdir -p "$out/wheels"
cp "$lock" "$out/requirements.lock.txt"

echo "== Paquetes Python ($version, $platform)"
# Binary wheels only: the server cannot compile anything.
# shellcheck disable=SC2086
"$pip" download --quiet --dest "$out/wheels" --only-binary=:all: --python-version "$version" --implementation cp \
    --abi "cp$(echo "$version" | tr -d .)" --abi abi3 --abi none $pip_platforms --platform any -r "$lock"

github_asset() { # repository, pattern of the asset name: prints the download URL of the latest release's asset
    curl -fsSL "https://api.github.com/repos/$1/releases/latest" | grep -oE "\"browser_download_url\": *\"[^\"]*$2\"" | head -1 | sed 's/.*"\(https[^"]*\)"/\1/'
}

download() { # url, destination directory
    echo "   $(basename "$1")"
    curl -fsSL --retry 3 -o "$2/$(basename "$1")" "$1"
}

if [ "$platform" = windows ]; then
    mkdir -p "$out/installers" "$out/tessdata"
    echo "== Instaladores para Windows"
    # Newest patch of this Python version that still ships a Windows installer (security-only releases do not).
    for patch in $(curl -fsSL https://www.python.org/ftp/python/ | grep -oE "href=\"$version\.[0-9]+/\"" | grep -oE '[0-9]+/"$' | tr -d '/"' | sort -rn); do
        url="https://www.python.org/ftp/python/$version.$patch/python-$version.$patch-amd64.exe"
        if curl -fsI "$url" >/dev/null 2>&1; then download "$url" "$out/installers"; break; fi
    done
    download "$(github_asset UB-Mannheim/tesseract 'w64-setup[^"]*\.exe')" "$out/installers"
    download "$(github_asset ArtifexSoftware/ghostpdl-downloads 'w64\.exe')" "$out/installers"
    # Spanish text and page orientation (--rotate-pages needs osd), copied into Tesseract's tessdata folder.
    for language in spa osd; do
        download "https://github.com/tesseract-ocr/tessdata/raw/main/$language.traineddata" "$out/tessdata"
    done
    # Poppler (pdfinfo, pdftotext, pdfimages): every PDF tool reads page counts and text with it.
    download "$(github_asset oschwartz10612/poppler-windows 'Release-[^"]*\.zip')" "$out/installers"
    if [ "$full" = true ]; then
        download "$(github_asset GyanD/codexffmpeg 'essentials_build\.zip')" "$out/installers"
        libreoffice="$(curl -fsSL https://download.documentfoundation.org/libreoffice/stable/ | grep -oE 'href="[0-9.]+/"' | head -1 | grep -oE '[0-9.]+[0-9]')"
        download "https://download.documentfoundation.org/libreoffice/stable/$libreoffice/win/x86_64/LibreOffice_${libreoffice}_Win_x86-64.msi" "$out/installers"
    fi
    ls "$out/installers" | grep -q '^python-' || { echo "No se encontró instalador de Python $version para Windows" >&2; exit 1; }
    (cd "$out/installers" && sha256sum ./* ../tessdata/* > ../SHA256SUMS.txt)
    rm -f "$root/build/$name.zip"
    (cd "$root/build" && zip -qr "$name.zip" "$name")
    echo "$root/build/$name.zip"
else
    mkdir -p "$out/debs"
    packages="tesseract-ocr tesseract-ocr-spa tesseract-ocr-osd ghostscript poppler-utils python3-venv"
    if [ "$full" = true ]; then packages="$packages libreoffice-writer-nogui ffmpeg"; fi
    echo "== Paquetes .deb para Ubuntu $ubuntu: $packages"
    # A clean container with python3 (as every Ubuntu Server has) downloads every dependency missing from it.
    docker run --rm -v "$out/debs:/out" "ubuntu:$ubuntu" sh -c \
        "apt-get update -qq && export DEBIAN_FRONTEND=noninteractive && apt-get install -qq -y --no-install-recommends python3 >/dev/null && apt-get clean \
        && apt-get install -qq -y --no-install-recommends --download-only $packages >/dev/null && cp /var/cache/apt/archives/*.deb /out/ && chown -R $(id -u):$(id -g) /out"
    (cd "$out" && sha256sum wheels/* debs/* > SHA256SUMS.txt)
    tar -czf "$root/build/$name.tar.gz" -C "$root/build" "$name"
    echo "$root/build/$name.tar.gz"
fi
