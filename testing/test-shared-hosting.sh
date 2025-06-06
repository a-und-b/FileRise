#!/bin/bash

# FileRise Shared Hosting Test Launcher
# This script launches PHP with shared hosting restrictions

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Default values
CONFIG="moderate"
SCENARIO=""
PORT=8000
OPEN_BASEDIR=""

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --strict)
            CONFIG="strict"
            shift
            ;;
        --moderate)
            CONFIG="moderate"
            shift
            ;;
        --scenario=*)
            SCENARIO="${1#*=}"
            shift
            ;;
        --port=*)
            PORT="${1#*=}"
            shift
            ;;
        --help)
            echo "FileRise Shared Hosting Test Launcher"
            echo ""
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --strict          Use strict shared hosting restrictions"
            echo "  --moderate        Use moderate restrictions (default)"
            echo "  --scenario=NAME   Test specific scenario (cpanel, plesk, generic, subfolder)"
            echo "  --port=PORT       Server port (default: 8000)"
            echo "  --help            Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0                           # Run with moderate restrictions"
            echo "  $0 --strict                  # Run with strict restrictions"
            echo "  $0 --scenario=cpanel         # Test cPanel-style installation"
            echo "  $0 --strict --port=8080      # Strict mode on port 8080"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

# Select configuration file
if [ "$CONFIG" = "strict" ]; then
    CONFIG_FILE="$SCRIPT_DIR/php-shared-hosting.ini"
    echo -e "${YELLOW}Using STRICT shared hosting restrictions${NC}"
else
    CONFIG_FILE="$SCRIPT_DIR/php-shared-hosting-moderate.ini"
    echo -e "${GREEN}Using MODERATE shared hosting restrictions${NC}"
fi

# Set up scenario-specific settings
case $SCENARIO in
    cpanel)
        DOC_ROOT="$SCRIPT_DIR/scenario1-public_html"
        OPEN_BASEDIR="$DOC_ROOT:$SCRIPT_DIR:/tmp"
        echo -e "${YELLOW}Testing cPanel scenario (public_html)${NC}"
        ;;
    plesk)
        DOC_ROOT="$SCRIPT_DIR/scenario2-httpdocs"
        OPEN_BASEDIR="$DOC_ROOT:$SCRIPT_DIR:/tmp"
        echo -e "${YELLOW}Testing Plesk scenario (httpdocs)${NC}"
        ;;
    generic)
        DOC_ROOT="$SCRIPT_DIR/scenario3-www"
        OPEN_BASEDIR="$DOC_ROOT:$SCRIPT_DIR:/tmp"
        echo -e "${YELLOW}Testing generic scenario (www)${NC}"
        ;;
    subfolder)
        DOC_ROOT="$SCRIPT_DIR/scenario4-subfolder"
        OPEN_BASEDIR="$DOC_ROOT:$SCRIPT_DIR:/tmp"
        echo -e "${YELLOW}Testing subfolder installation scenario${NC}"
        ;;
    *)
        DOC_ROOT="$PROJECT_DIR/public"
        OPEN_BASEDIR="$PROJECT_DIR:/tmp"
        echo -e "${GREEN}Testing standard installation${NC}"
        ;;
esac

# Display settings
echo ""
echo "Configuration:"
echo "  Document root: $DOC_ROOT"
echo "  Config file: $CONFIG_FILE"
echo "  Port: $PORT"
echo "  open_basedir: $OPEN_BASEDIR"
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed or not in PATH${NC}"
    exit 1
fi

# Create document root if it doesn't exist
if [ ! -d "$DOC_ROOT" ]; then
    echo -e "${YELLOW}Creating document root: $DOC_ROOT${NC}"
    mkdir -p "$DOC_ROOT"
fi

# Copy test files to scenario directories if testing scenarios
if [ ! -z "$SCENARIO" ]; then
    echo -e "${GREEN}Setting up test files for scenario...${NC}"
    cp "$SCRIPT_DIR/check-environment.php" "$DOC_ROOT/" 2>/dev/null || true
    cp "$SCRIPT_DIR/test-restrictions.php" "$DOC_ROOT/" 2>/dev/null || true
    
    # For subfolder scenario, create the filerise directory
    if [ "$SCENARIO" = "subfolder" ]; then
        mkdir -p "$DOC_ROOT/filerise"
        cp "$SCRIPT_DIR/check-environment.php" "$DOC_ROOT/filerise/" 2>/dev/null || true
        cp "$SCRIPT_DIR/test-restrictions.php" "$DOC_ROOT/filerise/" 2>/dev/null || true
    fi
fi

# Build PHP command
PHP_CMD="php -c $CONFIG_FILE"
PHP_CMD="$PHP_CMD -d open_basedir=$OPEN_BASEDIR"
PHP_CMD="$PHP_CMD -d error_reporting=E_ALL"
PHP_CMD="$PHP_CMD -d display_errors=On"
PHP_CMD="$PHP_CMD -S localhost:$PORT"
PHP_CMD="$PHP_CMD -t $DOC_ROOT"

# Display test URLs
echo ""
echo "Starting PHP server with shared hosting restrictions..."
echo ""
echo "Test URLs:"
echo "  Environment check: http://localhost:$PORT/check-environment.php"
echo "  Restriction test:  http://localhost:$PORT/test-restrictions.php"
if [ "$SCENARIO" = "subfolder" ]; then
    echo "  Subfolder check:   http://localhost:$PORT/filerise/check-environment.php"
fi
echo ""
echo -e "${GREEN}Press Ctrl+C to stop the server${NC}"
echo ""

# Start the server
exec $PHP_CMD 