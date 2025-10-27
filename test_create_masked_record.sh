#!/bin/bash

# Test script for POST /items endpoint (CreateMaskedRecordFunction)
# This endpoint creates a masked record (shortlink) in the tp_map table

# Configuration
API_ENDPOINT="https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev/items"
API_KEY="q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d"  # Replace with your actual API key

# Test data
MID=125
TP_TOKEN="MkmFJGQJlCyAuFWkkIiG"
TP_KEY="abc123"
DOMAIN="dev.trfc.link"
DESTINATION="https://youtube.com"
STATUS="active"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Testing POST /items endpoint${NC}"
echo "=========================================="
echo "API Endpoint: $API_ENDPOINT"
echo "MID: $MID"
echo "TP Token: $TP_TOKEN"
echo "TP Key: $TP_KEY"
echo "Domain: $DOMAIN"
echo "Destination: $DESTINATION"
echo "Status: $STATUS"
echo "=========================================="
echo

# Create the JSON payload
PAYLOAD=$(cat <<EOF
{
  "uid": $MID,
  "tpTkn": "$TP_TOKEN",
  "tpKey": "$TP_KEY",
  "domain": "$DOMAIN",
  "destination": "$DESTINATION",
  "status": "$STATUS"
}
EOF
)

echo -e "${YELLOW}Request Payload:${NC}"
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
    echo -e "${GREEN}✓ Success!${NC}"
    echo -e "${YELLOW}Response:${NC}"
    echo "$RESPONSE_BODY" | jq '.'
else
    echo -e "${RED}✗ Error!${NC}"
    echo -e "${YELLOW}Response:${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
fi

echo
echo "=========================================="
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Check CloudWatch logs for detailed error information"
echo "2. Run: aws logs tail /aws/lambda/dev-CreateMaskedRecordFunction --region ca-central-1 --follow"
