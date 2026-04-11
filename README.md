# 📱 FinSight – Financial Behavior Insights App

## 🚀 Overview

FinSight is a mobile application designed to collect and analyze financial behavior data in real-world scenarios. The app enables financial institutions, researchers, and analysts to better understand user needs, identify financial risks, and generate actionable insights.

This project was developed as part of an academic initiative, with a strong focus on real-world applicability in the financial sector.

---

## 🎯 Problem Statement

Financial institutions often struggle to collect structured and reliable data about customer financial behavior, especially in field environments.

FinSight solves this problem by providing a simple and efficient tool for data collection and analysis, supporting better decision-making and personalized financial services.

---

## 🧩 Features

### 🔐 Authentication

* Predefined users (for demonstration purposes):

  * Admin
  * Agent (data collector)

---

### 📊 Data Collection (Agent Flow)

* Select financial preference:

  * Credit
  * Investment
  * Savings
  * Loan
  * Card

* Select up to 3 financial challenges:

  * Debt
  * Lack of financial control
  * Low income
  * No access to credit
  * Fraud risk
  * Financial illiteracy

* Register user data:

  * Name
  * Phone number
  * Automatic timestamp
  * Geolocation (latitude & longitude)

---

### 📈 Analytics Dashboard (Admin)

* Total number of responses
* Most common financial preference
* Most reported financial problems
* Basic financial risk classification:

  * Low Risk
  * Medium Risk
  * High Risk

---

### 👥 User Management

* View collected responses
* Access user data

---

### 🧹 Data Management

* Clear all collected data

---

## 🏗️ Architecture

The application follows a structured architecture based on:

* MVVM (Model-View-ViewModel)
* Clean Architecture principles

### Layers:

* **UI Layer** – Activities and user interaction
* **Domain Layer** – Business logic
* **Data Layer** – Local database (Room)

---

## 🛠️ Tech Stack

* Kotlin
* Android Studio
* Room Database (SQLite)
* MVVM Architecture
* Android Location Services

---

## 📍 Use Cases

* Financial behavior research
* Field data collection
* Customer profiling
* Risk analysis for financial services

---

## 🔮 Future Improvements

* Cloud database integration (Firebase / AWS)
* User authentication with real credentials
* Advanced analytics dashboard
* Machine learning-based risk scoring
* API integration for financial services

---

## 👩‍💻 Author

Developed by Michelle Kaolin

---

## 📄 License

This project is for educational purposes.
