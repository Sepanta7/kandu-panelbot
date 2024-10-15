#!/bin/bash

# Check Ubuntu version
version=$(lsb_release -rs 2>/dev/null)

if [[ "$version" != "20.04" && "$version" != "22.04" && "$version" != "24.04" ]]; then
    echo "This script can only be run on Ubuntu 20.04, 22.04, or 24.04."
    exit 1
fi

# Display ASCII Art after running the script
echo "██╗  ██╗ █████╗ ███╗   ██╗██████╗ ██╗   ██╗"
echo "██║ ██╔╝██╔══██╗████╗  ██║██╔══██╗██║   ██║"
echo "█████╔╝ ███████║██╔██╗ ██║██║  ██║██║   ██║"
echo "██╔═██╗ ██╔══██║██║╚██╗██║██║  ██║██║   ██║"
echo "██║  ██╗██║  ██║██║ ╚████║██████╔╝╚██████╔╝"
echo "╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝  ╚═════╝ "
echo "                                           "

# Rest of the script...
