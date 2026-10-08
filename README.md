# OptimaChain

> **Predict. Prevent. Optimise.**

OptimaChain is an AI-powered supply chain intelligence and credit scoring platform designed to help distributors, managers, logistics operators, and financial institutions collaborate seamlessly. Starting with the cement distribution ecosystem, OptimaChain analyzes historical sales, inventory, weather metrics, and fulfillment data to deliver high-precision demand forecasting, automated review queues, and cryptographic credit scorecards for bank financing.

Instead of merely reflecting past performance, OptimaChain turns raw supply chain telemetry into actionable foresight—eliminating stockouts, reducing excess inventory, and unlocking institutional credit access for distribution-driven businesses across Africa.

---

## 🔗 Live Links & Repository

* **Live Platform:** [https://optimachain.hybriddefi.com](https://optimachain.hybriddefi.com)
* **GitHub Repository:** [https://github.com/thomasonyemechi/optima_chain](https://github.com/thomasonyemechi/optima_chain)

---

## 👥 Team Details

* **Team Name:** OptimaChain
* **Team ID:** Team 14
* **Team Members:**
  * **Olaleye Ayobami Joshua**
  * **Abayomi Joshua Olabamiji**
  * **Thomas Onyemechi Gideon (asst lead)**
  * **Eberechukwu Antoinette Uzuegbunam (lead)**

---

## 🔑 Demo Access Credentials

> **Universal Password for all accounts:** `OptimaDemo!2026`

| Role | Email Address |
| :--- | :--- |
| **Super Admin** | `demo.admin@optima-chain.test` |
| **Manager** | `manager@demo.com` |
| **Manager (Alt)** | `demo.manager@optima-chain.test` |
| **Logistics** | `logistics@demo.com` |
| **Logistics (Alt)** | `demo.logistics@optima-chain.test` |
| **Bank / Auditor** | `bank@demo.com` |

---

## ✨ Key Features

* **AI-Driven Demand Forecasting:** Utilizes XGBoost quantile regression coupled with real-time weather metrics (Open-Meteo) to dynamically compute Low ($L$), Expected ($E$), and High ($H$) demand bands for each distribution location.
* **Automated Demand Review Queue:** Automatically approves requests falling within safe parameters ($L \le R \le H$), flags anomalous spikes ($R > H$), and flags stockout risks ($R < L$).
* **Role-Based Workspaces:** Tailored dashboards and portals for Distributors, Managers, Logistics Teams, and Bank Auditors.
* **Fairness & Short-Supply Safeguards:** Incorporates company short-supply exemption logic to protect distributor credit scores during factory-wide stock outages.
* **Cryptographic Credit Scoring & Bank Verification:** Generates weighted, tamper-evident performance scorecards accessible via a public verification portal (`/verify/{code}`).
* **Discrepancy Tracking:** Automatically logs discrepancy tickets whenever delivered quantities ($D$) diverge from confirmed received quantities ($C$).

---

## 🛠️ Tech Stack & Architecture

OptimaChain uses a hybrid architecture pairing a robust PHP backend with a dedicated Python Machine Learning microservice.

| Layer | Technologies |
| :--- | :--- |
| **Frontend UI** | Laravel Blade, Livewire 3, Alpine.js, Tailwind CSS |
| **Backend Core** | Laravel 11 (PHP 8.2+), MySQL |
| **AI / ML Microservice** | Python 3.10+, FastAPI, XGBoost, Pandas, Scikit-Learn |
| **External APIs** | Open-Meteo Weather API |
| **Authentication & Roles** | Spatie Laravel-Permission |

---

## 🚀 Local Development Setup

Follow these steps to set up OptimaChain locally for development and testing.

### Prerequisites
* PHP $\ge$ 8.2 & Composer
* Python $\ge$ 3.10 & `pip`
* MySQL Database
* Node.js & npm (optional, if compiling custom Tailwind assets)

---

### 1. Backend Setup (Laravel)

```bash
# Clone the repository
git clone [https://github.com/thomasonyemechi/optima_chain.git](https://github.com/thomasonyemechi/optima_chain.git)
cd optima_chain

# Install PHP dependencies
composer install

# Environment configuration
cp .env.example .env
php artisan key:generate

# Configure your MySQL database details in .env:
# DB_DATABASE=optimachain
# DB_USERNAME=root
# DB_PASSWORD=

# Set Python AI Engine service endpoint in .env
echo "AI_FORECAST_SERVICE_URL=[http://127.0.0.1:8000](http://127.0.0.1:8000)" >> .env

# Run migrations and seed test accounts/data
php artisan migrate:fresh --seed

# Start the Laravel application
php artisan serve





#AI Engine Setup (Python FastAPI)
#Open a separate terminal window and run:


cd ai-engine

# Create and activate virtual environment
python -m venv venv

# On Mac/Linux:
source venv/bin/activate
# On Windows:
# venv\Scripts\activate

# Install required packages
pip install -r requirements.txt

# Train initial model artifact (generates demand_model.pkl)
python train.py

# Launch FastAPI microservice on port 8000
uvicorn main:app --reload --port 8000