#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   BASE_URL="https://your-domain.com" \
#   APP_SECRET="49382716504938271650493827165049" \
#   RIDER_TOKEN="app_users_token_for_rider" \
#   DRIVER_TOKEN="app_users_token_for_driver" \
#   DRIVER_ID="8" \
#   ./scripts/api_smoke.sh

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
APP_SECRET="${APP_SECRET:-}"
RIDER_TOKEN="${RIDER_TOKEN:-}"
DRIVER_TOKEN="${DRIVER_TOKEN:-}"
DRIVER_ID="${DRIVER_ID:-}"

if [[ -z "$APP_SECRET" || -z "$RIDER_TOKEN" || -z "$DRIVER_TOKEN" || -z "$DRIVER_ID" ]]; then
  echo "Missing required env vars. Set APP_SECRET, RIDER_TOKEN, DRIVER_TOKEN, DRIVER_ID."
  exit 1
fi

echo "== Generating bearer token =="
BEARER="$(curl -sS -X POST "$BASE_URL/api/generateToken" \
  -H "Content-Type: application/json" \
  -d "{\"secret\":\"$APP_SECRET\",\"user_token\":\"$RIDER_TOKEN\"}" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["data"]["token"] ?? "";')"

if [[ -z "$BEARER" ]]; then
  echo "Failed to get bearer token."
  exit 1
fi

echo "Bearer acquired."

api_post() {
  local path="$1"
  local payload="$2"
  echo
  echo "POST $path"
  curl -sS -X POST "$BASE_URL/api/v1/$path" \
    -H "Authorization: Bearer $BEARER" \
    -H "Content-Type: application/json" \
    -d "$payload"
  echo
}

api_get() {
  local path="$1"
  local query="$2"
  echo
  echo "GET $path?$query"
  curl -sS -X GET "$BASE_URL/api/v1/$path?$query" \
    -H "Authorization: Bearer $BEARER"
  echo
}

echo "== Support ticket create/list/thread/reply/status =="
CREATE_RESP="$(curl -sS -X POST "$BASE_URL/api/v1/createSupportTicket" \
  -H "Authorization: Bearer $BEARER" \
  -H "Content-Type: application/json" \
  -d "{\"token\":\"$RIDER_TOKEN\",\"title\":\"Smoke Ticket\",\"description\":\"Created from api_smoke.sh\"}")"
echo "$CREATE_RESP"
TICKET_ID="$(echo "$CREATE_RESP" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["data"]["id"] ?? "";')"

api_get "getSupportTickets" "token=$RIDER_TOKEN"

if [[ -n "$TICKET_ID" ]]; then
  api_get "getSupportTicketThread" "token=$RIDER_TOKEN&ticket_id=$TICKET_ID"
  api_post "replySupportTicket" "{\"token\":\"$RIDER_TOKEN\",\"ticket_id\":$TICKET_ID,\"message\":\"Smoke reply\"}"
  api_post "updateSupportTicketStatus" "{\"token\":\"$RIDER_TOKEN\",\"ticket_id\":$TICKET_ID,\"thread_status\":0}"
  api_post "updateSupportTicketStatus" "{\"token\":\"$RIDER_TOKEN\",\"ticket_id\":$TICKET_ID,\"thread_status\":1}"
fi

echo "== Call masking check =="
api_get "getMaskedCallNumber" "token=$RIDER_TOKEN&driver_id=$DRIVER_ID&ride_id=smoke_ride_001"

echo "== Recharge checks =="
api_get "getRechargePlans" "token=$DRIVER_TOKEN"
api_get "getDriverRechargeStatus" "token=$DRIVER_TOKEN"
api_post "startRechargePayment" "{\"token\":\"$DRIVER_TOKEN\",\"duration_days\":1}"

echo
echo "Smoke run completed."
echo "If any endpoint returns 503 with clear message, no-API-safe mode is working."
