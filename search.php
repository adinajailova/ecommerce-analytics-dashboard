<?php
// search.php - runs queries and shows results

$DB_HOST = '127.0.0.1';
$DB_USER = 'olistuser';
$DB_PASS = 'olistpass';
$DB_NAME = 'olist';

$type = isset($_GET['type']) ? trim($_GET['type']) : '';

// helper functions to safely read GET params
function get_int($key, $default) {
    $v = isset($_GET[$key]) ? intval($_GET[$key]) : $default;
    return $v > 0 ? $v : $default;
}
function get_float($key, $default) {
    $v = isset($_GET[$key]) ? floatval($_GET[$key]) : $default;
    return ($v > 0 && $v <= 5) ? $v : $default;
}
function get_str($key) {
    return isset($_GET[$key]) ? trim($_GET[$key]) : '';
}

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$title       = '';
$description = '';
$result      = null;
$error       = '';

switch ($type) {


    case 'top_categories':
        $limit = get_int('limit', 10);
        $title = 'Top Revenue Categories';
        $description = "Top $limit product categories ranked by total sales revenue.";
        $sql = "SELECT c.category_name_english AS Category,
                       ROUND(SUM(oi.price), 2)        AS `Total Revenue (R$)`,
                       COUNT(DISTINCT oi.order_id)    AS `Total Orders`
                FROM Category c
                JOIN Product      p  ON c.category_id  = p.category_id
                JOIN OrderItem    oi ON p.product_id   = oi.product_id
                GROUP BY c.category_id, c.category_name_english
                ORDER BY SUM(oi.price) DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'top_sellers':
        $limit = get_int('limit', 10);
        $state = get_str('state');
        $title = 'Top Sellers by Order Count';
        $description = $state
            ? "Top $limit sellers in state '$state' by number of orders fulfilled."
            : "Top $limit sellers by number of orders fulfilled.";
        if ($state !== '') {
            $sql = "SELECT oi.seller_id AS `Seller ID`,
                           s.seller_city AS City,
                           s.seller_state AS State,
                           COUNT(DISTINCT oi.order_id) AS `Total Orders`,
                           ROUND(SUM(oi.price), 2)     AS `Revenue (R$)`
                    FROM OrderItem oi
                    JOIN Seller s ON oi.seller_id = s.seller_id
                    WHERE s.seller_state = ?
                    GROUP BY oi.seller_id, s.seller_city, s.seller_state
                    ORDER BY COUNT(DISTINCT oi.order_id) DESC
                    LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $state, $limit);
        } else {
            $sql = "SELECT oi.seller_id AS `Seller ID`,
                           s.seller_city AS City,
                           s.seller_state AS State,
                           COUNT(DISTINCT oi.order_id) AS `Total Orders`,
                           ROUND(SUM(oi.price), 2)     AS `Revenue (R$)`
                    FROM OrderItem oi
                    JOIN Seller s ON oi.seller_id = s.seller_id
                    GROUP BY oi.seller_id, s.seller_city, s.seller_state
                    ORDER BY COUNT(DISTINCT oi.order_id) DESC
                    LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'seller_reviews':
        $min_reviews = get_int('min_reviews', 5);
        $limit       = get_int('limit', 10);
        $title = 'Top-Rated Sellers by Average Review Score';
        $description = "Top $limit sellers with at least $min_reviews reviews, ranked by average review score.";
        $sql = "SELECT oi.seller_id AS `Seller ID`,
                       s.seller_city AS City,
                       s.seller_state AS State,
                       ROUND(AVG(r.review_score), 2) AS `Avg Review Score`,
                       COUNT(r.review_id)            AS `Review Count`
                FROM OrderItem    oi
                JOIN Seller        s  ON oi.seller_id  = s.seller_id
                JOIN CustomerOrder co ON oi.order_id   = co.order_id
                JOIN Review        r  ON co.order_id   = r.order_id
                GROUP BY oi.seller_id, s.seller_city, s.seller_state
                HAVING COUNT(r.review_id) >= ?
                ORDER BY AVG(r.review_score) DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $min_reviews, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'high_spenders':
        $limit = get_int('limit', 10);
        $title = 'High-Spending Customers';
        $description = "Top $limit customers who spent more than the average customer spending amount.";
        $sql = "WITH customer_spending AS (
                    SELECT co.customer_id, SUM(p.payment_value) AS total_spent
                    FROM CustomerOrder co
                    JOIN Payment p ON co.order_id = p.order_id
                    GROUP BY co.customer_id
                )
                SELECT c.customer_id AS `Customer ID`,
                       c.customer_city AS City,
                       c.customer_state AS State,
                       ROUND(cs.total_spent, 2) AS `Total Spent (R$)`
                FROM customer_spending cs
                JOIN Customer c ON cs.customer_id = c.customer_id
                WHERE cs.total_spent > (SELECT AVG(total_spent) FROM customer_spending)
                ORDER BY cs.total_spent DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'top_products':
        $limit    = get_int('limit', 10);
        $category = get_str('category');
        $title = 'Most Ordered Products';
        $description = $category
            ? "Top $limit products matching category '$category', ranked by order frequency."
            : "Top $limit products ranked by order frequency across all categories.";
        if ($category !== '') {
            $like = '%' . $category . '%';
            $sql = "SELECT oi.product_id AS `Product ID`,
                           c.category_name_english AS Category,
                           COUNT(*)               AS `Times Ordered`,
                           ROUND(AVG(oi.price), 2) AS `Avg Price (R$)`
                    FROM OrderItem oi
                    JOIN Product  p ON oi.product_id = p.product_id
                    JOIN Category c ON p.category_id = c.category_id
                    WHERE c.category_name_english LIKE ?
                    GROUP BY oi.product_id, c.category_name_english
                    ORDER BY COUNT(*) DESC
                    LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $like, $limit);
        } else {
            $sql = "SELECT oi.product_id AS `Product ID`,
                           c.category_name_english AS Category,
                           COUNT(*)               AS `Times Ordered`,
                           ROUND(AVG(oi.price), 2) AS `Avg Price (R$)`
                    FROM OrderItem oi
                    JOIN Product  p ON oi.product_id = p.product_id
                    JOIN Category c ON p.category_id = c.category_id
                    GROUP BY oi.product_id, c.category_name_english
                    ORDER BY COUNT(*) DESC
                    LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'category_reviews':
        $min_reviews = get_int('min_reviews', 50);
        $limit       = get_int('limit', 10);
        $title = 'Category Review Rankings';
        $description = "Top $limit categories with at least $min_reviews reviews, ranked by average review score.";
        $sql = "SELECT c.category_name_english AS Category,
                       ROUND(AVG(r.review_score), 2) AS `Avg Review Score`,
                       COUNT(r.review_id)            AS `Review Count`
                FROM Category  c
                JOIN Product      p  ON c.category_id = p.category_id
                JOIN OrderItem    oi ON p.product_id  = oi.product_id
                JOIN CustomerOrder co ON oi.order_id  = co.order_id
                JOIN Review        r  ON co.order_id  = r.order_id
                GROUP BY c.category_id, c.category_name_english
                HAVING COUNT(r.review_id) >= ?
                ORDER BY AVG(r.review_score) DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $min_reviews, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'payment_summary':
        $title = 'Payment Type Summary';
        $description = 'Usage count, total amount, and average payment value for each payment type.';
        $sql = "SELECT payment_type                   AS `Payment Type`,
                       COUNT(*)                        AS `Usage Count`,
                       ROUND(SUM(payment_value), 2)   AS `Total Amount (R$)`,
                       ROUND(AVG(payment_value), 2)   AS `Avg Amount (R$)`
                FROM Payment
                GROUP BY payment_type
                ORDER BY COUNT(*) DESC";
        $result = $conn->query($sql);
        break;


    case 'top_cities':
        $limit = get_int('limit', 10);
        $state = get_str('state');
        $title = 'Top Customer Cities';
        $description = $state
            ? "Top $limit cities in state '$state' by number of registered customers."
            : "Top $limit cities by number of registered customers.";
        if ($state !== '') {
            $sql = "SELECT customer_city AS City,
                           customer_state AS State,
                           COUNT(*) AS `Customer Count`
                    FROM Customer
                    WHERE customer_state = ?
                    GROUP BY customer_city, customer_state
                    ORDER BY COUNT(*) DESC
                    LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $state, $limit);
        } else {
            $sql = "SELECT customer_city AS City,
                           customer_state AS State,
                           COUNT(*) AS `Customer Count`
                    FROM Customer
                    GROUP BY customer_city, customer_state
                    ORDER BY COUNT(*) DESC
                    LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'multi_category_sellers':
        $min_cat = get_int('min_categories', 2);
        $limit   = get_int('limit', 10);
        $title = 'Sellers in Multiple Categories';
        $description = "Top $limit sellers who sell products in at least $min_cat different categories.";
        $sql = "SELECT oi.seller_id AS `Seller ID`,
                       s.seller_city AS City,
                       s.seller_state AS State,
                       COUNT(DISTINCT p.category_id) AS `Num Categories`
                FROM OrderItem oi
                JOIN Seller   s ON oi.seller_id  = s.seller_id
                JOIN Product  p ON oi.product_id = p.product_id
                GROUP BY oi.seller_id, s.seller_city, s.seller_state
                HAVING COUNT(DISTINCT p.category_id) >= ?
                ORDER BY COUNT(DISTINCT p.category_id) DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $min_cat, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'fast_delivery':
        $min_orders = get_int('min_orders', 10);
        $limit      = get_int('limit', 10);
        $title = 'Fastest Delivery Sellers';
        $description = "Top $limit sellers with the shortest average delivery time (min $min_orders completed orders).";
        $sql = "SELECT oi.seller_id AS `Seller ID`,
                       s.seller_city AS City,
                       s.seller_state AS State,
                       ROUND(AVG(DATEDIFF(co.order_delivered_customer_date,
                                         co.order_purchase_timestamp)), 1) AS `Avg Delivery Days`,
                       COUNT(DISTINCT oi.order_id) AS `Orders Delivered`
                FROM OrderItem    oi
                JOIN Seller        s  ON oi.seller_id = s.seller_id
                JOIN CustomerOrder co ON oi.order_id  = co.order_id
                WHERE co.order_delivered_customer_date IS NOT NULL
                GROUP BY oi.seller_id, s.seller_city, s.seller_state
                HAVING COUNT(DISTINCT oi.order_id) >= ?
                ORDER BY AVG(DATEDIFF(co.order_delivered_customer_date,
                                     co.order_purchase_timestamp))
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $min_orders, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'delayed_sellers':
        $min_orders = get_int('min_orders', 10);
        $limit      = get_int('limit', 10);
        $title = 'Sellers with Delayed Deliveries';
        $description = "Top $limit sellers whose delay rate exceeds the marketplace average (min $min_orders orders).";
        $sql = "WITH seller_delay AS (
                    SELECT oi.seller_id,
                           COUNT(DISTINCT oi.order_id) AS total_orders,
                           SUM(CASE WHEN co.order_delivered_customer_date
                                         > co.order_estimated_delivery_date
                               THEN 1 ELSE 0 END) AS delayed_orders,
                           100.0 * SUM(CASE WHEN co.order_delivered_customer_date
                                                 > co.order_estimated_delivery_date
                               THEN 1 ELSE 0 END) / COUNT(DISTINCT oi.order_id) AS pct_delayed
                    FROM OrderItem    oi
                    JOIN CustomerOrder co ON oi.order_id = co.order_id
                    WHERE co.order_delivered_customer_date IS NOT NULL
                      AND co.order_estimated_delivery_date IS NOT NULL
                    GROUP BY oi.seller_id
                    HAVING COUNT(DISTINCT oi.order_id) >= ?
                ),
                market_avg AS (SELECT AVG(pct_delayed) AS avg_pct FROM seller_delay)
                SELECT sd.seller_id AS `Seller ID`,
                       sd.total_orders AS `Total Orders`,
                       sd.delayed_orders AS `Delayed Orders`,
                       ROUND(sd.pct_delayed, 1)     AS `% Delayed`,
                       ROUND(ma.avg_pct, 1)          AS `Market Avg %`
                FROM seller_delay sd CROSS JOIN market_avg ma
                WHERE sd.pct_delayed > ma.avg_pct
                ORDER BY sd.pct_delayed DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $min_orders, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case 'high_rev_low_review':
        $max_score = get_float('max_score', 4.0);
        $title = 'High Revenue, Low Review Score Categories';
        $description = "Categories with above-average revenue but an average review score below $max_score.";
        $sql = "WITH category_stats AS (
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
                SELECT category AS Category,
                       total_revenue AS `Total Revenue (R$)`,
                       avg_review    AS `Avg Review Score`
                FROM category_stats
                WHERE total_revenue > (SELECT AVG(total_revenue) FROM category_stats)
                  AND avg_review < ?
                ORDER BY total_revenue DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('d', $max_score);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    default:
        $error = 'Unknown query type. Please go back and select a valid query.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Query Results &mdash; E-commerce Marketplace</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f8;
            margin: 40px;
        }
        .container {
            background-color: #ffffff;
            border: 2px solid #2c5f8a;
            padding: 30px;
            max-width: 960px;
            margin: 0 auto;
            border-radius: 4px;
        }
        h1 { color: #1a3a5c; }
        .description { color: #444; margin-bottom: 16px; font-size: 14px; }
        .table-wrap {
            overflow-x: auto;
            border: 1px solid #2c5f8a;
            background-color: #f8fbff;
        }
        table { border-collapse: collapse; font-size: 13px; width: 100%; }
        th, td {
            border: 1px solid #2c5f8a;
            padding: 5px 10px;
            text-align: left;
            white-space: nowrap;
        }
        th { background-color: #2c5f8a; color: #fff; }
        tr:nth-child(even) { background-color: #eaf2fb; }
        a { color: #2c5f8a; }
        .error { color: #c00; }
        .no-results { color: #666; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($title ?: 'Query Results') ?></h1>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($result): ?>
            <p class="description"><?= htmlspecialchars($description) ?></p>
            <?php if ($result->num_rows > 0): ?>
                <p>Showing <strong><?= $result->num_rows ?></strong> result(s).</p>
                <div class="table-wrap">
                <table>
                    <tr>
                    <?php
                        $fields = $result->fetch_fields();
                        foreach ($fields as $f) {
                            echo '<th>' . htmlspecialchars($f->name) . '</th>';
                        }
                    ?>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($row as $val): ?>
                        <td><?= htmlspecialchars($val ?? '') ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endwhile; ?>
                </table>
                </div>
            <?php else: ?>
                <p class="no-results">No results found for the given parameters.</p>
            <?php endif; ?>
        <?php endif; ?>
        <p><a href="index.html">&larr; Back to query menu</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>
