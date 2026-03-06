&nbsp;

<div align="center">

# 🏨 Hotel Search Aggregator API

**A CodeIgniter 4 backend service that aggregates hotel search data from multiple suppliers into a unified, normalized response.**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7-EF4223?style=for-the-badge&logo=codeigniter&logoColor=white)](https://codeigniter.com/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![API](https://img.shields.io/badge/API-REST-blue?style=for-the-badge)](docs/)

</div>

---

## 📑 Table of Contents

- [Overview](#-overview)
- [Architecture](#-architecture)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Environment Configuration](#environment-configuration)
  - [Running the Services](#running-the-services)
- [API Reference](#-api-reference)
  - [Search Hotels](#search-hotels)
  - [Query Parameters](#query-parameters)
  - [Response Schema](#response-schema)
- [Data Normalization](#-data-normalization)
- [Caching Strategy](#-caching-strategy)
- [Rate Limiting](#-rate-limiting)
- [Structured Logging](#-structured-logging)
- [Error Handling & Resilience](#-error-handling--resilience)
- [Simulating Supplier Failures](#-simulating-supplier-failures)
- [Example Requests](#-example-requests)
- [Frontend UI](#-frontend-ui)
- [Testing](#-testing)
- [Design Decisions](#-design-decisions)
- [Evaluation Criteria Mapping](#-evaluation-criteria-mapping)

---

## 🔍 Overview

This project implements a **Hotel Search Aggregation Service** — a backend API that collects hotel availability data from two independent mock suppliers, each returning data in a different format, and produces a single, normalized, filterable, and sortable response.

**Key Capabilities:**

| Capability | Implementation |
|---|---|
| Multi-supplier aggregation | Parallel HTTP calls to Supplier A & B |
| Data normalization | Unified schema from heterogeneous sources |
| Server-side filtering | City, price range, rating threshold |
| Server-side sorting | Price and rating (ascending/descending) |
| Caching | File-based, 60-second TTL, MD5-keyed |
| Rate limiting | 10 requests / 60 seconds per IP |
| Partial responses | Graceful degradation on supplier failure |
| Structured logging | JSON-formatted supplier call telemetry |
| Failure simulation | Configurable slow responses & HTTP 500 errors |

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                          CLIENT                                     │
│                  (Browser / curl / Postman)                          │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           │  GET /api/hotels/search?city=Dubai&sort=price_asc
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   HOTEL AGGREGATOR (port 8080)                      │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │                    SearchController                            │ │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐  │ │
│  │  │  Rate    │→ │  Cache   │→ │ Supplier │→ │  Normalize   │  │ │
│  │  │ Limiter  │  │  Check   │  │  Calls   │  │  + Merge     │  │ │
│  │  │(Throttle)│  │ (File)   │  │ (HTTP)   │  │  + Filter    │  │ │
│  │  │ 10/60s   │  │ TTL:60s  │  │ T/O: 3s  │  │  + Sort      │  │ │
│  │  └──────────┘  └──────────┘  └─────┬────┘  └──────────────┘  │ │
│  └────────────────────────────────────┼──────────────────────────┘ │
│                                       │                             │
│  ┌────────────────┐    ┌──────────────┴──────────────┐              │
│  │ LoggerHelper   │    │                             │              │
│  │ (Structured    │    │    Supplier Client Layer     │              │
│  │  JSON Logs)    │    │                             │              │
│  └────────────────┘    └──────┬──────────────┬──────┘              │
│                               │              │                      │
│  ┌────────────────┐    ┌──────┴─────┐  ┌─────┴──────┐              │
│  │ HotelNormalizer│    │SupplierA   │  │SupplierB   │              │
│  │ (Schema Map)   │    │Client      │  │Client      │              │
│  └────────────────┘    └──────┬─────┘  └─────┬──────┘              │
└───────────────────────────────┼───────────────┼─────────────────────┘
                                │               │
                    ┌───────────▼───┐   ┌───────▼───────────┐
                    │ SUPPLIER A    │   │ SUPPLIER B        │
                    │ (port 8081)   │   │ (port 8082)       │
                    │               │   │                   │
                    │ Format:       │   │ Format:           │
                    │ {hotels:[{    │   │ {results:[{       │
                    │   id,         │   │   hotelId,        │
                    │   hotel_name, │   │   name,           │
                    │   city,       │   │   location,       │
                    │   price_usd,  │   │   cost,           │
                    │   stars       │   │   rating           │
                    │ }]}           │   │ }]}               │
                    │               │   │                   │
                    │ No failure    │   │ Supports:         │
                    │ simulation    │   │  ?slow=1 (5s)     │
                    │               │   │  ?fail=1 (500)    │
                    └───────────────┘   └───────────────────┘
```

---

## 🛠 Tech Stack

| Component | Technology |
|---|---|
| Language | PHP 8.2+ |
| Framework | CodeIgniter 4.7 |
| HTTP Client | CI4 CURLRequest (Guzzle-compatible) |
| Caching | File-based (`writable/cache/`) |
| Rate Limiting | CI4 Throttler (built-in) |
| Logging | CI4 Logger + custom structured JSON |
| Testing | PHPUnit |
| Database | None required |

---

## 📁 Project Structure

The solution is composed of **three independent CodeIgniter 4 applications**:

```
project-root/
│
├── hotel-aggregator/          # 🏨 Main Aggregator Service (port 8080)
│   ├── app/
│   │   ├── Controllers/
│   │   │   └── SearchController.php       # REST endpoint + rate limiting + caching
│   │   ├── Suppliers/
│   │   │   ├── SupplierAClient.php        # HTTP client for Supplier A
│   │   │   └── SupplierBClient.php        # HTTP client for Supplier B
│   │   ├── Normalizers/
│   │   │   └── HotelNormalizer.php        # Schema normalization layer
│   │   ├── Libraries/
│   │   │   └── LoggerHelper.php           # Structured logging utility
│   │   └── Config/
│   │       ├── Routes.php                 # API route definitions
│   │       └── Cache.php                  # Cache configuration
│   ├── public/
│   │   ├── search.html                    # Minimal frontend UI
│   │   ├── scripts/main.js                # Frontend logic
│   │   └── styles/main.css                # Frontend styles
│   ├── tests/
│   │   └── unit/
│   │       └── HealthTest.php             # Unit tests
│   └── writable/
│       ├── cache/                         # Cached search results
│       └── logs/                          # Structured log files
│
├── supplierA-api/             # 🅰️ Mock Supplier A (port 8081)
│   └── app/Controllers/
│       └── MockSupplierAController.php    # Returns Supplier A format data
│
└── supplierB-api/             # 🅱️ Mock Supplier B (port 8082)
    └── app/Controllers/
        └── MockSupplierBController.php    # Returns Supplier B format + failure sim
```

---

## 🚀 Getting Started

### Prerequisites

- **PHP 8.2** or higher
- **Composer** (PHP dependency manager)
- **php-curl** extension enabled
- **php-intl** extension enabled (required by CodeIgniter 4)
- **php-json** extension enabled

Verify your PHP installation:

```bash
php -v          # Should show PHP 8.2+
php -m | grep curl
php -m | grep intl
composer -V
```

### Installation

**1. Clone the repository:**

```bash
git clone https://github.com/<your-username>/hotel-search-aggregator.git
cd hotel-search-aggregator
```

**2. Install dependencies for all three services:**

```bash
# Main Aggregator
cd hotel-aggregator
composer install
cd ..

# Supplier A
cd supplierA-api
composer install
cd ..

# Supplier B
cd supplierB-api
composer install
cd ..
```

### Environment Configuration

Each service requires its own `.env` file. Copy the provided template and configure:

**hotel-aggregator:**

```bash
cd hotel-aggregator
cp env .env
```

Edit `.env` and set:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'
```

**supplierA-api:**

```bash
cd supplierA-api
cp env .env
```

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8081/'
```

**supplierB-api:**

```bash
cd supplierB-api
cp env .env
```

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8082/'
```

### Running the Services

Open **three separate terminal windows** and start each service:

```bash
# Terminal 1 — Supplier A (must start first)
cd supplierA-api
php spark serve --port 8081
```

```bash
# Terminal 2 — Supplier B (must start first)
cd supplierB-api
php spark serve --port 8082
```

```bash
# Terminal 3 — Main Aggregator
cd hotel-aggregator
php spark serve --port 8080
```

> **Note:** Both supplier services must be running before the aggregator can fetch data. The aggregator gracefully handles supplier unavailability (see [Error Handling](#-error-handling--resilience)).

**Verify all services are running:**

```bash
# Test Supplier A directly
curl -s http://localhost:8081/mock/supplierA/search | jq

# Test Supplier B directly
curl -s http://localhost:8082/mock/supplierB/search | jq

# Test Aggregator
curl -s http://localhost:8080/api/hotels/search | jq
```

---

## 📡 API Reference

### Search Hotels

```
GET /api/hotels/search
```

Returns aggregated, normalized hotel search results from all configured suppliers.

### Query Parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `city` | `string` | No | — | Filter results by city name (case-insensitive match) |
| `min_price` | `number` | No | — | Minimum price threshold (inclusive) |
| `max_price` | `number` | No | — | Maximum price threshold (inclusive) |
| `min_rating` | `number` | No | — | Minimum rating threshold, range 0–5 (inclusive) |
| `sort` | `string` | No | — | Sort order: `price_asc`, `price_desc`, `rating_asc`, `rating_desc` |
| `slow` | `1` | No | — | *Passed to Supplier B* — simulates slow response |
| `fail` | `1` | No | — | *Passed to Supplier B* — simulates HTTP 500 error |

### Response Schema

**Success Response (HTTP 200):**

```json
{
  "results": [
    {
      "id": "A1",
      "name": "Grand Palace",
      "city": "Dubai",
      "price": 180,
      "rating": 4.5,
      "supplier": "supplierA"
    },
    {
      "id": "B9",
      "name": "Royal Atlantis",
      "city": "Dubai",
      "price": 210,
      "rating": 4.7,
      "supplier": "supplierB"
    }
  ],
  "cached": false
}
```

**Rate Limited Response (HTTP 429):**

```json
{
  "error": "Rate limit exceeded. Try again later."
}
```

| Field | Type | Description |
|---|---|---|
| `results` | `array` | Array of normalized hotel objects |
| `results[].id` | `string` | Unique hotel identifier (prefixed by supplier) |
| `results[].name` | `string` | Hotel display name |
| `results[].city` | `string` | City / location |
| `results[].price` | `number` | Price in USD |
| `results[].rating` | `number` | Rating score (0–5 scale) |
| `results[].supplier` | `string` | Source supplier identifier (`supplierA` or `supplierB`) |
| `cached` | `boolean` | Whether the response was served from cache |

---

## 🔄 Data Normalization

The two suppliers return data in **different schemas**. The `HotelNormalizer` transforms both into a unified format:

### Supplier A → Normalized

| Supplier A Field | Normalized Field |
|---|---|
| `id` | `id` |
| `hotel_name` | `name` |
| `city` | `city` |
| `price_usd` | `price` |
| `stars` | `rating` |
| — | `supplier` → `"supplierA"` |

**Supplier A raw response:**

```json
{
  "hotels": [
    {
      "id": "A1",
      "hotel_name": "Grand Palace",
      "city": "Dubai",
      "price_usd": 180,
      "stars": 4.5
    },
    {
      "id": "A2",
      "hotel_name": "Sea View Resort",
      "city": "Dubai",
      "price_usd": 220,
      "stars": 5
    }
  ]
}
```

### Supplier B → Normalized

| Supplier B Field | Normalized Field |
|---|---|
| `hotelId` | `id` |
| `name` | `name` |
| `location` | `city` |
| `cost` | `price` |
| `rating` | `rating` |
| — | `supplier` → `"supplierB"` |

**Supplier B raw response:**

```json
{
  "results": [
    {
      "hotelId": "B9",
      "name": "Royal Atlantis",
      "location": "Dubai",
      "cost": 210,
      "rating": 4.7
    },
    {
      "hotelId": "B10",
      "name": "Palm Resort",
      "location": "Dubai",
      "cost": 150,
      "rating": 4.2
    }
  ]
}
```

---

## 💾 Caching Strategy

| Property | Value |
|---|---|
| **Backend** | File-based (`writable/cache/`) |
| **Key Generation** | `hotel_search_` + MD5 hash of sorted query parameters |
| **TTL** | 60 seconds |
| **Cache Indicator** | `"cached": true/false` in every response |
| **Invalidation** | Automatic expiry after TTL |

**How it works:**

1. Incoming request parameters are serialized and hashed (MD5).
2. If a cached entry exists and is within TTL, it is returned immediately with `"cached": true`.
3. Otherwise, suppliers are queried, results are normalized/merged, stored in cache, and returned with `"cached": false`.

**Verify caching behavior:**

```bash
# First request — cache miss
curl -s "http://localhost:8080/api/hotels/search?city=Dubai" | jq '.cached'
# Output: false

# Immediate second request — cache hit
curl -s "http://localhost:8080/api/hotels/search?city=Dubai" | jq '.cached'
# Output: true

# Wait 60+ seconds, then request again — cache miss
sleep 61
curl -s "http://localhost:8080/api/hotels/search?city=Dubai" | jq '.cached'
# Output: false
```

---

## 🚦 Rate Limiting

| Property | Value |
|---|---|
| **Mechanism** | CI4 built-in Throttler service |
| **Limit** | 10 requests per 60 seconds |
| **Scope** | Per IP address |
| **HTTP Status** | `429 Too Many Requests` |

**Test rate limiting:**

```bash
# Send 11 rapid requests
for i in $(seq 1 11); do
  echo "Request $i: $(curl -s -o /dev/null -w '%{http_code}' http://localhost:8080/api/hotels/search)"
done

# Expected: Requests 1-10 return 200, Request 11 returns 429
```

---

## 📋 Structured Logging

Every supplier HTTP call is logged to `writable/logs/log-YYYY-MM-DD.log` with structured JSON telemetry:

```
INFO - 2026-03-06 12:00:00 --> Supplier call:
{
  "supplier": "supplierA",
  "url": "http://localhost:8081/mock/supplierA/search",
  "params": {
    "city": "Dubai"
  },
  "duration_ms": 2.59,
  "response_size": 1,
  "error": null
}
```

**Logged fields:**

| Field | Description |
|---|---|
| `supplier` | Identifier of the supplier called |
| `url` | Full URL of the supplier endpoint |
| `params` | Query parameters forwarded to the supplier |
| `duration_ms` | Round-trip time in milliseconds |
| `response_size` | Number of hotel records returned |
| `error` | Error message (or `null` on success) |

**View logs in real-time:**

```bash
tail -f hotel-aggregator/writable/logs/log-$(date +%Y-%m-%d).log
```

---

## 🛡 Error Handling & Resilience

The aggregator is designed for **graceful degradation**:

| Scenario | Behavior |
|---|---|
| Both suppliers respond | Full merged results returned |
| Supplier A fails | Supplier B results returned alone |
| Supplier B fails | Supplier A results returned alone |
| Both suppliers fail | Empty `results` array returned (HTTP 200) |
| Supplier timeout (>3s) | Treated as failure, other supplier results returned |
| Invalid query params | Handled gracefully, no server crash |
| Rate limit exceeded | HTTP 429 with error message |

**Implementation details:**

- Each supplier call is wrapped in an independent `try/catch` block.
- HTTP timeout is set to **3 seconds** per supplier via CURLRequest configuration.
- Failed supplier calls are logged with error details for debugging.
- The API always returns a valid JSON response regardless of supplier health.

---

## 💥 Simulating Supplier Failures

Supplier B supports two failure simulation modes via query parameters. Since the aggregator forwards all query parameters to suppliers, you can trigger these from the main endpoint:

### Simulate Slow Response (Timeout)

Supplier B sleeps for **5 seconds**, exceeding the aggregator's **3-second timeout**:

```bash
# Via Aggregator — Supplier B will timeout, only Supplier A results returned
curl -s "http://localhost:8080/api/hotels/search?slow=1" | jq

# Direct Supplier B test (will take ~5 seconds)
curl -s "http://localhost:8082/mock/supplierB/search?slow=1"
```

**Expected result:** Only Supplier A hotels appear in the response.

### Simulate Server Error (HTTP 500)

Supplier B returns an HTTP 500 Internal Server Error:

```bash
# Via Aggregator — Supplier B returns 500, only Supplier A results returned
curl -s "http://localhost:8080/api/hotels/search?fail=1" | jq

# Direct Supplier B test
curl -s -w "\nHTTP Status: %{http_code}\n" "http://localhost:8082/mock/supplierB/search?fail=1"
```

**Expected result:** Only Supplier A hotels appear in the response.

### Simulate Both Suppliers Failing

Stop the Supplier A service and trigger Supplier B failure:

```bash
# Stop Supplier A (Ctrl+C in Terminal 1), then:
curl -s "http://localhost:8080/api/hotels/search?fail=1" | jq

# Expected: {"results": [], "cached": false}
```

---

## 📝 Example Requests

### Basic Search (All Hotels)

```bash
curl -s http://localhost:8080/api/hotels/search | jq
```

### Filter by City

```bash
curl -s "http://localhost:8080/api/hotels/search?city=Dubai" | jq
```

### Filter by Price Range

```bash
curl -s "http://localhost:8080/api/hotels/search?min_price=100&max_price=200" | jq
```

### Filter by Minimum Rating

```bash
curl -s "http://localhost:8080/api/hotels/search?min_rating=4.5" | jq
```

### Sort by Price (Ascending)

```bash
curl -s "http://localhost:8080/api/hotels/search?sort=price_asc" | jq
```

### Sort by Rating (Descending)

```bash
curl -s "http://localhost:8080/api/hotels/search?sort=rating_desc" | jq
```

### Combined Filters + Sort

```bash
curl -s "http://localhost:8080/api/hotels/search?city=Dubai&min_price=150&max_price=250&min_rating=4.0&sort=price_asc" | jq
```

### Postman Collection

You can import these endpoints into Postman:

| Method | URL | Description |
|---|---|---|
| `GET` | `http://localhost:8080/api/hotels/search` | All hotels |
| `GET` | `http://localhost:8080/api/hotels/search?city=Dubai` | Filter by city |
| `GET` | `http://localhost:8080/api/hotels/search?sort=price_asc` | Sort by price |
| `GET` | `http://localhost:8080/api/hotels/search?slow=1` | Simulate timeout |
| `GET` | `http://localhost:8080/api/hotels/search?fail=1` | Simulate failure |

---

## 🖥 Frontend UI

A minimal web interface is available for interactive testing:

```
http://localhost:8080/search.html
```

The frontend provides:
- City search input field
- Price range filters (min/max)
- Rating filter
- Sort dropdown
- Results displayed in a clean card layout

> **Note:** The frontend is intentionally minimal — this project focuses on backend engineering quality.

---

## 🧪 Testing

### Running Unit Tests

```bash
cd hotel-aggregator
php vendor/bin/phpunit
```

### Manual Integration Testing Checklist

| # | Test Case | Command | Expected |
|---|---|---|---|
| 1 | Basic aggregation | `curl -s localhost:8080/api/hotels/search \| jq '.results \| length'` | `4` (2 from each supplier) |
| 2 | City filter | `curl -s "localhost:8080/api/hotels/search?city=Dubai" \| jq` | All results have `city: "Dubai"` |
| 3 | Price filter | `curl -s "localhost:8080/api/hotels/search?min_price=200" \| jq` | All results have `price >= 200` |
| 4 | Sort ascending | `curl -s "localhost:8080/api/hotels/search?sort=price_asc" \| jq '[.results[].price]'` | Prices in ascending order |
| 5 | Cache hit | Run same query twice quickly | Second response has `"cached": true` |
| 6 | Rate limit | Send 11 requests rapidly | 11th request returns HTTP 429 |
| 7 | Supplier timeout | `curl -s "localhost:8080/api/hotels/search?slow=1" \| jq` | Only Supplier A results |
| 8 | Supplier failure | `curl -s "localhost:8080/api/hotels/search?fail=1" \| jq` | Only Supplier A results |

---

## 🧠 Design Decisions

| Decision | Rationale |
|---|---|
| **Three separate CI4 apps** | Simulates real-world microservice architecture where suppliers are independent external services |
| **File-based caching** | Zero infrastructure dependency; sufficient for demonstration; easily replaceable with Redis in production |
| **MD5 cache key** | Deterministic, fast hash of query parameters ensures identical queries hit the same cache entry |
| **3-second supplier timeout** | Balances user experience with supplier response tolerance; prevents cascading delays |
| **CI4 Throttler** | Leverages framework-native rate limiting without external dependencies |
| **Query params forwarded to suppliers** | Enables failure simulation through the aggregator endpoint without separate tooling |
| **No database** | Keeps the focus on aggregation logic; all data is supplier-sourced |
| **Structured JSON logging** | Production-ready observability pattern; easily parseable by log aggregation tools (ELK, Datadog, etc.) |

---

## ✅ Evaluation Criteria Mapping

| Criteria | Implementation |
|---|---|
| **Correctness of aggregation and normalization** | `HotelNormalizer` maps both supplier schemas to a unified format; `SearchController` merges arrays |
| **Code structure and maintainability** | Clean separation: Controllers → Suppliers → Normalizers → Libraries. Single Responsibility Principle throughout |
| **Error handling and resilience** | Independent try/catch per supplier, 3s timeout, partial response support, structured error logging |
| **Caching and performance** | File-based cache with 60s TTL, MD5-keyed, cache indicator in response |
| **Overall backend engineering maturity** | Rate limiting, structured logging, request validation, mock supplier simulation, minimal frontend, comprehensive documentation |

---

## 📄 License

This project was developed as a technical assessment submission.

---

<div align="center">

**Built with ❤️ using CodeIgniter 4**

</div>
