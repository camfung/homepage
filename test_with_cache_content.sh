#!/bin/bash

# Test script for POST /items endpoint WITH cache_content attribute
# This should prove that including cache_content fixes the error

# Configuration
API_ENDPOINT="https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev/items"
API_KEY="q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d"

# Test data
MID=125
TP_TOKEN="MkmFJGQJlCyAuFWkkIiG"
TP_KEY="abc123"
DOMAIN="dev.trfc.link"
DESTINATION="https://youtube.com"
STATUS="active"
CACHE_CONTENT=0  # Setting to 0 (false) to avoid disk space issues

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================="
echo "Testing POST /items WITH cache_content"
echo "==========================================${NC}"
echo "API Endpoint: $API_ENDPOINT"
echo "MID: $MID"
echo "TP Token: $TP_TOKEN"
echo "TP Key: $TP_KEY"
echo "Domain: $DOMAIN"
echo "Destination: $DESTINATION"
echo "Status: $STATUS"
echo "Cache Content: $CACHE_CONTENT"
echo "=========================================="
echo

# Create the JSON payload WITH cache_content
PAYLOAD=$(cat <<EOF
{
  "uid": $MID,
  "tpTkn": "$TP_TOKEN",
  "tpKey": "$TP_KEY",
  "domain": "$DOMAIN",
  "destination": "$DESTINATION",
  "status": "$STATUS",
  "cache_content": $CACHE_CONTENT
}
EOF
)

echo -e "${YELLOW}Request Payload (WITH cache_content):${NC}"
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
    echo -e "${GREEN}✓ SUCCESS! The cache_content field fixed the issue!${NC}"
    echo -e "${YELLOW}Response:${NC}"
    echo "$RESPONSE_BODY" | jq '.'
elif [ "$HTTP_CODE" -eq 400 ]; then
    echo -e "${YELLOW}⚠ Got 400 error (might be 'key is invalid' because key already exists)${NC}"
    echo -e "${YELLOW}Response:${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
    echo
    echo -e "${BLUE}This is expected if the key 'abc123' already exists in the database.${NC}"
    echo -e "${BLUE}The important thing is: we did NOT get the 502 'cache_content' KeyError!${NC}"
else
    echo -e "${RED}✗ Error!${NC}"
    echo -e "${YELLOW}Response:${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
fi

echo
echo "=========================================="
echo -e "${YELLOW}Comparison:${NC}"
echo "- WITHOUT cache_content: 502 error with KeyError: 'cache_content'"
echo "- WITH cache_content: Should succeed (or 400 if key exists)"
echo "=========================================="
