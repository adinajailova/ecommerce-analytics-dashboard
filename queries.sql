-- Q1: which categories made the most money?
SELECT c.category_name_english AS category,
       ROUND(SUM(oi.price), 2)           AS total_revenue,
       COUNT(DISTINCT oi.order_id)        AS total_orders
FROM Category c
JOIN Product      p  ON c.category_id  = p.category_id
JOIN OrderItem    oi ON p.product_id   = oi.product_id
GROUP BY c.category_id, c.category_name_english
ORDER BY total_revenue DESC
LIMIT 10;

-- Q2: which sellers got the most orders?
SELECT oi.seller_id,
       s.seller_city,
       s.seller_state,
       COUNT(DISTINCT oi.order_id)   AS total_orders,
       ROUND(SUM(oi.price), 2)       AS total_revenue
FROM OrderItem oi
JOIN Seller s ON oi.seller_id = s.seller_id
GROUP BY oi.seller_id, s.seller_city, s.seller_state
ORDER BY total_orders DESC
LIMIT 10;

-- Q3: which sellers have the best average review score (at least 5 reviews)
SELECT oi.seller_id,
       s.seller_city,
       s.seller_state,
       ROUND(AVG(r.review_score), 2) AS avg_review_score,
       COUNT(r.review_id)            AS review_count
FROM OrderItem    oi
JOIN Seller        s  ON oi.seller_id  = s.seller_id
JOIN CustomerOrder co ON oi.order_id   = co.order_id
JOIN Review        r  ON co.order_id   = r.order_id
GROUP BY oi.seller_id, s.seller_city, s.seller_state
HAVING COUNT(r.review_id) >= 5
ORDER BY avg_review_score DESC
LIMIT 10;

-- Q4: which customers spent more than the average customer?
WITH customer_spending AS (
    SELECT co.customer_id,
           SUM(p.payment_value) AS total_spent
    FROM CustomerOrder co
    JOIN Payment p ON co.order_id = p.order_id
    GROUP BY co.customer_id
)
SELECT c.customer_id,
       c.customer_city,
       c.customer_state,
       ROUND(cs.total_spent, 2) AS total_spent
FROM customer_spending cs
JOIN Customer c ON cs.customer_id = c.customer_id
WHERE cs.total_spent > (SELECT AVG(total_spent) FROM customer_spending)
ORDER BY cs.total_spent DESC
LIMIT 10;

-- Q5: which products show up in orders the most?
SELECT oi.product_id,
       c.category_name_english        AS category,
       COUNT(*)                        AS times_ordered,
       ROUND(AVG(oi.price), 2)         AS avg_price
FROM OrderItem oi
JOIN Product  p ON oi.product_id  = p.product_id
JOIN Category c ON p.category_id  = c.category_id
GROUP BY oi.product_id, c.category_name_english
ORDER BY times_ordered DESC
LIMIT 10;

-- Q6: which categories have the highest average review score (at least 50 reviews)
SELECT c.category_name_english AS category,
       ROUND(AVG(r.review_score), 2) AS avg_score,
       COUNT(r.review_id)            AS review_count
FROM Category  c
JOIN Product      p  ON c.category_id = p.category_id
JOIN OrderItem    oi ON p.product_id  = oi.product_id
JOIN CustomerOrder co ON oi.order_id  = co.order_id
JOIN Review        r  ON co.order_id  = r.order_id
GROUP BY c.category_id, c.category_name_english
HAVING COUNT(r.review_id) >= 50
ORDER BY avg_score DESC
LIMIT 10;

-- Q7: which sellers sell in more than one category?
SELECT oi.seller_id,
       s.seller_city,
       s.seller_state,
       COUNT(DISTINCT p.category_id) AS num_categories
FROM OrderItem oi
JOIN Seller   s ON oi.seller_id  = s.seller_id
JOIN Product  p ON oi.product_id = p.product_id
GROUP BY oi.seller_id, s.seller_city, s.seller_state
HAVING COUNT(DISTINCT p.category_id) > 1
ORDER BY num_categories DESC
LIMIT 10;

-- Q8: which orders had more than 3 different products?
SELECT oi.order_id,
       co.order_status,
       co.order_purchase_timestamp,
       COUNT(DISTINCT oi.product_id) AS distinct_products,
       ROUND(SUM(oi.price), 2)       AS total_price
FROM OrderItem    oi
JOIN CustomerOrder co ON oi.order_id = co.order_id
GROUP BY oi.order_id, co.order_status, co.order_purchase_timestamp
HAVING COUNT(DISTINCT oi.product_id) > 3
ORDER BY distinct_products DESC;

-- Q9: which sellers deliver the fastest on average (at least 10 orders)
SELECT oi.seller_id,
       s.seller_city,
       s.seller_state,
       ROUND(AVG(DATEDIFF(co.order_delivered_customer_date,
                          co.order_purchase_timestamp)), 1) AS avg_delivery_days,
       COUNT(DISTINCT oi.order_id)                          AS orders_delivered
FROM OrderItem    oi
JOIN Seller        s  ON oi.seller_id = s.seller_id
JOIN CustomerOrder co ON oi.order_id  = co.order_id
WHERE co.order_delivered_customer_date IS NOT NULL
GROUP BY oi.seller_id, s.seller_city, s.seller_state
HAVING COUNT(DISTINCT oi.order_id) >= 10
ORDER BY avg_delivery_days
LIMIT 10;

-- Q10: how is each payment type used and what are the totals?
SELECT payment_type,
       COUNT(*)                        AS usage_count,
       ROUND(SUM(payment_value), 2)    AS total_amount,
       ROUND(AVG(payment_value), 2)    AS avg_amount
FROM Payment
GROUP BY payment_type
ORDER BY usage_count DESC;

-- Q11: which cities have the most customers?
SELECT customer_city,
       customer_state,
       COUNT(*) AS customer_count
FROM Customer
GROUP BY customer_city, customer_state
ORDER BY customer_count DESC
LIMIT 10;

-- Q12: which categories make a lot of money but have bad reviews?
-- (above average revenue but average score under 4.0)
WITH category_stats AS (
    SELECT c.category_id,
           c.category_name_english          AS category,
           ROUND(SUM(oi.price), 2)          AS total_revenue,
           ROUND(AVG(r.review_score), 2)    AS avg_review
    FROM Category  c
    JOIN Product      p  ON c.category_id = p.category_id
    JOIN OrderItem    oi ON p.product_id  = oi.product_id
    JOIN CustomerOrder co ON oi.order_id  = co.order_id
    LEFT JOIN Review   r  ON co.order_id  = r.order_id
    GROUP BY c.category_id, c.category_name_english
)
SELECT category,
       total_revenue,
       avg_review AS avg_review_score
FROM category_stats
WHERE total_revenue > (SELECT AVG(total_revenue) FROM category_stats)
  AND avg_review < 4.0
ORDER BY total_revenue DESC;

-- Q13: which sellers make above average money but have below average reviews?
WITH seller_stats AS (
    SELECT oi.seller_id,
           ROUND(SUM(oi.price), 2)       AS total_revenue,
           ROUND(AVG(r.review_score), 2) AS avg_review
    FROM OrderItem    oi
    JOIN CustomerOrder co ON oi.order_id = co.order_id
    LEFT JOIN Review   r  ON co.order_id = r.order_id
    GROUP BY oi.seller_id
)
SELECT seller_id,
       total_revenue,
       avg_review AS avg_review_score
FROM seller_stats
WHERE total_revenue > (SELECT AVG(total_revenue) FROM seller_stats)
  AND avg_review   < (SELECT AVG(avg_review) FROM seller_stats
                      WHERE avg_review IS NOT NULL)
ORDER BY total_revenue DESC
LIMIT 10;

-- Q14: which customers bought from more than 5 different sellers?
SELECT co.customer_id,
       c.customer_city,
       c.customer_state,
       COUNT(DISTINCT oi.seller_id) AS num_sellers
FROM CustomerOrder co
JOIN Customer  c  ON co.customer_id = c.customer_id
JOIN OrderItem oi ON co.order_id    = oi.order_id
GROUP BY co.customer_id, c.customer_city, c.customer_state
HAVING COUNT(DISTINCT oi.seller_id) > 5
ORDER BY num_sellers DESC
LIMIT 10;

-- Q15: which sellers had more late deliveries than the marketplace average?
-- (only looking at sellers with at least 10 orders)
WITH seller_delay AS (
    SELECT oi.seller_id,
           COUNT(DISTINCT oi.order_id) AS total_orders,
           SUM(CASE
               WHEN co.order_delivered_customer_date > co.order_estimated_delivery_date
               THEN 1 ELSE 0
           END)                         AS delayed_orders,
           100.0 * SUM(CASE
               WHEN co.order_delivered_customer_date > co.order_estimated_delivery_date
               THEN 1 ELSE 0
           END) / COUNT(DISTINCT oi.order_id) AS pct_delayed
    FROM OrderItem    oi
    JOIN CustomerOrder co ON oi.order_id = co.order_id
    WHERE co.order_delivered_customer_date IS NOT NULL
      AND co.order_estimated_delivery_date IS NOT NULL
    GROUP BY oi.seller_id
    HAVING COUNT(DISTINCT oi.order_id) >= 10
),
market_avg AS (
    SELECT AVG(pct_delayed) AS avg_pct_delayed FROM seller_delay
)
SELECT sd.seller_id,
       sd.total_orders,
       sd.delayed_orders,
       ROUND(sd.pct_delayed, 1)          AS pct_delayed,
       ROUND(ma.avg_pct_delayed, 1)      AS market_avg_pct
FROM seller_delay sd
CROSS JOIN market_avg ma
WHERE sd.pct_delayed > ma.avg_pct_delayed
ORDER BY sd.pct_delayed DESC
LIMIT 10;
