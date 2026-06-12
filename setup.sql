create table Customer (
    customer_id varchar(32),
    customer_unique_id varchar(32) not null,
    customer_city varchar(100),
    customer_state varchar(10),
    primary key (customer_id)
);

create table Seller (
    seller_id varchar(32),
    seller_zip_code_prefix varchar(20),
    seller_city varchar(100),
    seller_state varchar(10),
    primary key (seller_id)
);

create table Category (
    category_id int,
    category_name varchar(100),
    category_name_english varchar(100),
    primary key (category_id)
);

create table Product (
    product_id varchar(32),
    category_id int not null,
    product_name varchar(255),
    product_description text,
    product_photos_qty int,
    product_weight_g int,
    product_length_cm int,
    product_height_cm int,
    product_width_cm int,
    primary key (product_id),
    constraint fk_product_category
        foreign key (category_id) references Category(category_id)
);

create table CustomerOrder (
    order_id varchar(32),
    customer_id varchar(32) not null,
    order_status varchar(30),
    order_purchase_timestamp timestamp,
    order_approved_at timestamp null,
    order_delivered_carrier_date timestamp null,
    order_delivered_customer_date timestamp null,
    order_estimated_delivery_date timestamp null,
    primary key (order_id),
    constraint fk_order_customer
        foreign key (customer_id) references Customer(customer_id)
);

create table OrderItem (
    order_id varchar(32),
    order_item_id int,
    product_id varchar(32) not null,
    seller_id varchar(32) not null,
    shipping_limit_date timestamp null,
    price numeric(10,2),
    freight_value numeric(10,2),
    primary key (order_id, order_item_id),
    constraint fk_orderitem_order
        foreign key (order_id) references CustomerOrder(order_id),
    constraint fk_orderitem_product
        foreign key (product_id) references Product(product_id),
    constraint fk_orderitem_seller
        foreign key (seller_id) references Seller(seller_id)
);

create table Payment (
    payment_id int auto_increment,
    order_id varchar(32) not null,
    payment_sequential int not null,
    payment_type varchar(30),
    payment_installments int,
    payment_value numeric(10,2),
    primary key (payment_id),
    unique (order_id, payment_sequential),
    constraint fk_payment_order
        foreign key (order_id) references CustomerOrder(order_id)
);

create table Review (
    review_id varchar(32),
    order_id varchar(32) not null,
    review_score int,
    review_comment_title varchar(255),
    review_comment_message text,
    review_creation_date timestamp null,
    review_answer_timestamp timestamp null,
    primary key (review_id),
    unique (order_id),
    constraint fk_review_order
        foreign key (order_id) references CustomerOrder(order_id)
);
