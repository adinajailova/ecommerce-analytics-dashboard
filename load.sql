load data local infile 'customer_clean.csv'
into table Customer
fields terminated by ','
optionally enclosed by '"'
lines terminated by '\n'
ignore 1 lines
(customer_id, customer_unique_id, customer_city, customer_state);

load data local infile 'seller_clean.csv'
into table Seller
fields terminated by ','
optionally enclosed by '"'
lines terminated by '\n'
ignore 1 lines
(seller_id, seller_zip_code_prefix, seller_city, seller_state);

load data local infile 'category_clean.csv'
into table Category
fields terminated by ','
optionally enclosed by '"'
lines terminated by '\n'
ignore 1 lines
(category_id, category_name, category_name_english);

load data local infile 'product_clean.csv'
into table Product
fields terminated by ','
optionally enclosed by '"'
lines terminated by '\n'
ignore 1 lines
(product_id, category_id, product_name, product_description, product_photos_qty,
 product_weight_g, product_length_cm, product_height_cm, product_width_cm);

load data local infile 'order_clean.csv'
into table CustomerOrder
fields terminated by ','
optionally enclosed by '"'
lines terminated by '\n'
ignore 1 lines
(order_id, customer_id, order_status, order_purchase_timestamp, order_approved_at,
 order_delivered_carrier_date, order_delivered_customer_date, order_estimated_delivery_date);

load data local infile 'orderitem_clean.csv'
into table OrderItem
fields terminated by ','
optionally enclosed by '"'
lines terminated by '\n'
ignore 1 lines
(order_id, order_item_id, product_id, seller_id, shipping_limit_date, price, freight_value);

load data local infile 'payment_clean.csv'
into table Payment
fields terminated by ','
optionally enclosed by '"'
lines terminated by '\n'
ignore 1 lines
(order_id, payment_sequential, payment_type, payment_installments, payment_value);

load data local infile 'review_clean.csv'
into table Review
fields terminated by ','
optionally enclosed by '"'
lines terminated by '\n'
ignore 1 lines
(review_id, order_id, review_score, review_comment_title, review_comment_message,
 review_creation_date, review_answer_timestamp);
