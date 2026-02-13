> # ParamAds - AI-Powered Marketing Intelligence Platform
> **Version 2.0.0**

ParamAds is a production-grade, multi-tenant performance marketing intelligence and automation platform. It provides a unified dashboard to connect, analyze, and optimize ad campaigns across Meta (Facebook) and Google Ads. The platform leverages a Python-based AI microservice to deliver predictive forecasting, anomaly detection, natural language insights, and automated budget optimization.

This project was built by **Manus AI** based on the detailed specifications provided.

---

## Key Features

| Category                  | Feature                                                              |
| ------------------------- | -------------------------------------------------------------------- |
| **Web Installer**         | Step-by-step browser-based installation wizard.                      |
|                           | System requirement checks, database & API key configuration.         |
|                           | CyberPanel deployment script and manual guide.                       |
| **Admin Dashboard**       | Centralized management for users, organizations, and system settings.|
|                           | API key management for Meta, Google, and Stripe.                     |
|                           | View system logs and application health.                             |
| **User Dashboard**        | Intuitive interface for campaign management and performance tracking.|
|                           | Detailed analytics and data visualization.                           |
| **Ad Creation**           | Create and manage ad campaigns directly within the platform.         |
|                           | Geographic targeting with pincode and zipcode support.               |
| **Core Platform**         | Multi-tenant architecture with organization and user management.     |
|                           | Subscription billing via Stripe (Cashier integration).               |
|                           | Role-based access control (Owner, Admin, Manager, Analyst, Viewer).  |
| **Ad Integrations**       | Secure OAuth 2.0 for Meta Ads and Google Ads.                        |
|                           | Automated token management and refresh.                              |
|                           | Centralized campaign control (pause, activate, budget edits).        |
| **Metrics & Automation**  | 15-minute interval metric synchronization.                           |
|                           | Advanced rule-based automation engine (e.g., "if ROAS > 3, increase budget by 20%"). |
|                           | Comprehensive dashboard API for KPI visualization.                   |
| **Creative Intelligence** | Automated creative performance scoring and fatigue detection.        |
|                           | Trend analysis to identify high-performing ad creatives.             |
| **Audience Intelligence** | Pixel connection and audience synchronization.                       |
|                           | Retargeting funnel builder and audience overlap analysis.            |
| **Predictive AI Layer**   | Time-series forecasting for key metrics (Spend, ROAS, CPA).          |
|                           | Anomaly detection using Isolation Forest and statistical methods.    |
|                           | Natural language (NL) performance summaries and Q&A.                 |
| **Cross-Platform Intel**  | Metric normalization for unified cross-platform reporting.           |
|                           | Unified campaign health scoring and budget reallocation recommendations. |
| **Agency & Enterprise**   | Sub-account management for agencies.                                 |
|                           | White-labeling (custom domain, branding, colors).                    |
|                           | Scheduled, exportable reports (PDF, CSV).                            |
|                           | Webhooks for real-time event notifications.                          |
|                           | Detailed activity logging for audit trails.                          |

---

## Tech Stack

| Component             | Technology                                                              |
| --------------------- | ----------------------------------------------------------------------- |
| **Backend**           | Laravel 11 (PHP 8.1+) with Blade templates & Tailwind CSS               |
| **AI Microservice**   | Python 3.11 with FastAPI, Prophet, Scikit-learn, Pandas                 |
| **Database**          | MySQL 8.0                                                               |
| **Cache & Queues**    | Redis 7                                                                 |
| **Web Server**        | Nginx / OpenLiteSpeed (for CyberPanel)                                  |
| **Containerization**  | Docker & Docker Compose                                                 |

---

## Getting Started

### Prerequisites

- Docker
- Docker Compose (v2+)
- Git

### Installation

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/bhavyashah000/ParamAds.git
    cd ParamAds
    ```

2.  **Run the setup script:**

    This script will build the Docker containers, start the services, and prepare the application.

    ```bash
    chmod +x deploy.sh
    ./deploy.sh setup
    ```

3.  **Run the Web Installer:**

    Access the application in your browser to complete the installation:

    -   **URL:** `http://localhost/install`

    The installer will guide you through:
    -   System requirements check
    -   Database configuration
    -   API key setup (Meta, Google, Stripe)
    -   Admin account creation

4.  **Access the application:**

    -   **Web Interface:** `http://localhost`
    -   **API Base URL:** `http://localhost/api`

### CyberPanel Deployment

For deployment on a CyberPanel server, please refer to the detailed instructions in `CYBERPANEL_GUIDE.md` and use the `cyberpanel-deploy.sh` script.

---

## Project Structure

```
/ParamAds
├── backend/                # Laravel 11 Application
│   ├── app/
│   │   ├── Modules/        # Core feature modules (Auth, Billing, Campaigns, etc.)
│   │   └── ...
│   ├── config/
│   ├── database/
│   ├── resources/
│   │   └── views/        # Blade templates for Installer, Admin, and User Dashboards
│   └── routes/
├── ai-service/             # Python FastAPI AI Microservice
│   ├── routers/            # API endpoint definitions
│   ├── services/           # Business logic (forecasting, anomaly detection)
│   └── main.py             # Application entrypoint
├── docker/                 # Docker configurations
├── .env.production         # Production environment template
├── docker-compose.yml      # Docker Compose configuration
├── deploy.sh               # Deployment management script
├── cyberpanel-deploy.sh    # CyberPanel automated deployment script
├── CYBERPANEL_GUIDE.md     # Manual CyberPanel deployment guide
└── README.md               # This file
```
