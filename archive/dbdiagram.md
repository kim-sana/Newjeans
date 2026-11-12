Rancangan Final implementasi Database dan Backend
Penjelasan di 

Enum user_role {
admin
customer
}

Enum order_status {
PENDING_PAYMENT
WAITING_APPROVAL
COMPLETED
CANCELLED
}

Enum proof_status {
PENDING
APPROVED
REJECTED
}

Table users {
id integer [primary key, increment, not null]
name varchar [not null]
email varchar [not null, unique]
password varchar [not null]
role user_role [not null, default: 'customer']
created_at timestamp
}

Table album_types {
id integer [primary key, increment, not null]
name varchar [not null, unique]
created_at timestamp
deleted_at timestamp
}

Table albums {
id integer [primary key, increment, not null]
album_type_id integer [ref: > album_types.id, not null]
name varchar [not null]
cover_album varchar [not null]
release_date datetime
spotify_url varchar
price decimal(10,2) [not null, default: 0, note: 'USD']
stock integer [not null, default: 0]
is_for_sale boolean [not null, default: false]
created_at timestamp
deleted_at timestamp
}

Table songs {
id integer [primary key, increment, not null]
album_id integer [ref: > albums.id, not null]
title varchar [not null]
is_featured tinyint [default: 0]
audio_url varchar
created_at timestamp
deleted_at timestamp
}

Table news {
id integer [primary key, increment, not null]
created_by integer [ref: > users.id]
slug varchar [not null, unique]
title varchar [not null]
thumbnail varchar [not null]
content text [not null]
status enum [default: 'draft']
created_at timestamp
deleted_at timestamp
}

Table events {
id integer [primary key, increment, not null]
created_by integer [ref: > users.id]
slug varchar [not null, unique]
title varchar [not null]
venue varchar [not null]
city varchar [not null]
event_date datetime
poster varchar
description text [not null]
created_at timestamp
deleted_at timestamp
}

Table members {
id integer [primary key, increment, not null]
created_by integer [ref: > users.id]
name varchar [not null]
photo varchar [not null]
description varchar [not null]
member_roles varchar [not null]
created_at timestamp
deleted_at timestamp
}

Table histories {
id integer [primary key, increment, not null]
created_by integer [ref: > users.id]
image varchar [not null]
title varchar [not null]
description varchar [not null]
event_date datetime
created_at timestamp
deleted_at timestamp
}

Table gallery_items {
id integer [primary key, increment, not null]
created_by integer [ref: > users.id]
sort_order integer [default: 0]
image varchar [not null]
created_at timestamp
deleted_at timestamp
}

Table shipping_options {
id integer [primary key, increment, not null]
name varchar [not null, unique, note: 'Reguler / Express']
fee decimal(10,2) [not null, default: 0, note: 'flat, USD']
estimate_text varchar [note: 'teks estimasi simulasi']
is_active boolean [not null, default: true]
created_at timestamp
}

Table carts {
id integer [primary key, increment, not null]
user_id integer [ref: > users.id, not null, unique]
created_at timestamp
}

Table cart_items {
id integer [primary key, increment, not null]
cart_id integer [ref: > carts.id, not null]
album_id integer [ref: > albums.id, not null]
qty integer [not null, default: 1]
created_at timestamp
}

Table orders {
id integer [primary key, increment, not null]
invoice_number varchar [not null, unique]
user_id integer [ref: > users.id, not null]
shipping_option_id integer [ref: > shipping_options.id, not null]
address text [not null, note: 'satu text input, snapshot saat checkout']
subtotal decimal(10,2) [not null]
shipping_cost decimal(10,2) [not null]
total decimal(10,2) [not null]
status order_status [not null, default: 'PENDING_PAYMENT']
paid_at timestamp
completed_at timestamp
cancelled_at timestamp
created_at timestamp
}

Table order_items {
id integer [primary key, increment, not null]
order_id integer [ref: > orders.id, not null]
album_id integer [ref: > albums.id, not null]
album_name varchar [not null, note: 'snapshot']
price decimal(10,2) [not null, note: 'snapshot USD']
qty integer [not null]
subtotal decimal(10,2) [not null]
created_at timestamp
}

Table payment_proofs {
id integer [primary key, increment, not null]
order_id integer [ref: > orders.id, not null]
file_path varchar [not null]
status proof_status [not null, default: 'PENDING']
reject_note varchar
reviewed_by integer [ref: > users.id]
reviewed_at timestamp
created_at timestamp
}

Table order_status_histories {
id integer [primary key, increment, not null]
order_id integer [ref: > orders.id, not null]
status order_status [not null]
note varchar
created_at timestamp
}
