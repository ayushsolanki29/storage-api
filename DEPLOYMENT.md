# Mini Cloudinary - Hostinger Deployment Guide

## Prerequisites
- Hostinger shared hosting account
- PHP 8.x enabled
- MySQL/MariaDB database
- SSL certificate (free Let's Encrypt)

## Step 1: Database Setup
1. Create a new MySQL database in Hostinger control panel
2. Import the `mini_cloudinary.sql` file
3. Note down database credentials

## Step 2: File Upload
1. Upload all files to `public_html/mini-cloudinary/`
2. Set proper permissions:
   ```bash
   chmod 755 uploads/
   chmod 644 config/database.php