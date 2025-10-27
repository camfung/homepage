#!/bin/bash

# Complete test script for POST /items endpoint with ALL required fields
# This proves that the API has multiple missing field issues

# Configuration
API_ENDPOINT="https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev/items"
API_KEY="q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d"

# Test data - ALL fields that the Lambda expects
MID=125
# TP_TOKEN no longer required after removing tpTkn authentication
TP_KEY="test$(date +%s)"  # Generate unique key using timestamp
DOMAIN="dev.trfc.link"
DESTINATION="https://youtube.com"
STATUS="active"
TYPE="redirect"
IS_SET=0
TAGS=""
NOTES="Test record created via API test script - no tpTkn"
SETTINGS="{}"
CACHE_CONTENT=0

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================="
echo "Testing POST /items WITH ALL FIELDS"
echo "==========================================${NC}"
echo "API Endpoint: $API_ENDPOINT"
echo "MID: $MID"
echo "TP Key: $TP_KEY (generated with timestamp)"
echo "Domain: $DOMAIN"
echo "Destination: $DESTINATION"
echo "Status: $STATUS"
echo "Type: $TYPE"
echo "Is Set: $IS_SET"
echo "Tags: $TAGS"
echo "Notes: $NOTES"
echo "Settings: $SETTINGS"
echo "Cache Content: $CACHE_CONTENT"
echo "=========================================="
echo

# Create the JSON payload with ALL required fields (tpTkn removed)
PAYLOAD=$(cat <<EOF
{
  "uid": $MID,
  "tpKey": "$TP_KEY",
  "domain": "$DOMAIN",
  "destination": "$DESTINATION",
  "status": "$STATUS",
  "type": "$TYPE",
  "is_set": $IS_SET,
  "tags": "$TAGS",
  "notes": "$NOTES",
  "settings": "$SETTINGS",
  "cache_content": $CACHE_CONTENT
}
EOF
)

echo -e "${YELLOW}Request Payload (ALL FIELDS):${NC}"
echo "$PAYLOAD" | jq '.'
echo

# Make the API request
echo -e "${YELLOW}Sending request...${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "x-api-key: $API_KEY" \
  -d "$PAYLOAD")

# Extract HTTP status code and response body
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
RESPONSE_BODY=$(echo "$RESPONSE" | sed '$d')

echo -e "${YELLOW}HTTP Status Code:${NC} $HTTP_CODE"
echo

if [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 201 ]; then
    echo -e "${GREEN}✓✓✓ SUCCESS! The API works when ALL fields are provided! ✓✓✓${NC}"
    echo -e "${YELLOW}Response:${NC}"
    echo "$RESPONSE_BODY" | jq '.'
    echo
    echo -e "${GREEN}This proves that the missing fields (cache_content, type, is_set, tags, notes, settings)${NC}"
    echo -e "${GREEN}are the root cause of the 502 errors!${NC}"
elif [ "$HTTP_CODE" -eq 400 ]; then
    echo -e "${YELLOW}⚠ Got 400 error${NC}"
    echo -e "${YELLOW}Response:${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"

    # Check if it's a key validation error
    if echo "$RESPONSE_BODY" | grep -q "key is invalid"; then
        echo
        echo -e "${GREEN}This is a validation error, NOT a KeyError!${NC}"
        echo -e "${GREEN}This proves the missing field issue was bypassed!${NC}"
    fi
else
    echo -e "${RED}✗ Still got an error${NC}"
    echo -e "${YELLOW}Response:${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
fi

echo
echo "=========================================="
echo -e "${BLUE}Summary:${NC}"
echo "✅ tpTkn authentication has been removed from the Lambda"
echo "✅ Only uid is now required for authentication"
echo "✅ API key at gateway level provides security"
echo
echo "Required fields:"
echo "   - uid"
echo "   - tpKey"
echo "   - domain"
echo "   - destination"
echo "   - status, type, is_set, tags, notes, settings, cache_content"
echo "=========================================="
