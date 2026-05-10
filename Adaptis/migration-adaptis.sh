#!/bin/bash
# migrate-payment.sh
# Script to migrate Magento payment plugin from Ipay88_Payment to Adaptis_Payment

# Magento CLI command
MAGENTO="php -d memory_limit=512M ../../../bin/magento"

# Colors for readability
GREEN="\e[32m"
RED="\e[31m"
YELLOW="\e[33m"
NC="\e[0m" # No Color

echo -e "${GREEN}=== Starting Magento Payment Plugin Migration ===${NC}"

# Step 1: Disable old module (if exists)
echo -e "${GREEN}Checking for Ipay88_Payment module...${NC}"
if $MAGENTO module:status | grep -q "Ipay88_Payment"; then
    echo -e "${GREEN}Disabling Ipay88_Payment...${NC}"
    if sudo $MAGENTO module:disable Ipay88_Payment; then
        if $MAGENTO module:status | grep -q "Ipay88_Payment.*disabled"; then
            echo -e "${GREEN}Ipay88_Payment successfully disabled.${NC}"
        else
            echo -e "${RED}ERROR: Ipay88_Payment still enabled. Aborting.${NC}"
            exit 1
        fi
    else
        echo -e "${RED}Failed to disable Ipay88_Payment. Aborting.${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}Ipay88_Payment module not found. Skipping disable step.${NC}"
fi
# Step 2: Enable new module
echo -e "${GREEN}Enabling Adaptis_Payment...${NC}"
if sudo $MAGENTO module:enable Adaptis_Payment; then
    echo -e "${GREEN}Adaptis_Payment successfully enabled.${NC}"
else
    echo -e "${RED}Failed to enable Adaptis_Payment. Aborting.${NC}"
    exit 1
fi

# Step 3: Upgrade database
echo -e "${GREEN}Running setup:upgrade...${NC}"
sudo $MAGENTO setup:upgrade || { echo -e "${RED}setup:upgrade failed${NC}"; exit 1; }

# Step 4: Recompile DI
echo -e "${GREEN}Running setup:di:compile...${NC}"
sudo $MAGENTO setup:di:compile || { echo -e "${RED}setup:di:compile failed${NC}"; exit 1; }

# Step 5: Deploy static content
echo -e "${GREEN}Deploying static content (en_US)...${NC}"
sudo $MAGENTO setup:static-content:deploy -f en_US || { echo -e "${RED}Static content deploy failed${NC}"; exit 1; }

# Step 6: Flush cache
echo -e "${GREEN}Flushing cache...${NC}"
sudo $MAGENTO cache:flush || { echo -e "${RED}Cache flush failed${NC}"; exit 1; }

# Step 7: Reindex
echo -e "${GREEN}Reindexing data...${NC}"
sudo $MAGENTO indexer:reindex || { echo -e "${RED}Reindex failed${NC}"; exit 1; }

# Step 8: Fix permissions
echo -e "${GREEN}Fixing permissions for var/cache and pub...${NC}"
sudo chmod -R 757 "../../../var/cache/" || { echo -e "${RED}Failed to chmod var/cache${NC}"; exit 1; }
sudo chmod -R 757 "../../../pub/" || { echo -e "${RED}Failed to chmod pub${NC}"; exit 1; }

echo -e "${GREEN}=== Migration Completed Successfully! ===${NC}"

