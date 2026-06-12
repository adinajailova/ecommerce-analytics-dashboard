import pandas as pd

customers = pd.read_csv('olist_customers_dataset.csv')
sellers = pd.read_csv('olist_sellers_dataset.csv')
products = pd.read_csv('olist_products_dataset.csv')
translations = pd.read_csv('product_category_name_translation.csv')
orders = pd.read_csv('olist_orders_dataset.csv')
order_items = pd.read_csv('olist_order_items_dataset.csv')
payments = pd.read_csv('olist_order_payments_dataset.csv')
reviews = pd.read_csv('olist_order_reviews_dataset.csv')

category_df = (
    products[['product_category_name']]
    .drop_duplicates()
    .merge(translations, on='product_category_name', how='left')
    .reset_index(drop=True)
)
category_df['category_id'] = category_df.index + 1
category_df = category_df[['category_id', 'product_category_name', 'product_category_name_english']]
category_df.columns = ['category_id', 'category_name', 'category_name_english']

product_df = products.merge(
    category_df[['category_id', 'category_name']],
    left_on='product_category_name',
    right_on='category_name',
    how='left'
)

customer_df = customers[['customer_id', 'customer_unique_id', 'customer_city', 'customer_state']].copy()
seller_df = sellers[['seller_id', 'seller_zip_code_prefix', 'seller_city', 'seller_state']].copy()

product_df = product_df[[
    'product_id',
    'category_id',
    'product_category_name',
    'product_name_lenght',
    'product_photos_qty',
    'product_weight_g',
    'product_length_cm',
    'product_height_cm',
    'product_width_cm'
]].copy()

product_df.columns = [
    'product_id',
    'category_id',
    'product_name',
    'product_description',
    'product_photos_qty',
    'product_weight_g',
    'product_length_cm',
    'product_height_cm',
    'product_width_cm'
]

order_df = orders[[
    'order_id',
    'customer_id',
    'order_status',
    'order_purchase_timestamp',
    'order_approved_at',
    'order_delivered_carrier_date',
    'order_delivered_customer_date',
    'order_estimated_delivery_date'
]].copy()

orderitem_df = order_items[[
    'order_id',
    'order_item_id',
    'product_id',
    'seller_id',
    'shipping_limit_date',
    'price',
    'freight_value'
]].copy()

payment_df = payments[[
    'order_id',
    'payment_sequential',
    'payment_type',
    'payment_installments',
    'payment_value'
]].copy()

review_df = reviews[[
    'review_id',
    'order_id',
    'review_score',
    'review_comment_title',
    'review_comment_message',
    'review_creation_date',
    'review_answer_timestamp'
]].copy()

for df in [customer_df, seller_df, category_df, product_df, order_df, orderitem_df, payment_df, review_df]:
    df.replace('', pd.NA, inplace=True)

customer_df.to_csv('customer_clean.csv', index=False)
seller_df.to_csv('seller_clean.csv', index=False)
category_df.to_csv('category_clean.csv', index=False)
product_df.to_csv('product_clean.csv', index=False)
order_df.to_csv('order_clean.csv', index=False)
orderitem_df.to_csv('orderitem_clean.csv', index=False)
payment_df.to_csv('payment_clean.csv', index=False)
review_df.to_csv('review_clean.csv', index=False)
