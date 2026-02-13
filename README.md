> # ParamAds - AI-Powered Marketing Intelligence Platform
> **Version 1.0.0**

ParamAds is a production-grade, multi-tenant performance marketing intelligence and automation platform. It provides a unified dashboard to connect, analyze, and optimize ad campaigns across Meta (Facebook) and Google Ads. The platform leverages a Python-based AI microservice to deliver predictive forecasting, anomaly detection, natural language insights, and automated budget optimization.

This project was built by **Manus AI** based on the detailed specifications provided.

---

## Key Features

| Category                  | Feature                                                              |
| ------------------------- | -------------------------------------------------------------------- |
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
| **Backend**           | Laravel 11 (PHP 8.1+)                                                   |
| **AI Microservice**   | Python 3.11 with FastAPI, Prophet, Scikit-learn, Pandas                 |
| **Database**          | MySQL 8.0                                                               |
| **Cache & Queues**    | Redis 7                                                                 |
| **Web Server**        | Nginx                                                                   |
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
    git clone <repository_url> paramads
    cd paramads
    ```

2.  **Configure Environment:**

    Copy the production environment template and fill in your credentials.

    ```bash
    cp .env.production .env
    ```

    > **Important:** You must fill in all required variables in the `.env` file, especially `APP_KEY`, database passwords, and external API keys.

3.  **Run the setup script:**

    This script will build the Docker containers, start the services, run database migrations, and seed initial data.

    ```bash
    chmod +x deploy.sh
    ./deploy.sh setup
    ```

4.  **Access the application:**

    -   **Web Interface:** `http://localhost`
    -   **API Base URL:** `http://localhost/api`

### Deployment

The `deploy.sh` script provides a simple interface for managing the application stack.

```bash
# Start all services
./deploy.sh start

# Stop all services
./deploy.sh stop

# View logs for a service (e.g., backend)
./deploy.sh logs backend

# Run database migrations
./deploy.sh migrate

# Create a database backup
./deploy.sh backup
```

---

## Project Structure

The project is organized into a modular structure within the Laravel backend, with a separate directory for the Python AI service.

```
/paramads
├── backend/                # Laravel 11 Application
│   ├── app/
│   │   ├── Modules/        # Core feature modules (Auth, Billing, Campaigns, etc.)
│   │   └── ...
│   ├── config/
│   ├── database/
│   └── routes/
├── ai-service/             # Python FastAPI AI Microservice
│   ├── routers/            # API endpoint definitions
│   ├── services/           # Business logic (forecasting, anomaly detection)
│   └── main.py             # Application entrypoint
├── docker/                 # Docker configurations
│   ├── backend/
│   ├── ai-service/
│   └── nginx/
├── .env.production         # Production environment template
├── docker-compose.yml      # Docker Compose configuration
├── deploy.sh               # Deployment management script
└── README.md               # This file
```
