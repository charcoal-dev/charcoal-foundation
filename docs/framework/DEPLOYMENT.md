# Quick Deployment Guide

This guide outlines the fastest way to deploy **Charcoal Monolith Foundation** using Docker.

## Prerequisites

- **Requirements**:
    - [Docker](https://docs.docker.com/get-docker/) installed and running
    - [Docker Compose](https://docs.docker.com/compose/install/) (if not included with Docker)
    - Git

---

## Deployment Steps

1. **Clone the repository**
   ```sh
   git clone https://github.com/charcoal-dev/charcoal-monolith-foundation
   ```

2. **Enter the project directory**
   ```sh
   cd charcoal-monolith-foundation/
   ```

3. **Prepare configuration files**
   ```sh
   mv config_sample/ config/
   mv sample.env .env
   ```

4. **Run the build script** (this will set up Docker containers)
   ```sh
   bin/build.sh
   ```

    - **This will automatically**:
        - Build Docker containers for the framework
        - Set up **PHP 8.3**, **MySQL 8.1**, and **Redis 7.2**
        - Start all necessary services

5. **Verify deployment**
   ```sh
   bin/services.sh ps
   ```

   If everything is running correctly, you should see your services listed as active.
    
6. **Run the installer**
   ```sh
   ./charcoal.sh install
   ```
   This script will create database tables and fill all initially required objects for the application to run.
  
7. **Access the application**
   Open your web browser and go to:
   ```
   http://your-ip-address:3000
   ```

   Replace `your-ip-address` with the actual IP of your server or `localhost` if running locally.

---

## Notes

- This script **fully automates deployment** using **Docker**.
- No manual setup of PHP, MySQL, or Redis is required—Docker handles it all.
- **Works natively on Ubuntu** and should be compatible with **Debian, Arch, and Fedora** with minor adjustments.

For additional configuration, check the **[full documentation](FOUNDATION.md)**.

