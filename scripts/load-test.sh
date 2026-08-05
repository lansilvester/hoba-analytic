#!/usr/bin/env bash
# Load test sederhana untuk API Pixel Joy Analytic (via nginx :80).
# Prasyarat: stack docker up + login berhasil.
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost}"
EMAIL="${EMAIL:-admin@hoba.test}"
PASSWORD="${PASSWORD:-password}"
REQUESTS="${REQUESTS:-200}"
CONCURRENCY="${CONCURRENCY:-20}"

TOKEN=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" \
  | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["token"]??"";')

if [ -z "$TOKEN" ]; then
  echo "Login gagal - periksa BASE_URL/kredensial." >&2
  exit 1
fi

echo "Load test: $REQUESTS request, konkurensi $CONCURRENCY ke $BASE_URL/api/articles"
ab -n "$REQUESTS" -c "$CONCURRENCY" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  "$BASE_URL/api/articles" 2>&1 | grep -E "Requests per second|Failed requests|Transfer rate" || true
