-- Добавляет новые фотографии товаров, удаляет лишние

INSERT INTO product_images (product_id, image_path) VALUES
    ('3', '/assets/images/products/3/1.png'),
    ('3', '/assets/images/products/3/2.png'),
    ('3', '/assets/images/products/3/3.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('9', '/assets/images/products/9/1.png'),
    ('9', '/assets/images/products/9/2.png'),
    ('9', '/assets/images/products/9/3.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('10', '/assets/images/products/10/1.png'),
    ('10', '/assets/images/products/10/2.png'),
    ('10', '/assets/images/products/10/3.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('11', '/assets/images/products/11/1.png'),
    ('11', '/assets/images/products/11/2.png'),
    ('11', '/assets/images/products/11/3.png'),
    ('11', '/assets/images/products/11/4.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('12', '/assets/images/products/12/1.png'),
    ('12', '/assets/images/products/12/2.png'),
    ('12', '/assets/images/products/12/3.png'),
    ('12', '/assets/images/products/12/4.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('13', '/assets/images/products/13/1.png'),
    ('13', '/assets/images/products/13/2.png'),
    ('13', '/assets/images/products/13/3.png'),
    ('13', '/assets/images/products/13/4.png'),
    ('13', '/assets/images/products/13/5.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('14', '/assets/images/products/14/1.png'),
    ('14', '/assets/images/products/14/2.png'),
    ('14', '/assets/images/products/14/3.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('15', '/assets/images/products/15/1.png'),
    ('15', '/assets/images/products/15/2.png'),
    ('15', '/assets/images/products/15/3.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('5', '/assets/images/products/5/1.png'),
    ('5', '/assets/images/products/5/2.png'),
    ('5', '/assets/images/products/5/3.png'),
    ('5', '/assets/images/products/5/4.png'),
    ('5', '/assets/images/products/5/5.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('16', '/assets/images/products/16/1.png'),
    ('16', '/assets/images/products/16/2.png'),
    ('16', '/assets/images/products/16/3.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('17', '/assets/images/products/17/1.png'),
    ('17', '/assets/images/products/17/2.png'),
    ('17', '/assets/images/products/17/3.png'),
    ('17', '/assets/images/products/17/4.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('6', '/assets/images/products/6/1.png'),
    ('6', '/assets/images/products/6/2.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('18', '/assets/images/products/18/1.png'),
    ('18', '/assets/images/products/18/2.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('4', '/assets/images/products/4/1.png'),
    ('4', '/assets/images/products/4/2.png'),
    ('4', '/assets/images/products/4/3.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('19', '/assets/images/products/19/1.png'),
    ('19', '/assets/images/products/19/2.png');

INSERT INTO product_images (product_id, image_path) VALUES
    ('7', '/assets/images/products/7/1.png');

DELETE FROM product_images WHERE id = '8';