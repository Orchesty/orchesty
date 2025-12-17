CREATE TABLE IF NOT EXISTS category (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    category_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_category (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    UNIQUE KEY UK_product_category (product_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO category (id, name) VALUES (1, 'Category 1'), (2, 'Category 2'), (3, 'Category 3');

INSERT INTO product (id, name) VALUES (1, 'Product 1'), (2, 'Product 2'), (3, 'Product 3'), (4, 'Product 4'), (5, 'Product 5'), (6, 'Product 6');

INSERT INTO product_category (category_id, product_id) VALUES (1, 1);
INSERT INTO product_category (category_id, product_id) VALUES (2, 2);
INSERT INTO product_category (category_id, product_id) VALUES (3, 3);
INSERT INTO product_category (category_id, product_id) VALUES (1, 4);
INSERT INTO product_category (category_id, product_id) VALUES (2, 5);
INSERT INTO product_category (category_id, product_id) VALUES (3, 6);
INSERT INTO product_category (category_id, product_id) VALUES (2, 1);
INSERT INTO product_category (category_id, product_id) VALUES (3, 2);
INSERT INTO product_category (category_id, product_id) VALUES (1, 3);
