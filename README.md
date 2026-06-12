Interactive business intelligence dashboard analyzing 99,441 orders from Brazilian marketplace using MySQL and Metabase.

## Key Insights

- Revenue Analysis: Identified top-performing product categories generating R$13.5M in total revenue
- Payment Trends: 74% of customers prefer credit card payments (103,886 transactions)
- Operational Issues: Detected sellers with 50-88% delay rates (6-10x worse than 8% market average)
- Performance Benchmarks: Top sellers deliver in 3.9-4.8 days vs marketplace average

## Dashboard Preview

![Dashboard Screenshot](dashboard_screenshot.png)

## Tech Stack

- Database: MySQL 9.6
- Visualization: Metabase (Docker)
- Data Processing: Python (pandas)
- Version Control: Git/GitHub

## Database Schema

8 normalized tables (3NF) with referential integrity:
- Customer (99,441 records) - buyer information and location
- Seller (3,095 records) - merchant profiles
- Category (73 records) - product classifications
- Product (32,951 records) - catalog with dimensions/weights
- CustomerOrder (99,441 records) - order lifecycle timestamps
- OrderItem (112,650 records) - line items with pricing
- Payment (103,886 records) - transaction details
- Review (99,224 records) - customer ratings and comments

See [ER Diagram](er_diagram.pdf) for complete schema with foreign keys.

## Dashboard Visualizations

### 1. Top Revenue Categories
Type: Horizontal bar chart  
Insight: Health & beauty leads with R$1.26M, followed by watches/gifts (R$1.21M)

### 2. Payment Type Distribution  
Type: Pie chart  
Insight: Credit card dominates at 73.92%, boleto 19.04%, voucher 5.56%

### 3. Delayed Sellers Analysis
Type: Table with comparative metrics  
Insight: Identifies problematic sellers with 33-88% delay rates vs 7.9% market baseline  
Business value: Enables targeted seller performance interventions

### 4. Fastest Delivery Sellers
Type: Ranked table  
Insight: Top 10 sellers average 3.9-4.9 day delivery  
Business value: Benchmarks for operational excellence

## Quick Start

### Prerequisites

Install Docker Desktop for Mac and MySQL 9.6 via Homebrew:

brew install mysql

### Setup Database

Start MySQL:

mysql.server start

Create database:

mysql -u root -p
CREATE DATABASE olist;
exit;

Load schema and data:

mysql -u root -p olist < setup.sql
mysql --local-infile=1 -u root -p olist < load.sql

### Launch Metabase

Start Metabase container:

docker run -d -p 3000:3000 --name metabase metabase/metabase

Open browser to http://localhost:3000

Connect to MySQL using:
- Host: host.docker.internal
- Port: 3306
- Database: olist
- Username: root

### Run Analytical Queries

Execute sample queries:

mysql -u root -p olist < queries.sql

## Advanced SQL Features

The project demonstrates proficiency in:

- Multi-table JOINs: 5-way joins across Category → Product → OrderItem → Order → Review
- Common Table Expressions (CTEs): Used in 4 queries for marketplace benchmarking
- Window Functions: Comparative analysis (above/below average metrics)
- Conditional Aggregation: CASE WHEN for delay rate calculations
- Date Arithmetic: DATEDIFF() for delivery time analysis
- Subqueries: Nested and correlated subqueries for filtering
- Aggregate Functions: SUM, AVG, COUNT, COUNT DISTINCT
- Post-Aggregation Filtering: HAVING clauses with thresholds

Example query - Delayed Sellers vs Market Average:

WITH marketplace_avg AS (
    SELECT COUNT(CASE WHEN order_delivered_customer_date > order_estimated_delivery_date 
                      THEN 1 END) * 100.0 / COUNT(*) AS avg_delay_rate
    FROM CustomerOrder
    WHERE order_delivered_customer_date IS NOT NULL
),
seller_delays AS (
    SELECT s.seller_id, COUNT(DISTINCT co.order_id) AS total_orders,
           COUNT(CASE WHEN co.order_delivered_customer_date > co.order_estimated_delivery_date 
                      THEN 1 END) * 100.0 / COUNT(*) AS delay_rate
    FROM Seller s
    JOIN OrderItem oi ON s.seller_id = oi.seller_id
    JOIN CustomerOrder co ON oi.order_id = co.order_id
    WHERE co.order_delivered_customer_date IS NOT NULL
    GROUP BY s.seller_id
    HAVING COUNT(DISTINCT co.order_id) >= 10
)
SELECT seller_id, total_orders, delay_rate, 
       (SELECT avg_delay_rate FROM marketplace_avg) AS market_avg
FROM seller_delays
WHERE delay_rate > (SELECT avg_delay_rate FROM marketplace_avg)
ORDER BY delay_rate DESC;

## Data Source

Brazilian E-Commerce Public Dataset by Olist
- 100K orders from 2016-2018
- Real marketplace transaction data
- 8 interconnected CSV files
- Preprocessed with Python/pandas for data quality

## Sample Insights

| Metric | Value | Context |
|--------|-------|---------|
| Total Revenue | R$ 13.5M | Across 73 product categories |
| Avg Order Value | R$ 136 | Per transaction |
| Top Category Share | 9.3% | Health & beauty dominance |
| Credit Card Usage | 73.9% | Payment preference |
| Worst Seller Delay | 88.5% | vs 7.9% market avg |
| Best Delivery Time | 3.9 days | Top performer benchmark |

## Project Structure

ecommerce-analytics-dashboard/
├── setup.sql                    # DDL - create 8 tables with constraints
├── load.sql                     # Bulk load cleaned CSV data
├── cleanup.sql                  # Drop all tables
├── queries.sql                  # 15 analytical queries
├── preprocess_data.py           # Python ETL pipeline
├── er_diagram.pdf               # Database schema diagram
├── dashboard_screenshot.png     # Metabase dashboard
├── index.html                   # Web query interface (optional)
├── search.php                   # PHP backend (optional)
├── *_clean.csv                  # Preprocessed data files
└── README.md                    # This file

## Business Applications

This analytics framework supports:

1. Category Management: Identify high-revenue categories for inventory investment
2. Seller Performance: Flag underperforming sellers for training/offboarding
3. Payment Optimization: Optimize checkout flow based on payment preferences
4. Logistics Planning: Benchmark delivery times for SLA setting
5. Customer Segmentation: Analyze spending patterns by geography