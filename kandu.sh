#!/bin/bash

# Check Ubuntu version
version=$(lsb_release -rs)

if [[ "$version" != "20.04" && "$version" != "22.04" && "$version" != "24.04" ]]; then
    echo "This script can only be run on Ubuntu 20.04, 22.04, or 24.04."
    exit 1
fi

read -p "Please enter the domain: " domain

echo "Requesting SSL for $domain ..."
certbot certonly --standalone -d $domain

if [ $? -ne 0 ]; then
    echo "Failed to obtain SSL."
    exit 1
fi

echo "SSL for $domain obtained successfully."

read -p "Please enter your email: " email
read -p "Please enter the bot token: " bot_token
read -p "Please enter the admin chat ID: " admin_chat_id

echo "<?php
\$domain = '$domain';
\$bot_token = '$bot_token';
\$admin_chat_id = '$admin_chat_id';

echo 'Domain: ' . \$domain . \"<br>\";
echo 'Bot Token: ' . \$bot_token . \"<br>\";
echo 'Admin Chat ID: ' . \$admin_chat_id . \"<br>\";
?>" > baseinfo.php

echo "The baseinfo.php file has been created successfully with the provided information."
