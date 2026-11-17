SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS album_types (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_album_types_name (name),
    UNIQUE KEY uq_album_types_slug (slug),
    KEY idx_album_types_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS albums (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    album_type_id INT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    cover_album VARCHAR(255) NOT NULL,
    release_date DATETIME NULL,
    spotify_url VARCHAR(255) NULL,
    youtube_url VARCHAR(255) NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    is_for_sale TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_albums_album_type_id (album_type_id),
    KEY idx_albums_release_date (release_date),
    KEY idx_albums_deleted_at (deleted_at),
    KEY idx_albums_is_for_sale (is_for_sale),
    CONSTRAINT fk_albums_album_type FOREIGN KEY (album_type_id) REFERENCES album_types (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS songs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    album_id INT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    track_number INT NOT NULL DEFAULT 0,
    is_title TINYINT(1) NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    audio_url VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_songs_album_id (album_id),
    KEY idx_songs_is_featured (is_featured),
    KEY idx_songs_deleted_at (deleted_at),
    CONSTRAINT fk_songs_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by INT UNSIGNED NULL,
    slug VARCHAR(190) NOT NULL,
    title VARCHAR(200) NOT NULL,
    thumbnail VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    source_name VARCHAR(120) NULL,
    source_url VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_slug (slug),
    KEY idx_news_status (status),
    KEY idx_news_published_at (published_at),
    KEY idx_news_deleted_at (deleted_at),
    CONSTRAINT fk_news_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by INT UNSIGNED NULL,
    slug VARCHAR(190) NOT NULL,
    title VARCHAR(200) NOT NULL,
    venue VARCHAR(160) NOT NULL,
    city VARCHAR(120) NOT NULL,
    event_date DATETIME NULL,
    poster VARCHAR(255) NULL,
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_events_slug (slug),
    KEY idx_events_event_date (event_date),
    KEY idx_events_deleted_at (deleted_at),
    CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    photo VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    member_roles VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_members_deleted_at (deleted_at),
    CONSTRAINT fk_members_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS histories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by INT UNSIGNED NULL,
    image VARCHAR(255) NOT NULL,
    title VARCHAR(160) NOT NULL,
    description VARCHAR(255) NOT NULL,
    event_date DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_histories_event_date (event_date),
    KEY idx_histories_deleted_at (deleted_at),
    CONSTRAINT fk_histories_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    image VARCHAR(255) NOT NULL,
    alt_text VARCHAR(200) NULL,
    caption VARCHAR(200) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_gallery_items_sort_order (sort_order),
    KEY idx_gallery_items_deleted_at (deleted_at),
    CONSTRAINT fk_gallery_items_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    invoice_number VARCHAR(40) NOT NULL,
    access_token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    recipient_name VARCHAR(120) NOT NULL,
    recipient_phone VARCHAR(40) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    destination_country_code CHAR(2) NOT NULL,
    destination_country VARCHAR(120) NOT NULL,
    destination_city_id INT UNSIGNED NOT NULL,
    destination_city VARCHAR(160) NOT NULL,
    destination_region VARCHAR(160) NULL,
    destination_latitude DECIMAL(9,6) NOT NULL,
    destination_longitude DECIMAL(9,6) NOT NULL,
    distance_km DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    shipping_amount DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    payment_provider VARCHAR(40) NOT NULL DEFAULT 'mock',
    status ENUM('PENDING_PAYMENT', 'PAID', 'CANCELLED') NOT NULL DEFAULT 'PENDING_PAYMENT',
    paid_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    cancel_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_invoice_number (invoice_number),
    UNIQUE KEY uq_orders_access_token (access_token),
    KEY idx_orders_user_id (user_id),
    KEY idx_orders_status_created (status, created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    album_id INT UNSIGNED NOT NULL,
    album_name VARCHAR(160) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_items_order_id (order_id),
    KEY idx_order_items_album_id (album_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
